/* =========================================================
   JAVASCRIPT FIX - Badge "DIAMBIL" Tidak Terpotong
   Tambahkan script ini ke halaman Anda
   ========================================================= */

/**
 * Fix Badge Positioning - Pastikan badge tidak terpotong
 */
function fixBadgePositioning() {
  // Cari semua badge yang terpotong
  const badges = document.querySelectorAll(".completed-badge-overlay");

  badges.forEach((badge) => {
    const parent = badge.closest(".order-item-row");
    if (!parent) return;

    // Pastikan parent memiliki overflow visible
    parent.style.overflow = "visible";
    parent.style.position = "relative";

    // Cek apakah badge terpotong
    const badgeRect = badge.getBoundingClientRect();
    const parentRect = parent.getBoundingClientRect();

    // Jika badge keluar dari parent (terpotong di kanan)
    if (badgeRect.right > parentRect.right) {
      // Adjust position
      badge.style.right = "auto";
      badge.style.left = `${parentRect.width - badgeRect.width - 8}px`;
    }

    // Jika badge keluar dari parent (terpotong di atas)
    if (badgeRect.top < parentRect.top) {
      badge.style.top = "8px";
    }
  });
}

/**
 * Ensure All Parent Containers Have Overflow Visible
 */
function ensureVisibleOverflow() {
  const containers = [
    ".table-container",
    ".customer-orders-container",
    ".order-item-row",
    ".order-item-grid",
  ];

  containers.forEach((selector) => {
    const elements = document.querySelectorAll(selector);
    elements.forEach((el) => {
      // Set overflow visible untuk vertical
      if (selector === ".customer-orders-container") {
        el.style.overflowX = "auto";
        el.style.overflowY = "visible";
      } else {
        el.style.overflow = "visible";
      }

      // Remove CSS containment
      el.style.contain = "none";
    });
  });
}

/**
 * Observer untuk detect badge baru
 */
function setupBadgeObserver() {
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === 1) {
          // Element node
          if (
            node.classList &&
            node.classList.contains("completed-badge-overlay")
          ) {
            fixBadgePositioning();
          }
          // Check children
          const badges =
            node.querySelectorAll &&
            node.querySelectorAll(".completed-badge-overlay");
          if (badges && badges.length > 0) {
            fixBadgePositioning();
          }
        }
      });
    });
  });

  // Observe document body untuk changes
  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });

  return observer;
}

/**
 * Force Redraw - Untuk browser yang cache positioning
 */
function forceRedraw() {
  const badges = document.querySelectorAll(".completed-badge-overlay");
  badges.forEach((badge) => {
    badge.style.display = "none";
    badge.offsetHeight; // Trigger reflow
    badge.style.display = "flex";
  });
}

/**
 * Initialize Fixes
 */
function initBadgeFixes() {
  // Wait for DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      ensureVisibleOverflow();
      fixBadgePositioning();
      setupBadgeObserver();
    });
  } else {
    ensureVisibleOverflow();
    fixBadgePositioning();
    setupBadgeObserver();
  }

  // Fix on window resize
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      fixBadgePositioning();
      forceRedraw();
    }, 250);
  });

  // Fix on scroll (for mobile)
  let scrollTimer;
  window.addEventListener(
    "scroll",
    () => {
      clearTimeout(scrollTimer);
      scrollTimer = setTimeout(() => {
        fixBadgePositioning();
      }, 100);
    },
    { passive: true }
  );

  // Fix saat orientation change (mobile)
  window.addEventListener("orientationchange", () => {
    setTimeout(() => {
      ensureVisibleOverflow();
      fixBadgePositioning();
      forceRedraw();
    }, 300);
  });
}

/**
 * Manual Fix Function - Call ini jika badge masih terpotong
 */
function fixBadgeManually() {
  console.log("🔧 Fixing badge positioning manually...");
  ensureVisibleOverflow();
  fixBadgePositioning();
  forceRedraw();
  console.log("✅ Badge fix complete!");
}

// Auto-initialize
initBadgeFixes();

// Expose manual fix ke global scope
window.fixBadgeManually = fixBadgeManually;

// Debug: Log jika badge terpotong
if (window.location.search.includes("debug")) {
  setTimeout(() => {
    const badges = document.querySelectorAll(".completed-badge-overlay");
    console.log(`Found ${badges.length} badges`);
    badges.forEach((badge, i) => {
      const rect = badge.getBoundingClientRect();
      const parent = badge.closest(".order-item-row");
      const parentRect = parent ? parent.getBoundingClientRect() : null;

      console.log(`Badge ${i + 1}:`, {
        badge: rect,
        parent: parentRect,
        clipped:
          parentRect &&
          (rect.right > parentRect.right ||
            rect.top < parentRect.top ||
            rect.bottom > parentRect.bottom),
      });
    });
  }, 1000);
}

/* =========================================================
   ALTERNATIVE SOLUTION - CSS in JS
   Jika CSS file tidak ter-load dengan benar
   ========================================================= */
function injectCriticalCSS() {
  const style = document.createElement("style");
  style.textContent = `
    .table-container { overflow: visible !important; }
    .customer-orders-container { 
      overflow-x: auto !important; 
      overflow-y: visible !important; 
    }
    .order-item-row { overflow: visible !important; }
    .order-item-grid { overflow: visible !important; }
    .completed-badge-overlay { z-index: 9999 !important; }
  `;
  document.head.appendChild(style);
}

// Inject CSS as backup
injectCriticalCSS();
