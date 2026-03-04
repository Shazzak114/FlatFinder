<?php

require_once __DIR__ . '/bootstrap.php';

$admin = require_admin();
$pdo = db();

$stats = [
    'users' => 0,
    'pending_listings' => 0,
    'approved_listings' => 0,
    'reports' => 0,
];

$stats['users'] = (int)$pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$stats['pending_listings'] = (int)$pdo->query("SELECT COUNT(*) AS c FROM listings WHERE status = 'pending'")->fetch()['c'];
$stats['approved_listings'] = (int)$pdo->query("SELECT COUNT(*) AS c FROM listings WHERE status IN ('approved','published')")->fetch()['c'];
$stats['reports'] = (int)$pdo->query("SELECT COUNT(*) AS c FROM listing_reports WHERE status = 'open'")->fetch()['c'];

$title = 'Dashboard';
require __DIR__ . '/partials/header.php';
?>

<div class="card">
  <h1 class="title">Dashboard</h1>

  <div class="hstack">
    <div class="card" style="flex:1;min-width:220px">
      <div style="color:var(--muted);font-size:13px">Users</div>
      <div style="font-size:28px;font-weight:900;margin-top:6px"><?php echo e($stats['users']); ?></div>
    </div>

    <div class="card" style="flex:1;min-width:220px">
      <div style="color:var(--muted);font-size:13px">Pending listings</div>
      <div style="font-size:28px;font-weight:900;margin-top:6px"><?php echo e($stats['pending_listings']); ?></div>
      <div style="margin-top:10px">
        <a class="btn small ghost" href="/admin/listings.php?status=pending">Review</a>
      </div>
    </div>

    <div class="card" style="flex:1;min-width:220px">
      <div style="color:var(--muted);font-size:13px">Approved (incl. published)</div>
      <div style="font-size:28px;font-weight:900;margin-top:6px"><?php echo e($stats['approved_listings']); ?></div>
      <div style="margin-top:10px">
        <a class="btn small ghost" href="/admin/listings.php?status=approved">View</a>
      </div>
    </div>

    <div class="card" style="flex:1;min-width:220px">
      <div style="color:var(--muted);font-size:13px">Open reports</div>
      <div style="font-size:28px;font-weight:900;margin-top:6px"><?php echo e($stats['reports']); ?></div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
