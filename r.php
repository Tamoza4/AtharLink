<?php
declare(strict_types=1);

/**
 * AtharLink Core High-Speed Redirection Endpoint
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Icon.php';
require_once __DIR__ . '/src/Tracker.php';

use AtharLink\Helpers;
use AtharLink\Icon;
use AtharLink\Tracker;

// Extract slug from GET parameter or PATH_INFO / REQUEST_URI
$slug = $_GET['slug'] ?? null;

if (empty($slug)) {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!empty($uri)) {
        if (preg_match('#/(c|r)/([a-zA-Z0-9_-]+)#i', $uri, $matches)) {
            $slug = $matches[2];
        }
    }
}

if (empty($slug)) {
    header('Location: ' . Helpers::baseUrl());
    exit;
}

Tracker::handleRedirect((string)$slug);
