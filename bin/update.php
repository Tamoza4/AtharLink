<?php
declare(strict_types=1);

/**
 * AtharLink System & Database Updater
 * ------------------------------------
 * Safely updates an existing AtharLink installation:
 * 1. Takes an automatic timestamped database backup.
 * 2. Applies database schema migrations and new indexes.
 * 3. Verifies SQLite integrity.
 * 4. Resets OPcache (if enabled).
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/src/Helpers.php';
require_once dirname(__DIR__) . '/src/Database.php';

use AtharLink\Database;
use AtharLink\Helpers;

// Ensure CLI execution or explicit secret parameter
if (php_sapi_name() !== 'cli') {
    $secret = $_GET['secret'] ?? '';
    $appSecret = function_exists('get_app_secret') ? get_app_secret() : '';
    if ($secret === '' || !hash_equals($appSecret, $secret)) {
        header('HTTP/1.1 403 Forbidden');
        echo "Access Denied: This script must be run via CLI or with a valid secret token.\n";
        exit;
    }
    header('Content-Type: text/plain; charset=UTF-8');
}

echo "=================================================\n";
echo "       AtharLink System Updater v" . APP_VERSION . "\n";
echo "=================================================\n\n";

// 1. Check if installation exists
if (!file_exists(DB_PATH) || !file_exists(LOCK_FILE)) {
    echo "[!] Notice: AtharLink is not yet installed on this server.\n";
    echo "    Please run the web installer first by visiting your domain.\n";
    exit(1);
}

// 2. Storage write permission check
if (!is_writable(STORAGE_DIR)) {
    echo "[-] Error: Storage directory is not writable (" . STORAGE_DIR . ").\n";
    echo "    Please run: chmod -R 775 storage\n";
    exit(1);
}
echo "[+] Storage directory is writable.\n";

// 3. Automated Pre-Update Database Backup
$backupTimestamp = date('Ymd_His');
$backupFileName = 'backup_pre_update_' . $backupTimestamp . '.sqlite';
$backupFilePath = STORAGE_DIR . DIRECTORY_SEPARATOR . $backupFileName;

try {
    // Flush WAL to disk first
    $pdo = Database::getConnection();
    $pdo->exec("PRAGMA wal_checkpoint(TRUNCATE);");

    if (copy(DB_PATH, $backupFilePath)) {
        echo "[+] Automated backup created successfully:\n";
        echo "    -> " . $backupFileName . " (" . round(filesize($backupFilePath) / 1024, 1) . " KB)\n";
    } else {
        echo "[!] Warning: Could not create pre-update backup. Proceeding with caution...\n";
    }
} catch (\Throwable $e) {
    echo "[!] Backup warning: " . $e->getMessage() . "\n";
}

// 4. Run Schema Migrations
echo "\n[*] Applying database schema updates & migrations...\n";
try {
    Database::initSchema();
    echo "[+] Database schema is up to date.\n";
} catch (\Throwable $e) {
    echo "[-] Error applying migrations: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Database Integrity Check
echo "\n[*] Running database integrity verification...\n";
try {
    $integrity = $pdo->query("PRAGMA integrity_check;")->fetchColumn();
    if ($integrity === 'ok') {
        echo "[+] Integrity check passed: OK (Database is healthy).\n";
    } else {
        echo "[!] Integrity check returned warning: " . $integrity . "\n";
    }
} catch (\Throwable $e) {
    echo "[-] Integrity check failed: " . $e->getMessage() . "\n";
}

// 6. Reset OPcache (if active)
if (function_exists('opcache_reset')) {
    @opcache_reset();
    echo "[+] OPcache reset executed.\n";
}

echo "\n=================================================\n";
echo " [SUCCESS] AtharLink has been updated successfully!\n";
echo " Version: " . APP_VERSION . "\n";
echo " Time:    " . date('Y-m-d H:i:s T') . "\n";
echo "=================================================\n";
exit(0);
