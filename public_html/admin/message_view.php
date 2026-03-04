<?php

require_once __DIR__ . '/bootstrap.php';

$admin = require_admin();
$pdo = db();

$id = int_param($_GET['id'] ?? 0, 0);
if ($id < 1) {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, email, subject, message, created_at, is_read FROM contact_messages WHERE id = ?');
$stmt->execute([$id]);
$msg = $stmt->fetch();

if (!$msg) {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

if ((int)$msg['is_read'] !== 1) {
    $u = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
    $u->execute([$id]);
    $msg['is_read'] = 1;
}

$title = 'Message #' . $id;
require __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="hstack" style="justify-content:space-between">
    <h1 class="title" style="margin:0">Message</h1>
    <a class="btn small ghost" href="/admin/messages.php">Back</a>
  </div>

  <div style="margin-top:12px">
    <div style="color:var(--muted);font-size:13px">From</div>
    <div style="font-weight:900;margin-top:4px"><?php echo e($msg['name']); ?> <span style="color:var(--muted);font-weight:700">(<?php echo e($msg['email']); ?>)</span></div>
  </div>

  <div style="margin-top:12px">
    <div style="color:var(--muted);font-size:13px">Subject</div>
    <div style="font-weight:900;margin-top:4px"><?php echo e($msg['subject'] ?: '(no subject)'); ?></div>
  </div>

  <div style="margin-top:12px">
    <div style="color:var(--muted);font-size:13px">Received</div>
    <div style="margin-top:4px"><?php echo e($msg['created_at']); ?></div>
  </div>

  <div style="margin-top:12px">
    <div style="color:var(--muted);font-size:13px">Message</div>
    <div class="card" style="margin-top:8px;white-space:pre-wrap;line-height:1.6"><?php echo e($msg['message']); ?></div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
