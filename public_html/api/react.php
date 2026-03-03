<?php
declare(strict_types=1);

require_once __DIR__ . '/utils/bootstrap.php';

require_method('POST');

$userId = require_login();
$ip = client_ip();

rate_limit_or_429('react:user:' . $userId, 30, 10, null);
rate_limit_or_429('react:ip:' . $ip, 60, 10, null);

$body = array_merge($_POST, get_json_body());

$listingId = param_int($body, 'listing_id', 1, 1000000000);
$action = param_string($body, 'action', 20);

if ($listingId === null || $action === null) {
    error_response('Missing listing_id or action', 400);
}

$action = strtolower($action);
$map = [
    'like' => ['type' => 'like', 'op' => 'add', 'col' => 'likes_count'],
    'unlike' => ['type' => 'like', 'op' => 'remove', 'col' => 'likes_count'],
    'spam' => ['type' => 'spam', 'op' => 'add', 'col' => 'spam_count'],
    'unspam' => ['type' => 'spam', 'op' => 'remove', 'col' => 'spam_count'],
];

if (!isset($map[$action])) {
    error_response('Invalid action', 400);
}

$reactionType = $map[$action]['type'];
$op = $map[$action]['op'];
$counterCol = $map[$action]['col'];

try {
    $stmt = $pdo->prepare("SELECT id, is_hidden, status FROM listings WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $listingId]);
    $listing = $stmt->fetch();

    if (!$listing || (int)$listing['is_hidden'] === 1 || $listing['status'] !== 'published') {
        error_response('Not found', 404);
    }

    if ($op === 'add') {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO listing_reactions (listing_id, user_id, reaction_type, created_at)
            VALUES (:listing_id, :user_id, :reaction_type, NOW())
        ");
        $stmt->execute([
            ':listing_id' => $listingId,
            ':user_id' => $userId,
            ':reaction_type' => $reactionType,
        ]);

        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE listings SET {$counterCol} = {$counterCol} + 1 WHERE id = :id");
            $stmt->execute([':id' => $listingId]);
        }
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM listing_reactions
            WHERE listing_id = :listing_id AND user_id = :user_id AND reaction_type = :reaction_type
        ");
        $stmt->execute([
            ':listing_id' => $listingId,
            ':user_id' => $userId,
            ':reaction_type' => $reactionType,
        ]);

        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE listings SET {$counterCol} = GREATEST({$counterCol} - 1, 0) WHERE id = :id");
            $stmt->execute([':id' => $listingId]);
        }
    }

    $stmt = $pdo->prepare("SELECT likes_count, spam_count FROM listings WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $listingId]);
    $counts = $stmt->fetch();

    json_response([
        'listing_id' => $listingId,
        'likes_count' => (int)($counts['likes_count'] ?? 0),
        'spam_count' => (int)($counts['spam_count'] ?? 0),
    ]);
} catch (PDOException $e) {
    error_response('Unable to react', 409);
}
