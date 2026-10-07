<?php
declare(strict_types=1);

/**
 * AtharLink CLI - Emergency Admin Password Reset Tool
 * 
 * Security: This file CANNOT be accessed or executed from the web.
 * It strictly terminates with 403 Forbidden if not invoked via PHP CLI.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain');
    die("Access Denied: This utility can ONLY be executed via the server command line (CLI).\n");
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/src/Helpers.php';
require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/Auth.php';

use AtharLink\Database;

$username = $argv[1] ?? null;
$newPassword = $argv[2] ?? null;

echo "\n";
echo "========================================================\n";
echo "    AtharLink CLI - Admin Password Reset Utility\n";
echo "========================================================\n\n";

if (!$username || !$newPassword) {
    echo "Usage:\n";
    echo "  php bin/reset_password.php <username> <new_password>\n\n";
    echo "Example:\n";
    echo "  php bin/reset_password.php admin MyNewStrongPass123\n\n";
    exit(1);
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "[ERROR] User '{$username}' was not found in the database.\n\n";
        exit(1);
    }

    if (strlen($newPassword) < 8) {
        echo "[ERROR] Password must be at least 8 characters long.\n\n";
        exit(1);
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = :p, failed_login_attempts = 0, locked_until = NULL 
        WHERE id = :id
    ");
    $updateStmt->execute([':p' => $hash, ':id' => $user['id']]);

    echo "[SUCCESS] Password for administrator '{$username}' has been successfully reset!\n";
    echo "You can now log in to the dashboard with your new password.\n\n";
    exit(0);

} catch (\Throwable $e) {
    echo "[EXCEPTION] " . $e->getMessage() . "\n\n";
    exit(1);
}
