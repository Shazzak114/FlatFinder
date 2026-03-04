<?php
declare(strict_types=1);

// Keep ping lightweight: it should respond even if DB credentials are wrong.

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$ok = true;
$dbOk = true;

try {
    $pdo = db();
    $pdo->query('SELECT 1');
} catch (Throwable $t) {
    $dbOk = false;
    $ok = false;
}

http_response_code($ok ? 200 : 500);

echo json_encode([
    'ok' => $ok,
    'db_ok' => $dbOk,
    'time' => gmdate('c'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
