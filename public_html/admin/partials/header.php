<?php
$admin = $admin ?? null;
$title = $title ?? 'Admin';
$flash = flash_get();
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
    <div class="brand">Admin</div>
    <?php if ($admin): ?>
      <div class="topbar__right">
        <div class="who"><?php echo e($admin['username']); ?></div>
        <a class="btn small ghost" href="/admin/logout.php">Logout</a>
      </div>
    <?php endif; ?>
  </header>

  <div class="layout">
    <?php if ($admin): ?>
      <?php require __DIR__ . '/nav.php'; ?>
    <?php endif; ?>

    <main class="main">
      <?php if ($flash): ?>
        <div class="flash <?php echo e($flash['type']); ?>"><?php echo e($flash['msg']); ?></div>
      <?php endif; ?>
