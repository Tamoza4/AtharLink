<?php
declare(strict_types=1);

/**
 * AtharLink RESTful API v1
 */

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/src/Helpers.php';
require_once dirname(__DIR__, 2) . '/src/Database.php';
require_once dirname(__DIR__, 2) . '/src/Auth.php';
require_once dirname(__DIR__, 2) . '/src/LinkManager.php';

use AtharLink\Helpers;
use AtharLink\Auth;
use AtharLink\LinkManager;
use AtharLink\Database;

// Set JSON headers & CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse request path
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$apiPath = '';

if ($requestUri) {
    if (preg_match('#/api/v1/?(.*)$#i', $requestUri, $matches)) {
        $apiPath = trim($matches[1], '/');
    }
}

// Fallback to query parameter endpoint
if ($apiPath === '' && !empty($_GET['endpoint'])) {
    $apiPath = trim((string)$_GET['endpoint'], '/');
}

$segments = explode('/', $apiPath);
$resource = $segments[0] ?? '';
$param1 = $segments[1] ?? ($_GET['slug'] ?? null);

$method = $_SERVER['REQUEST_METHOD'];

// 1. PUBLIC COUNTER ENDPOINT (Zero token required, zero private data leaked)
if ($resource === 'counter') {
    $slug = (string)($param1 ?? ($_GET['slug'] ?? ''));
    if (empty($slug)) {
        Helpers::json(['success' => false, 'error' => 'Missing slug parameter'], 400);
    }

    $link = LinkManager::findBySlug($slug);
    if (!$link) {
        Helpers::json(['success' => false, 'error' => 'Link not found'], 404);
    }

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(id) AS total_clicks,
            COALESCE(SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END), 0) AS unique_clicks
        FROM clicks 
        WHERE link_id = :lid
    ");
    $stmt->execute([':lid' => $link['id']]);
    $stats = $stmt->fetch();

    $type = $_GET['type'] ?? 'all';
    $total = (int)($stats['total_clicks'] ?? 0) + (int)($link['initial_clicks'] ?? 0);
    $unique = (int)($stats['unique_clicks'] ?? 0) + (int)($link['initial_unique_clicks'] ?? 0);

    Helpers::json([
        'success'       => true,
        'slug'          => $slug,
        'clicks'        => $type === 'unique' ? $unique : $total,
        'total_clicks'  => $total,
        'unique_clicks' => $unique
    ]);
}

// 2. AUTHENTICATED ENDPOINTS - Verify Bearer Token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (empty($authHeader) && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}

$apiToken = null;
if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    $apiToken = $matches[1];
}

if (!$apiToken) {
    Helpers::json(['success' => false, 'error' => 'Unauthorized: Missing or invalid Bearer token.'], 401);
}

$user = Auth::verifyApiToken($apiToken);
if (!$user) {
    Helpers::json(['success' => false, 'error' => 'Unauthorized: Invalid API token.'], 401);
}

// Parse JSON body for POST/PUT requests
$body = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}
$inputData = array_merge($_POST, $body);

// 3. RESOURCE: LINKS (/api/v1/links)
if ($resource === 'links') {
    // POST /api/v1/links - Create link
    if ($method === 'POST') {
        $res = LinkManager::create($inputData);
        if ($res['success']) {
            Helpers::json($res, 201);
        } else {
            Helpers::json($res, 400);
        }
    }

    // GET /api/v1/links/{slug} - Retrieve link details & statistics
    if ($method === 'GET') {
        if (empty($param1)) {
            // Return all links
            $links = LinkManager::getAll();
            foreach ($links as &$l) {
                $l['is_password_protected'] = (!empty($l['password_hash']) || !empty($l['password_plain']));
                unset($l['password_hash'], $l['password_plain']);
            }
            unset($l);
            Helpers::json(['success' => true, 'links' => $links]);
        }

        $link = LinkManager::findBySlug((string)$param1);
        if (!$link) {
            Helpers::json(['success' => false, 'error' => 'Link not found'], 404);
        }

        $link['is_password_protected'] = (!empty($link['password_hash']) || !empty($link['password_plain']));
        unset($link['password_hash'], $link['password_plain']);

        $stats = LinkManager::getLinkStats((int)$link['id'], $_GET['period'] ?? '30d');
        Helpers::json(['success' => true, 'link' => $link, 'stats' => $stats]);
    }

    // DELETE /api/v1/links/{slug} - Delete or deactivate link
    if ($method === 'DELETE') {
        if (empty($param1)) {
            Helpers::json(['success' => false, 'error' => 'Missing slug identifier'], 400);
        }

        $link = LinkManager::findBySlug((string)$param1);
        if (!$link) {
            Helpers::json(['success' => false, 'error' => 'Link not found'], 404);
        }

        // Support soft deactivation vs hard delete
        if (isset($_GET['action']) && $_GET['action'] === 'deactivate') {
            LinkManager::update((int)$link['id'], ['is_active' => 0]);
            Helpers::json(['success' => true, 'message' => 'Link deactivated successfully.']);
        } else {
            LinkManager::delete((int)$link['id']);
            Helpers::json(['success' => true, 'message' => 'Link deleted successfully.']);
        }
    }

    Helpers::json(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Default 404 for unrecognized resources
Helpers::json(['success' => false, 'error' => 'Endpoint not found'], 404);
