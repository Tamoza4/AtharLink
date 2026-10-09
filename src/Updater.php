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
            @exec('cd ' . escapeshellarg(APP_ROOT) . ' && git pull origin main 2>&1', $output, $code);
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
}
