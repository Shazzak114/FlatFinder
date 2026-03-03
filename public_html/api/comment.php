<?php
declare(strict_types=1);

require_once __DIR__ . '/utils/bootstrap.php';

$userId = require_login();
$ip = client_ip();

if (request_method() === 'POST') {
    rate_limit_or_429('comment:post:user:' . $userId, 5, 10, null);
    rate_limit_or_429('comment:post:ip:' . $ip, 10, 10, null);

    $body = array_merge($_POST, get_json_body());

    $listingId = param_int($body, 'listing_id', 1, 1000000000);
    $text = param_string($body, 'text', 1000);
    $parentId = param_int($body, 'parent_id', 1, 1000000000);

    if ($listingId === null || $text === null) {
        error_response('Missing listing_id or text', 400);
    }

    $stmt = $pdo->prepare("SELECT id, is_hidden, status FROM listings WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $listingId]);
    $listing = $stmt->fetch();

    if (!$listing || (int)$listing['is_hidden'] === 1 || $listing['status'] !== 'published') {
        error_response('Not found', 404);
    }

    if ($parentId !== null) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM listing_comments
            WHERE id = :id AND listing_id = :listing_id
            LIMIT 1
        ");
        $stmt->execute([':id' => $parentId, ':listing_id' => $listingId]);
        if (!$stmt->fetch()) {
            error_response('Invalid parent_id', 400);
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO listing_comments (listing_id, user_id, parent_id, body, created_at)
        VALUES (:listing_id, :user_id, :parent_id, :body, NOW())
    ");
    $stmt->execute([
        ':listing_id' => $listingId,
        ':user_id' => $userId,
        ':parent_id' => $parentId,
        ':body' => $text,
    ]);

    json_response([
        'comment_id' => (int)$pdo->lastInsertId(),
    ], 201);
}

if (request_method() === 'GET') {
    rate_limit_or_429('comment:get:user:' . $userId, 20, 10, null);

    $listingId = param_int($_GET, 'listing_id', 1, 1000000000);
    $limit = param_int($_GET, 'limit', 1, 50) ?? 50;

    if ($listingId === null) {
        error_response('Missing listing_id', 400);
    }

    $stmt = $pdo->prepare("SELECT id, is_hidden, status FROM listings WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $listingId]);
    $listing = $stmt->fetch();

    if (!$listing || (int)$listing['is_hidden'] === 1 || $listing['status'] !== 'published') {
        error_response('Not found', 404);
    }

    $sql = "
        SELECT
            c.id,
            c.user_id,
            COALESCE(u.display_name, u.name, '') AS display_name,
            c.parent_id,
            c.body,
            c.created_at
        FROM listing_comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.listing_id = :listing_id
        ORDER BY c.created_at ASC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':listing_id', $listingId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    json_response(['comments' => $stmt->fetchAll()]);
}

error_response('Method not allowed', 405);
