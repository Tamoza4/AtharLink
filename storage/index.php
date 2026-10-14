<?php
declare(strict_types=1);

/**
 * AtharLink Storage Guard
 * Defense-in-depth barrier against direct folder access on non-Apache servers.
 */
http_response_code(403);
header('Content-Type: text/plain; charset=UTF-8');
exit('Access Denied: Direct access to storage directory is strictly forbidden.');
