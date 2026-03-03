<?php

require_once __DIR__ . '/bootstrap.php';

$admin = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $action = (string)($_POST['action'] ?? '');
    $id = int_param($_POST['id'] ?? 0, 0);

    if ($id > 0 && ($action === 'mark_read' || $action === 'mark_unread')) {
        $isRead = $action === 'mark_read' ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = ? WHERE id = ?');
        $stmt->execute([$isRead, $id]);
        flash_set('ok', 'Updated.');
        header('Location: ' . build_url('/admin/messages.php', ['page' => page_param(), 'per_page' => per_page_param()]));
        exit;
    }

    flash_set('danger', 'Invalid action.');
    header('Location: /admin/messages.php');
    exit;
}

$page = page_param();
$perPage = per_page_param(20, 100);
$offset = pagination_offset($page, $perPage);

$sql = "SELECT id, name, email, subject, message, created_at, is_read
        FROM contact_messages
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll();

$title = 'Messages Inbox';
require __DIR__ . '/partials/header.php';
?>

<div class="card">
  <h1 class="title">Messages</h1>

  <div style="overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>Status</th>
          <th>From</th>
          <th>Subject</th>
          <th>Received</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5" style="color:var(--muted)">No messages.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $m): ?>
          <tr>
            <td>
              <?php if ((int)$m['is_read'] === 1): ?>
                <span class="badge">read</span>
              <?php else: ?>
                <span class="badge accent">unread</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-weight:800"><?php echo e($m['name']); ?></div>
              <div style="color:var(--muted);font-size:13px"><?php echo e($m['email']); ?></div>
            </td>
            <td>
              <a href="/admin/message_view.php?id=<?php echo e($m['id']); ?>" style="font-weight:800">
                <?php echo e($m['subject'] ?: '(no subject)'); ?>
              </a>
              <div style="color:var(--muted);font-size:13px;margin-top:4px">
                <?php
                  $excerpt = trim(preg_replace('/\s+/', ' ', (string)$m['message']));
                  if (strlen($excerpt) > 120) $excerpt = substr($excerpt, 0, 120) . '…';
                  echo e($excerpt);
                ?>
              </div>
            </td>
            <td><?php echo e($m['created_at']); ?></td>
            <td>
              <div class="hstack">
                <a class="btn small ghost" href="/admin/message_view.php?id=<?php echo e($m['id']); ?>">Open</a>

                <form method="post" action="/admin/messages.php?page=<?php echo e($page); ?>&per_page=<?php echo e($perPage); ?>">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
                  <input type="hidden" name="id" value="<?php echo e($m['id']); ?>" />
                  <?php if ((int)$m['is_read'] === 1): ?>
                    <input type="hidden" name="action" value="mark_unread" />
                    <button class="btn small ghost" type="submit">Unread</button>
                  <?php else: ?>
                    <input type="hidden" name="action" value="mark_read" />
                    <button class="btn small ghost" type="submit">Read</button>
                  <?php endif; ?>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination('/admin/messages.php', $page, $perPage, count($rows)); ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
