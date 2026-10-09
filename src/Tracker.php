<?php
declare(strict_types=1);

namespace AtharLink;

use PDO;

/**
 * High-Throughput Redirection, Tracking, and Safe Streaming Engine
 */
class Tracker
{
    /**
     * Handle incoming link redirection and logging
     */
    public static function handleRedirect(string $slug): void
    {
        $slug = trim($slug);
        if (!Helpers::isValidSlug($slug)) {
            self::renderErrorPage(I18n::t('err_invalid_slug_title'), I18n::t('err_invalid_slug_desc'), 404);
            return;
        }

        $pdo = Database::getConnection();

        // 1. Ultra-fast single indexed query lookup
        $stmt = $pdo->prepare("SELECT * FROM links WHERE slug = :s LIMIT 1");
        $stmt->execute([':s' => $slug]);
        $link = $stmt->fetch();

        if (!$link) {
            self::renderErrorPage(I18n::t('err_not_found_title'), I18n::t('err_not_found_desc'), 404);
            return;
        }

        // 2. Pre-Redirect Checks

        // Check if link is active
        if ((int)$link['is_active'] !== 1) {
            self::renderErrorPage(I18n::t('err_disabled_title'), I18n::t('err_disabled_desc'), 403);
            return;
        }

        // Check if link is expired
        if (!empty($link['expires_at'])) {
            $expireTime = strtotime($link['expires_at']);
            if ($expireTime !== false && $expireTime <= time()) {
                self::renderErrorPage(I18n::t('err_expired_title'), I18n::t('err_expired_desc'), 410);
                return;
            }
        }

        // Check click limit
        if (!empty($link['click_limit'])) {
            $limitStmt = $pdo->prepare("SELECT COUNT(*) FROM clicks WHERE link_id = :lid");
            $limitStmt->execute([':lid' => $link['id']]);
            $currentClicks = (int)($link['initial_clicks'] ?? 0) + (int)$limitStmt->fetchColumn();

            if ($currentClicks >= (int)$link['click_limit']) {
                // Automatically deactivate link
                $deactivateStmt = $pdo->prepare("UPDATE links SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :lid");
                $deactivateStmt->execute([':lid' => $link['id']]);

                self::renderErrorPage(I18n::t('err_limit_title'), I18n::t('err_limit_desc'), 403);
                return;
            }
        }

        // Check password protection
        if (!empty($link['password_hash']) || !empty($link['password_plain'])) {
            $sessionKey = 'athar_unlocked_' . $link['id'];
            $attemptKey = 'athar_pattempts_' . $link['id'];
            $lockKey    = 'athar_plock_' . $link['id'];

            if (empty($_SESSION[$sessionKey])) {
                // Rate limit lockout check (15 minutes after 5 failed attempts)
                if (!empty($_SESSION[$lockKey]) && $_SESSION[$lockKey] > time()) {
                    self::renderPasswordPrompt($link, 'too_many_attempts');
                    return;
                }

                // Process password submission or show form
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_password'])) {
                    $submittedPass = (string)($_POST['link_password'] ?? '');
                    $token = $_POST['csrf_token'] ?? '';

                    if (!Helpers::verifyCsrf($token)) {
                        self::renderPasswordPrompt($link, 'csrf_expired');
                        return;
                    }

                    $isCorrect = false;
                    if (!empty($link['password_hash']) && password_verify($submittedPass, $link['password_hash'])) {
                        $isCorrect = true;
                    } else {
                        $plain = Helpers::decryptSensitive($link['password_plain'] ?? null);
                        if ($plain !== null && $submittedPass === $plain) {
                            $isCorrect = true;
                        }
                    }

                    if ($isCorrect) {
                        $_SESSION[$sessionKey] = true;
                        unset($_SESSION[$attemptKey], $_SESSION[$lockKey]);
                    } else {
                        $failed = (int)($_SESSION[$attemptKey] ?? 0) + 1;
                        $_SESSION[$attemptKey] = $failed;
                        if ($failed >= 5) {
                            $_SESSION[$lockKey] = time() + (15 * 60);
                            self::renderPasswordPrompt($link, 'too_many_attempts');
                        } else {
                            self::renderPasswordPrompt($link, 'invalid_password');
                        }
                        return;
                    }
                } else {
                    self::renderPasswordPrompt($link);
                    return;
                }
            }
        }

        // 3. Unique Click Detection
        $linkId = (int)$link['id'];
        $uniquenessWindow = (int)Database::getSetting('uniqueness_window', (string)DEFAULT_UNIQUENESS_WINDOW);
        $cookieName = 'athar_u_' . $linkId;
        $isUnique = 1;

