<?php
declare(strict_types=1);

require_once __DIR__ . '/utils/bootstrap.php';

require_method('POST');

$ip = client_ip();
rate_limit_or_429('report_listing:ip:' . $ip, 5, 600, null);

$body = array_merge($_POST, get_json_body());

$listingId = param_int($body, 'listing_id', 1, 1000000000);
$reason = param_string($body, 'reason', 80);
$details = param_string($body, 'details', 500);

if ($listingId === null || $reason === null) {
    error_response('Missing listing_id or reason', 400);
}

$userId = current_user_id();
$appSecret = (string)(app_config()['app_secret'] ?? '');
$ipHash = hash_hmac('sha256', $ip, $appSecret);

// Avoid transactions/row locks (shared hosting + MyISAM limitations).
try {
    $stmt = $pdo->prepare("SELECT id, report_count, is_hidden, status FROM listings WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $listingId]);
    $listing = $stmt->fetch();

    if (!$listing || $listing['status'] !== 'published') {
        error_response('Not found', 404);
    }

    if ((int)$listing['is_hidden'] === 1) {
        json_response(['report_count' => (int)$listing['report_count'], 'is_hidden' => true]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO listing_reports (listing_id, reporter_user_id, reporter_ip_hash, reason, details, created_at)
        VALUES (:listing_id, :reporter_user_id, :reporter_ip_hash, :reason, :details, NOW())
    ");
    $stmt->execute([
        ':listing_id' => $listingId,
        ':reporter_user_id' => $userId,
        ':reporter_ip_hash' => $ipHash,
        ':reason' => $reason,
        ':details' => $details,
    ]);

    $stmt = $pdo->prepare("UPDATE listings SET report_count = report_count + 1 WHERE id = :id");
    $stmt->execute([':id' => $listingId]);

    $stmt = $pdo->prepare("SELECT report_count FROM listings WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $listingId]);
    $reportCount = (int)($stmt->fetchColumn() ?: 0);

    $isHidden = false;
    if ($reportCount >= 3) {
        $stmt = $pdo->prepare("UPDATE listings SET is_hidden = 1, hidden_reason = 'auto_reported' WHERE id = :id");
        $stmt->execute([':id' => $listingId]);
        $isHidden = true;
    }

    json_response([
        'report_count' => $reportCount,
        'is_hidden' => $isHidden,
    ]);
} catch (PDOException $e) {
    // Likely duplicate report by same reporter (unique index), or invalid listing.
    error_response('Unable to submit report', 409);
}
