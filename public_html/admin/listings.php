<?php

require_once __DIR__ . '/bootstrap.php';

$admin = require_admin();
$pdo = db();

$status = (string)($_GET['status'] ?? 'pending');
$allowedStatuses = ['pending', 'approved', 'published', 'rejected'];
if (!in_array($status, $allowedStatuses, true)) $status = 'pending';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $action = (string)($_POST['action'] ?? '');
    $listingId = int_param($_POST['listing_id'] ?? 0, 0);

    if ($listingId > 0 && $action === 'approve') {
        $stmt = $pdo->prepare("UPDATE listings SET status = 'approved', approved_at = NOW(), publish_at = DATE_ADD(NOW(), INTERVAL 1 MINUTE), rejected_at = NULL, rejected_reason = NULL WHERE id = ? AND status = 'pending'");
        $stmt->execute([$listingId]);
        flash_set('ok', 'Listing approved. It will publish after 1 minute.');
        header('Location: ' . build_url('/admin/listings.php', ['status' => $status, 'page' => page_param(), 'per_page' => per_page_param()]));
        exit;
    }

    if ($listingId > 0 && $action === 'reject') {
        $reason = trim((string)($_POST['reason'] ?? ''));
        $stmt = $pdo->prepare("UPDATE listings SET status = 'rejected', rejected_at = NOW(), rejected_reason = ? WHERE id = ? AND status = 'pending'");
        $stmt->execute([$reason, $listingId]);
        flash_set('ok', 'Listing rejected.');
        header('Location: ' . build_url('/admin/listings.php', ['status' => $status, 'page' => page_param(), 'per_page' => per_page_param()]));
        exit;
    }

    flash_set('danger', 'Invalid action.');
    header('Location: ' . build_url('/admin/listings.php', ['status' => $status]));
    exit;
}

$page = page_param();
$perPage = per_page_param(20, 100);
$offset = pagination_offset($page, $perPage);

$sql = "SELECT id, user_id, title, status, created_at, approved_at, publish_at, rejected_at, rejected_reason
        FROM listings
        WHERE status = ?
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute([$status]);
$rows = $stmt->fetchAll();

$title = 'Listings Moderation';
require __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="hstack" style="justify-content:space-between">
    <h1 class="title" style="margin:0">Listings</h1>

    <form method="get" action="/admin/listings.php" class="hstack">
      <label style="min-width:220px">
        <select name="status">
          <?php foreach ($allowedStatuses as $s): ?>
            <option value="<?php echo e($s); ?>" <?php echo $s === $status ? 'selected' : ''; ?>><?php echo e(ucfirst($s)); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn small ghost" type="submit">Filter</button>
    </form>
  </div>

  <div style="margin-top:12px;overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>User</th>
          <th>Status</th>
          <th>Created</th>
          <th>Publish at</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" style="color:var(--muted)">No listings.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo e($r['id']); ?></td>
            <td><?php echo e($r['title']); ?></td>
            <td><?php echo e($r['user_id']); ?></td>
            <td>
              <?php
                $badge = 'badge';
                if ($r['status'] === 'pending') $badge .= ' accent';
                if ($r['status'] === 'approved' || $r['status'] === 'published') $badge .= ' ok';
                if ($r['status'] === 'rejected') $badge .= ' danger';
              ?>
              <span class="<?php echo e($badge); ?>"><?php echo e($r['status']); ?></span>
              <?php if ($r['status'] === 'rejected' && $r['rejected_reason']): ?>
                <div style="color:var(--muted);font-size:12px;margin-top:4px">Reason: <?php echo e($r['rejected_reason']); ?></div>
              <?php endif; ?>
            </td>
            <td><?php echo e($r['created_at']); ?></td>
            <td><?php echo e($r['publish_at']); ?></td>
            <td>
              <?php if ($r['status'] === 'pending'): ?>
                <div class="hstack">
                  <form method="post" action="/admin/listings.php?status=<?php echo e(urlencode($status)); ?>&page=<?php echo e($page); ?>&per_page=<?php echo e($perPage); ?>">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
                    <input type="hidden" name="action" value="approve" />
                    <input type="hidden" name="listing_id" value="<?php echo e($r['id']); ?>" />
                    <button class="btn small ok" type="submit">Approve</button>
                  </form>

                  <form method="post" action="/admin/listings.php?status=<?php echo e(urlencode($status)); ?>&page=<?php echo e($page); ?>&per_page=<?php echo e($perPage); ?>" class="hstack" style="flex-wrap:nowrap">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
                    <input type="hidden" name="action" value="reject" />
                    <input type="hidden" name="listing_id" value="<?php echo e($r['id']); ?>" />
                    <input class="input" type="text" name="reason" placeholder="Reason (optional)" style="min-width:160px" />
                    <button class="btn small danger" type="submit">Reject</button>
                  </form>
                </div>
              <?php else: ?>
                <span style="color:var(--muted)">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination('/admin/listings.php', $page, $perPage, count($rows), ['status' => $status]); ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
