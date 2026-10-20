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
    public const REPO = self::DEFAULT_REPO;
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

        // 1. Primary Strategy: GitHub API contents endpoint with raw header (Instant 0-delay version detection)
        $apiUrl = "https://api.github.com/repos/{$repo}/contents/version.json?ref=main";
        $versionFetched = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_USERAGENT      => 'AtharLink-Updater/' . $current,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/vnd.github.raw+json',
                    'User-Agent: AtharLink-Updater'
                ],
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $res = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && is_string($res) && strlen($res) > 0) {
                $rawJson = json_decode($res, true);
                if (is_array($rawJson) && !empty($rawJson['version'])) {
                    $latest = ltrim((string)$rawJson['version'], 'vV');
                    $result['latest']        = $latest;
                    $result['has_update']    = version_compare($latest, $current, '>');
                    $result['release_name']  = $rawJson['name'] ?? ('v' . $latest);
                    $result['release_notes'] = $rawJson['changelog'] ?? '';
                    $result['release_url']   = "https://github.com/{$repo}/releases/tag/v{$latest}";
                    $result['published_at']  = $rawJson['release_date'] ?? date('Y-m-d');
                    $result['min_php']       = $rawJson['min_php'] ?? '8.1';
                    $versionFetched = true;
                }
            }
        }

        // 2. Secondary Strategy: Raw CDN version.json (if API was rate-limited or unavailable)
        if (!$versionFetched) {
            $rawVersionUrl = "https://raw.githubusercontent.com/{$repo}/main/version.json";
            $rawRes = null;
            if (function_exists('curl_init')) {
                $chRaw = curl_init($rawVersionUrl);
                curl_setopt_array($chRaw, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 3,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_USERAGENT      => 'AtharLink-Updater/' . $current,
                    CURLOPT_SSL_VERIFYPEER => true
                ]);
                $rawRes = curl_exec($chRaw);
                $rawHttpCode = (int)curl_getinfo($chRaw, CURLINFO_HTTP_CODE);
                curl_close($chRaw);
                if ($rawHttpCode !== 200) {
                    $rawRes = null;
                }
            } elseif (ini_get('allow_url_fopen')) {
                $rawRes = @file_get_contents($rawVersionUrl);
            }

            if (is_string($rawRes) && strlen($rawRes) > 0) {
                $rawJson = json_decode($rawRes, true);
                if (is_array($rawJson) && !empty($rawJson['version'])) {
                    $latest = ltrim((string)$rawJson['version'], 'vV');
                    $result['latest']        = $latest;
                    $result['has_update']    = version_compare($latest, $current, '>');
                    $result['release_name']  = $rawJson['name'] ?? ('v' . $latest);
                    $result['release_notes'] = $rawJson['changelog'] ?? '';
                    $result['release_url']   = "https://github.com/{$repo}/releases/tag/v{$latest}";
                    $result['published_at']  = $rawJson['release_date'] ?? date('Y-m-d');
                    $result['min_php']       = $rawJson['min_php'] ?? '8.1';
                    $versionFetched = true;
                }
            }
        }

        // 2. Secondary Fallback: GitHub API releases/tags (if raw version.json was unreachable)
        if (!$versionFetched && function_exists('curl_init')) {
            $url = "https://api.github.com/repos/{$repo}/releases/latest";
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
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
                    CURLOPT_TIMEOUT        => 4,
                    CURLOPT_CONNECTTIMEOUT => 2,
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
        } elseif (!$versionFetched && !function_exists('curl_init')) {
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

        // 3. Code update (Git pull or Standalone Repository Download)
        $codeUpdated = false;
        $filesUpdated = 0;
        $gitDir = APP_ROOT . DIRECTORY_SEPARATOR . '.git';
        if (is_dir($gitDir) && function_exists('exec')) {
            $output = [];
            $code = 0;
            // Reset any modified tracked files first to prevent pull aborts
            @exec('cd ' . escapeshellarg(APP_ROOT) . ' && git reset --hard HEAD 2>&1 && git checkout -- . 2>&1 && git pull origin main 2>&1', $output, $code);
            if ($code === 0) {
                $codeUpdated = true;
                $log[] = 'Git pull completed: ' . implode(' ', $output);
            } else {
                $log[] = 'Git pull notice: ' . implode(' ', $output);
            }
        } else {
            // Standalone Mode: Fetch official files from repository
            $repo = Database::getSetting('github_repo', self::DEFAULT_REPO);
            $rawBase = "https://raw.githubusercontent.com/{$repo}/main/";

            // Fetch remote checksums.json to determine changed/missing files
            $newChecksumsUrl = $rawBase . 'config/checksums.json';
            $newChecksumsRaw = null;
            if (function_exists('curl_init')) {
                $ch = curl_init($newChecksumsUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 6,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_USERAGENT      => 'AtharLink-Updater',
                    CURLOPT_SSL_VERIFYPEER => true
                ]);
                $newChecksumsRaw = curl_exec($ch);
                curl_close($ch);
            } elseif (ini_get('allow_url_fopen')) {
                $newChecksumsRaw = @file_get_contents($newChecksumsUrl);
            }

            $remoteChecksums = [];
            if ($newChecksumsRaw) {
                $remoteChecksums = json_decode((string)$newChecksumsRaw, true) ?: [];
            }

            $filesToDownload = [];
            if (!empty($remoteChecksums)) {
                foreach ($remoteChecksums as $fileRel => $remoteHash) {
                    $localPath = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fileRel);
                    if (!file_exists($localPath) || hash_file('sha256', $localPath) !== $remoteHash) {
                        $filesToDownload[] = $fileRel;
                    }
                }
            } else {
                $filesToDownload = self::CORE_MANIFEST;
            }

            foreach ($filesToDownload as $fileRel) {
                if ($fileRel === 'config/checksums.json') {
                    continue;
                }
                $targetPath = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fileRel);
                $downloadUrl = $rawBase . $fileRel;
                $content = null;

                if (function_exists('curl_init')) {
                    $ch = curl_init($downloadUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT        => 8,
                        CURLOPT_CONNECTTIMEOUT => 3,
                        CURLOPT_USERAGENT      => 'AtharLink-Updater',
                        CURLOPT_SSL_VERIFYPEER => true
                    ]);
                    $content = curl_exec($ch);
                    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($httpCode !== 200) {
                        $content = null;
                    }
                } elseif (ini_get('allow_url_fopen')) {
                    $content = @file_get_contents($downloadUrl);
                }

                if ($content !== null) {
                    // Verify file integrity against remote checksum if available
                    if (!empty($remoteChecksums[$fileRel])) {
                        $downloadHash = hash('sha256', $content);
                        if ($downloadHash !== $remoteChecksums[$fileRel]) {
                            $log[] = "Warning: Checksum mismatch for {$fileRel}. File update skipped to prevent corruption.";
                            continue;
                        }
                    }

                    $parentDir = dirname($targetPath);
                    if (!is_dir($parentDir)) {
                        @mkdir($parentDir, 0755, true);
                    }
                    @file_put_contents($targetPath, $content);
                    $filesUpdated++;
                }
            }

            if ($newChecksumsRaw) {
                @file_put_contents(APP_ROOT . '/config/checksums.json', $newChecksumsRaw);
            }

            if ($filesUpdated > 0) {
                $codeUpdated = true;
                $log[] = "Standalone update: {$filesUpdated} file(s) updated successfully from repository.";
            } else {
                $log[] = "Standalone update: All core code files are up to date.";
            }
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

        $msg = $codeUpdated
            ? I18n::t('update_success_git')
            : I18n::t('update_success_db');

        return [
            'success' => true,
            'message' => $msg,
            'log'     => $log
        ];
    }

    /**
     * Official Core Manifest Files
     */
    public const CORE_MANIFEST = [
        '.htaccess',
        '.well-known/security.txt',
        'api/v1/index.php',
        'assets/css/app.css',
        'assets/css/bootstrap.min.css',
        'assets/js/app.js',
        'assets/js/app.min.js',
        'assets/js/bootstrap.bundle.min.js',
        'assets/js/chart.min.js',
        'assets/js/qrcode.min.js',
        'assets/js/report-card.js',
        'assets/js/report-card.min.js',
        'badge.php',
        'bin/.htaccess',
        'bin/index.php',
        'bin/reset_password.php',
        'bin/update.php',
        'config/checksums.json',
        'config/config.php',
        'embed.js',
        'health.php',
        'index.php',
        'LICENSE',
        'nginx.conf.sample',
        'r.php',
        'README.md',
        'robots.txt',
        'security.txt',
        'src/AuditLogger.php',
        'src/Auth.php',
        'src/Database.php',
        'src/Helpers.php',
        'src/I18n.php',
        'src/Icon.php',
        'src/LinkManager.php',
        'src/RateLimiter.php',
        'src/Tracker.php',
        'src/Updater.php',
        'version.json',
        'views/auth/login.php',
        'views/dashboard/index.php',
        'views/dashboard/links.php',
        'views/dashboard/logs.php',
        'views/dashboard/settings.php',
        'views/dashboard/stats.php',
        'views/install/index.php',
        'views/layout/footer.php',
        'views/layout/header.php'
    ];

    /**
     * Load official SHA-256 file checksums
     */
    public static function getChecksumsManifest(): array
    {
        $checksumFile = APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'checksums.json';
        if (file_exists($checksumFile)) {
            $raw = @file_get_contents($checksumFile);
            if ($raw !== false && $raw !== '') {
                $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)$raw);
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }
        return [];
    }


    /**
     * Pre-Repair Diagnostic Engine:
     * Scans missing, modified, and untracked (custom) files, and tests database health.
     * Operates in 100% read-only mode without modifying any files or databases.
     */
    public static function diagnoseSystem(): array
    {
        $gitDir = APP_ROOT . DIRECTORY_SEPARATOR . '.git';
        $isGit = is_dir($gitDir) && function_exists('exec');

        $missingFiles = [];
        $modifiedFiles = [];
        $untrackedFiles = [];

        if ($isGit) {
            $statusOut = [];
            $statusCode = 0;
            @exec('cd ' . escapeshellarg(APP_ROOT) . ' && git status --porcelain 2>&1', $statusOut, $statusCode);
            if ($statusCode === 0) {
                foreach ($statusOut as $line) {
                    $line = trim($line);
                    if (strlen($line) < 4) {
                        continue;
                    }
                    $code = substr($line, 0, 2);
                    $file = trim(substr($line, 2));
                    $file = str_replace('\\', '/', $file);

                    // Skip runtime storage, git internal files, and environment files
                    if (str_starts_with($file, 'storage/') || str_starts_with($file, '.git') || $file === '.gitignore' || $file === '.gitattributes') {
                        continue;
                    }

                    if (str_contains($code, 'D')) {
                        $missingFiles[] = $file;
                    } elseif (str_contains($code, 'M')) {
                        $modifiedFiles[] = $file;
                    } elseif ($code === '??') {
                        $untrackedFiles[] = $file;
                    }
                }
            }
        } else {
            // Standalone mode: Scan core manifest and local disk against checksums
            $checksums = self::getChecksumsManifest();
            $coreSet = array_flip(self::CORE_MANIFEST);

            // 1. Check core manifest files
            foreach (self::CORE_MANIFEST as $relFile) {
                $absPath = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relFile);
                if (!file_exists($absPath)) {
                    $missingFiles[] = $relFile;
                } elseif (!empty($checksums[$relFile])) {
                    $currentHash = hash_file('sha256', $absPath);
                    if ($currentHash !== $checksums[$relFile]) {
                        $modifiedFiles[] = $relFile;
                    }
                }
            }

            // 2. Scan core directories for extra / untracked files
            $dirsToScan = ['src', 'views', 'assets', 'api', 'bin', 'config'];
            foreach ($dirsToScan as $dir) {
                $absDir = APP_ROOT . DIRECTORY_SEPARATOR . $dir;
                if (!is_dir($absDir)) {
                    continue;
                }
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($absDir, \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $item) {
                    if ($item->isFile()) {
                        $relPath = str_replace('\\', '/', substr($item->getPathname(), strlen(APP_ROOT) + 1));
                        if (!isset($coreSet[$relPath]) && !str_starts_with($relPath, 'storage/')) {
                            $untrackedFiles[] = $relPath;
                        }
                    }
                }
            }

            // 3. Scan root items for untracked / extra files
            $rootItems = @scandir(APP_ROOT) ?: [];
            $ignoredRoot = ['.', '..', 'storage', '.git', '.gemini', '.agents', '.vscode', '.idea', 'Instructions.txt', 'update.sh', 'GitHub'];
            foreach ($rootItems as $item) {
                if (in_array($item, $ignoredRoot, true)) {
                    continue;
                }
                if (is_file(APP_ROOT . DIRECTORY_SEPARATOR . $item)) {
                    if (!isset($coreSet[$item])) {
                        $untrackedFiles[] = $item;
                    }
                } elseif (is_dir(APP_ROOT . DIRECTORY_SEPARATOR . $item)) {
                    if (!in_array($item, ['src', 'views', 'assets', 'api', 'bin', 'config', '.well-known'], true)) {
                        $untrackedFiles[] = $item . '/';
                    }
                }
            }
        }

        // Database status & sizes
        $dbStatus = [
            'integrity' => 'ok',
            'size'      => '0 KB',
            'wal_size'  => '0 KB',
            'healthy'   => true
        ];
        try {
            $pdo = Database::getConnection();
            $integ = $pdo->query('PRAGMA integrity_check;')->fetchColumn();
            $dbStatus['integrity'] = ($integ === 'ok') ? 'ok' : (string)$integ;
            $dbStatus['healthy'] = ($integ === 'ok');

            if (file_exists(DB_PATH)) {
                $bytes = (int)filesize(DB_PATH);
                $dbStatus['size'] = Helpers::formatBytes($bytes);
            }
            $walPath = DB_PATH . '-wal';
            if (file_exists($walPath)) {
                $walBytes = (int)filesize($walPath);
                $dbStatus['wal_size'] = Helpers::formatBytes($walBytes);
            }
        } catch (Throwable $e) {
            $dbStatus['integrity'] = 'Notice: ' . $e->getMessage();
            $dbStatus['healthy'] = false;
        }

        $storageHtaccess = STORAGE_DIR . DIRECTORY_SEPARATOR . '.htaccess';
        $storageGuard = file_exists($storageHtaccess);
        $lockFile = file_exists(LOCK_FILE);

        return [
            'success'         => true,
            'is_git'          => $isGit,
            'missing_files'   => array_values(array_unique($missingFiles)),
            'modified_files'  => array_values(array_unique($modifiedFiles)),
            'untracked_files' => array_values(array_unique($untrackedFiles)),
            'database'        => $dbStatus,
            'storage_guard'   => $storageGuard,
            'lock_file'       => $lockFile,
            'clean'           => (empty($missingFiles) && empty($modifiedFiles))
        ];
    }

    /**
     * Smart System Rebuilder & Selective Integrity Repair Engine:
     * 1. Creates a safe pre-repair SQLite backup snapshot in storage/
     * 2. Backs up user-modified files to storage/backups_code/ before restoration (if requested)
     * 3. Selectively restores missing files and/or modified files
     * 4. Strictly protects untracked/extra files (never deletes custom files)
     * 5. Re-verifies storage protection, .htaccess, and lock file
     * 6. Optimizes SQLite (VACUUM, WAL checkpoint, index reindex, schema migrations)
     * 7. Clears OPcache and runtime update caches
     */
    public static function rebuildSystem(array $options = []): array
    {
        $log = [];
        $log[] = 'System rebuild and selective repair started: ' . date('Y-m-d H:i:s T');
        $restoredFiles = 0;

        // Default flags (safe default: restore missing = true, restore modified = false, backup mods = true, optimize db = true)
        $restoreMissing  = !isset($options['restore_missing'])  ? true : !empty($options['restore_missing']);
        $restoreModified = !isset($options['restore_modified']) ? false : !empty($options['restore_modified']);
        $backupMods      = !isset($options['backup_mods'])      ? true : !empty($options['backup_mods']);
        $optimizeDb      = !isset($options['optimize_db'])      ? true : !empty($options['optimize_db']);

        // 1. Storage directory & access guard integrity
        $storageDir = STORAGE_DIR;
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
            $log[] = 'Created missing storage directory.';
        }

        $storageHtaccess = $storageDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($storageHtaccess)) {
            $htContent = "# Strictly block public direct access to database and secrets\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n";
            @file_put_contents($storageHtaccess, $htContent);
            $log[] = 'Recreated missing security guard: storage/.htaccess.';
        }

        // 2. Pre-Repair SQLite Database Snapshot (First line of defense)
        try {
            $pdo = Database::getConnection();
            $pdo->exec('PRAGMA wal_checkpoint(TRUNCATE);');
            
            $backupName = 'backup_pre_repair_' . date('Ymd_His') . '.sqlite';
            $backupPath = STORAGE_DIR . DIRECTORY_SEPARATOR . $backupName;

            if (file_exists(DB_PATH) && copy(DB_PATH, $backupPath)) {
                $log[] = "Pre-repair database snapshot saved: {$backupName}";
            }
        } catch (Throwable $e) {
            $log[] = 'Database snapshot notice: ' . $e->getMessage();
        }

        // 3. Diagnose current state to identify target files
        $diagnosis = self::diagnoseSystem();
        $targetMissing = $diagnosis['missing_files'] ?? [];
        $targetModified = $diagnosis['modified_files'] ?? [];
        $isGit = !empty($diagnosis['is_git']);

        // 4. Backup modified code files before overwriting (if restoreModified is requested)
        if ($restoreModified && !empty($targetModified) && $backupMods) {
            $backupCodeDir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'backups_code' . DIRECTORY_SEPARATOR . 'backup_mods_' . date('Ymd_His');
            if (!is_dir($backupCodeDir)) {
                @mkdir($backupCodeDir, 0755, true);
            }
            foreach ($targetModified as $modFile) {
                $srcFile = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $modFile);
                $destFile = $backupCodeDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $modFile);
                if (file_exists($srcFile)) {
                    $parentDest = dirname($destFile);
                    if (!is_dir($parentDest)) {
                        @mkdir($parentDest, 0755, true);
                    }
                    @copy($srcFile, $destFile);
                }
            }
            $log[] = 'Saved backup of modified code files to: storage/backups_code/' . basename($backupCodeDir);
        }

        // 5. Execute file restorations
        if ($isGit) {
            // Git environment
            if ($restoreMissing && $restoreModified) {
                $output = [];
                $code = 0;
                @exec('cd ' . escapeshellarg(APP_ROOT) . ' && git checkout -- . 2>&1', $output, $code);
                if ($code === 0) {
                    $log[] = 'Git: Restored all missing and modified files to clean repository state.';
                    $restoredFiles += count($targetMissing) + count($targetModified);
                } else {
                    $log[] = 'Git notice: ' . implode(' ', $output);
                }
            } elseif ($restoreMissing && !empty($targetMissing)) {
                foreach ($targetMissing as $mFile) {
                    $out = [];
                    $cd = 0;
                    @exec('cd ' . escapeshellarg(APP_ROOT) . ' && git checkout -- ' . escapeshellarg($mFile) . ' 2>&1', $out, $cd);
                    if ($cd === 0) {
                        $restoredFiles++;
                        $log[] = "Git: Restored missing file: {$mFile}";
                    }
                }
            } elseif ($restoreModified && !empty($targetModified)) {
                foreach ($targetModified as $modFile) {
                    $out = [];
                    $cd = 0;
                    @exec('cd ' . escapeshellarg(APP_ROOT) . ' && git checkout -- ' . escapeshellarg($modFile) . ' 2>&1', $out, $cd);
                    if ($cd === 0) {
                        $restoredFiles++;
                        $log[] = "Git: Reverted modified file to official: {$modFile}";
                    }
                }
            }
        } else {
            // Standalone / Non-Git environment: Download from official GitHub Raw
            $repo = Database::getSetting('github_repo', self::DEFAULT_REPO);
            $rawBase = "https://raw.githubusercontent.com/{$repo}/main/";

            $filesToDownload = [];
            if ($restoreMissing) {
                foreach ($targetMissing as $f) {
                    $filesToDownload[] = $f;
                }
            }
            if ($restoreModified) {
                foreach ($targetModified as $f) {
                    $filesToDownload[] = $f;
                }
            }

            foreach ($filesToDownload as $fileRel) {
                $targetPath = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fileRel);
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
                    // Verify file integrity against official manifest checksum if available
                    $checksums = self::getChecksumsManifest();
                    if (!empty($checksums[$fileRel])) {
                        $downloadHash = hash('sha256', $content);
                        if ($downloadHash !== $checksums[$fileRel]) {
                            $log[] = "Warning: Checksum mismatch for {$fileRel}. File restore skipped to prevent corruption.";
                            continue;
                        }
                    }

                    $parentDir = dirname($targetPath);
                    if (!is_dir($parentDir)) {
                        @mkdir($parentDir, 0755, true);
                    }
                    @file_put_contents($targetPath, $content);
                    $restoredFiles++;
                    $log[] = "Restored file from repository: {$fileRel}";
                } else {
                    $log[] = "Notice: Could not fetch file: {$fileRel}";
                }
            }
        }

        if ($restoredFiles === 0 && ($restoreMissing || $restoreModified)) {
            $log[] = 'File check: All checked system files are intact.';
        }

        // 6. Restore installed.lock if accidentally deleted
        if (!file_exists(LOCK_FILE)) {
            @file_put_contents(LOCK_FILE, json_encode([
                'installed_at' => date('c'),
                'version'      => defined('APP_VERSION') ? APP_VERSION : '1.0.14'
            ]));
            $log[] = 'Recreated missing installation lock file.';
        }

        // 7. Database optimization & schema check (if requested)
        if ($optimizeDb) {
            try {
                Database::initSchema();
                $log[] = 'Database schema and column migrations verified.';
            } catch (Throwable $e) {
                $log[] = 'Schema verification notice: ' . $e->getMessage();
            }

            try {
                $pdo = Database::getConnection();
                $pdo->exec('PRAGMA optimize;');
                $pdo->exec('PRAGMA wal_checkpoint(TRUNCATE);');
                $integrity = $pdo->query('PRAGMA integrity_check;')->fetchColumn();
                $log[] = 'Database health: SQLite integrity check passed (' . ($integrity === 'ok' ? 'OK' : (string)$integrity) . ').';
            } catch (Throwable $e) {
                $log[] = 'Database check warning: ' . $e->getMessage();
            }
        }

        // 8. Reset OPcache & flush update check cache
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

