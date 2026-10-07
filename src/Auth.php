<?php
declare(strict_types=1);

namespace AtharLink;

use PDO;

/**
 * Authentication, Brute-Force Rate Limiting, and Session Manager
 */
class Auth
{
    /**
     * Check if current session is authenticated
     */
    public static function check(): bool
    {
        return !empty($_SESSION['athar_user_id']);
    }

    /**
     * Get currently logged-in user details
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        $userId = (int)$_SESSION['athar_user_id'];
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, username, api_token, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        return isset($_SESSION['athar_user_id']) ? (int)$_SESSION['athar_user_id'] : null;
    }

    /**
     * Authenticate user credentials with Brute-Force Rate Limiting
     */
    public static function login(string $username, string $password): array
    {
        $pdo = Database::getConnection();
        $username = trim($username);

        // Fetch user record
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        $currentTime = date('Y-m-d H:i:s');

        // Check if user account is locked
        if ($user && !empty($user['locked_until']) && $user['locked_until'] > $currentTime) {
            $lockExpires = strtotime($user['locked_until']);
            $remainingMinutes = max(1, (int)ceil(($lockExpires - time()) / 60));
            return [
                'success' => false,
                'message' => I18n::t('account_locked_temp', ['minutes' => $remainingMinutes])
            ];
        }

        // Verify password
        if (!$user || !password_verify($password, $user['password_hash'])) {
            if ($user) {
                $failedAttempts = (int)$user['failed_login_attempts'] + 1;
                $lockedUntil = null;

                if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
                    $lockedUntil = date('Y-m-d H:i:s', strtotime('+' . LOGIN_LOCKOUT_MINUTES . ' minutes'));
                }

                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET failed_login_attempts = :attempts, locked_until = :locked
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':attempts' => $failedAttempts,
                    ':locked'   => $lockedUntil,
                    ':id'       => $user['id']
                ]);

                if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
                    return [
                        'success' => false,
                        'message' => I18n::t('account_locked_max', ['minutes' => LOGIN_LOCKOUT_MINUTES])
                    ];
                }
            }

            return [
                'success' => false,
                'message' => I18n::t('invalid_credentials')
            ];
        }

        // Success: Reset failed attempts & unlock
        $resetStmt = $pdo->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, locked_until = NULL 
            WHERE id = :id
        ");
        $resetStmt->execute([':id' => $user['id']]);

        // Prevent Session Fixation attacks
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['athar_user_id'] = (int)$user['id'];
        $_SESSION['athar_username'] = $user['username'];
        $_SESSION['athar_logged_in_at'] = time();

        return [
            'success' => true,
            'message' => I18n::t('login_success')
        ];
    }

    /**
     * Terminate user session
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        @session_destroy();
    }

    /**
     * Register a new user
     */
    public static function createUser(string $username, string $password): array
    {
        $username = trim($username);
        if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
            return [
                'success' => false,
                'message' => I18n::t('username_requirements')
            ];
        }

        if (strlen($password) < 8) {
            return [
                'success' => false,
                'message' => I18n::t('password_requirements')
            ];
        }

        $pdo = Database::getConnection();

        // Check if username exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $checkStmt->execute([':u' => $username]);
        if ($checkStmt->fetch()) {
            return [
                'success' => false,
                'message' => I18n::t('username_taken')
            ];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $apiToken = bin2hex(random_bytes(32));

        $insertStmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, api_token) 
            VALUES (:u, :p, :t)
        ");
        $insertStmt->execute([
            ':u' => $username,
            ':p' => $hash,
            ':t' => $apiToken
        ]);

        return [
            'success'   => true,
            'user_id'   => (int)$pdo->lastInsertId(),
            'api_token' => $apiToken,
            'message'   => I18n::t('user_created_success')
        ];
    }

    /**
     * Verify API Bearer Token
     */
    public static function verifyApiToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE api_token = :t LIMIT 1");
        $stmt->execute([':t' => $token]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Regenerate user API Token
     */
    public static function regenerateApiToken(int $userId): string
    {
        $pdo = Database::getConnection();
        $newToken = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET api_token = :t WHERE id = :id");
        $stmt->execute([':t' => $newToken, ':id' => $userId]);
        return $newToken;
    }

    /**
     * Check if AtharLink is installed
     */
    public static function isInstalled(): bool
    {
        if (file_exists(LOCK_FILE)) {
            return true;
        }

        // Additional safeguard: check if users exist in database
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $count = (int)$stmt->fetchColumn();
            if ($count > 0) {
                // Self-healing: restore lock file immediately
                @file_put_contents(LOCK_FILE, json_encode([
                    'installed_at' => date('c'),
                    'version'      => APP_VERSION
                ]));
                return true;
            }
        } catch (\Throwable) {}

        return false;
    }

    /**
     * Get or generate Emergency Recovery Key for admin password reset
     */
    public static function getRecoveryKey(): string
    {
        if (file_exists(RECOVERY_FILE)) {
            $key = trim((string)file_get_contents(RECOVERY_FILE));
            if (!empty($key)) {
                return $key;
            }
        }

        // Generate formatted key: ATHAR-XXXX-XXXX-XXXX
        $raw = strtoupper(bin2hex(random_bytes(6)));
        $formatted = 'ATHAR-' . substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 4);

        if (is_dir(STORAGE_DIR) && is_writable(STORAGE_DIR)) {
            @file_put_contents(RECOVERY_FILE, $formatted, LOCK_EX);
        }

        return $formatted;
    }

    /**
     * Verify Emergency Recovery Key
     */
    public static function verifyRecoveryKey(string $inputKey): bool
    {
        $current = self::getRecoveryKey();
        $cleanInput = strtoupper((string)preg_replace('/[^A-Z0-9]/', '', $inputKey));
        $cleanCurrent = strtoupper((string)preg_replace('/[^A-Z0-9]/', '', $current));

        return !empty($cleanInput) && hash_equals($cleanCurrent, $cleanInput);
    }

    /**
     * Reset Admin Password using Emergency Recovery Key
     */
    public static function resetPasswordWithRecoveryKey(string $recoveryKey, string $newPassword): array
    {
        if (!self::verifyRecoveryKey($recoveryKey)) {
            return [
                'success' => false,
                'message' => I18n::t('recovery_key_invalid')
            ];
        }

        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => I18n::t('password_requirements')
            ];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id ASC LIMIT 1");
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => I18n::t('user_not_found')
            ];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = :p, failed_login_attempts = 0, locked_until = NULL 
            WHERE id = :id
        ");
        $updateStmt->execute([':p' => $hash, ':id' => $user['id']]);

        // One-time rotation: regenerate recovery key
        @unlink(RECOVERY_FILE);
        $newKey = self::getRecoveryKey();

        return [
            'success'          => true,
            'message'          => I18n::t('password_reset_success'),
            'new_recovery_key' => $newKey
        ];
    }
}
