<?php
declare(strict_types=1);

require_once __DIR__ . '/utils/bootstrap.php';

$userId = require_login();
$ip = client_ip();

// Rate limit phone reveals to reduce scraping.
rate_limit_or_429('get_phone:ip:' . $ip, 10, 60, null);
rate_limit_or_429('get_phone:user:' . $userId, 5, 60, null);

$listingId = param_int($_GET, 'listing_id', 1, 1000000000);
if ($listingId === null) {
    error_response('Missing listing_id', 400);
}

$sql = "
    SELECT
        id,
        user_id,
        phone,
        is_hidden,
        status
    FROM listings
    WHERE id = :id
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $listingId]);
$listing = $stmt->fetch();

if (!$listing) {
    error_response('Not found', 404);
}

$isOwner = ((int)$listing['user_id'] === $userId);
if (((int)$listing['is_hidden'] === 1 || $listing['status'] !== 'published') && !$isOwner) {
    error_response('Not found', 404);
}

$phone = trim((string)($listing['phone'] ?? ''));
if ($phone === '') {
    error_response('Phone not available', 404);
}

json_response(['phone' => $phone]);
