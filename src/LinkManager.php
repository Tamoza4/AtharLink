<?php
declare(strict_types=1);

namespace AtharLink;

use PDO;

require_once __DIR__ . '/AuditLogger.php';

/**
 * Link CRUD, Analytics Aggregator, and Data Export Engine
 */
class LinkManager
{
    /**
     * Create a new tracked link
     */
    public static function create(array $data): array
    {
        $pdo = Database::getConnection();

        // 1. Validate Target URL (Strict http/https scheme validation)
        $targetUrl = trim($data['target_url'] ?? '');
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => I18n::t('target_url_invalid')];
        }
        $scheme = parse_url($targetUrl, PHP_URL_SCHEME);
        if (!$scheme || !in_array(strtolower($scheme), ['http', 'https'], true)) {
            return ['success' => false, 'message' => I18n::t('target_url_invalid')];
        }

        // 2. Slug handling
        $slug = trim($data['slug'] ?? '');
        if ($slug === '') {
            do {
                $slug = Helpers::generateSlug(6);
            } while (!self::isSlugAvailable($slug));
        } else {
            if (!Helpers::isValidSlug($slug)) {
                return ['success' => false, 'message' => I18n::t('slug_invalid')];
            }
            if (!self::isSlugAvailable($slug)) {
                return ['success' => false, 'message' => I18n::t('slug_taken')];
            }
        }

        // 3. Optional fields
        $title = !empty($data['title']) ? trim((string)$data['title']) : null;
        $redirectType = in_array((int)($data['redirect_type'] ?? 302), [301, 302, 2], true) 
            ? (int)($data['redirect_type'] ?? 302) 
            : 302;

        $passwordHash = null;
        $passwordEncrypted = null;
        if (!empty($data['password'])) {
            $rawPass = trim((string)$data['password']);
            if ($rawPass !== '') {
                $passwordHash = password_hash($rawPass, PASSWORD_BCRYPT);
                $passwordEncrypted = Helpers::encryptSensitive($rawPass);
            }
        }

        $rawAuthLang = $data['auth_lang'] ?? 'auto';
        $authLang = in_array($rawAuthLang, ['auto', 'ar', 'en'], true) ? (string)$rawAuthLang : 'auto';

        $clickLimit = !empty($data['click_limit']) ? max(1, (int)$data['click_limit']) : null;
        $expiresAt = !empty($data['expires_at']) ? date('Y-m-d H:i:s', strtotime($data['expires_at'])) : null;
        $forwardUtm = isset($data['forward_utm']) ? (int)(bool)$data['forward_utm'] : 1;
        $isActive = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;
        $initialClicks = isset($data['total_clicks']) ? max(0, (int)$data['total_clicks']) : (isset($data['initial_clicks']) ? max(0, (int)$data['initial_clicks']) : 0);
        $initialUnique = isset($data['unique_clicks']) ? max(0, (int)$data['unique_clicks']) : (isset($data['initial_unique_clicks']) ? max(0, (int)$data['initial_unique_clicks']) : 0);

        $stmt = $pdo->prepare("
            INSERT INTO links (
                slug, target_url, title, redirect_type,
                password_hash, password_plain, auth_lang, click_limit, expires_at,
                is_active, forward_utm, initial_clicks, initial_unique_clicks,
                created_at, updated_at
            ) VALUES (
                :slug, :target, :title, :rtype,
                :phash, :pplain, :alang, :climit, :expires,
                :active, :utm, :init_c, :init_u,
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ");

        $stmt->execute([
            ':slug'    => $slug,
            ':target'  => $targetUrl,
            ':title'   => $title,
            ':rtype'   => $redirectType,
            ':phash'   => $passwordHash,
            ':pplain'  => $passwordEncrypted,
            ':alang'   => $authLang,
            ':climit'  => $clickLimit,
            ':expires' => $expiresAt,
            ':active'  => $isActive,
            ':utm'     => $forwardUtm,
            ':init_c'  => $initialClicks,
            ':init_u'  => $initialUnique
        ]);

        $newId = (int)$pdo->lastInsertId();

        AuditLogger::log('create_link', 'Created link: ' . $slug . ' ➔ ' . $targetUrl, [
            'id'            => $newId,
            'slug'          => $slug,
            'target_url'    => $targetUrl,
            'title'         => $title,
            'redirect_type' => $redirectType,
            'is_protected'  => !empty($passwordHash),
            'click_limit'   => $clickLimit,
            'expires_at'    => $expiresAt
        ]);

        return [
            'success' => true,
            'id'      => $newId,
            'slug'    => $slug,
            'url'     => Helpers::trackingUrl($slug),
            'message' => I18n::t('link_created_success')
        ];
    }

    /**
     * Update an existing link
     */
    public static function update(int $id, array $data): array
    {
        $pdo = Database::getConnection();
        $link = self::findById($id);
        if (!$link) {
            return ['success' => false, 'message' => I18n::t('link_not_found')];
        }

        // Validate target URL
        $targetUrl = trim($data['target_url'] ?? $link['target_url']);
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => I18n::t('target_url_invalid')];
        }
        $scheme = parse_url($targetUrl, PHP_URL_SCHEME);
        if (!$scheme || !in_array(strtolower($scheme), ['http', 'https'], true)) {
            return ['success' => false, 'message' => I18n::t('target_url_invalid')];
        }

        // Validate slug if changed
        $slug = trim($data['slug'] ?? $link['slug']);
        if ($slug !== $link['slug']) {
            if (!Helpers::isValidSlug($slug)) {
                return ['success' => false, 'message' => I18n::t('slug_invalid')];
            }
            if (!self::isSlugAvailable($slug, $id)) {
                return ['success' => false, 'message' => I18n::t('slug_taken')];
            }
        }

        $title = isset($data['title']) ? trim((string)$data['title']) : $link['title'];
        $redirectType = in_array((int)($data['redirect_type'] ?? $link['redirect_type']), [301, 302, 2], true)
            ? (int)($data['redirect_type'] ?? $link['redirect_type'])
            : (int)$link['redirect_type'];

        // Password update handling
        $passwordHash = $link['password_hash'];
        $passwordPlain = $link['password_plain'] ?? null;

        if (!empty($data['remove_password'])) {
            $passwordHash = null;
            $passwordPlain = null;
        } elseif (isset($data['password'])) {
            $rawPass = trim((string)$data['password']);
            if ($rawPass === '') {
                $passwordHash = null; // Removed password protection
                $passwordPlain = null;
            } else {
                $passwordHash = password_hash($rawPass, PASSWORD_BCRYPT);
                $passwordPlain = Helpers::encryptSensitive($rawPass);
            }
        }

        $authLang = in_array($data['auth_lang'] ?? ($link['auth_lang'] ?? 'auto'), ['auto', 'ar', 'en'], true)
            ? (string)($data['auth_lang'] ?? ($link['auth_lang'] ?? 'auto'))
            : 'auto';

        $clickLimit = !empty($data['click_limit']) ? max(1, (int)$data['click_limit']) : null;
        $expiresAt = !empty($data['expires_at']) ? date('Y-m-d H:i:s', strtotime($data['expires_at'])) : null;
        $forwardUtm = isset($data['forward_utm']) ? (int)(bool)$data['forward_utm'] : (int)$link['forward_utm'];
        $isActive = isset($data['is_active']) ? (int)(bool)$data['is_active'] : (int)$link['is_active'];
        // Calculate new initial clicks based on submitted total/unique clicks or legacy initial counts
        $recStmt = $pdo->prepare("
            SELECT 
                COUNT(id) AS rec_total, 
                COALESCE(SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END), 0) AS rec_unique 
            FROM clicks 
            WHERE link_id = :id
        ");
        $recStmt->execute([':id' => $id]);
        $rec = $recStmt->fetch();
        $recTotal = (int)($rec['rec_total'] ?? 0);

        if (isset($data['total_clicks'])) {
            $desiredTotal = max(0, (int)$data['total_clicks']);
            if ($desiredTotal >= $recTotal) {
                $initialClicks = $desiredTotal - $recTotal;
            } else {
                $excess = $recTotal - $desiredTotal;
                $trimStmt = $pdo->prepare("DELETE FROM clicks WHERE id IN (SELECT id FROM clicks WHERE link_id = :id ORDER BY id ASC LIMIT :lim)");
                $trimStmt->bindValue(':id', $id, \PDO::PARAM_INT);
                $trimStmt->bindValue(':lim', $excess, \PDO::PARAM_INT);
                $trimStmt->execute();
                $initialClicks = 0;
            }
        } elseif (isset($data['initial_clicks'])) {
            $initialClicks = max(0, (int)$data['initial_clicks']);
        } else {
            $initialClicks = (int)($link['initial_clicks'] ?? 0);
        }

        if (isset($data['unique_clicks'])) {
            $desiredUnique = max(0, (int)$data['unique_clicks']);
            $currentRecUniqueStmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END), 0) FROM clicks WHERE link_id = :id");
            $currentRecUniqueStmt->execute([':id' => $id]);
            $currentRecUnique = (int)($currentRecUniqueStmt->fetchColumn() ?: 0);

            if ($desiredUnique >= $currentRecUnique) {
                $initialUnique = $desiredUnique - $currentRecUnique;
            } else {
                $initialUnique = 0;
            }
        } elseif (isset($data['initial_unique_clicks'])) {
            $initialUnique = max(0, (int)$data['initial_unique_clicks']);
        } else {
            $initialUnique = (int)($link['initial_unique_clicks'] ?? 0);
        }

        $stmt = $pdo->prepare("
            UPDATE links SET
                slug = :slug,
                target_url = :target,
                title = :title,
                redirect_type = :rtype,
                password_hash = :phash,
                password_plain = :pplain,
                auth_lang = :alang,
                click_limit = :climit,
                expires_at = :expires,
                is_active = :active,
                forward_utm = :utm,
                initial_clicks = :init_c,
                initial_unique_clicks = :init_u,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        $stmt->execute([
            ':slug'    => $slug,
            ':target'  => $targetUrl,
            ':title'   => $title,
            ':rtype'   => $redirectType,
            ':phash'   => $passwordHash,
            ':pplain'  => $passwordPlain,
            ':alang'   => $authLang,
            ':climit'  => $clickLimit,
            ':expires' => $expiresAt,
            ':active'  => $isActive,
            ':utm'     => $forwardUtm,
            ':init_c'  => $initialClicks,
            ':init_u'  => $initialUnique,
            ':id'      => $id
        ]);

        // Compute exact differences between previous state ($link) and updated state
        $changes = [];
        $descHighlights = [];

        if ($link['target_url'] !== $targetUrl) {
            $changes['target_url'] = [
                'field' => 'Target URL',
                'old'   => $link['target_url'],
                'new'   => $targetUrl
            ];
            $descHighlights[] = "Destination: {$link['target_url']} ➔ {$targetUrl}";
        }

        if ($link['slug'] !== $slug) {
            $changes['slug'] = [
                'field' => 'Slug',
                'old'   => $link['slug'],
                'new'   => $slug
            ];
            $descHighlights[] = "Slug: {$link['slug']} ➔ {$slug}";
        }

        if ((string)($link['title'] ?? '') !== (string)($title ?? '')) {
            $changes['title'] = [
                'field' => 'Title',
                'old'   => (string)($link['title'] ?? ''),
                'new'   => (string)($title ?? '')
            ];
            $descHighlights[] = "Title updated";
        }

        if ((int)$link['redirect_type'] !== (int)$redirectType) {
            $changes['redirect_type'] = [
                'field' => 'Redirect Type',
                'old'   => (int)$link['redirect_type'],
                'new'   => (int)$redirectType
            ];
            $descHighlights[] = "Redirect: {$link['redirect_type']} ➔ {$redirectType}";
        }

        if ((int)$link['is_active'] !== (int)$isActive) {
            $oldSt = (int)$link['is_active'] === 1 ? 'Active' : 'Paused';
            $newSt = (int)$isActive === 1 ? 'Active' : 'Paused';
            $changes['status'] = [
                'field' => 'Status',
                'old'   => $oldSt,
                'new'   => $newSt
            ];
            $descHighlights[] = "Status: {$newSt}";
        }

        if (!empty($data['remove_password']) || (!empty($link['password_hash']) && empty($passwordHash))) {
            $changes['password'] = [
                'field' => 'Password Protection',
                'old'   => 'Protected',
                'new'   => 'Removed'
            ];
            $descHighlights[] = "Password removed";
        } elseif (!empty($data['password']) && trim((string)$data['password']) !== '') {
            $changes['password'] = [
                'field' => 'Password Protection',
                'old'   => empty($link['password_hash']) ? 'None' : 'Protected',
                'new'   => 'Updated Password'
            ];
            $descHighlights[] = "Password updated";
        }

        if ((string)($link['click_limit'] ?? '') !== (string)($clickLimit ?? '')) {
            $changes['click_limit'] = [
                'field' => 'Click Limit',
                'old'   => $link['click_limit'] ?? 'Unlimited',
                'new'   => $clickLimit ?? 'Unlimited'
            ];
        }

        if ((string)($link['expires_at'] ?? '') !== (string)($expiresAt ?? '')) {
            $changes['expires_at'] = [
                'field' => 'Expiration Date',
                'old'   => $link['expires_at'] ?? 'Never',
                'new'   => $expiresAt ?? 'Never'
            ];
        }

        $logDesc = 'Updated link: ' . $slug;
        if (!empty($descHighlights)) {
            $logDesc .= ' (' . implode(' | ', $descHighlights) . ')';
        }

        AuditLogger::log('update_link', $logDesc, [
            'id'            => $id,
            'slug'          => $slug,
            'target_url'    => $targetUrl,
            'changes'       => $changes,
            'old'           => [
                'slug'          => $link['slug'],
                'target_url'    => $link['target_url'],
                'title'         => $link['title'] ?? '',
                'redirect_type' => (int)$link['redirect_type'],
                'is_active'     => (int)$link['is_active'],
            ],
            'new'           => [
                'slug'          => $slug,
                'target_url'    => $targetUrl,
                'title'         => $title ?? '',
                'redirect_type' => $redirectType,
                'is_active'     => $isActive,
            ]
        ]);

        return ['success' => true, 'message' => I18n::t('link_updated_success')];
    }

    /**
     * Delete a link and its clicks (Cascaded)
     */
    public static function delete(int $id): bool
    {
        $link = self::findById($id);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM links WHERE id = :id");
        $res = $stmt->execute([':id' => $id]);

        if ($res && $link) {
            AuditLogger::log('delete_link', 'Deleted link: ' . $link['slug'] . ' (' . $link['target_url'] . ')', [
                'id'         => $id,
                'slug'       => $link['slug'],
                'target_url' => $link['target_url'],
                'title'      => $link['title'] ?? ''
            ]);
        }

        return $res;
    }

    /**
     * Toggle Link active / paused state
     */
    public static function toggleActive(int $id): bool
    {
        $link = self::findById($id);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE links SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $res = $stmt->execute([':id' => $id]);

        if ($res && $link) {
            $newState = ((int)$link['is_active'] === 1) ? 0 : 1;
            $statusWord = $newState ? 'Resumed' : 'Paused';
            AuditLogger::log('toggle_link', "{$statusWord} link: {$link['slug']} ({$link['target_url']})", [
                'id'         => $id,
                'slug'       => $link['slug'],
                'target_url' => $link['target_url'],
                'status'     => $newState ? 'active' : 'paused',
                'changes'    => [
                    'status' => [
                        'field' => 'Status',
                        'old'   => $newState ? 'Paused' : 'Active',
                        'new'   => $newState ? 'Active' : 'Paused'
                    ]
                ]
            ]);
        }

        return $res;
    }

    /**
     * Reset link click counts
     */
    public static function resetClicks(int $id): bool
    {
        $link = self::findById($id);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM clicks WHERE link_id = :id");
        $res = $stmt->execute([':id' => $id]);

        if ($res && $link) {
            AuditLogger::log('reset_clicks', 'Reset click counters for link: ' . $link['slug'], [
                'id'   => $id,
                'slug' => $link['slug']
            ]);
        }

        return $res;
    }

    /**
     * Check if slug is available
     */
    public static function isSlugAvailable(string $slug, ?int $excludeId = null): bool
    {
        $pdo = Database::getConnection();
        $sql = "SELECT id FROM links WHERE slug = :s";
        $params = [':s' => $slug];

        if ($excludeId !== null) {
            $sql .= " AND id != :ex";
            $params[':ex'] = $excludeId;
        }

        $sql .= " LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return !$stmt->fetch();
    }

    /**
     * Find link by ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM links WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Find link by Slug
     */
    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM links WHERE slug = :s LIMIT 1");
        $stmt->execute([':s' => $slug]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Get all links with aggregate click stats
     */
    public static function getAll(array $filters = []): array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                l.*,
                (COALESCE(l.initial_clicks, 0) + COUNT(c.id)) AS total_clicks,
                (COALESCE(l.initial_unique_clicks, 0) + COALESCE(SUM(CASE WHEN c.is_unique = 1 THEN 1 ELSE 0 END), 0)) AS unique_clicks,
                MAX(c.clicked_at) AS last_click_at
            FROM links l
            LEFT JOIN clicks c ON l.id = c.link_id
        ";

        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(l.slug LIKE :s OR l.title LIKE :s OR l.target_url LIKE :s)";
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = "l.is_active = :act";
            $params[':act'] = (int)$filters['is_active'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY l.id ORDER BY l.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Compute Global Overview Analytics for Dashboard
     */
    public static function getOverviewStats(string $period = '30d'): array
    {
        $pdo = Database::getConnection();

        // 1. Total links count & active links
        $linksCountStmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_links,
                COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active_links,
                COALESCE(SUM(initial_clicks), 0) AS total_initial_clicks,
                COALESCE(SUM(initial_unique_clicks), 0) AS total_initial_uniques
            FROM links
        ");
        $linksInfo = $linksCountStmt->fetch();

        $initialClicksAll = (int)($linksInfo['total_initial_clicks'] ?? 0);
        $initialUniquesAll = (int)($linksInfo['total_initial_uniques'] ?? 0);

        // Compute date filter boundary
        $filterDate = self::resolvePeriodDate($period);

        // 2. Click counts (in selected period & all-time)
        $clicksStmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_clicks,
                COALESCE(SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END), 0) AS unique_clicks
            FROM clicks
            WHERE clicked_at >= :d
        ");
        $clicksStmt->execute([':d' => $filterDate]);
        $periodClicks = $clicksStmt->fetch();

        $allClicksStmt = $pdo->query("SELECT COUNT(*) AS total_all FROM clicks");
        $allClicks = (int)$allClicksStmt->fetchColumn() + $initialClicksAll;

        $totalPeriodClicks = (int)($periodClicks['total_clicks'] ?? 0) + $initialClicksAll;
        $uniquePeriodClicks = (int)($periodClicks['unique_clicks'] ?? 0) + $initialUniquesAll;
        $avgUniqueRate = $totalPeriodClicks > 0 
            ? round(($uniquePeriodClicks / $totalPeriodClicks) * 100, 1) 
            : 0.0;

        // 3. Click Peak Hours (0-23 hours distribution)
        $peakStmt = $pdo->prepare("
            SELECT 
                strftime('%H', clicked_at) AS click_hour,
                COUNT(*) AS count
            FROM clicks
            WHERE clicked_at >= :d
            GROUP BY click_hour
            ORDER BY count DESC
            LIMIT 1
        ");
        $peakStmt->execute([':d' => $filterDate]);
        $peakRow = $peakStmt->fetch();
        $peakHour = $peakRow ? sprintf('%02d:00 - %02d:00', (int)$peakRow['click_hour'], ((int)$peakRow['click_hour'] + 1) % 24) : I18n::t('not_available');

        // 4. Timeline Series for Chart.js
        $timelineData = self::getTimelineSeries(null, $period);

        // 5. Referrer Breakdown
        $refStmt = $pdo->prepare("
            SELECT 
                COALESCE(referrer_domain, 'Direct') AS domain,
                COUNT(*) AS count
            FROM clicks
            WHERE clicked_at >= :d
            GROUP BY domain
            ORDER BY count DESC
            LIMIT 6
        ");
        $refStmt->execute([':d' => $filterDate]);
        $referrers = $refStmt->fetchAll();

        // 6. Device Distribution
        $devStmt = $pdo->prepare("
            SELECT 
                device_type,
                COUNT(*) AS count
            FROM clicks
            WHERE clicked_at >= :d
            GROUP BY device_type
            ORDER BY count DESC
        ");
        $devStmt->execute([':d' => $filterDate]);
        $devices = $devStmt->fetchAll();

        // 7. Operating System Distribution
        $osStmt = $pdo->prepare("
            SELECT 
                platform,
                COUNT(*) AS count
            FROM clicks
            WHERE clicked_at >= :d
            GROUP BY platform
            ORDER BY count DESC
            LIMIT 5
        ");
        $osStmt->execute([':d' => $filterDate]);
        $platforms = $osStmt->fetchAll();

        // 8. Most Used Browsers Distribution
        $browserStmt = $pdo->prepare("
            SELECT 
                browser,
                COUNT(*) AS count
            FROM clicks
            WHERE clicked_at >= :d
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 6
        ");
        $browserStmt->execute([':d' => $filterDate]);
        $browsers = $browserStmt->fetchAll();

        // 9. 24-Hour Activity Curve (Hourly Distribution)
        $hourlyLabels = [];
        $hourlyCounts = [];
        for ($i = 0; $i < 24; $i++) {
            $hStr = sprintf('%02d:00', $i);
            $hourlyLabels[] = $hStr;
            $hourlyCounts[$i] = 0;
        }

        $hourlyStmt = $pdo->prepare("
            SELECT 
                CAST(strftime('%H', clicked_at) AS INTEGER) AS hr,
                COUNT(*) AS count
            FROM clicks
            WHERE clicked_at >= :d
            GROUP BY hr
        ");
        $hourlyStmt->execute([':d' => $filterDate]);
        $hourlyRows = $hourlyStmt->fetchAll();
        foreach ($hourlyRows as $row) {
            $hr = (int)$row['hr'];
            if (isset($hourlyCounts[$hr])) {
                $hourlyCounts[$hr] = (int)$row['count'];
            }
        }
        $hourlyData = [
            'labels' => $hourlyLabels,
            'counts' => array_values($hourlyCounts)
        ];

        // 10. Smart Traffic Channels Classification
        $allRefStmt = $pdo->prepare("
            SELECT 
                COALESCE(referrer_domain, '') AS domain,
                COUNT(*) AS count
            FROM clicks
            WHERE clicked_at >= :d
            GROUP BY domain
        ");
        $allRefStmt->execute([':d' => $filterDate]);
        $allRefs = $allRefStmt->fetchAll();

        $channelCounts = [
            'social'   => 0,
            'search'   => 0,
            'direct'   => 0,
            'referral' => 0
        ];

        $socialRegex = '/(twitter|t\.co|^x\.com|facebook|fb\.com|instagram|whatsapp|telegram|t\.me|linkedin|tiktok|youtube|reddit|pinterest|threads\.net|snapchat)/i';
        $searchRegex = '/(google|bing|yahoo|duckduckgo|yandex|ecosia|baidu|ask\.com|qwant)/i';

        foreach ($allRefs as $ref) {
            $dom = strtolower(trim((string)$ref['domain']));
            $c = (int)$ref['count'];

            if ($dom === '' || $dom === 'direct') {
                $channelCounts['direct'] += $c;
            } elseif (preg_match($socialRegex, $dom)) {
                $channelCounts['social'] += $c;
            } elseif (preg_match($searchRegex, $dom)) {
                $channelCounts['search'] += $c;
            } else {
                $channelCounts['referral'] += $c;
            }
        }

        $totalChannelClicks = array_sum($channelCounts);
        $channels = [
            'social'   => ['count' => $channelCounts['social'], 'rate' => $totalChannelClicks > 0 ? round(($channelCounts['social'] / $totalChannelClicks) * 100, 1) : 0],
            'search'   => ['count' => $channelCounts['search'], 'rate' => $totalChannelClicks > 0 ? round(($channelCounts['search'] / $totalChannelClicks) * 100, 1) : 0],
            'direct'   => ['count' => $channelCounts['direct'], 'rate' => $totalChannelClicks > 0 ? round(($channelCounts['direct'] / $totalChannelClicks) * 100, 1) : 0],
            'referral' => ['count' => $channelCounts['referral'], 'rate' => $totalChannelClicks > 0 ? round(($channelCounts['referral'] / $totalChannelClicks) * 100, 1) : 0],
            'total'    => $totalChannelClicks
        ];

        // 11. Geographic Table (Top Countries)
        $geoStmt = $pdo->prepare("
            SELECT 
                country_code,
                COUNT(*) AS count
            FROM clicks
            WHERE clicked_at >= :d
            GROUP BY country_code
            ORDER BY count DESC
            LIMIT 10
        ");
        $geoStmt->execute([':d' => $filterDate]);
        $countries = $geoStmt->fetchAll();

        // 12. Top Performing Links Leaderboard
        $topLinksStmt = $pdo->prepare("
            SELECT 
                l.id,
                l.slug,
                l.title,
                l.target_url,
                (COUNT(c.id) + COALESCE(l.initial_clicks, 0)) AS total_clicks,
                (COALESCE(SUM(CASE WHEN c.is_unique = 1 THEN 1 ELSE 0 END), 0) + COALESCE(l.initial_unique_clicks, 0)) AS unique_clicks
            FROM links l
            LEFT JOIN clicks c ON l.id = c.link_id AND c.clicked_at >= :d
            GROUP BY l.id
            ORDER BY total_clicks DESC
            LIMIT 8
        ");
        $topLinksStmt->execute([':d' => $filterDate]);
        $topLinks = $topLinksStmt->fetchAll();

        // 13. Live Recent Activity Feed
        $recentStmt = $pdo->query("
            SELECT 
                c.id,
                c.clicked_at,
                c.country_code,
                c.device_type,
                c.platform,
                c.browser,
                c.referrer_domain,
                c.is_unique,
                l.slug,
                l.title
            FROM clicks c
            JOIN links l ON c.link_id = l.id
            ORDER BY c.clicked_at DESC
            LIMIT 8
        ");
        $recentActivity = $recentStmt ? $recentStmt->fetchAll() : [];

        return [
            'total_links'        => (int)($linksInfo['total_links'] ?? 0),
            'active_links'       => (int)($linksInfo['active_links'] ?? 0),
            'total_clicks'       => $totalPeriodClicks,
            'all_time_clicks'    => $allClicks,
            'unique_clicks'      => $uniquePeriodClicks,
            'avg_unique_rate'    => $avgUniqueRate,
            'peak_hour'          => $peakHour,
            'timeline'           => $timelineData,
            'hourly'             => $hourlyData,
            'channels'           => $channels,
            'referrers'          => $referrers,
            'devices'            => $devices,
            'platforms'          => $platforms,
            'browsers'           => $browsers,
            'countries'          => $countries,
            'top_links'          => $topLinks,
            'recent_activity'    => $recentActivity,
            'period'             => $period
        ];
    }

    /**
     * Compute Granular Analytics for a Single Link
     */
    public static function getLinkStats(int $linkId, string $period = '30d'): ?array
    {
        $link = self::findById($linkId);
        if (!$link) {
            return null;
        }

        $pdo = Database::getConnection();
        $filterDate = self::resolvePeriodDate($period);

        $countsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_clicks,
                COALESCE(SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END), 0) AS unique_clicks
            FROM clicks
            WHERE link_id = :lid AND clicked_at >= :d
        ");
        $countsStmt->execute([':lid' => $linkId, ':d' => $filterDate]);
        $counts = $countsStmt->fetch();

        $totalClicks = (int)($counts['total_clicks'] ?? 0) + (int)($link['initial_clicks'] ?? 0);
        $uniqueClicks = (int)($counts['unique_clicks'] ?? 0) + (int)($link['initial_unique_clicks'] ?? 0);
        $conversionRate = $totalClicks > 0 ? round(($uniqueClicks / $totalClicks) * 100, 1) : 0.0;

        // Referrers
        $refStmt = $pdo->prepare("
            SELECT COALESCE(referrer_domain, 'Direct') AS domain, COUNT(*) AS count
            FROM clicks
            WHERE link_id = :lid AND clicked_at >= :d
            GROUP BY domain ORDER BY count DESC LIMIT 6
        ");
        $refStmt->execute([':lid' => $linkId, ':d' => $filterDate]);

        // Devices
        $devStmt = $pdo->prepare("
            SELECT device_type, COUNT(*) AS count
            FROM clicks
            WHERE link_id = :lid AND clicked_at >= :d
            GROUP BY device_type ORDER BY count DESC
        ");
        $devStmt->execute([':lid' => $linkId, ':d' => $filterDate]);

        // Countries
        $geoStmt = $pdo->prepare("
            SELECT country_code, COUNT(*) AS count
            FROM clicks
            WHERE link_id = :lid AND clicked_at >= :d
            GROUP BY country_code ORDER BY count DESC LIMIT 10
        ");
        $geoStmt->execute([':lid' => $linkId, ':d' => $filterDate]);

        // Recent Click logs
        $logsStmt = $pdo->prepare("
            SELECT * FROM clicks 
            WHERE link_id = :lid 
            ORDER BY clicked_at DESC 
            LIMIT 25
        ");
        $logsStmt->execute([':lid' => $linkId]);

        return [
            'link'            => $link,
            'total_clicks'    => $totalClicks,
            'unique_clicks'   => $uniqueClicks,
            'conversion_rate' => $conversionRate,
            'timeline'        => self::getTimelineSeries($linkId, $period),
            'referrers'       => $refStmt->fetchAll(),
            'devices'         => $devStmt->fetchAll(),
            'countries'       => $geoStmt->fetchAll(),
            'recent_logs'     => $logsStmt->fetchAll(),
            'period'          => $period
        ];
    }

    /**
     * Build Timeline Chart Data
     */
    private static function getTimelineSeries(?int $linkId, string $period): array
    {
        $pdo = Database::getConnection();

        if ($period === '24h') {
            // Group by hour for last 24h
            $sql = "
                SELECT 
                    strftime('%Y-%m-%d %H:00', clicked_at) AS time_point,
                    COUNT(*) AS total_clicks,
                    COALESCE(SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END), 0) AS unique_clicks
                FROM clicks
                WHERE clicked_at >= datetime('now', '-24 hours')
            ";
            if ($linkId !== null) {
                $sql .= " AND link_id = " . (int)$linkId;
            }
            $sql .= " GROUP BY time_point ORDER BY time_point ASC";
        } else {
            // Group by day
            $days = ($period === '7d') ? 7 : 30;
            $sql = "
                SELECT 
                    strftime('%Y-%m-%d', clicked_at) AS time_point,
                    COUNT(*) AS total_clicks,
                    COALESCE(SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END), 0) AS unique_clicks
                FROM clicks
                WHERE clicked_at >= datetime('now', '-{$days} days')
            ";
            if ($linkId !== null) {
                $sql .= " AND link_id = " . (int)$linkId;
            }
            $sql .= " GROUP BY time_point ORDER BY time_point ASC";
        }

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();

        $labels = [];
        $totalPoints = [];
        $uniquePoints = [];

        foreach ($rows as $r) {
            $labels[] = $r['time_point'];
            $totalPoints[] = (int)$r['total_clicks'];
            $uniquePoints[] = (int)$r['unique_clicks'];
        }

        return [
            'labels' => $labels,
            'totals' => $totalPoints,
            'uniques' => $uniquePoints
        ];
    }

    /**
     * Resolve boundary date string from period identifier
     */
    private static function resolvePeriodDate(string $period): string
    {
        return match ($period) {
            '24h'   => date('Y-m-d H:i:s', strtotime('-24 hours')),
            '7d'    => date('Y-m-d 00:00:00', strtotime('-7 days')),
            'all'   => '1970-01-01 00:00:00',
            default => date('Y-m-d 00:00:00', strtotime('-30 days')),
        };
    }

    /**
     * Export Links list to CSV or JSON
     */
    public static function exportLinks(string $format = 'csv'): string
    {
        $links = self::getAll();

        if ($format === 'json') {
            return (string)json_encode($links, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // CSV format
        $output = fopen('php://temp', 'r+');
        // UTF-8 BOM for Excel Arabic support
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            I18n::t('csv_id'),
            I18n::t('csv_slug'),
            I18n::t('csv_title'),
            I18n::t('csv_target'),
            I18n::t('csv_type'),
            I18n::t('csv_active'),
            I18n::t('csv_total_clicks'),
            I18n::t('csv_unique_clicks'),
            I18n::t('csv_created_at')
        ]);

        foreach ($links as $l) {
            fputcsv($output, [
                $l['id'],
                $l['slug'],
                $l['title'] ?? '',
                $l['target_url'],
                $l['redirect_type'],
                $l['is_active'] ? I18n::t('status_active') : I18n::t('status_paused'),
                $l['total_clicks'],
                $l['unique_clicks'],
                $l['created_at']
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return (string)$csvContent;
    }

    /**
     * Export Click Logs to CSV or JSON
     */
    public static function exportClicks(?int $linkId = null, string $format = 'csv'): string
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT c.*, l.slug, l.title, l.target_url
            FROM clicks c
            JOIN links l ON c.link_id = l.id
        ";
        if ($linkId !== null) {
            $sql .= " WHERE c.link_id = " . (int)$linkId;
        }
        $sql .= " ORDER BY c.clicked_at DESC LIMIT 10000";

        $stmt = $pdo->query($sql);
        $clicks = $stmt->fetchAll();

        if ($format === 'json') {
            return (string)json_encode($clicks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // CSV format
        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            I18n::t('csv_click_id'),
            I18n::t('csv_slug'),
            I18n::t('csv_link_title'),
            I18n::t('col_ip_address'),
            I18n::t('csv_is_unique'),
            I18n::t('csv_country'),
            I18n::t('csv_referrer'),
            I18n::t('csv_device'),
            I18n::t('csv_browser'),
            I18n::t('csv_os'),
            I18n::t('csv_click_time')
        ]);

        foreach ($clicks as $c) {
            fputcsv($output, [
                $c['id'],
                $c['slug'],
                $c['title'] ?? '',
                !empty($c['ip_address']) ? $c['ip_address'] : ($c['ip_hash'] ?? ''),
                $c['is_unique'] ? I18n::t('yes_label') : I18n::t('no_label'),
                $c['country_code'],
                $c['referrer_domain'] ?? I18n::t('direct_referrer'),
                $c['device_type'],
                $c['browser'],
                $c['platform'],
                $c['clicked_at']
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return (string)$csvContent;
    }
}
