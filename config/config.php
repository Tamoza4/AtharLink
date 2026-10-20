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

    // Content-Security-Policy (CSP) - Hardened (Zero unsafe-eval)
    header("Content-Security-Policy: default-src 'self' data: blob:; script-src 'self' 'unsafe-inline' blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https:; connect-src 'self' https://api.github.com; object-src 'none'; base-uri 'self';");
}

// Application Constants (with 12-Factor Environment Variable support)
define('APP_NAME', getenv('ATHAR_APP_NAME') ?: 'AtharLink');
define('APP_VERSION', '1.0.17');
define('APP_ROOT', dirname(__DIR__));
define('STORAGE_DIR', getenv('ATHAR_STORAGE_DIR') ?: (APP_ROOT . DIRECTORY_SEPARATOR . 'storage'));
define('DB_PATH', getenv('ATHAR_DB_PATH') ?: (STORAGE_DIR . DIRECTORY_SEPARATOR . 'athar_database.sqlite'));
define('LOCK_FILE', STORAGE_DIR . DIRECTORY_SEPARATOR . 'installed.lock');
define('SECRET_FILE', STORAGE_DIR . DIRECTORY_SEPARATOR . '.secret_key');
define('RECOVERY_FILE', STORAGE_DIR . DIRECTORY_SEPARATOR . '.recovery_key');

// Uniqueness Tracking Window (Default: 24 hours in seconds)
define('DEFAULT_UNIQUENESS_WINDOW', (int)(getenv('ATHAR_UNIQUENESS_WINDOW') ?: 86400));

// Rate Limiting Defaults
define('MAX_LOGIN_ATTEMPTS', (int)(getenv('ATHAR_MAX_LOGIN_ATTEMPTS') ?: 5));
define('LOGIN_LOCKOUT_MINUTES', (int)(getenv('ATHAR_LOGIN_LOCKOUT_MINUTES') ?: 15));
define('SESSION_IDLE_TIMEOUT', (int)(getenv('ATHAR_SESSION_IDLE_TIMEOUT') ?: 7200)); // 2 hours of inactivity in seconds

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

// Global Exception Handler & Graceful Degradation (Prevents White Screen of Death)
if (!defined('ATHAR_EXCEPTION_HANDLER_SET')) {
    define('ATHAR_EXCEPTION_HANDLER_SET', true);

    set_exception_handler(function (\Throwable $e) {
        error_log(sprintf(
            "[AtharLink Fatal Exception] %s in %s:%d\nStack Trace:\n%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        if (!headers_sent()) {
            http_response_code(503);
            header('Retry-After: 30');
        }

        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "Error: " . $e->getMessage() . PHP_EOL);
            exit(1);
        }

        $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/'))
            || (isset($_GET['action']) && in_array($_GET['action'], ['check_slug', 'get_count', 'check_updates', 'get_overview_report_data'], true));

        if ($isJson) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Service temporarily unavailable. Please retry shortly.',
                'code'    => 503
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الخدمة غير متوفرة مؤقتاً | ' . APP_NAME . '</title>
    <style>
        body {
            background: radial-gradient(circle at 50% 20%, #1e1b4b 0%, #090d16 80%);
            color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            box-sizing: border-box;
        }
        .error-card {
            background: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px 32px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .icon { font-size: 48px; margin-bottom: 16px; display: inline-block; }
        h1 { font-size: 20px; font-weight: 700; color: #fff; margin: 0 0 12px; }
        p { color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 0 0 24px; }
        .btn {
            background: #4f46e5;
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon">⚙️</div>
        <h1>الخدمة تخضع لصيانة سريعة</h1>
        <p>النظام يخضع لعملية معالجة أو ضغط اتصالات مؤقت. كافة بياناتك وروابطك محفوظة بأمان تام وسيعود النظام للعمل خلال ثوانٍ.</p>
        <button class="btn" onclick="location.reload()">إعادة المحاولة الآن</button>
    </div>
</body>
</html>';
        exit;
    });
}
