<?php

require_once __DIR__ . '/bootstrap.php';

$admin = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || strlen($password) < 8) {
            flash_set('danger', 'Username required, password must be at least 8 characters.');
            header('Location: /admin/admin_users.php');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, is_active, created_at) VALUES (?, ?, 1, NOW())');

        try {
            $stmt->execute([$username, $hash]);
            flash_set('ok', 'Admin user created.');
        } catch (PDOException $e) {
            flash_set('danger', 'Could not create admin user (username may already exist).');
        }

        header('Location: /admin/admin_users.php');
        exit;
    }

    if ($action === 'toggle') {
        $id = int_param($_POST['id'] ?? 0, 0);
        if ($id < 1) {
            flash_set('danger', 'Invalid user.');
            header('Location: /admin/admin_users.php');
            exit;
        }

        if ($id === (int)$admin['id']) {
            flash_set('danger', 'You cannot disable your own account.');
            header('Location: /admin/admin_users.php');
            exit;
        }

        $stmt = $pdo->prepare('UPDATE admin_users SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?');
        $stmt->execute([$id]);
        flash_set('ok', 'Updated.');
        header('Location: /admin/admin_users.php');
        exit;
    }

    flash_set('danger', 'Invalid action.');
    header('Location: /admin/admin_users.php');
    exit;
}

$page = page_param();
$perPage = per_page_param(20, 100);
$offset = pagination_offset($page, $perPage);

$sql = "SELECT id, username, is_active, created_at, last_login_at
        FROM admin_users
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll();

$title = 'Admin Users';
require __DIR__ . '/partials/header.php';
?>

<div class="card">
  <h1 class="title">Admin Users</h1>

  <div class="card">
    <div style="font-weight:900;margin-bottom:10px">Create Admin</div>
    <form method="post" action="/admin/admin_users.php" class="hstack" style="align-items:flex-end">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
      <input type="hidden" name="action" value="create" />

      <label style="flex:1;min-width:180px">
        <div style="color:var(--muted);font-size:13px;margin-bottom:6px">Username</div>
        <input class="input" type="text" name="username" required />
      </label>

      <label style="flex:1;min-width:180px">
        <div style="color:var(--muted);font-size:13px;margin-bottom:6px">Password</div>
        <input class="input" type="password" name="password" required />
      </label>

      <button class="btn" type="submit" style="min-width:140px">Create</button>
    </form>
  </div>

  <div style="margin-top:14px;overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Status</th>
          <th>Created</th>
          <th>Last login</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6" style="color:var(--muted)">No admin users.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo e($r['id']); ?></td>
            <td>
              <?php echo e($r['username']); ?>
              <?php if ((int)$r['id'] === (int)$admin['id']): ?>
                <span class="badge accent" style="margin-left:6px">you</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int)$r['is_active'] === 1): ?>
                <span class="badge ok">active</span>
              <?php else: ?>
                <span class="badge danger">disabled</span>
              <?php endif; ?>
            </td>
            <td><?php echo e($r['created_at']); ?></td>
            <td><?php echo e($r['last_login_at']); ?></td>
            <td>
              <?php if ((int)$r['id'] === (int)$admin['id']): ?>
                <span style="color:var(--muted)">—</span>
              <?php else: ?>
                <form method="post" action="/admin/admin_users.php" style="display:inline">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
                  <input type="hidden" name="action" value="toggle" />
                  <input type="hidden" name="id" value="<?php echo e($r['id']); ?>" />
                  <button class="btn small ghost" type="submit">Enable/Disable</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
