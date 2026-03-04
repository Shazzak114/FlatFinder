<?php
// Public listing details page.
$id = isset($_GET['id']) ? $_GET['id'] : '';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Listing Details</title>

  <link rel="stylesheet" href="/assets/css/styles.css" />
  <script src="/assets/js/app.js" defer></script>
</head>
<body>
  <header class="topbar">
    <a class="pill" href="/index.php" data-i18n="back">Back</a>
    <div class="brand" data-i18n="details">Details</div>

    <nav class="actions" aria-label="Primary">
      <button class="pill ghost" id="langToggle" type="button" aria-label="Language">
        <span id="langLabel">BN</span>
      </button>
      <button class="pill ghost" id="themeToggle" type="button" aria-label="Theme">
        <span id="themeLabel">Dark</span>
      </button>
    </nav>
  </header>

  <main class="details">
    <div class="details__card">
      <div class="details__title" id="detailsTitle">—</div>
      <div class="details__meta" id="detailsMeta">—</div>
      <div class="details__body" id="detailsBody">Loading…</div>
    </div>
  </main>

  <script>
    window.__LISTING_ID__ = <?php echo json_encode($id); ?>;
    window.__PAGE__ = "listing";
  </script>
</body>
</html>
