<?php

require_once __DIR__ . '/bootstrap.php';

$admin = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $action = (string)($_POST['action'] ?? '');
    $userId = int_param($_POST['user_id'] ?? 0, 0);

    if ($userId > 0 && $action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET nid_status = 'approved', nid_reviewed_at = NOW(), nid_denied_reason = NULL WHERE id = ? AND nid_status = 'pending'");
        $stmt->execute([$userId]);
        flash_set('ok', 'NID approved.');
        header('Location: /admin/nid_queue.php');
        exit;
    }

    if ($userId > 0 && $action === 'deny') {
        $reason = trim((string)($_POST['reason'] ?? ''));
        $stmt = $pdo->prepare("UPDATE users SET nid_status = 'denied', nid_reviewed_at = NOW(), nid_denied_reason = ? WHERE id = ? AND nid_status = 'pending'");
        $stmt->execute([$reason, $userId]);
        flash_set('ok', 'NID denied.');
        header('Location: /admin/nid_queue.php');
        exit;
    }

    flash_set('danger', 'Invalid action.');
    header('Location: /admin/nid_queue.php');
    exit;
}

$page = page_param();
$perPage = per_page_param(20, 100);
$offset = pagination_offset($page, $perPage);

$sql = "SELECT id, name, email, phone, nid_front_media_id, nid_back_media_id, nid_selfie_media_id, created_at
        FROM users
        WHERE nid_status = 'pending'
        ORDER BY created_at ASC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll();

$title = 'NID Review Queue';
require __DIR__ . '/partials/header.php';
?>

<div class="card">
  <h1 class="title">NID Review Queue</h1>

  <div style="overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>User</th>
          <th>NID Front</th>
          <th>NID Back</th>
          <th>Selfie</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5" style="color:var(--muted)">No pending NID reviews.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $u): ?>
          <tr>
            <td>
              <div style="font-weight:800"><?php echo e($u['name']); ?></div>
              <div style="color:var(--muted);font-size:13px"><?php echo e($u['email']); ?><?php echo $u['phone'] ? ' • ' . e($u['phone']) : ''; ?></div>
              <div style="color:var(--muted);font-size:12px;margin-top:4px">User ID: <?php echo e($u['id']); ?></div>
            </td>
            <td>
              <?php if ($u['nid_front_media_id']): ?>
                <img class="thumb" alt="NID front" src="/admin/media.php?id=<?php echo e($u['nid_front_media_id']); ?>&thumb=1&w=180&h=120" />
              <?php else: ?>
                <span style="color:var(--muted)">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u['nid_back_media_id']): ?>
                <img class="thumb" alt="NID back" src="/admin/media.php?id=<?php echo e($u['nid_back_media_id']); ?>&thumb=1&w=180&h=120" />
              <?php else: ?>
                <span style="color:var(--muted)">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u['nid_selfie_media_id']): ?>
                <img class="thumb" alt="Selfie" src="/admin/media.php?id=<?php echo e($u['nid_selfie_media_id']); ?>&thumb=1&w=180&h=120" />
              <?php else: ?>
                <span style="color:var(--muted)">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="hstack">
                <form method="post" action="/admin/nid_queue.php">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
                  <input type="hidden" name="action" value="approve" />
                  <input type="hidden" name="user_id" value="<?php echo e($u['id']); ?>" />
                  <button class="btn small ok" type="submit">Approve</button>
                </form>

                <form method="post" action="/admin/nid_queue.php" class="hstack" style="flex-wrap:nowrap">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
                  <input type="hidden" name="action" value="deny" />
                  <input type="hidden" name="user_id" value="<?php echo e($u['id']); ?>" />
                  <input class="input" type="text" name="reason" placeholder="Reason (optional)" style="min-width:160px" />
                  <button class="btn small danger" type="submit">Deny</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination('/admin/nid_queue.php', $page, $perPage, count($rows)); ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
