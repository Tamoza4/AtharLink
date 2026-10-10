<?php
declare(strict_types=1);

/**
 * AtharLink Configuration & Environment Initialization
 */

// Error handling settings
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set default timezone (can be overridden by system settings)
date_default_timezone_set('UTC');

// OWASP Security Headers (Defense-in-depth across all server environments)
if (!headers_sent() && php_sapi_name() !== 'cli') {
    @header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');

    // Strict-Transport-Security (HSTS) over HTTPS
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    // Content-Security-Policy (CSP)
    header("Content-Security-Policy: default-src 'self' data: blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https:; connect-src 'self' https://api.github.com; object-src 'none'; base-uri 'self';");
}

// Application Constants
define('APP_NAME', 'AtharLink');
define('APP_VERSION', '1.0.11');
define('APP_ROOT', dirname(__DIR__));
define('STORAGE_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'storage');
define('DB_PATH', STORAGE_DIR . DIRECTORY_SEPARATOR . 'athar_database.sqlite');
define('LOCK_FILE', STORAGE_DIR . DIRECTORY_SEPARATOR . 'installed.lock');
define('SECRET_FILE', STORAGE_DIR . DIRECTORY_SEPARATOR . '.secret_key');
define('RECOVERY_FILE', STORAGE_DIR . DIRECTORY_SEPARATOR . '.recovery_key');

// Uniqueness Tracking Window (Default: 24 hours in seconds)
define('DEFAULT_UNIQUENESS_WINDOW', 86400);

// Rate Limiting Defaults
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// Secret Key for Cookie Signing and Hashing
if (!function_exists('get_app_secret')) {
    function get_app_secret(): string {
        if (file_exists(SECRET_FILE)) {
            $secret = trim((string)file_get_contents(SECRET_FILE));
            if (!empty($secret)) {
                return $secret;
            }
        }
        $secret = bin2hex(random_bytes(32));
        if (is_dir(STORAGE_DIR) && is_writable(STORAGE_DIR)) {
            @file_put_contents(SECRET_FILE, $secret, LOCK_EX);
        }
        return $secret;
    }
}

// Session Security Configuration
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Global Security Headers (Protects login, visitor pages, and admin against clickjacking and MIME-sniffing)
if (!headers_sent() && php_sapi_name() !== 'cli') {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}
