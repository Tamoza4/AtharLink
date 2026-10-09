<?php
declare(strict_types=1);

namespace AtharLink;

/**
 * Global Helper Utilities
 */
class Helpers
{
    /**
     * Escape HTML output against XSS (OWASP compliant)
     */
    public static function e(?string $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Send JSON HTTP response and terminate
     */
    public static function json(mixed $data, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Check if current request was sent via AJAX / Fetch
     */
    public static function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json'));
    }

    /**
     * Get Application Base URL
     */
    public static function baseUrl(string $path = ''): string
    {
        static $cachedBase = null;
        if ($cachedBase === null) {
            if (php_sapi_name() === 'cli') {
                $cachedBase = 'http://localhost';
            } else {
                $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

                $protocol = $isSecure ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                
                // Get root directory script path
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
                // Remove /api/v1 or subdirectories if called from api
                $scriptDir = preg_replace('#/(api(/v1)?|c|r)$#i', '', $scriptDir);
                $scriptDir = rtrim((string)$scriptDir, '/');

                $cachedBase = $protocol . $host . ($scriptDir !== '' ? $scriptDir : '');
            }
        }

        $trimmedPath = ltrim($path, '/');
        return $trimmedPath === '' ? $cachedBase : $cachedBase . '/' . $trimmedPath;
    }

    /**
     * Generate Tracking URL for a slug
     */
    public static function trackingUrl(string $slug): string
    {
        // Support clean rewrite /c/{slug} or fallback to r.php?slug={slug}
        return self::baseUrl('r.php?slug=' . urlencode($slug));
    }

    /**
     * Get Client IP Address with proxy support
     */
    public static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Standard proxy
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', (string)$_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * Anonymize IP hash (GDPR compliant)
     */
    public static function hashIp(string $ip): string
    {
        // Anonymize the last octet / portion of IP before hashing
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            $anonymized = implode('.', $parts);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $anonymized = implode(':', array_slice($parts, 0, 3)) . '::';
        } else {
            $anonymized = '0.0.0.0';
        }

        $secret = get_app_secret();
        return hash_hmac('sha256', $anonymized, $secret);
    }

    /**
     * Detect Country ISO code from Cloudflare / Proxy headers
     */
    public static function getCountryCode(): string
    {
        $headers = [
            'HTTP_CF_IPCOUNTRY',
            'HTTP_X_COUNTRY_CODE',
            'HTTP_GEOIP_COUNTRY_CODE'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $code = strtoupper(trim((string)$_SERVER[$header]));
                if (preg_match('/^[A-Z]{2}$/', $code)) {
                    return $code;
                }
            }
        }

