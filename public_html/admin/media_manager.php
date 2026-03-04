<?php

require_once __DIR__ . '/bootstrap.php';

$admin = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $action = (string)($_POST['action'] ?? '');
    $id = int_param($_POST['id'] ?? 0, 0);

    if ($id > 0 && $action === 'toggle_active') {
        $stmt = $pdo->prepare('UPDATE media SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END WHERE id = ?');
        $stmt->execute([$id]);
        flash_set('ok', 'Updated.');
        header('Location: ' . build_url('/admin/media_manager.php', ['page' => page_param(), 'per_page' => per_page_param()]));
        exit;
    }

    if ($id > 0 && $action === 'delete') {
        $stmt = $pdo->prepare('SELECT id, file_path, (SELECT COUNT(*) FROM media_links ml WHERE ml.media_id = media.id) AS link_count FROM media WHERE id = ?');
        $stmt->execute([$id]);
        $m = $stmt->fetch();

        if (!$m) {
            flash_set('danger', 'Media not found.');
            header('Location: /admin/media_manager.php');
            exit;
        }

        if ((int)$m['link_count'] > 0) {
            flash_set('danger', 'Cannot delete linked media.');
            header('Location: /admin/media_manager.php');
            exit;
        }

        $abs = resolve_storage_path((string)$m['file_path']);
        if ($abs && file_exists($abs)) {
            @unlink($abs);
        }

        $del = $pdo->prepare('DELETE FROM media WHERE id = ?');
        $del->execute([$id]);

        flash_set('ok', 'Media deleted.');
        header('Location: /admin/media_manager.php');
        exit;
    }

    flash_set('danger', 'Invalid action.');
    header('Location: /admin/media_manager.php');
    exit;
}

$page = page_param();
$perPage = per_page_param(24, 100);
$offset = pagination_offset($page, $perPage);

$sql = "SELECT m.id, m.original_name, m.mime_type, m.file_path, m.active, m.uploaded_at,
               (SELECT COUNT(*) FROM media_links ml WHERE ml.media_id = m.id) AS link_count
        FROM media m
        ORDER BY m.uploaded_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll();

$title = 'Media Manager';
require __DIR__ . '/partials/header.php';
?>

<div class="card">
  <h1 class="title">Media Manager</h1>

  <div style="overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>Preview</th>
          <th>ID</th>
          <th>Name</th>
          <th>MIME</th>
          <th>Linked</th>
          <th>Status</th>
          <th>Uploaded</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" style="color:var(--muted)">No media.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $m): ?>
          <tr>
            <td>
              <img class="thumb" alt="preview" src="/admin/media.php?id=<?php echo e($m['id']); ?>&thumb=1&w=180&h=120" />
            </td>
            <td><?php echo e($m['id']); ?></td>
            <td>
              <div style="font-weight:800"><?php echo e($m['original_name']); ?></div>
              <div style="color:var(--muted);font-size:12px;margin-top:4px"><?php echo e($m['file_path']); ?></div>
            </td>
            <td><?php echo e($m['mime_type']); ?></td>
            <td>
              <?php if ((int)$m['link_count'] > 0): ?>
                <span class="badge ok">linked (<?php echo e($m['link_count']); ?>)</span>
              <?php else: ?>
                <span class="badge">unlinked</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int)$m['active'] === 1): ?>
                <span class="badge ok">active</span>
              <?php else: ?>
                <span class="badge danger">inactive</span>
              <?php endif; ?>
            </td>
            <td><?php echo e($m['uploaded_at']); ?></td>
            <td>
              <div class="hstack">
                <form method="post" action="/admin/media_manager.php?page=<?php echo e($page); ?>&per_page=<?php echo e($perPage); ?>">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
                  <input type="hidden" name="action" value="toggle_active" />
                  <input type="hidden" name="id" value="<?php echo e($m['id']); ?>" />
                  <button class="btn small ghost" type="submit">Active</button>
                </form>

                <?php if ((int)$m['link_count'] === 0): ?>
                  <form method="post" action="/admin/media_manager.php?page=<?php echo e($page); ?>&per_page=<?php echo e($perPage); ?>" onsubmit="return confirm('Delete this unlinked media?');">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?php echo e($m['id']); ?>" />
                    <button class="btn small danger" type="submit">Delete</button>
                  </form>
                <?php else: ?>
                  <span style="color:var(--muted)">—</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination('/admin/media_manager.php', $page, $perPage, count($rows)); ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
