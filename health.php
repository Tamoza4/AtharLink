<?php
declare(strict_types=1);

/**
 * AtharLink Production Liveness & Readiness Health Probe
 * Returns JSON status with HTTP 200 (healthy) or HTTP 503 (degraded/unhealthy).
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Database.php';

$response = [
    'status'    => 'ok',
    'timestamp' => date('c'),
    'app'       => APP_NAME,
    'version'   => APP_VERSION,
    'checks'    => [
        'php_version' => PHP_VERSION,
        'storage'     => is_dir(STORAGE_DIR) && is_writable(STORAGE_DIR) ? 'ok' : 'fail',
        'database'    => 'unknown',
        'wal_mode'    => 'unknown',
    ]
];

$httpCode = 200;

try {
    $pdo = AtharLink\Database::getConnection();
    $stmt = $pdo->query('SELECT 1');
    if ($stmt && (int)$stmt->fetchColumn() === 1) {
        $response['checks']['database'] = 'ok';
    } else {
        throw new Exception('Database ping query failed');
    }

    $journal = $pdo->query('PRAGMA journal_mode')->fetchColumn();
    $response['checks']['wal_mode'] = strtolower((string)$journal) === 'wal' ? 'ok' : 'suboptimal (' . $journal . ')';
} catch (Throwable $e) {
    $httpCode = 503;
    $response['status'] = 'degraded';
    $response['checks']['database'] = 'fail';
    $response['error'] = 'Database connectivity degraded: ' . $e->getMessage();
}

if ($response['checks']['storage'] !== 'ok') {
    $httpCode = 503;
    $response['status'] = 'unhealthy';
    $response['error'] = 'Storage directory not writable';
}

http_response_code($httpCode);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
