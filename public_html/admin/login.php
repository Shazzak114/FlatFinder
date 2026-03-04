<?php

require_once __DIR__ . '/bootstrap.php';

if (current_admin()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$next = $_GET['next'] ?? '/admin/dashboard.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, username, password_hash, is_active FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if (!$row) {
        // Optional: bootstrap admin user from config if table is empty.
        $cfg = app_config();
        $fallbackUser = (string)($cfg['admin']['username'] ?? '');
        $fallbackHash = (string)($cfg['admin']['password_hash'] ?? '');

        if ($fallbackUser !== '' && $fallbackHash !== '' && hash_equals($fallbackUser, $username) && password_verify($password, $fallbackHash)) {
            $count = (int)$pdo->query('SELECT COUNT(*) AS c FROM admin_users')->fetch()['c'];
            if ($count === 0) {
                $ins = $pdo->prepare('INSERT INTO admin_users (username, password_hash, is_active, created_at) VALUES (?, ?, 1, NOW())');
                $ins->execute([$fallbackUser, $fallbackHash]);

                $row = [
                    'id' => (int)$pdo->lastInsertId(),
                    'username' => $fallbackUser,
                    'password_hash' => $fallbackHash,
                    'is_active' => 1,
                ];
            }
        }
    }

    if (!$row || (int)$row['is_active'] !== 1 || !password_verify($password, (string)$row['password_hash'])) {
        $error = 'Invalid credentials.';
    } else {
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int)$row['id'];

        $u = $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?');
        $u->execute([(int)$row['id']]);

        header('Location: ' . $next);
        exit;
    }
}

$title = 'Admin Login';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo e($title); ?></title>
  <link rel="stylesheet" href="/admin/assets/admin.css" />
</head>
<body>
  <header class="topbar">
    <div class="brand">Admin Login</div>
  </header>

  <main class="main" style="max-width:520px;margin:0 auto;padding-top:calc(var(--header-h) + 18px)">
    <div class="card">
      <h1 class="title">Sign in</h1>

      <?php if ($error): ?>
        <div class="flash danger"><?php echo e($error); ?></div>
      <?php endif; ?>

      <form method="post" action="<?php echo e(build_url('/admin/login.php', ['next' => $next])); ?>">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />

        <div style="margin-bottom:10px">
          <label>
            <div style="color:var(--muted);font-size:13px;margin-bottom:6px">Username</div>
            <input class="input" type="text" name="username" autocomplete="username" required />
          </label>
        </div>

        <div style="margin-bottom:10px">
          <label>
            <div style="color:var(--muted);font-size:13px;margin-bottom:6px">Password</div>
            <input class="input" type="password" name="password" autocomplete="current-password" required />
          </label>
        </div>

        <button class="btn" type="submit">Login</button>
      </form>

      <div style="margin-top:10px;color:var(--muted);font-size:13px">
        First login options:
        <ul style="margin:8px 0 0; padding-left:18px; line-height:1.5">
          <li>Create the first admin user directly in the <code>admin_users</code> table, or</li>
          <li>Set <code>ADMIN_USERNAME</code> and <code>ADMIN_PASSWORD_HASH</code> in <code>config/config.php</code> (only works if <code>admin_users</code> is empty).</li>
        </ul>
      </div>
    </div>
  </main>
</body>
</html>
