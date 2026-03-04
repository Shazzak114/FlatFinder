<?php
// Public map UI (mobile-first). No admin functionality here.
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Map • To-Let Listings</title>

  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
  />
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
  />
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
  />
  <link rel="stylesheet" href="/assets/css/styles.css" />

  <script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
    defer
  ></script>
  <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" defer></script>
  <script src="/assets/js/app.js" defer></script>
</head>
<body>
  <header class="topbar">
    <button class="icon-btn" id="filtersToggle" type="button" aria-label="Filters">
      <span class="icon">☰</span>
    </button>

    <div class="brand" data-i18n="brand">To-Let Map</div>

    <nav class="actions" aria-label="Primary">
      <a class="pill" href="#" data-action="compare" data-i18n="compare">Compare</a>
      <a class="pill" href="#" data-action="wishlist" data-i18n="wishlist">Wishlist</a>
      <a class="pill accent" href="#" data-action="addToLet" data-i18n="add_to_let">Add To Let</a>
      <a class="pill" href="#" data-action="account" data-i18n="account">Account</a>

      <button class="pill ghost" id="langToggle" type="button" aria-label="Language">
        <span id="langLabel">BN</span>
      </button>
      <button class="pill ghost" id="themeToggle" type="button" aria-label="Theme">
        <span id="themeLabel">Dark</span>
      </button>
    </nav>
  </header>

  <aside class="sidebar" id="sidebar" aria-label="Filters">
    <div class="sidebar__header">
      <div class="sidebar__title" data-i18n="filters">Filters</div>
      <button class="icon-btn" id="filtersClose" type="button" aria-label="Close filters">
        <span class="icon">✕</span>
      </button>
    </div>

    <div class="sidebar__content">
      <section class="card">
        <div class="card__title" data-i18n="category">Category</div>
        <label class="check">
          <input type="checkbox" name="cat" value="small_family" checked />
          <span>👫 <span data-i18n="cat_small_family">Small family / Sublet</span></span>
        </label>
        <label class="check">
          <input type="checkbox" name="cat" value="big_family" checked />
          <span>👨‍👩‍👧‍👧 <span data-i18n="cat_big_family">Big family</span></span>
        </label>
        <label class="check">
          <input type="checkbox" name="cat" value="girls" checked />
          <span>🙋🏻‍♀️ <span data-i18n="cat_girls">Girls / Jobholder women</span></span>
        </label>
        <label class="check">
          <input type="checkbox" name="cat" value="boys" checked />
          <span>👨‍✈️ <span data-i18n="cat_boys">Boys / Jobholder men</span></span>
        </label>
      </section>

      <section class="card">
        <div class="card__title" data-i18n="budget">Budget</div>
        <div class="row">
          <input id="minPrice" class="input" type="number" inputmode="numeric" placeholder="Min" />
          <input id="maxPrice" class="input" type="number" inputmode="numeric" placeholder="Max" />
        </div>
        <button id="applyFilters" class="btn" type="button" data-i18n="apply">Apply</button>
      </section>

      <section class="card">
        <div class="card__title" data-i18n="search">Search</div>
        <input id="searchText" class="input" type="search" placeholder="Area / Road / Landmark" />
      </section>

      <section class="card hint">
        <div class="hint__line">
          <span class="dot"></span>
          <span data-i18n="hint_zoom">Zoom in (16+) to see emoji markers.</span>
        </div>
        <div class="hint__line">
          <span class="dot"></span>
          <span data-i18n="hint_tap">Tap a marker to open the listing sheet.</span>
        </div>
      </section>
    </div>
  </aside>

  <div class="backdrop" id="backdrop" hidden></div>

  <main class="main">
    <div id="map" class="map" role="application" aria-label="Listings map"></div>

    <div class="toast" id="toast" hidden></div>

    <section class="sheet" id="bottomSheet" aria-label="Listing" aria-hidden="true">
      <div class="sheet__handle" id="sheetHandle" role="button" aria-label="Close">
        <div class="sheet__bar"></div>
      </div>
      <div class="sheet__content">
        <div class="sheet__title" id="sheetTitle">—</div>
        <div class="sheet__meta" id="sheetMeta">—</div>
        <div class="sheet__desc" id="sheetDesc">—</div>
        <div class="sheet__actions">
          <button class="btn" id="sheetDetailsBtn" type="button" data-i18n="view_details">View details</button>
          <button class="btn ghost" id="sheetCloseBtn" type="button" data-i18n="close">Close</button>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
