<?php

require_once __DIR__ . '/bootstrap.php';

require_admin();

$pdo = db();
$id = int_param($_GET['id'] ?? 0, 0);
if ($id < 1) {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare('SELECT id, file_path, mime_type, active FROM media WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m || (int)$m['active'] !== 1) {
    http_response_code(404);
    exit;
}

$abs = resolve_storage_path((string)$m['file_path']);
if (!$abs || !file_exists($abs)) {
    http_response_code(404);
    exit;
}

$thumb = isset($_GET['thumb']) && (string)$_GET['thumb'] !== '0';
$w = int_param($_GET['w'] ?? 200, 200);
$h = int_param($_GET['h'] ?? 200, 200);
if ($w < 20) $w = 20;
if ($h < 20) $h = 20;
if ($w > 800) $w = 800;
if ($h > 800) $h = 800;

header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');

$mime = (string)$m['mime_type'];

if (!$thumb) {
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($abs));
    readfile($abs);
    exit;
}

$img = null;
if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
    $img = @imagecreatefromjpeg($abs);
} elseif ($mime === 'image/png') {
    $img = @imagecreatefrompng($abs);
} elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
    $img = @imagecreatefromwebp($abs);
}

if (!$img) {
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($abs));
    readfile($abs);
    exit;
}

$srcW = imagesx($img);
$srcH = imagesy($img);

$scale = min($w / $srcW, $h / $srcH);
if ($scale > 1) $scale = 1;

$dstW = (int)max(1, floor($srcW * $scale));
$dstH = (int)max(1, floor($srcH * $scale));

$dst = imagecreatetruecolor($dstW, $dstH);
imagecopyresampled($dst, $img, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

imagedestroy($img);

header('Content-Type: image/jpeg');
imagejpeg($dst, null, 84);
imagedestroy($dst);
exit;
