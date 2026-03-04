<?php

require_once __DIR__ . '/bootstrap.php';

$admin = require_admin();
$pdo = db();

$page = page_param();
$perPage = per_page_param(20, 100);
$offset = pagination_offset($page, $perPage);

$pendingNid = (int)$pdo->query("SELECT COUNT(*) AS c FROM users WHERE nid_status = 'pending'")->fetch()['c'];

$sql = "SELECT id, name, email, phone, nid_status, is_active, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll();

$title = 'Users';
require __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="hstack" style="justify-content:space-between">
    <h1 class="title" style="margin:0">Users</h1>
    <div class="hstack">
      <a class="btn small ghost" href="/admin/nid_queue.php">NID Queue (<?php echo e($pendingNid); ?>)</a>
    </div>
  </div>

  <div style="margin-top:12px;overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>NID</th>
          <th>Active</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" style="color:var(--muted)">No users.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo e($r['id']); ?></td>
            <td><?php echo e($r['name']); ?></td>
            <td><?php echo e($r['email']); ?></td>
            <td><?php echo e($r['phone']); ?></td>
            <td>
              <?php
                $st = (string)$r['nid_status'];
                $badge = 'badge';
                if ($st === 'approved') $badge .= ' ok';
                else if ($st === 'denied') $badge .= ' danger';
                else if ($st === 'pending') $badge .= ' accent';
              ?>
              <span class="<?php echo e($badge); ?>"><?php echo e($st ?: 'none'); ?></span>
            </td>
            <td>
              <?php if ((int)$r['is_active'] === 1): ?>
                <span class="badge ok">active</span>
              <?php else: ?>
                <span class="badge danger">disabled</span>
              <?php endif; ?>
            </td>
            <td><?php echo e($r['created_at']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination('/admin/users.php', $page, $perPage, count($rows)); ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