        $clientIp = Helpers::getClientIp();
        $ipHash = Helpers::hashIp($clientIp);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Check tracking cookie
        if (isset($_COOKIE[$cookieName])) {
            $verifiedTime = Helpers::verifyTrackingValue($_COOKIE[$cookieName]);
            if ($verifiedTime !== null && (time() - (int)$verifiedTime) < $uniquenessWindow) {
                $isUnique = 0;
            }
        }

        // Fallback uniqueness check against DB (ip_hash + user_agent within window)
        if ($isUnique === 1) {
            $windowStart = date('Y-m-d H:i:s', time() - $uniquenessWindow);
            $checkStmt = $pdo->prepare("
                SELECT id FROM clicks 
                WHERE link_id = :lid 
                  AND ip_hash = :iph 
                  AND user_agent = :ua 
                  AND clicked_at >= :win 
                LIMIT 1
            ");
            $checkStmt->execute([
                ':lid' => $linkId,
                ':iph' => $ipHash,
                ':ua'  => $userAgent,
                ':win' => $windowStart
            ]);

            if ($checkStmt->fetch()) {
                $isUnique = 0;
            }
        }

        // Set/refresh signed tracking cookie
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie(
            $cookieName,
            Helpers::signTrackingValue((string)time()),
            [
                'expires'  => time() + $uniquenessWindow,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        // 4. Record Click in Clicks Table
        $countryCode = Helpers::getCountryCode();
        $rawReferrer = $_SERVER['HTTP_REFERER'] ?? null;
        $referrerDomain = Helpers::extractDomain($rawReferrer);
        $uaInfo = Helpers::parseUserAgent($userAgent);

        $clickInsertStmt = $pdo->prepare("
            INSERT INTO clicks (
                link_id, is_unique, ip_address, ip_hash, country_code,
                referrer, referrer_domain, device_type, browser,
                platform, user_agent, clicked_at
            ) VALUES (
                :lid, :uniq, :ip, :iph, :country,
                :ref, :domain, :dev, :browser,
                :platform, :ua, CURRENT_TIMESTAMP
            )
        ");
        $clickInsertStmt->execute([
            ':lid'      => $linkId,
            ':uniq'     => $isUnique,
            ':ip'       => $clientIp,
            ':iph'      => $ipHash,
            ':country'  => $countryCode,
            ':ref'      => $rawReferrer,
            ':domain'   => $referrerDomain,
            ':dev'      => $uaInfo['device_type'],
            ':browser'  => $uaInfo['browser'],
            ':platform' => $uaInfo['platform'],
            ':ua'       => substr($userAgent, 0, 500)
        ]);

        // 5. UTM Parameter Forwarding
        $targetUrl = $link['target_url'];
        if ((int)$link['forward_utm'] === 1 && !empty($_GET)) {
            $targetUrl = Helpers::mergeUtmParameters($targetUrl, $_GET);
        }

        // 6. Execute Redirection based on redirect_type
        $redirectType = (int)$link['redirect_type'];

        // Force Download (Safe Streaming Mode - Type 2)
        if ($redirectType === 2) {
            self::streamSafeDownload($targetUrl);
            return;
        }

        // 301 Permanent Redirect
        if ($redirectType === 301) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $targetUrl);
            exit;
        }

        // 302 Temporary Redirect (Default) with strict no-cache headers
        header('HTTP/1.1 302 Found');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Location: ' . $targetUrl);
        exit;
    }

    /**
     * Safe chunked streaming download without memory exhaustion and with SSRF prevention
     */
    private static function streamSafeDownload(string $targetUrl): void
    {
        // 1. Strict SSRF check
        if (!Helpers::isSafeDestinationUrl($targetUrl)) {
            self::renderErrorPage(I18n::t('err_unauthorized_title'), I18n::t('err_unauthorized_desc'), 403);
            return;
        }

        // Determine filename
        $parsedPath = parse_url($targetUrl, PHP_URL_PATH);
        $filename = 'download';
        if ($parsedPath) {
            $base = basename($parsedPath);
            if (!empty($base)) {
                $filename = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $base);
            }
        }

        // Open remote stream with timeout and safe stream context
        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => 30,
                'follow_location' => 0, // Strict SSRF defense: do not follow redirects to private/internal IPs
                'user_agent'      => 'AtharLink/1.0 (Safe Download Streamer)'
            ]
        ]);

        $stream = @fopen($targetUrl, 'rb', false, $context);
        if ($stream === false) {
            self::renderErrorPage(I18n::t('err_download_failed_title'), I18n::t('err_download_failed_desc'), 502);
            return;
        }

        // Send streaming headers
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        // Chunked stream output - never load entire file into memory
        while (!feof($stream)) {
            echo fread($stream, 65536); // 64KB chunks
            flush();
        }

        fclose($stream);
        exit;
    }

    /**
     * Branded password verification screen with separate dual-language toggle
     */
    private static function renderPasswordPrompt(array $link, ?string $errorCode = null): void
    {
        http_response_code(401);

        // Language resolution:
        // 1. ?lang=en or ?lang=ar passed directly via language switch button
        // 2. Cookie 'athar_auth_lang'
        // 3. Link's designated 'auth_lang' setting ('en', 'ar', or 'auto')
        // 4. Global cookie 'athar_lang'
        // 5. Browser Accept-Language header
        $queryLang = $_GET['lang'] ?? null;
        if (in_array($queryLang, ['ar', 'en'], true)) {
            $lang = $queryLang;
            setcookie('athar_auth_lang', $lang, time() + (86400 * 30), '/');
        } elseif (isset($_COOKIE['athar_auth_lang']) && in_array($_COOKIE['athar_auth_lang'], ['ar', 'en'], true)) {
            $lang = $_COOKIE['athar_auth_lang'];
        } elseif (!empty($link['auth_lang']) && in_array($link['auth_lang'], ['ar', 'en'], true)) {
            $lang = $link['auth_lang'];
        } elseif (isset($_COOKIE['athar_lang']) && in_array($_COOKIE['athar_lang'], ['ar', 'en'], true)) {
            $lang = $_COOKIE['athar_lang'];
        } else {
            $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            $lang = (stripos($accept, 'ar') === 0 || stripos($accept, 'ar-') !== false) ? 'ar' : 'en';
        }

        $dir = ($lang === 'ar') ? 'rtl' : 'ltr';

        $strings = [
            'ar' => [
                'badge'            => 'رابط محمي بكلمة مرور',
                'default_title'    => 'رابط محمي بكلمة مرور',
                'description'      => 'هذا الرابط محمي بواسطة كلمة مرور، يرجى إدخال كلمة المرور للمتابعة.',
                'label_password'   => 'كلمة المرور',
                'placeholder'      => 'أدخل كلمة المرور...',
                'submit_btn'       => 'تحقق ومتابعة التوجيه',
                'csrf_expired'     => 'انتهت صلاحية جلسة التحقق، يرجى المحاولة مرة أخرى.',
                'invalid_password' => 'كلمة المرور غير صحيحة. يرجى المحاولة مرة أخرى.',
                'too_many_attempts'=> 'تم تجاوز الحد الأقصى للمحاولات الخاطئة. يرجى الانتظار 15 دقيقة للمحاولة مجدداً.',
                'toggle_label'     => 'English',
                'toggle_target'    => 'en'
            ],
            'en' => [
                'badge'            => 'Password Protected Link',
                'default_title'    => 'Password Protected Link',
                'description'      => 'This link is protected with a password. Please enter the password to proceed.',
                'label_password'   => 'Password',
                'placeholder'      => 'Enter password...',
                'submit_btn'       => 'Verify & Proceed',
                'csrf_expired'     => 'Session expired. Please try again.',
                'invalid_password' => 'Incorrect password. Please try again.',
                'too_many_attempts'=> 'Too many incorrect attempts. Please wait 15 minutes before trying again.',
                'toggle_label'     => 'Arabic',
                'toggle_target'    => 'ar'
            ]
        ];

        $t = $strings[$lang];
        $title = !empty($link['title']) ? $link['title'] : $t['default_title'];
        $csrfToken = Helpers::csrfToken();
        $slug = Helpers::e($link['slug']);

        $errorMsg = null;
        if ($errorCode) {
            $errorMsg = $t[$errorCode] ?? $errorCode;
        }
        $errorHtml = $errorMsg ? '<div class="alert alert-danger py-2 mb-3 small fw-medium text-center">' . Helpers::e($errorMsg) . '</div>' : '';

        ?>
        <!DOCTYPE html>
        <html lang="<?= Helpers::e($lang) ?>" dir="<?= Helpers::e($dir) ?>" data-bs-theme="dark">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= Helpers::e($title) ?> | <?= APP_NAME ?></title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="<?= Helpers::baseUrl('assets/css/bootstrap.min.css') ?>">
            <link rel="stylesheet" href="<?= Helpers::baseUrl('assets/css/app.css') ?>">
            <style>
                body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; margin: 0; }
                .tamoza-prompt-box { position: relative; width: 100%; max-width: 440px; background: #161A23 !important; border: 1px solid var(--tamoza-border); border-radius: var(--tamoza-radius-lg); box-shadow: 0 20px 45px rgba(0,0,0,0.4); padding: 40px 32px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="tamoza-prompt-box">
                <!-- On-Page Language Switcher -->
                <div class="d-flex justify-content-end mb-2">
                    <a href="<?= Helpers::baseUrl('r.php?slug=' . urlencode($slug) . '&lang=' . $t['toggle_target']) ?>" class="btn btn-tamoza-subtle btn-sm px-3 py-1 rounded-3 d-inline-flex align-items-center gap-2">
                        <?= Icon::get('globe', '', 14) ?>
                        <span><?= $t['toggle_label'] ?></span>
                    </a>
                </div>

                <div class="mb-3">
                    <span class="tamoza-badge badge-indigo px-3 py-2 fs-6 d-inline-flex align-items-center gap-2">
                        <?= Icon::get('lock', '', 16) ?>
                        <span><?= $t['badge'] ?></span>
                    </span>
                </div>
                <h3 class="fw-bold mb-2"><?= Helpers::e($title) ?></h3>
                <p class="text-secondary small mb-4"><?= $t['description'] ?></p>
                
                <?= $errorHtml ?>

                <form method="POST" action="<?= Helpers::baseUrl('r.php?slug=' . urlencode($slug)) ?>" class="text-start">
                    <input type="hidden" name="csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= $t['label_password'] ?></label>
                        <div class="input-group">
                            <input type="password" name="link_password" id="authPasswordInput" class="form-control tamoza-input" placeholder="<?= $t['placeholder'] ?>" required autofocus>
                            <button type="button" class="btn btn-tamoza-secondary d-flex align-items-center justify-content-center" id="toggleAuthPassBtn" title="Show / Hide Password">
                                <span id="authEyeIcon" class="d-inline-flex align-items-center"><?= Icon::get('eye', '', 16) ?></span>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-tamoza-primary w-100 py-3 fw-bold fs-6 d-inline-flex align-items-center justify-content-center gap-2">
                        <?= Icon::get('shield', '', 18) ?>
                        <span><?= $t['submit_btn'] ?></span>
                    </button>
                </form>
            </div>

            <script>
            (function() {
                const eyeIcon = <?= json_encode(Icon::get('eye', '', 16)) ?>;
                const eyeOffIcon = <?= json_encode(Icon::get('eye-off', '', 16)) ?>;
                const btn = document.getElementById('toggleAuthPassBtn');
                const inp = document.getElementById('authPasswordInput');
                const iconContainer = document.getElementById('authEyeIcon');
                
                if (btn && inp && iconContainer) {
                    btn.addEventListener('click', function() {
                        if (inp.type === 'password') {
                            inp.type = 'text';
                            iconContainer.innerHTML = eyeOffIcon;
                        } else {
                            inp.type = 'password';
                            iconContainer.innerHTML = eyeIcon;
                        }
                    });
                }
            })();
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Render branded error page
     */
    private static function renderErrorPage(string $title, string $message, int $statusCode = 404): void
    {
        http_response_code($statusCode);
        $lang = I18n::getLang();
        $dir = I18n::getDir();
        ?>
        <!DOCTYPE html>
        <html lang="<?= Helpers::e($lang) ?>" dir="<?= Helpers::e($dir) ?>" data-bs-theme="dark">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= Helpers::e($title) ?> | <?= APP_NAME ?></title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="<?= Helpers::baseUrl('assets/css/bootstrap.min.css') ?>">
            <link rel="stylesheet" href="<?= Helpers::baseUrl('assets/css/app.css') ?>">
            <style>
                body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; margin: 0; }
                .tamoza-error-box { width: 100%; max-width: 480px; background: var(--tamoza-surface-card); backdrop-filter: blur(16px) saturate(140%); -webkit-backdrop-filter: blur(16px) saturate(140%); border: 1px solid var(--tamoza-border); border-radius: var(--tamoza-radius-lg); box-shadow: 0 20px 40px rgba(0,0,0,0.25); padding: 48px 32px; text-align: center; }
            </style>
        </head>
        <body class="<?= $lang === 'en' ? 'lang-en' : 'lang-ar' ?>">
            <div class="tamoza-error-box">
                <div class="display-3 text-danger fw-bold mb-2"><?= $statusCode ?></div>
                <h3 class="fw-bold mb-3"><?= Helpers::e($title) ?></h3>
                <p class="text-secondary mb-4"><?= Helpers::e($message) ?></p>
                <a href="<?= Helpers::baseUrl() ?>" class="btn btn-tamoza-secondary px-4"><?= Helpers::e(I18n::t('back_to_home')) ?></a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
