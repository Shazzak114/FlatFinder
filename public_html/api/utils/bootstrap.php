<?php
declare(strict_types=1);

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/request.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/listing_integrity.php';

require_once __DIR__ . '/../../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
    }

    session_name('bk_user');
    session_start();
}

// Make config available to endpoints that expect it.
$GLOBALS['APP_CONFIG'] = app_config();

// Warm DB connection early to fail fast (but keep JSON error).
try {
    $pdo = db();
} catch (Throwable $t) {
    error_response('Database connection failed', 500);
}
