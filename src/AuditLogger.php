<?php
declare(strict_types=1);

namespace AtharLink;

use PDO;
use Throwable;

/**
 * Enterprise Admin Audit & Activity Logger
 * Records administrative authentication, link operations, settings alterations, and security events.
 */
class AuditLogger
{
    /**
     * Log an administrative or security event
     *
     * @param string $action Standard action key (e.g. 'login', 'logout', 'create_link', etc.)
     * @param string $description Clear human-readable description
     * @param array|null $details Structured metadata/changes (stored as JSON)
     * @param string|null $username Optional username override
     * @param int|null $userId Optional user ID override
     */
    public static function log(
        string $action,
        string $description,
        ?array $details = null,
        ?string $username = null,
        ?int $userId = null
    ): void {
        try {
            $pdo = Database::getConnection();

            if ($username === null) {
                $username = $_SESSION['athar_username'] ?? 'system';
            }
            if ($userId === null && isset($_SESSION['athar_user_id'])) {
                $userId = (int)$_SESSION['athar_user_id'];
            }

            $ip = Helpers::getClientIp();
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 500) : null;
            $detailsJson = ($details !== null && !empty($details))
                ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null;

            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, username, action, description, details, ip_address, user_agent, created_at)
                VALUES (:uid, :uname, :action, :desc, :details, :ip, :ua, CURRENT_TIMESTAMP)
            ");

            $stmt->execute([
                ':uid'     => $userId,
                ':uname'   => $username,
                ':action'  => $action,
                ':desc'    => $description,
                ':details' => $detailsJson,
                ':ip'      => $ip,
                ':ua'      => $ua,
            ]);
        } catch (Throwable $e) {
            // Fail silently to never interrupt the core application request
            error_log('AtharLink AuditLog Error: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve paginated audit logs with optional filter by action or keyword
     */
    public static function getLogs(int $limit = 50, int $offset = 0, ?string $action = null, ?string $search = null): array
    {
        $pdo = Database::getConnection();

        $where = [];
        $params = [];

        if (!empty($action) && $action !== 'all') {
            $where[] = "action = :action";
            $params[':action'] = $action;
        }

        if (!empty($search)) {
            $where[] = "(description LIKE :search OR username LIKE :search OR ip_address LIKE :search OR details LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT * FROM audit_logs
            {$whereClause}
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total audit logs matching criteria
     */
    public static function countLogs(?string $action = null, ?string $search = null): int
    {
        $pdo = Database::getConnection();

        $where = [];
        $params = [];

        if (!empty($action) && $action !== 'all') {
            $where[] = "action = :action";
            $params[':action'] = $action;
        }

        if (!empty($search)) {
            $where[] = "(description LIKE :search OR username LIKE :search OR ip_address LIKE :search OR details LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs {$whereClause}");
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Get distinct action types in the log for filtering dropdown
     */
    public static function getDistinctActions(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Prune logs older than $days days
     */
    public static function prune(int $days = 60): int
    {
        $pdo = Database::getConnection();
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE created_at < :cutoff");
        $stmt->execute([':cutoff' => $cutoff]);
        return $stmt->rowCount();
    }

    /**
     * Clear all logs
     */
    public static function clearAll(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec("DELETE FROM audit_logs;");
    }

    /**
     * Export audit logs to CSV or JSON
     */
    public static function export(string $format = 'csv'): string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, created_at, username, action, description, details, ip_address, user_agent FROM audit_logs ORDER BY id DESC");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($format === 'json') {
            return json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // CSV export
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'Timestamp', 'Username', 'Action', 'Description', 'Details', 'IP Address', 'User Agent']);

        foreach ($logs as $row) {
            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $row['username'],
                $row['action'],
                $row['description'],
                $row['details'] ?? '',
                $row['ip_address'] ?? '',
                $row['user_agent'] ?? ''
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return (string)$csv;
    }
}
