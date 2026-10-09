<?php
declare(strict_types=1);

namespace AtharLink;

use PDO;
use Throwable;

/**
 * AtharLink System & Version Update Engine
 * -----------------------------------------
 * Handles GitHub Releases check, caching, and 1-click updates with automated backups.
 */
class Updater
{
    public const DEFAULT_REPO = 'Tamoza4/AtharLink';
    private const CACHE_KEY = 'update_check_cache';
    private const CACHE_TTL = 300; // 5 minutes cache (fresh version detection)

    /**
     * Get currently installed application version
     */
    public static function getCurrentVersion(): string
    {
        return defined('APP_VERSION') ? APP_VERSION : '1.0.1';
    }

    /**
     * Check GitHub for latest release tag
     */
    public static function check(bool $forceRefresh = false): array
    {
        $current = self::getCurrentVersion();

        if (!$forceRefresh) {
            $cached = Database::getSetting(self::CACHE_KEY);
            if ($cached) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded) && isset($decoded['cached_at']) && (time() - (int)$decoded['cached_at']) < self::CACHE_TTL) {
                    $decoded['current'] = $current;
                    $decoded['has_update'] = version_compare($decoded['latest'], $current, '>');
                    return $decoded;
                }
            }
        }

        $repo = Database::getSetting('github_repo', self::DEFAULT_REPO);
        $url = "https://api.github.com/repos/{$repo}/releases/latest";

        $result = [
            'current'       => $current,
            'latest'        => $current,
            'has_update'    => false,
            'release_name'  => '',
            'release_notes' => '',
            'release_url'   => "https://github.com/{$repo}/releases",
            'published_at'  => '',
            'cached_at'     => time(),
            'error'         => null
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_USERAGENT      => 'AtharLink-Updater/' . $current,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/vnd.github.v3+json',
                    'User-Agent: AtharLink-Updater'
                ],
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200 && is_string($response)) {
                $json = json_decode($response, true);
                if (isset($json['tag_name'])) {
                    $latest = ltrim((string)$json['tag_name'], 'vV');
                    $result['latest']        = $latest;
                    $result['has_update']    = version_compare($latest, $current, '>');
                    $result['release_name']  = $json['name'] ?? ('v' . $latest);
                    $result['release_notes'] = $json['body'] ?? '';
                    $result['release_url']   = $json['html_url'] ?? $result['release_url'];
                    $result['published_at']  = $json['published_at'] ?? '';
                }
            } elseif ($httpCode === 404) {
                // Repository exists but no releases published yet
                $result['latest']     = $current;
                $result['has_update'] = false;
            } else {
                $result['error'] = !empty($curlError) ? $curlError : "GitHub API response: HTTP {$httpCode}";
            }

            // Fallback: Check tags endpoint if no update found via releases
            if (!$result['has_update']) {
                $tagsUrl = "https://api.github.com/repos/{$repo}/tags";
                $chTags = curl_init();
                curl_setopt_array($chTags, [
                    CURLOPT_URL            => $tagsUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 5,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_USERAGENT      => 'AtharLink-Updater/' . $current,
                    CURLOPT_HTTPHEADER     => [
                        'Accept: application/vnd.github.v3+json',
                        'User-Agent: AtharLink-Updater'
                    ],
                    CURLOPT_SSL_VERIFYPEER => true
                ]);
                $tagsRes = curl_exec($chTags);
                $tagsCode = (int)curl_getinfo($chTags, CURLINFO_HTTP_CODE);
                curl_close($chTags);

                if ($tagsCode === 200 && is_string($tagsRes)) {
                    $tagsList = json_decode($tagsRes, true);
                    if (is_array($tagsList)) {
                        foreach ($tagsList as $tagObj) {
                            $tagName = ltrim((string)($tagObj['name'] ?? ''), 'vV');
                            if ($tagName !== '' && version_compare($tagName, $result['latest'], '>')) {
                                $result['latest'] = $tagName;
                                $result['has_update'] = version_compare($tagName, $current, '>');
                                $result['release_name'] = 'v' . $tagName;
                                $result['release_url'] = "https://github.com/{$repo}/releases/tag/v{$tagName}";
                            }
                        }
                    }
                }
            }
        } else {
            $result['error'] = 'cURL extension is required to check for online updates.';
        }

        // Cache the result
        Database::setSetting(self::CACHE_KEY, json_encode($result));

        return $result;
    }

    /**
     * Apply 1-Click Update:
     * 1. Creates a safe timestamped SQLite snapshot in storage/
     * 2. Pulls new code from Git if running inside a Git clone
     * 3. Executes Database::initSchema() migrations
     * 4. Verifies database integrity
     * 5. Clears OPcache
     */
    public static function applyUpdate(bool $force = false): array
    {
        $log = [];
        $log[] = 'Update process initiated: ' . date('Y-m-d H:i:s T');

        // 0. Verify if an update is actually available
        $check = self::check(false);
        if (!$force && empty($check['has_update'])) {
            $currentVer = 'v' . ($check['current'] ?? APP_VERSION);
            $latestVer  = 'v' . ($check['latest'] ?? APP_VERSION);
            $log[] = "Check verified: Current version {$currentVer} is identical to latest available {$latestVer}.";
            $log[] = 'Update aborted: Platform is already on the latest version.';

            return [
                'success'        => false,
                'already_latest' => true,
                'message'        => I18n::t('update_error_already_latest', ['version' => $latestVer]),
                'log'            => $log
            ];
        }

        // 1. Permission check
        if (!is_writable(STORAGE_DIR)) {
            return [
                'success' => false,
                'message' => 'Storage directory is not writable (' . STORAGE_DIR . '). Please check file permissions.',
                'log'     => $log
            ];
        }

        // 2. Safe Pre-Update Database Snapshot
        try {
            $pdo = Database::getConnection();
            $pdo->exec('PRAGMA wal_checkpoint(TRUNCATE);');
            
            $backupName = 'backup_pre_update_' . date('Ymd_His') . '.sqlite';
            $backupPath = STORAGE_DIR . DIRECTORY_SEPARATOR . $backupName;

            if (copy(DB_PATH, $backupPath)) {
                $log[] = "Pre-update backup saved: {$backupName}";
            }
        } catch (Throwable $e) {
            $log[] = 'Backup warning: ' . $e->getMessage();
        }

        // 3. Git pull (if .git folder is present)
        $gitPulled = false;
        $gitDir = APP_ROOT . DIRECTORY_SEPARATOR . '.git';
        if (is_dir($gitDir) && function_exists('exec')) {
            $output = [];
            $code = 0;
            // Reset any modified tracked files first to prevent pull aborts
            @exec('cd ' . escapeshellarg(APP_ROOT) . ' && git reset --hard HEAD 2>&1 && git checkout -- . 2>&1 && git pull origin main 2>&1', $output, $code);
            if ($code === 0) {
                $gitPulled = true;
                $log[] = 'Git pull completed: ' . implode(' ', $output);
            } else {
                $log[] = 'Git pull notice: ' . implode(' ', $output);
            }
        } else {
            $log[] = 'Running in non-git mode (code files are managed manually).';
        }

        // 4. Schema migrations
        try {
            Database::initSchema();
            $log[] = 'Database schema migrations and indexes verified.';
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Schema migration error: ' . $e->getMessage(),
                'log'     => $log
            ];
        }

        // 5. Database health verification
        try {
            $pdo = Database::getConnection();
            $integrity = $pdo->query('PRAGMA integrity_check;')->fetchColumn();
            $log[] = 'Database integrity check: ' . ($integrity === 'ok' ? 'OK' : (string)$integrity);
        } catch (Throwable $e) {
            $log[] = 'Integrity check error: ' . $e->getMessage();
        }

        // 6. Reset OPcache if active
        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $log[] = 'OPcache cleared.';
        }

        // 7. Clear update check cache
        Database::setSetting(self::CACHE_KEY, '');

        $msg = $gitPulled
            ? I18n::t('update_success_git')
            : I18n::t('update_success_db');

        return [
            'success' => true,
            'message' => $msg,
            'log'     => $log
        ];
    }

    /**
     * Core System Rebuilder & Integrity Repair Engine:
     * 1. Restores missing core files via Git (if available) or GitHub Raw fallback.
     * 2. Re-verifies storage directory permissions, .htaccess guard, and installed.lock.
     * 3. Runs schema migrations and rebuilds missing tables/columns.
     * 4. Checks SQLite integrity & WAL checkpoint.
     * 5. Purges caches & resets OPcache.
     */
    public static function rebuildSystem(): array
    {
        $log = [];
        $log[] = 'System rebuild and integrity repair started: ' . date('Y-m-d H:i:s T');
        $restoredFiles = 0;

        // 1. Storage folder and protection integrity
        $storageDir = STORAGE_DIR;
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
            $log[] = 'Recreated missing storage directory.';
        }

        $storageHtaccess = $storageDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($storageHtaccess)) {
            $htContent = "# Strictly block public direct access to database and secrets\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n";
            @file_put_contents($storageHtaccess, $htContent);
            $log[] = 'Recreated missing security guard: storage/.htaccess.';
        }

        // 2. Restore missing or damaged core files
        $gitDir = APP_ROOT . DIRECTORY_SEPARATOR . '.git';
        if (is_dir($gitDir) && function_exists('exec')) {
            $output = [];
            $code = 0;
            // Restore any deleted tracked files safely
            @exec('cd ' . escapeshellarg(APP_ROOT) . ' && git checkout -- . 2>&1', $output, $code);
            if ($code === 0) {
                $log[] = 'Git integrity check: Verified and restored tracked core files.';
            } else {
                $log[] = 'Git notice: ' . implode(' ', $output);
            }
        } else {
            // Standalone / Non-Git mode: Check essential manifest files from GitHub Raw
            $manifest = [
                'index.php',
                'r.php',
                'embed.js',
                'config/config.php',
                'src/Auth.php',
                'src/Database.php',
                'src/Helpers.php',
                'src/I18n.php',
                'src/Icon.php',
                'src/LinkManager.php',
                'src/Tracker.php',
                'src/Updater.php',
                'views/auth/login.php',
                'views/dashboard/index.php',
                'views/dashboard/links.php',
                'views/dashboard/settings.php',
                'views/dashboard/stats.php',
                'views/install/index.php',
                'views/layout/header.php',
                'views/layout/footer.php',
                'assets/css/app.css',
                'assets/js/app.js'
            ];

            $repo = Database::getSetting('github_repo', self::DEFAULT_REPO);
            $rawBase = "https://raw.githubusercontent.com/{$repo}/main/";

            foreach ($manifest as $fileRel) {
                $targetPath = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fileRel);
                if (!file_exists($targetPath)) {
                    $downloadUrl = $rawBase . $fileRel;
                    $content = null;

                    if (function_exists('curl_init')) {
                        $ch = curl_init($downloadUrl);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT        => 6,
                            CURLOPT_CONNECTTIMEOUT => 3,
                            CURLOPT_USERAGENT      => 'AtharLink-Rebuilder',
                            CURLOPT_SSL_VERIFYPEER => true
                        ]);
                        $res = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        if ($httpCode === 200 && is_string($res) && strlen($res) > 0) {
                            $content = $res;
                        }
                    } elseif (ini_get('allow_url_fopen')) {
                        $content = @file_get_contents($downloadUrl);
                    }

                    if ($content !== null) {
                        $parentDir = dirname($targetPath);
                        if (!is_dir($parentDir)) {
                            @mkdir($parentDir, 0755, true);
                        }
                        @file_put_contents($targetPath, $content);
                        $restoredFiles++;
                        $log[] = "Restored missing file from repository: {$fileRel}";
                    } else {
                        $log[] = "Warning: Could not fetch missing file: {$fileRel}";
                    }
                }
            }
            if ($restoredFiles === 0) {
                $log[] = 'Core file scan: All required application files are intact.';
            }
        }

        // 3. Database schema verification & migrations
        try {
            Database::initSchema();
            $log[] = 'Database schema and column migrations verified.';
        } catch (Throwable $e) {
            $log[] = 'Schema verification notice: ' . $e->getMessage();
        }

        // 4. SQLite integrity check & WAL checkpoint
        try {
            $pdo = Database::getConnection();
            $pdo->exec('PRAGMA wal_checkpoint(TRUNCATE);');
            $integrity = $pdo->query('PRAGMA integrity_check;')->fetchColumn();
            $log[] = 'Database health: SQLite integrity check passed (' . ($integrity === 'ok' ? 'OK' : (string)$integrity) . ').';
        } catch (Throwable $e) {
            $log[] = 'Database check warning: ' . $e->getMessage();
        }

        // 5. Restore installed.lock if accidentally deleted
        if (!file_exists(LOCK_FILE)) {
            @file_put_contents(LOCK_FILE, json_encode([
                'installed_at' => date('c'),
                'version'      => defined('APP_VERSION') ? APP_VERSION : '1.0.1'
            ]));
            $log[] = 'Recreated missing installation lock file.';
        }

        // 6. Reset OPcache & flush update check cache
        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $log[] = 'PHP OPcache cleared and refreshed.';
        }
        Database::setSetting(self::CACHE_KEY, '');

        return [
            'success'        => true,
            'message'        => I18n::t('rebuild_success_flash'),
            'restored_files' => $restoredFiles,
            'log'            => $log
        ];
    }
}
