<?php
declare(strict_types=1);

namespace AtharLink;

use PDO;
use PDOException;

/**
 * SQLite Database Connection & Schema Migration Engine
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Get or initialize PDO SQLite connection
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $storageDir = STORAGE_DIR;
            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0755, true);
            }

            $dsn = 'sqlite:' . DB_PATH;
            try {
                self::$instance = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 5,
                ]);

                // Enable high-throughput concurrent WAL mode & integrity pragmas
                self::$instance->exec('PRAGMA journal_mode = WAL;');
                self::$instance->exec('PRAGMA synchronous = NORMAL;');
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                self::$instance->exec('PRAGMA busy_timeout = 5000;');

                self::ensureMigrations(self::$instance);

                // Apply application timezone once database connection is established
                Helpers::applyTimezone();
            } catch (PDOException $e) {
                error_log('AtharLink DB Connection Error: ' . $e->getMessage());
                throw new PDOException('Could not connect to AtharLink database: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Ensure column migrations are applied to existing databases
     */
    private static function ensureMigrations(PDO $pdo): void
    {
        try {
            $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='links'")->fetchColumn();
            if ($tableExists) {
                $cols = $pdo->query("PRAGMA table_info(links)")->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('initial_clicks', $cols, true)) {
                    $pdo->exec("ALTER TABLE links ADD COLUMN initial_clicks INTEGER DEFAULT 0;");
                }
                if (!in_array('initial_unique_clicks', $cols, true)) {
                    $pdo->exec("ALTER TABLE links ADD COLUMN initial_unique_clicks INTEGER DEFAULT 0;");
                }
                if (!in_array('password_plain', $cols, true)) {
                    $pdo->exec("ALTER TABLE links ADD COLUMN password_plain TEXT NULL;");
                }
                if (!in_array('auth_lang', $cols, true)) {
                    $pdo->exec("ALTER TABLE links ADD COLUMN auth_lang TEXT DEFAULT 'auto';");
                }
            }

            $clicksTableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='clicks'")->fetchColumn();
            if ($clicksTableExists) {
                $clickCols = $pdo->query("PRAGMA table_info(clicks)")->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('ip_address', $clickCols, true)) {
                    $pdo->exec("ALTER TABLE clicks ADD COLUMN ip_address TEXT NULL;");
                }
            }

            // Ensure audit_logs table exists
            $auditTableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='audit_logs'")->fetchColumn();
            if (!$auditTableExists) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS audit_logs (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NULL,
                        username TEXT NOT NULL,
                        action TEXT NOT NULL,
                        description TEXT NOT NULL,
                        details TEXT NULL,
                        ip_address TEXT NULL,
                        user_agent TEXT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                    CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action);
                    CREATE INDEX IF NOT EXISTS idx_audit_logs_created ON audit_logs(created_at);
                ");
            }
        } catch (\Throwable $e) {
            error_log('AtharLink Migration Check Notice: ' . $e->getMessage());
        }
    }

    /**
     * Initialize all required tables and indexes
     */
    public static function initSchema(): void
    {
        $pdo = self::getConnection();

        // 1. Administrators / Users
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                api_token TEXT UNIQUE,
                failed_login_attempts INTEGER DEFAULT 0,
                locked_until DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 2. Tracked Links
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                target_url TEXT NOT NULL,
                title TEXT NULL,
                redirect_type INTEGER DEFAULT 302,
                password_hash TEXT NULL,
                click_limit INTEGER NULL,
                expires_at DATETIME NULL,
                is_active INTEGER DEFAULT 1,
                forward_utm INTEGER DEFAULT 1,
                initial_clicks INTEGER DEFAULT 0,
                initial_unique_clicks INTEGER DEFAULT 0,
                password_plain TEXT NULL,
                auth_lang TEXT DEFAULT 'auto',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Column migration for existing installations
        try {
            $cols = $pdo->query("PRAGMA table_info(links)")->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array('initial_clicks', $cols, true)) {
                $pdo->exec("ALTER TABLE links ADD COLUMN initial_clicks INTEGER DEFAULT 0;");
            }
            if (!in_array('initial_unique_clicks', $cols, true)) {
                $pdo->exec("ALTER TABLE links ADD COLUMN initial_unique_clicks INTEGER DEFAULT 0;");
            }
            if (!in_array('password_plain', $cols, true)) {
                $pdo->exec("ALTER TABLE links ADD COLUMN password_plain TEXT NULL;");
            }
            if (!in_array('auth_lang', $cols, true)) {
                $pdo->exec("ALTER TABLE links ADD COLUMN auth_lang TEXT DEFAULT 'auto';");
            }
        } catch (\Throwable $e) {
            // Ignore if column already exists
        }

        // 3. Granular Click Logs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS clicks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                link_id INTEGER NOT NULL,
                is_unique INTEGER DEFAULT 0,
                ip_address TEXT NULL,
                ip_hash TEXT NOT NULL,
                country_code TEXT DEFAULT 'XX',
                referrer TEXT NULL,
                referrer_domain TEXT NULL,
                device_type TEXT DEFAULT 'desktop',
                browser TEXT DEFAULT 'Other',
                platform TEXT DEFAULT 'Other',
                user_agent TEXT NULL,
                clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE
            );
        ");

        // Indexes for ultra-fast lookups and aggregate queries
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_clicks_link_id ON clicks(link_id);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_clicks_clicked_at ON clicks(clicked_at);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_links_slug ON links(slug);");

        // 4. Global Settings Key-Value
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT
            );
        ");

        // Set default settings if not already present
        self::ensureDefaultSetting('site_title', 'AtharLink');
        self::ensureDefaultSetting('uniqueness_window', (string)DEFAULT_UNIQUENESS_WINDOW);
        self::ensureDefaultSetting('theme', 'dark');
        self::ensureDefaultSetting('language', 'ar');

        // 5. Admin Audit & Activity Logs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                username TEXT NOT NULL,
                action TEXT NOT NULL,
                description TEXT NOT NULL,
                details TEXT NULL,
                ip_address TEXT NULL,
                user_agent TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_audit_logs_created ON audit_logs(created_at);");
    }

    /**
     * Retrieve a global setting
     */
    public static function getSetting(string $key, ?string $default = null): ?string
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = :k LIMIT 1");
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : $default;
    }

    /**
     * Set or update a global setting
     */
    public static function setSetting(string $key, string $value): bool
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (:k, :v)
            ON CONFLICT(setting_key) DO UPDATE SET setting_value = :v
        ");
        return $stmt->execute([':k' => $key, ':v' => $value]);
    }

    /**
     * Ensure default setting exists
     */
    private static function ensureDefaultSetting(string $key, string $defaultValue): void
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (:k, :v)");
        $stmt->execute([':k' => $key, ':v' => $defaultValue]);
    }
}
