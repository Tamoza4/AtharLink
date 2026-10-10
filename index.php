<?php
declare(strict_types=1);

/**
 * AtharLink Application Front Controller & Dashboard Router
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/LinkManager.php';
require_once __DIR__ . '/src/I18n.php';
require_once __DIR__ . '/src/Icon.php';
require_once __DIR__ . '/src/Updater.php';
require_once __DIR__ . '/src/AuditLogger.php';

use AtharLink\Helpers;
use AtharLink\Database;
use AtharLink\Auth;
use AtharLink\LinkManager;
use AtharLink\I18n;
use AtharLink\Icon;
use AtharLink\Updater;
use AtharLink\AuditLogger;

$action = $_GET['action'] ?? '';
$page   = $_GET['page'] ?? 'overview';

// Language Toggle (Dual-Language AR / EN) - Available everywhere including installer
if ($action === 'toggle_lang') {
    $current = I18n::getLang();
    $newLang = $current === 'en' ? 'ar' : 'en';
    setcookie('athar_lang', $newLang, time() + 31536000, '/');
    $redirect = $_SERVER['HTTP_REFERER'] ?? Helpers::baseUrl();
    header('Location: ' . $redirect);
    exit;
}

// ----------------------------------------------------
// 1. SELF-INSTALLATION & FIRST-RUN SETUP
// ----------------------------------------------------
if (!Auth::isInstalled()) {
    if ($action === 'do_install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim((string)($_POST['admin_username'] ?? ''));
        $pass1    = (string)($_POST['admin_password'] ?? '');
        $pass2    = (string)($_POST['admin_password_confirm'] ?? '');

        if ($pass1 !== $pass2) {
            $errorMessage = I18n::t('passwords_dont_match');
            require __DIR__ . '/views/install/index.php';
            exit;
        }

        try {
            // 1. Initialize schema & WAL mode
            Database::initSchema();

            // 2. Create primary admin
            $userRes = Auth::createUser($username, $pass1);
            if (!$userRes['success']) {
                $errorMessage = $userRes['message'];
                require __DIR__ . '/views/install/index.php';
                exit;
            }

            // 3. Create installed.lock file & Emergency Recovery Key
            @file_put_contents(LOCK_FILE, json_encode([
                'installed_at' => date('c'),
                'version'      => APP_VERSION
            ]));
            Auth::getRecoveryKey();

            $_SESSION['flash_success'] = I18n::t('install_success');
            header('Location: ' . Helpers::baseUrl('index.php'));
            exit;

        } catch (\Throwable $e) {
            $errorMessage = I18n::t('install_error_prefix') . $e->getMessage();
            require __DIR__ . '/views/install/index.php';
            exit;
        }
    }

    // Show installer screen
    require __DIR__ . '/views/install/index.php';
    exit;
}

// Block installer if already installed
if ($action === 'do_install') {
    die(I18n::t('install_already_done'));
}

// ----------------------------------------------------
// 2. PUBLIC ACTIONS (No login required)
// ----------------------------------------------------





// AJAX Live Slug Availability Check
if ($action === 'check_slug') {
    $slug = (string)($_GET['slug'] ?? '');
    $isAvailable = Helpers::isValidSlug($slug) && LinkManager::isSlugAvailable($slug);
    Helpers::json(['available' => $isAvailable]);
}

// Public Link Count Endpoint (CORS enabled, Zero token required)
if ($action === 'get_count' || $action === 'public_counter') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit;
    }

    $slug = trim((string)($_GET['slug'] ?? ''));
    $type = ($_GET['type'] ?? 'unique') === 'all' ? 'all' : 'unique';
    $link = LinkManager::findBySlug($slug);
    if (!$link) {
        Helpers::json(['success' => false, 'error' => 'Link not found'], 404);
    }
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(id) AS total_clicks,
            COALESCE(SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END), 0) AS unique_clicks
        FROM clicks 
        WHERE link_id = :lid
    ");
    $stmt->execute([':lid' => $link['id']]);
    $stats = $stmt->fetch();
    $total = (int)($stats['total_clicks'] ?? 0) + (int)($link['initial_clicks'] ?? 0);
    $unique = (int)($stats['unique_clicks'] ?? 0) + (int)($link['initial_unique_clicks'] ?? 0);

    Helpers::json([
        'success'       => true,
        'slug'          => $slug,
        'clicks'        => $type === 'all' ? $total : $unique,
        'total_clicks'  => $total,
        'unique_clicks' => $unique
    ]);
}

// Login Handling
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!Helpers::verifyCsrf($token)) {
            $errorMessage = I18n::t('session_expired');
            require __DIR__ . '/views/auth/login.php';
            exit;
        }

        $res = Auth::login((string)$_POST['username'], (string)$_POST['password']);
        if ($res['success']) {
            $_SESSION['flash_success'] = I18n::t('welcome_back');
            header('Location: ' . Helpers::baseUrl());
            exit;
        } else {
            $errorMessage = $res['message'];
            require __DIR__ . '/views/auth/login.php';
            exit;
        }
    }

    if (Auth::check()) {
        header('Location: ' . Helpers::baseUrl());
        exit;
    }

    require __DIR__ . '/views/auth/login.php';
    exit;
}

// Emergency Password Recovery Handling
if ($action === 'recover_password') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!Helpers::verifyCsrf($token)) {
            $errorMessage = I18n::t('session_expired');
            require __DIR__ . '/views/auth/login.php';
            exit;
        }

        $key = trim((string)($_POST['recovery_key'] ?? ''));
        $newPass = (string)($_POST['new_password'] ?? '');

        $res = Auth::resetPasswordWithRecoveryKey($key, $newPass);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
            header('Location: ' . Helpers::baseUrl('index.php?action=login'));
            exit;
        } else {
            $errorMessage = $res['message'];
            require __DIR__ . '/views/auth/login.php';
            exit;
        }
    }

    require __DIR__ . '/views/auth/login.php';
    exit;
}

// Logout Handling
if ($action === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (Helpers::verifyCsrf($token)) {
            Auth::logout();
            $_SESSION['flash_success'] = I18n::t('logout_success');
        }
    } else {
        Auth::logout();
        $_SESSION['flash_success'] = I18n::t('logout_success');
    }
    $adminSlug = Database::getSetting('admin_login_slug', 'admin');
    if (empty($adminSlug)) $adminSlug = 'admin';
    header('Location: ' . Helpers::baseUrl($adminSlug));
    exit;
}

// ----------------------------------------------------
// 3. AUTHENTICATION ENFORCEMENT
// ----------------------------------------------------
if (!Auth::check()) {
    $requestPath = Helpers::getRequestPath();
    $adminSlug = Database::getSetting('admin_login_slug', 'admin');
    if (empty($adminSlug)) {
        $adminSlug = 'admin';
    }

    // 1. If visitor requested the admin entrance slug or standard login action/page
    if ($requestPath === $adminSlug || $action === 'login' || $page === 'login') {
        require __DIR__ . '/views/auth/login.php';
        exit;
    }

    // 2. If an unauthenticated AJAX request was made, return 401 JSON instead of HTML login page
    if (Helpers::isAjax()) {
        Helpers::json(['success' => false, 'message' => I18n::t('session_expired')], 401);
    }

    // 3. Check if guest homepage / unauthenticated redirection is enabled and configured
    $redirectEnabled = Database::getSetting('guest_redirect_enabled', '0') === '1';
    $guestRedirect = Database::getSetting('guest_redirect_url', '');
    if ($redirectEnabled && !empty($guestRedirect)) {
        $targetHost = parse_url($guestRedirect, PHP_URL_HOST);
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        // Prevent redirect loop if target host matches current host
        if ($targetHost && strtolower($targetHost) !== strtolower($currentHost)) {
            header('Location: ' . $guestRedirect, true, 302);
            exit;
        }
    }

    // 4. Default fallback: show login page
    require __DIR__ . '/views/auth/login.php';
    exit;
}

// ----------------------------------------------------
// 4. PROTECTED ACTIONS (Admin Only)
// ----------------------------------------------------

// Create Link
if ($action === 'create_link' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        $res = LinkManager::create($_POST);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=links'));
    exit;
}

// Update Link
if ($action === 'update_link' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        $id = (int)($_POST['link_id'] ?? 0);
        $res = LinkManager::update($id, $_POST);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=links'));
    exit;
}

// Delete Link
if ($action === 'delete_link' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        $id = (int)($_POST['link_id'] ?? 0);
        LinkManager::delete($id);
        $_SESSION['flash_success'] = I18n::t('link_deleted_success');
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=links'));
    exit;
}

// Toggle Link State (Pause / Resume)
if ($action === 'toggle_link') {
    $token = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
    $id    = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));

    if (!Helpers::verifyCsrf($token)) {
        $_SESSION['flash_error'] = I18n::t('security_check_failed');
    } else {
        LinkManager::toggleActive($id);
        $_SESSION['flash_success'] = I18n::t('link_status_updated');
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=links'));
    exit;
}

// Reset Link Clicks
if ($action === 'reset_clicks' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        $id = (int)($_POST['link_id'] ?? 0);
        LinkManager::resetClicks($id);
        $_SESSION['flash_success'] = I18n::t('clicks_reset_success');
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=links'));
    exit;
}

// Export Links to CSV / JSON
if ($action === 'export_links') {
    $format = $_GET['format'] ?? 'csv';
    $content = LinkManager::exportLinks($format);

    if ($format === 'json') {
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="athar_links_' . date('Y-m-d') . '.json"');
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="athar_links_' . date('Y-m-d') . '.csv"');
    }

    echo $content;
    exit;
}

// Export Clicks to CSV / JSON
if ($action === 'export_clicks') {
    $format = $_GET['format'] ?? 'csv';
    $linkId = isset($_GET['link_id']) ? (int)$_GET['link_id'] : null;
    $content = LinkManager::exportClicks($linkId, $format);

    $slugSuffix = '';
    if ($linkId !== null) {
        $singleLink = LinkManager::findById($linkId);
        if ($singleLink && !empty($singleLink['slug'])) {
            $slugClean = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$singleLink['slug']);
            if ($slugClean !== '') {
                $slugSuffix = '_' . $slugClean;
            }
        }
    }

    if ($format === 'json') {
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="athar_clicks' . $slugSuffix . '_' . date('Y-m-d') . '.json"');
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="athar_clicks' . $slugSuffix . '_' . date('Y-m-d') . '.csv"');
    }

    echo $content;
    exit;
}

// Fetch Single Link Stats for Report Card Preview
if ($action === 'get_link_report_data') {
    $linkId = (int)($_GET['id'] ?? 0);
    $stats = LinkManager::getLinkStats($linkId, 'all');
    if (!$stats) {
        Helpers::json(['success' => false, 'error' => 'Link not found'], 404);
    }
    Helpers::json([
        'success'         => true,
        'slug'            => $stats['link']['slug'],
        'title'           => $stats['link']['title'] ?? '',
        'target_url'      => $stats['link']['target_url'],
        'is_active'       => (int)$stats['link']['is_active'],
        'total_clicks'    => $stats['total_clicks'],
        'unique_clicks'   => $stats['unique_clicks'],
        'conversion_rate' => $stats['conversion_rate'],
        'countries'       => $stats['countries'] ?? [],
        'devices'         => $stats['devices'] ?? [],
        'referrers'       => $stats['referrers'] ?? []
    ]);
}

// Fetch Global Overview Stats for Report Card Preview
if ($action === 'get_overview_report_data') {
    $period = $_GET['period'] ?? '30d';
    $stats = LinkManager::getOverviewStats($period);
    Helpers::json([
        'success'         => true,
        'period'          => $stats['period'],
        'total_links'     => $stats['total_links'],
        'active_links'    => $stats['active_links'],
        'total_clicks'    => $stats['total_clicks'],
        'all_time_clicks' => $stats['all_time_clicks'],
        'unique_clicks'   => $stats['unique_clicks'],
        'avg_unique_rate' => $stats['avg_unique_rate'],
        'peak_hour'       => $stats['peak_hour'],
        'top_links'       => $stats['top_links'] ?? [],
        'countries'       => $stats['countries'] ?? [],
        'devices'         => $stats['devices'] ?? []
    ]);
}

// Regenerate API Token
if ($action === 'regen_token' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        Auth::regenerateApiToken((int)Auth::id());
        AuditLogger::log('regen_token', 'Regenerated admin API Bearer Token');
        $_SESSION['flash_success'] = I18n::t('token_regen_success');
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=settings'));
    exit;
}

// Save Settings
if ($action === 'save_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        $hours = max(1.0, (float)($_POST['uniqueness_hours'] ?? 24));
        $seconds = (int)round($hours * 3600);

        // Guest Redirection Feature (Enable Toggle + URL)
        $redirectEnabled = !empty($_POST['guest_redirect_enabled']) ? '1' : '0';
        $guestRedirect = isset($_POST['guest_redirect_url'])
            ? trim((string)$_POST['guest_redirect_url'])
            : Database::getSetting('guest_redirect_url', '');

        if ($redirectEnabled === '1' && !empty($guestRedirect)) {
            if (!filter_var($guestRedirect, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $guestRedirect)) {
                $_SESSION['flash_error'] = I18n::t('guest_redirect_invalid');
                header('Location: ' . Helpers::baseUrl('index.php?page=settings'));
                exit;
            }
        }

        // Custom Admin Entrance Slug validation & saving
        $adminSlug = trim((string)($_POST['admin_login_slug'] ?? 'admin'), " /\t\n\r\0\x0B");
        if (empty($adminSlug)) {
            $adminSlug = 'admin';
        }
        $reservedSlugs = ['api', 'c', 'r', 'assets', 'storage', 'bin', 'config', 'index.php', 'r.php', 'embed.js', 'badge.php'];
        if (!preg_match('/^[a-zA-Z0-9_-]{2,40}$/', $adminSlug) || in_array(strtolower($adminSlug), $reservedSlugs, true)) {
            $_SESSION['flash_error'] = I18n::t('admin_slug_invalid');
            header('Location: ' . Helpers::baseUrl('index.php?page=settings'));
            exit;
        }

        // Timezone validation & saving
        $appTimezone = trim((string)($_POST['app_timezone'] ?? 'UTC'));
        if (!in_array($appTimezone, timezone_identifiers_list(), true)) {
            $appTimezone = 'UTC';
        }

        Database::setSetting('uniqueness_window', (string)$seconds);
        Database::setSetting('guest_redirect_enabled', $redirectEnabled);
        Database::setSetting('guest_redirect_url', $guestRedirect);
        Database::setSetting('admin_login_slug', $adminSlug);
        Database::setSetting('app_timezone', $appTimezone);
        Helpers::applyTimezone(true);
        Helpers::getTimezoneModifier(true);

        AuditLogger::log('save_settings', 'Updated system settings', [
            'uniqueness_hours'       => $hours,
            'guest_redirect_enabled' => $redirectEnabled,
            'guest_redirect_url'     => $guestRedirect,
            'admin_login_slug'       => $adminSlug,
            'app_timezone'           => $appTimezone
        ]);

        $_SESSION['flash_success'] = I18n::t('settings_saved_success');
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=settings'));
    exit;
}

// Download SQLite Database Backup
if ($action === 'download_backup') {
    if (!file_exists(DB_PATH)) {
        $_SESSION['flash_error'] = I18n::t('db_file_not_found');
        header('Location: ' . Helpers::baseUrl('index.php?page=settings'));
        exit;
    }

    $filename = 'athar_backup_' . date('Y-m-d_H-i') . '.sqlite';
    AuditLogger::log('download_backup', 'Downloaded database backup: ' . $filename);

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.sqlite3');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize(DB_PATH));

    readfile(DB_PATH);
    exit;
}

// Restore SQLite Database Backup
if ($action === 'restore_backup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = I18n::t('backup_upload_error');
        } else {
            $tmpPath = $_FILES['backup_file']['tmp_name'];

            // 1. Verify SQLite Header (SQLite format 3\000)
            $handle = @fopen($tmpPath, 'rb');
            $header = $handle ? fread($handle, 16) : '';
            if ($handle) fclose($handle);

            if ($header !== "SQLite format 3\000") {
                $_SESSION['flash_error'] = I18n::t('backup_invalid_format');
            } else {
                // 2. Reject any file containing PHP executable tags
                $contentSample = file_get_contents($tmpPath, false, null, 0, 8192);
                if (preg_match('/<\?php|<\?=/i', (string)$contentSample)) {
                    $_SESSION['flash_error'] = I18n::t('backup_security_rejected');
                } else {
                    // 1. Capture current admin credentials so current password never gets overwritten
                    $currentUsers = [];
                    try {
                        $oldPdo = Database::getConnection();
                        $currentUsers = $oldPdo->query("SELECT * FROM users")->fetchAll(\PDO::FETCH_ASSOC);
                    } catch (\Throwable) {}

                    // 2. Safe restore copy
                    @copy($tmpPath, DB_PATH);

                    // 3. Automatically restore current users so password remains unchanged
                    if (!empty($currentUsers)) {
                        try {
                            $newPdo = new \PDO('sqlite:' . DB_PATH, null, null, [
                                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                            ]);
                            $newPdo->exec("DELETE FROM users;");
                            $insertUserStmt = $newPdo->prepare("
                                INSERT INTO users (id, username, password_hash, api_token, failed_login_attempts, locked_until, created_at)
                                VALUES (:id, :username, :password_hash, :api_token, :failed_login_attempts, :locked_until, :created_at)
                            ");
                            foreach ($currentUsers as $u) {
                                $insertUserStmt->execute([
                                    ':id'                    => $u['id'],
                                    ':username'              => $u['username'],
                                    ':password_hash'         => $u['password_hash'],
                                    ':api_token'             => $u['api_token'] ?? null,
                                    ':failed_login_attempts' => $u['failed_login_attempts'] ?? 0,
                                    ':locked_until'          => $u['locked_until'] ?? null,
                                    ':created_at'            => $u['created_at'] ?? date('Y-m-d H:i:s')
                                ]);
                            }
                        } catch (\Throwable $e) {
                            error_log('AtharLink Admin Auto-Preserve Notice: ' . $e->getMessage());
                        }
                    }

                    // 4. Re-verify schema migrations and integrity
                    try {
                        Database::initSchema();
                    } catch (\Throwable $e) {}

                    AuditLogger::log('restore_backup', 'Restored database from uploaded backup file (kept active credentials)', [
                        'filename' => $_FILES['backup_file']['name'] ?? 'backup.sqlite'
                    ]);

                    $_SESSION['flash_success'] = I18n::t('backup_restored_success');
                }
            }
        }
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=settings'));
    exit;
}

// Check for System Updates (Force Refresh)
if ($action === 'check_updates') {
    $result = Updater::check(true);
    if (Helpers::isAjax()) {
        Helpers::json($result);
    }
    if (!empty($result['error'])) {
        $_SESSION['flash_error'] = I18n::t('update_failed_flash', ['error' => $result['error']]);
    } elseif (!empty($result['has_update'])) {
        $_SESSION['flash_success'] = I18n::t('update_available_badge', ['version' => 'v' . $result['latest']]);
    } else {
        $_SESSION['flash_success'] = I18n::t('system_up_to_date');
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=settings'));
    exit;
}

// Apply 1-Click Update
if ($action === 'apply_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        if (Helpers::isAjax()) {
            Helpers::json(['success' => false, 'message' => I18n::t('csrf_invalid')], 403);
        }
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        $res = Updater::applyUpdate();
        if (Helpers::isAjax()) {
            Helpers::json($res);
        }
        if ($res['success']) {
            AuditLogger::log('apply_update', 'Applied system update successfully', ['version' => $res['latest'] ?? '']);
            $_SESSION['flash_success'] = $res['message'];
        } elseif (!empty($res['already_latest'])) {
            $_SESSION['flash_info'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = I18n::t('update_failed_flash', ['error' => $res['message']]);
        }
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=settings'));
    exit;
}

// Rebuild & Repair System Files and Integrity
if ($action === 'rebuild_repair' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        if (Helpers::isAjax()) {
            Helpers::json(['success' => false, 'message' => I18n::t('csrf_invalid')], 403);
        }
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        $res = Updater::rebuildSystem();
        if (Helpers::isAjax()) {
            Helpers::json($res);
        }
        if ($res['success']) {
            AuditLogger::log('rebuild_repair', 'Rebuilt and repaired system files and database integrity');
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'] ?? 'Rebuild error';
        }
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=settings'));
    exit;
}

// Prune Audit Logs (e.g. older than 30, 60, or 90 days)
if ($action === 'prune_logs' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        $days = max(1, (int)($_POST['days'] ?? 60));
        $deleted = AuditLogger::prune($days);
        AuditLogger::log('prune_logs', 'Pruned audit logs older than ' . $days . ' days', ['deleted_count' => $deleted, 'days' => $days]);
        $_SESSION['flash_success'] = I18n::t('logs_pruned_success', ['count' => $deleted, 'days' => $days]);
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=logs'));
    exit;
}

// Clear All Audit Logs
if ($action === 'clear_logs' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = I18n::t('csrf_invalid');
    } else {
        AuditLogger::clearAll();
        AuditLogger::log('clear_logs', 'Cleared all audit logs');
        $_SESSION['flash_success'] = I18n::t('logs_cleared_success');
    }
    header('Location: ' . Helpers::baseUrl('index.php?page=logs'));
    exit;
}

// Export Audit Logs to CSV / JSON
if ($action === 'export_logs') {
    $format = ($_GET['format'] ?? 'csv') === 'json' ? 'json' : 'csv';
    $content = AuditLogger::export($format);
    AuditLogger::log('export_logs', 'Exported audit logs in format: ' . strtoupper($format));

    if ($format === 'json') {
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="athar_audit_logs_' . date('Y-m-d') . '.json"');
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="athar_audit_logs_' . date('Y-m-d') . '.csv"');
    }
    echo $content;
    exit;
}

// ----------------------------------------------------
// 5. VIEW RENDERING & ROUTING
// ----------------------------------------------------

switch ($page) {
    case 'links':
        $activePage = 'links';
        $pageTitle = I18n::t('links_title');
        $links = LinkManager::getAll();
        require __DIR__ . '/views/layout/header.php';
        require __DIR__ . '/views/dashboard/links.php';
        require __DIR__ . '/views/layout/footer.php';
        break;

    case 'stats':
        $activePage = 'links';
        $pageTitle = I18n::t('stats_page_title');
        $linkId = (int)($_GET['id'] ?? 0);
        $stats = LinkManager::getLinkStats($linkId, $_GET['period'] ?? '30d');

        if (!$stats) {
            $_SESSION['flash_error'] = I18n::t('link_not_found');
            header('Location: ' . Helpers::baseUrl('index.php?page=links'));
            exit;
        }

        require __DIR__ . '/views/layout/header.php';
        require __DIR__ . '/views/dashboard/stats.php';
        require __DIR__ . '/views/layout/footer.php';
        break;

    case 'logs':
        $activePage = 'logs';
        $pageTitle = I18n::t('nav_logs');
        $filterAction = !empty($_GET['filter_action']) ? (string)$_GET['filter_action'] : null;
        $search = trim((string)($_GET['q'] ?? ''));
        $pageNum = max(1, (int)($_GET['p'] ?? 1));
        $perPage = 50;
        $totalLogs = AuditLogger::countLogs($filterAction, $search);
        $totalPages = max(1, (int)ceil($totalLogs / $perPage));
        $offset = ($pageNum - 1) * $perPage;
        $logs = AuditLogger::getLogs($perPage, $offset, $filterAction, $search);
        $distinctActions = AuditLogger::getDistinctActions();

        require __DIR__ . '/views/layout/header.php';
        require __DIR__ . '/views/dashboard/logs.php';
        require __DIR__ . '/views/layout/footer.php';
        break;

    case 'settings':
        $activePage = 'settings';
        $pageTitle = I18n::t('settings_title');
        require __DIR__ . '/views/layout/header.php';
        require __DIR__ . '/views/dashboard/settings.php';
        require __DIR__ . '/views/layout/footer.php';
        break;

    case 'overview':
    default:
        $activePage = 'overview';
        $pageTitle = I18n::t('nav_overview');
        $stats = LinkManager::getOverviewStats($_GET['period'] ?? '30d');
        require __DIR__ . '/views/layout/header.php';
        require __DIR__ . '/views/dashboard/index.php';
        require __DIR__ . '/views/layout/footer.php';
        break;
}
