<?php

require_once __DIR__ . '/bootstrap.php';

$admin = require_admin();
$pdo = db();

$keys = [
    'about' => 'About',
    'contact' => 'Contact',
    'privacy_policy' => 'Privacy Policy',
    'footer' => 'Footer',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $sql = 'INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()';
    $stmt = $pdo->prepare($sql);

    foreach ($keys as $k => $_label) {
        $val = (string)($_POST[$k] ?? '');
        $stmt->execute([$k, $val]);
    }

    flash_set('ok', 'Settings saved.');
    header('Location: /admin/settings.php');
    exit;
}

$stmt = $pdo->prepare('SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN (?,?,?,?)');
$stmt->execute(array_keys($keys));
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$title = 'Settings';
require __DIR__ . '/partials/header.php';
?>

<div class="card">
  <div class="hstack" style="justify-content:space-between">
    <h1 class="title" style="margin:0">Settings</h1>
    <a class="btn small ghost" href="/admin/media_manager.php">Media</a>
  </div>

  <form method="post" action="/admin/settings.php">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />

    <?php foreach ($keys as $k => $label): ?>
      <div style="margin-top:12px">
        <div style="color:var(--muted);font-size:13px;margin-bottom:6px"><?php echo e($label); ?></div>
        <textarea class="textarea" name="<?php echo e($k); ?>"><?php echo e($settings[$k] ?? ''); ?></textarea>
      </div>
    <?php endforeach; ?>

    <div style="margin-top:12px">
      <button class="btn" type="submit">Save</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
