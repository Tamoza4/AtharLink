<?php
declare(strict_types=1);

namespace AtharLink;

use PDO;
use Throwable;

/**
 * High-Performance SQLite-Backed Rate Limiter & Brute-Force Shield
 */
class RateLimiter
{
    /**
     * Check if an IP address is currently locked out for a given action
     *
     * @param string $action Action key (e.g., 'login', 'recovery', 'api')
     * @param int $maxAttempts Maximum allowed failures within the window
     * @param int $windowMinutes Time window in minutes
     * @param string|null $ip Optional explicit IP, defaults to client IP
     * @return bool True if locked out, false if permitted
     */
    public static function isLocked(string $action, int $maxAttempts = 5, int $windowMinutes = 15, ?string $ip = null): bool
    {
        $ip = $ip ?? Helpers::getClientIp();
        $since = time() - ($windowMinutes * 60);

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM rate_limits 
                WHERE action_key = :act AND ip_address = :ip AND attempted_at >= :since
            ");
            $stmt->execute([
                ':act'   => $action,
                ':ip'    => $ip,
                ':since' => $since
            ]);
            $count = (int)$stmt->fetchColumn();

            return $count >= $maxAttempts;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get remaining lockout duration in minutes for an IP
     */
    public static function getRemainingLockoutMinutes(string $action, int $windowMinutes = 15, ?string $ip = null): int
    {
        $ip = $ip ?? Helpers::getClientIp();
        $since = time() - ($windowMinutes * 60);

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT MIN(attempted_at) FROM rate_limits 
                WHERE action_key = :act AND ip_address = :ip AND attempted_at >= :since
            ");
            $stmt->execute([
                ':act'   => $action,
                ':ip'    => $ip,
                ':since' => $since
            ]);
            $oldest = (int)$stmt->fetchColumn();
            if ($oldest <= 0) {
                return 1;
            }

            $elapsed = time() - $oldest;
            $remaining = ($windowMinutes * 60) - $elapsed;
            return max(1, (int)ceil($remaining / 60));
        } catch (Throwable) {
            return 1;
        }
    }

    /**
     * Record a failed attempt for an IP
     */
    public static function recordAttempt(string $action, ?string $ip = null): void
    {
        $ip = $ip ?? Helpers::getClientIp();

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO rate_limits (action_key, ip_address, attempted_at)
                VALUES (:act, :ip, :ts)
            ");
            $stmt->execute([
                ':act' => $action,
                ':ip'  => $ip,
                ':ts'  => time()
            ]);

            // Periodic probabilistic cleanup (1 in 50 requests)
            if (mt_rand(1, 50) === 1) {
                self::pruneOldAttempts();
            }
        } catch (Throwable) {}
    }

    /**
     * Clear recorded attempts for an IP (e.g. after successful login)
     */
    public static function clearAttempts(string $action, ?string $ip = null): void
    {
        $ip = $ip ?? Helpers::getClientIp();

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE action_key = :act AND ip_address = :ip");
            $stmt->execute([':act' => $action, ':ip' => $ip]);
        } catch (Throwable) {}
    }

    /**
     * Prune expired rate limit records (older than 24 hours)
     */
    public static function pruneOldAttempts(): void
    {
        try {
            $cutoff = time() - 86400;
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE attempted_at < :cutoff");
            $stmt->execute([':cutoff' => $cutoff]);
        } catch (Throwable) {}
    }
}