        return 'XX';
    }

    /**
     * Parse User-Agent string for Device Type, Browser, and Operating System
     */
    public static function parseUserAgent(?string $ua): array
    {
        $ua = trim((string)($ua ?? ''));
        $result = [
            'device_type' => 'desktop',
            'browser'     => 'Other',
            'platform'    => 'Other'
        ];

        if ($ua === '') {
            return $result;
        }

        // 1. Bot / Crawler detection
        if (preg_match('/(bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|curl|wget|python|postman)/i', $ua)) {
            $result['device_type'] = 'bot';
            $result['browser'] = 'Bot';
            return $result;
        }

        // 2. Device Type detection
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $result['device_type'] = 'tablet';
        } elseif (preg_match('/(mobile|ipod|iphone|android|blackberry|iemobile|opera mini)/i', $ua)) {
            $result['device_type'] = 'mobile';
        } else {
            $result['device_type'] = 'desktop';
        }

        // 3. Platform / OS detection
        if (preg_match('/windows nt/i', $ua)) {
            $result['platform'] = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $result['platform'] = 'macOS';
        } elseif (preg_match('/android/i', $ua)) {
            $result['platform'] = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $ua)) {
            $result['platform'] = 'iOS';
        } elseif (preg_match('/linux/i', $ua)) {
            $result['platform'] = 'Linux';
        }

        // 4. Browser detection
        if (preg_match('/edg\/|edge\//i', $ua)) {
            $result['browser'] = 'Edge';
        } elseif (preg_match('/opr\/|opera\//i', $ua)) {
            $result['browser'] = 'Opera';
        } elseif (preg_match('/chrome\/|crios\//i', $ua)) {
            $result['browser'] = 'Chrome';
        } elseif (preg_match('/firefox\/|fxios\//i', $ua)) {
            $result['browser'] = 'Firefox';
        } elseif (preg_match('/safari\//i', $ua) && !preg_match('/chrome\/|crios\//i', $ua)) {
            $result['browser'] = 'Safari';
        }

        return $result;
    }

    /**
     * Generate secure random alphanumeric slug
     */
    public static function generateSlug(int $length = 6): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($chars) - 1;
        $slug = '';
        for ($i = 0; $i < $length; $i++) {
            $slug .= $chars[random_int(0, $max)];
        }
        return $slug;
    }

    /**
     * Validate slug characters and length
     */
    public static function isValidSlug(string $slug): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $slug);
    }

    /**
     * Merge inbound UTM parameters to destination URL
     */
    public static function mergeUtmParameters(string $targetUrl, array $incomingParams): string
    {
        $utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        $utmToForward = [];

        foreach ($utmKeys as $key) {
            if (isset($incomingParams[$key]) && is_string($incomingParams[$key]) && trim($incomingParams[$key]) !== '') {
                $utmToForward[$key] = trim($incomingParams[$key]);
            }
        }

        if (empty($utmToForward)) {
            return $targetUrl;
        }

        $parts = parse_url($targetUrl);
        if ($parts === false) {
            return $targetUrl;
        }

        $targetQuery = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $targetQuery);
        }

        // Merge: inbound UTM will supplement if not already present
        $mergedQuery = array_merge($utmToForward, $targetQuery);

        $rebuilt = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? '');
        if (!empty($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }
        if (!empty($parts['path'])) {
            $rebuilt .= $parts['path'];
        }
        if (!empty($mergedQuery)) {
            $rebuilt .= '?' . http_build_query($mergedQuery);
        }
        if (!empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    /**
     * Strict SSRF validation to block local and private network addresses
     */
    public static function isSafeDestinationUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        if (empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'] ?? '';
        if (empty($host)) {
            return false;
        }

        // Resolve host to IP
        $ips = @gethostbynamel($host);
        if ($ips === false || empty($ips)) {
            // Check direct IP host
            $ips = [$host];
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return false;
            }

            // Reject private and reserved IP ranges (RFC 1918, RFC 5735, RFC 3927)
            $isNotPublic = !filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            if ($isNotPublic) {
                return false;
            }

            // Extra checks for localhost / loopback
            if (str_starts_with($ip, '127.') || $ip === '::1' || str_starts_with($ip, '169.254.')) {
                return false;
            }
        }

        return true;
    }

    /**
     * CSRF Token Generator
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Render CSRF Hidden Input Field
     */
    public static function csrfInput(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::e(self::csrfToken()) . '">';
    }

    /**
     * Verify CSRF Token
     */
    public static function verifyCsrf(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Sign Tracking Cookie
     */
    public static function signTrackingValue(string $value): string
    {
        $secret = get_app_secret();
        $signature = hash_hmac('sha256', $value, $secret);
        return base64_encode($value . '|' . $signature);
    }

    /**
     * Verify Signed Tracking Cookie
     */
    public static function verifyTrackingValue(?string $signed): ?string
    {
        if (empty($signed)) {
            return null;
        }

        $decoded = base64_decode($signed, true);
        if ($decoded === false || !str_contains($decoded, '|')) {
            return null;
        }

        [$value, $signature] = explode('|', $decoded, 2);
        $secret = get_app_secret();
        $expected = hash_hmac('sha256', $value, $secret);

        if (hash_equals($expected, $signature)) {
            return $value;
        }

        return null;
    }

    /**
     * Clean Referrer Domain parser
     */
    public static function extractDomain(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return null;
        }
        return strtolower(preg_replace('/^www\./i', '', $host));
    }

    /**
     * Encrypt sensitive string at rest (AES-256-GCM authenticated encryption)
     */
    public static function encryptSensitive(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return null;
        }

        $key = hash('sha256', get_app_secret(), true);
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        if ($ciphertext === false) {
            return null;
        }

        return 'enc:v1:' . base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt sensitive string at rest (AES-256-GCM)
     */
    public static function decryptSensitive(?string $cipher): ?string
    {
        if ($cipher === null || $cipher === '') {
            return null;
        }

        if (!str_starts_with($cipher, 'enc:v1:')) {
            // Backward-compatibility: if existing DB record contains unencrypted legacy plaintext, return as is
            return $cipher;
        }

        $payload = base64_decode(substr($cipher, 7), true);
        if ($payload === false || strlen($payload) < 28) {
            return null;
        }

        $iv = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $ciphertext = substr($payload, 28);

        $key = hash('sha256', get_app_secret(), true);
        $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $plain !== false ? $plain : null;
    }

    /**
     * Format a timestamp into a human-readable relative time string (dual-language localized)
     */
    public static function timeAgo(string|int $datetime): string
    {
        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime((string)$datetime);
        if (!$timestamp) {
            return I18n::t('not_available');
        }

        $diff = time() - $timestamp;
        if ($diff < 0) {
            $diff = 0;
        }

        if ($diff < 45) {
            return I18n::t('time_just_now');
        }

        $minutes = (int)round($diff / 60);
        if ($minutes < 60) {
            return $minutes <= 1 
                ? I18n::t('time_minute_ago') 
                : I18n::t('time_minutes_ago', ['n' => $minutes]);
        }

        $hours = (int)round($diff / 3600);
        if ($hours < 24) {
            return $hours <= 1 
                ? I18n::t('time_hour_ago') 
                : I18n::t('time_hours_ago', ['n' => $hours]);
        }

        $days = (int)round($diff / 86400);
        if ($days < 30) {
            return $days <= 1 
                ? I18n::t('time_day_ago') 
                : I18n::t('time_days_ago', ['n' => $days]);
        }

        $months = (int)round($diff / 2592000);
        if ($months < 12) {
            return $months <= 1 
                ? I18n::t('time_month_ago') 
                : I18n::t('time_months_ago', ['n' => $months]);
        }

        $years = (int)round($diff / 31536000);
        return $years <= 1 
            ? I18n::t('time_year_ago') 
            : I18n::t('time_years_ago', ['n' => $years]);
    }
}
