<?php
declare(strict_types=1);

/**
 * AtharLink Dynamic SVG Badge Generator
 * Generates lightweight, retina-ready SVG badges (like shields.io) for any link.
 * 
 * Usage:
 *   <img src="https://domain.com/badge.php?slug=app&type=unique&label=Downloads&color=indigo" alt="Downloads">
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/LinkManager.php';

use AtharLink\Database;
use AtharLink\LinkManager;

$slug  = trim((string)($_GET['slug'] ?? ''));
$type  = ($_GET['type'] ?? 'unique') === 'all' ? 'all' : 'unique';
$label = trim((string)($_GET['label'] ?? 'clicks'));
$color = trim((string)($_GET['color'] ?? 'indigo'));

// Sanitize label text
$label = preg_replace('/[^\p{L}\p{N}_\-\s]/u', '', $label);
if ($label === '') {
    $label = 'clicks';
}

$colorMap = [
    'indigo'  => '#6366F1',
    'emerald' => '#10B981',
    'green'   => '#10B981',
    'blue'    => '#3B82F6',
    'warning' => '#F59E0B',
    'amber'   => '#F59E0B',
    'orange'  => '#F97316',
    'red'     => '#EF4444',
    'purple'  => '#8B5CF6',
    'dark'    => '#1E293B',
];

$bgRight = $colorMap[$color] ?? (preg_match('/^#[a-fA-F0-9]{6}$/', '#' . ltrim($color, '#')) ? '#' . ltrim($color, '#') : '#6366F1');

$count = 0;
if ($slug !== '') {
    try {
        $link = LinkManager::findBySlug($slug);
        if ($link) {
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

            $total  = (int)($stats['total_clicks'] ?? 0) + (int)($link['initial_clicks'] ?? 0);
            $unique = (int)($stats['unique_clicks'] ?? 0) + (int)($link['initial_unique_clicks'] ?? 0);
            $count  = ($type === 'all') ? $total : $unique;
        }
    } catch (\Throwable $e) {
        $count = 0;
    }
}

// Format number (e.g. 1,450)
$formattedCount = number_format($count);

// Approximate text widths for SVG proportional spacing
$labelLen = mb_strlen($label, 'UTF-8');
$valLen   = mb_strlen($formattedCount, 'UTF-8');

$leftWidth  = max(56, (int)($labelLen * 7.5 + 16));
$rightWidth = max(38, (int)($valLen * 8.2 + 16));
$totalWidth = $leftWidth + $rightWidth;

$leftTextX  = (int)($leftWidth / 2);
$rightTextX = (int)($leftWidth + ($rightWidth / 2));

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=60, s-maxage=60');
header('Access-Control-Allow-Origin: *');

echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$totalWidth}" height="20" role="img" aria-label="{$label}: {$formattedCount}">
    <linearGradient id="s" x2="0" y2="100%">
        <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
        <stop offset="1" stop-opacity=".1"/>
    </linearGradient>
    <clipPath id="r">
        <rect width="{$totalWidth}" height="20" rx="4" fill="#fff"/>
    </clipPath>
    <g clip-path="url(#r)">
        <rect width="{$leftWidth}" height="20" fill="#24292E"/>
        <rect x="{$leftWidth}" width="{$rightWidth}" height="20" fill="{$bgRight}"/>
        <rect width="{$totalWidth}" height="20" fill="url(#s)"/>
    </g>
    <g fill="#fff" text-anchor="middle" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif" text-rendering="geometricPrecision" font-size="110">
        <text aria-hidden="true" x="{$leftTextX}0" y="150" fill="#010101" fill-opacity=".3" transform="scale(.1)" textLength="{$labelLen}00">{$label}</text>
        <text x="{$leftTextX}0" y="140" transform="scale(.1)" fill="#fff" textLength="{$labelLen}00">{$label}</text>
        <text aria-hidden="true" x="{$rightTextX}0" y="150" fill="#010101" fill-opacity=".3" transform="scale(.1)" textLength="{$valLen}00" font-weight="bold">{$formattedCount}</text>
        <text x="{$rightTextX}0" y="140" transform="scale(.1)" fill="#fff" font-weight="bold" textLength="{$valLen}00">{$formattedCount}</text>
    </g>
</svg>
SVG;
exit;
