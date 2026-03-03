<?php
declare(strict_types=1);

require_once __DIR__ . '/utils/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    error_response('Missing id', 400);
}

$sql = "
    SELECT
        id,
        user_id,
        title,
        description,
        price,
        category_key,
        area,
        address,
        lat,
        lng,
        phone,
        created_at
    FROM listings
    WHERE id = :id AND status = 'published' AND is_hidden = 0
    LIMIT 1
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        error_response('Not found', 404);
    }

    json_response(['listing' => $row]);
} catch (Throwable $t) {
    error_response('Unable to load listing', 500);
}
