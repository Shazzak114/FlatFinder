/* global L */
(function () {
  "use strict";

  var API = {
    listings: "/api/get_listings.php",
    details: "/api/get_listing_details.php"
  };

  var state = {
    map: null,
    clusterLayer: null,
    emojiLayer: null,
    lastListingsKey: "",
    listings: [],
    selected: null,
    lang: "en",
    theme: "light",
    drawerOpen: false
  };

  var i18n = {
    en: {
      brand: "To-Let Map",
      compare: "Compare",
      wishlist: "Wishlist",
      add_to_let: "Add To Let",
      account: "Account",
      filters: "Filters",
      category: "Category",
      cat_small_family: "Small family / Sublet",
      cat_big_family: "Big family",
      cat_girls: "Girls / Jobholder women",
      cat_boys: "Boys / Jobholder men",
      budget: "Budget",
      apply: "Apply",
      search: "Search",
      hint_zoom: "Zoom in (16+) to see emoji markers.",
      hint_tap: "Tap a marker to open the listing sheet.",
      view_details: "View details",
      close: "Close",
      back: "Back",
      details: "Details",
      loading: "Loading…",
      failed: "Could not load listings."
    },
    bn: {
      brand: "বাসা ভাড়া ম্যাপ",
      compare: "তুলনা",
      wishlist: "উইশলিস্ট",
      add_to_let: "ভাড়া দিতে দিন",
      account: "অ্যাকাউন্ট",
      filters: "ফিল্টার",
      category: "ক্যাটাগরি",
      cat_small_family: "ছোট পরিবার / সাবলেট",
      cat_big_family: "বড় পরিবার",
      cat_girls: "মেয়েদের / চাকুরিজীবী নারী",
      cat_boys: "ছেলেদের / চাকুরিজীবী পুরুষ",
      budget: "বাজেট",
      apply: "প্রয়োগ",
      search: "সার্চ",
      hint_zoom: "ইমোজি মার্কার দেখতে ১৬+ জুম করুন।",
      hint_tap: "মার্কার ট্যাপ করলে লিস্টিং শীট খুলবে।",
      view_details: "ডিটেইলস",
      close: "বন্ধ",
      back: "ফিরে যান",
      details: "ডিটেইলস",
      loading: "লোড হচ্ছে…",
      failed: "লিস্টিং লোড করা যায়নি।"
    }
  };

  function $(id) {
    return document.getElementById(id);
  }

  function setPref(key, value) {
    try {
      localStorage.setItem(key, value);
    } catch (e) {
      // ignore
    }
    document.cookie = encodeURIComponent(key) + "=" + encodeURIComponent(value) + "; path=/; max-age=31536000";
  }

  function readCookie(key) {
    var prefix = encodeURIComponent(key) + "=";
    var parts = String(document.cookie || "").split(";");
    for (var i = 0; i < parts.length; i += 1) {
      var p = parts[i].trim();
      if (p.indexOf(prefix) === 0) {
        return decodeURIComponent(p.slice(prefix.length));
      }
    }
    return null;
  }

  function getPref(key, fallback) {
    var v = null;
    try {
      v = localStorage.getItem(key);
    } catch (e) {
      v = null;
    }

    if (!v) v = readCookie(key);
    return v || fallback;
  }

  function applyI18n(lang) {
    state.lang = lang;
    document.documentElement.lang = lang;

    var dict = i18n[lang] || i18n.en;
    document.querySelectorAll("[data-i18n]").forEach(function (el) {
      var k = el.getAttribute("data-i18n");
      if (dict[k]) el.textContent = dict[k];
    });

    var langLabel = $("langLabel");
    if (langLabel) langLabel.textContent = lang === "en" ? "BN" : "EN";
  }

  function applyTheme(theme) {
    state.theme = theme;
    document.body.classList.toggle("dark", theme === "dark");

    var themeLabel = $("themeLabel");
    if (themeLabel) themeLabel.textContent = theme === "dark" ? "Light" : "Dark";
  }

  function showToast(msg) {
    var toast = $("toast");
    if (!toast) return;
    toast.textContent = msg;
    toast.hidden = false;
    window.clearTimeout(showToast._t);
    showToast._t = window.setTimeout(function () {
      toast.hidden = true;
    }, 2600);
  }

  function debounce(fn, wait) {
    var t = null;
    return function () {
      var args = arguments;
      window.clearTimeout(t);
      t = window.setTimeout(function () {
        fn.apply(null, args);
      }, wait);
    };
  }

  function getSelectedCategories() {
    var cats = [];
    document.querySelectorAll("input[name='cat']:checked").forEach(function (el) {
      cats.push(el.value);
    });
    return cats;
  }

  function getFilters() {
    var minPrice = $("minPrice");
    var maxPrice = $("maxPrice");
    var searchText = $("searchText");

    return {
      categories: getSelectedCategories(),
      minPrice: minPrice && minPrice.value ? Number(minPrice.value) : null,
      maxPrice: maxPrice && maxPrice.value ? Number(maxPrice.value) : null,
      q: searchText && searchText.value ? String(searchText.value).trim() : ""
    };
  }

  function normalizeText(v) {
    return String(v || "").toLowerCase();
  }

  function getEmojiForListing(listing) {
    var t = normalizeText(listing.category || listing.type || listing.room_type || listing.listing_type || listing.for || "");

    if (t.includes("big") && t.includes("family")) return "👨‍👩‍👧‍👧";
    if (t.includes("small") && t.includes("family")) return "👫";
    if (t.includes("sublet") || t.includes("one room")) return "👫";

    if (t.includes("girl") || t.includes("women") || t.includes("female")) return "🙋🏻‍♀️";
    if (t.includes("boy") || t.includes("men") || t.includes("male") || t.includes("jobholder man")) return "👨‍✈️";

    // When API already provides a category key.
    if (listing.category_key === "big_family") return "👨‍👩‍👧‍👧";
    if (listing.category_key === "small_family") return "👫";
    if (listing.category_key === "girls") return "🙋🏻‍♀️";
    if (listing.category_key === "boys") return "👨‍✈️";

    return "👫";
  }

  function emojiIcon(emoji) {
    return L.divIcon({
      className: "",
      html: '<div class="emoji-marker" aria-hidden="true">' + emoji + "</div>",
      iconSize: [30, 30],
      iconAnchor: [15, 15]
    });
  }

  function pinIcon() {
    return undefined; // Leaflet default icon
  }

  function listingSummaryText(listing) {
    var parts = [];
    if (listing.price) parts.push("৳" + listing.price);
    if (listing.area) parts.push(listing.area);
    if (listing.address) parts.push(listing.address);
    return parts.filter(Boolean).join(" • ") || "—";
  }

  function openSheet(listing) {
    state.selected = listing;

    var sheet = $("bottomSheet");
    if (!sheet) return;

    $("sheetTitle").textContent = listing.title || listing.name || "Listing";
    $("sheetMeta").textContent = listingSummaryText(listing);
    $("sheetDesc").textContent = listing.short_description || listing.description_short || listing.note || "";

    sheet.classList.add("is-open");
    sheet.setAttribute("aria-hidden", "false");
  }

  function closeSheet() {
    var sheet = $("bottomSheet");
    if (!sheet) return;

    sheet.classList.remove("is-open");
    sheet.setAttribute("aria-hidden", "true");
    state.selected = null;
  }

  function setDrawer(open) {
    state.drawerOpen = open;

    var sidebar = $("sidebar");
    var backdrop = $("backdrop");
    if (!sidebar || !backdrop) return;

    sidebar.classList.toggle("is-open", open);
    backdrop.hidden = !open;
  }

  function encodeQuery(params) {
    var usp = new URLSearchParams();
    Object.keys(params).forEach(function (k) {
      var v = params[k];
      if (v === null || v === undefined) return;
      if (Array.isArray(v)) {
        v.forEach(function (x) {
          usp.append(k + "[]", x);
        });
        return;
      }
      if (v === "") return;
      usp.set(k, v);
    });
    return usp.toString();
  }

  function buildListingsKey(bounds, filters) {
    var b = bounds;
    return [
      b.getSouth().toFixed(4),
      b.getWest().toFixed(4),
      b.getNorth().toFixed(4),
      b.getEast().toFixed(4),
      String(filters.categories || []).trim(),
      String(filters.minPrice || ""),
      String(filters.maxPrice || ""),
      filters.q
    ].join("|");
  }

  function fetchListings() {
    if (!state.map) return;

    var bounds = state.map.getBounds();
    var filters = getFilters();

    var key = buildListingsKey(bounds, filters);
    if (key === state.lastListingsKey) return;
    state.lastListingsKey = key;

    var south = bounds.getSouth();
    var west = bounds.getWest();
    var north = bounds.getNorth();
    var east = bounds.getEast();

    var params = {
      // Common patterns: either bbox=south,west,north,east or separate bound params.
      bbox: [south, west, north, east].join(","),
      south: south,
      west: west,
      north: north,
      east: east,

      zoom: state.map.getZoom(),
      q: filters.q,
      min_price: filters.minPrice,
      max_price: filters.maxPrice,
      category: filters.categories,
      categories: filters.categories
    };

    var url = API.listings + "?" + encodeQuery(params);

    fetch(url, { credentials: "same-origin" })
      .then(function (r) {
        if (!r.ok) throw new Error("HTTP " + r.status);
        return r.json();
      })
      .then(function (data) {
        var items = Array.isArray(data) ? data : data.listings;
        if (!Array.isArray(items)) items = [];
        state.listings = items;
        renderMarkers();
      })
      .catch(function () {
        var dict = i18n[state.lang] || i18n.en;
        showToast(dict.failed);
      });
  }

  function clearLayers() {
    if (state.clusterLayer) state.clusterLayer.clearLayers();
    if (state.emojiLayer) state.emojiLayer.clearLayers();
  }

  function renderMarkers() {
    if (!state.map) return;

    clearLayers();

    state.listings.forEach(function (listing) {
      var lat = Number(listing.lat || listing.latitude);
      var lng = Number(listing.lng || listing.lon || listing.longitude);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

      var title = listing.title || listing.name || "Listing";

      var pin = L.marker([lat, lng], { title: title, icon: pinIcon() });
      pin.on("click", function () {
        openSheet(listing);
      });
      state.clusterLayer.addLayer(pin);

      var emoji = getEmojiForListing(listing);
      var em = L.marker([lat, lng], { title: title, icon: emojiIcon(emoji), keyboard: false });
      em.on("click", function () {
        openSheet(listing);
      });
      state.emojiLayer.addLayer(em);
    });

    syncMarkerMode();
  }

  function syncMarkerMode() {
    if (!state.map) return;
    var z = state.map.getZoom();
    var useEmoji = z >= 16;

    if (useEmoji) {
      if (state.map.hasLayer(state.clusterLayer)) state.map.removeLayer(state.clusterLayer);
      if (!state.map.hasLayer(state.emojiLayer)) state.map.addLayer(state.emojiLayer);
    } else {
      if (state.map.hasLayer(state.emojiLayer)) state.map.removeLayer(state.emojiLayer);
      if (!state.map.hasLayer(state.clusterLayer)) state.map.addLayer(state.clusterLayer);
    }
  }

  function initMap() {
    var mapEl = $("map");
    if (!mapEl) return;

    state.map = L.map(mapEl, {
      zoomControl: false,
      preferCanvas: true
    });

    L.control.zoom({ position: "bottomright" }).addTo(state.map);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 20,
      attribution: "&copy; OpenStreetMap contributors"
    }).addTo(state.map);

    state.clusterLayer = L.markerClusterGroup({
      showCoverageOnHover: false,
      spiderfyOnMaxZoom: true,
      disableClusteringAtZoom: 16
    });

    state.emojiLayer = L.layerGroup();

    state.map.setView([23.8103, 90.4125], 13); // Default: Dhaka

    state.map.on("zoomend", function () {
      syncMarkerMode();
    });

    state.map.on(
      "moveend",
      debounce(function () {
        fetchListings();
      }, 250)
    );

    // Initial load
    fetchListings();
  }

  function initToggles() {
    var themeToggle = $("themeToggle");
    var langToggle = $("langToggle");

    state.theme = getPref("ui_theme", "light");
    state.lang = getPref("ui_lang", "en");
    applyTheme(state.theme);
    applyI18n(state.lang);

    if (themeToggle) {
      themeToggle.addEventListener("click", function () {
        var next = state.theme === "dark" ? "light" : "dark";
        setPref("ui_theme", next);
        applyTheme(next);
      });
    }

    if (langToggle) {
      langToggle.addEventListener("click", function () {
        var next = state.lang === "en" ? "bn" : "en";
        setPref("ui_lang", next);
        applyI18n(next);
      });
    }
  }

  function initDrawer() {
    var openBtn = $("filtersToggle");
    var closeBtn = $("filtersClose");
    var backdrop = $("backdrop");

    if (openBtn) {
      openBtn.addEventListener("click", function () {
        setDrawer(true);
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        setDrawer(false);
      });
    }

    if (backdrop) {
      backdrop.addEventListener("click", function () {
        setDrawer(false);
      });
    }
  }

  function initFilters() {
    var applyBtn = $("applyFilters");
    if (applyBtn) {
      applyBtn.addEventListener("click", function () {
        state.lastListingsKey = "";
        fetchListings();
        setDrawer(false);
      });
    }

    document.querySelectorAll("input[name='cat']").forEach(function (el) {
      el.addEventListener("change", function () {
        state.lastListingsKey = "";
        fetchListings();
      });
    });

    var search = $("searchText");
    if (search) {
      search.addEventListener(
        "input",
        debounce(function () {
          state.lastListingsKey = "";
          fetchListings();
        }, 350)
      );
    }
  }

  function initSheet() {
    var handle = $("sheetHandle");
    var closeBtn = $("sheetCloseBtn");
    var detailsBtn = $("sheetDetailsBtn");

    if (handle) handle.addEventListener("click", closeSheet);
    if (closeBtn) closeBtn.addEventListener("click", closeSheet);

    if (detailsBtn) {
      detailsBtn.addEventListener("click", function () {
        if (!state.selected) return;
        var id = state.selected.id || state.selected.listing_id;
        if (!id) return;
        window.location.href = "/listing.php?id=" + encodeURIComponent(id);
      });
    }

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeSheet();
    });
  }

  function renderListingDetailsPage(id) {
    var titleEl = $("detailsTitle");
    var metaEl = $("detailsMeta");
    var bodyEl = $("detailsBody");

    var dict = i18n[state.lang] || i18n.en;

    if (!id) {
      if (bodyEl) bodyEl.textContent = "Missing listing id.";
      return;
    }

    if (bodyEl) bodyEl.textContent = dict.loading;

    fetch(API.details + "?" + encodeQuery({ id: id }), { credentials: "same-origin" })
      .then(function (r) {
        if (!r.ok) throw new Error("HTTP " + r.status);
        return r.json();
      })
      .then(function (data) {
        var d = data && data.listing ? data.listing : data;
        if (!d || typeof d !== "object") d = {};

        if (titleEl) titleEl.textContent = d.title || d.name || "Listing";
        if (metaEl) metaEl.textContent = listingSummaryText(d);

        var html = "";
        var fields = [
          ["Description", d.description || d.details || d.note],
          ["Contact", d.contact || d.phone],
          ["Address", d.address],
          ["Area", d.area],
          ["Price", d.price ? "৳" + d.price : ""]
        ];

        fields.forEach(function (pair) {
          var label = pair[0];
          var val = pair[1];
          if (!val) return;
          html += '<div style="margin-bottom:12px">' +
            '<div style="font-weight:800;opacity:.86">' +
            label +
            "</div>" +
            '<div style="opacity:.92">' +
            String(val) +
            "</div>" +
            "</div>";
        });

        if (!html) html = "No details available.";
        if (bodyEl) bodyEl.innerHTML = html;
      })
      .catch(function () {
        if (bodyEl) bodyEl.textContent = "Could not load details.";
      });
  }

  function initActionHooks() {
    document.querySelectorAll("[data-action]").forEach(function (a) {
      a.addEventListener("click", function (e) {
        e.preventDefault();
        showToast("Hook: " + a.getAttribute("data-action"));
      });
    });
  }

  function boot() {
    initToggles();

    var page = window.__PAGE__ || "map";
    if (page === "listing") {
      renderListingDetailsPage(window.__LISTING_ID__);
      return;
    }

    initDrawer();
    initFilters();
    initSheet();
    initActionHooks();

    // Leaflet scripts are deferred; wait until L exists.
    var tries = 0;
    (function waitForLeaflet() {
      tries += 1;
      if (typeof L !== "undefined") {
        initMap();
        return;
      }
      if (tries > 60) {
        showToast("Leaflet failed to load.");
        return;
      }
      window.setTimeout(waitForLeaflet, 50);
    })();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
