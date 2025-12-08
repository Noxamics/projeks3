// ================================================
// VIEW TOGGLE FUNCTIONALITY
// File: js/karyawan/view-toggle.js
// Switch between Card and Table view
// Fixed: Mobile toggle buttons working properly
// ================================================

/**
 * Initialize view toggle functionality
 * Works for both desktop and mobile toggle buttons
 */
function initializeViewToggle() {
  console.log("Initializing view toggle...");

  // Get ALL toggle buttons (both desktop and mobile)
  const allToggleButtons = document.querySelectorAll(".toggle-btn");
  const cardView = document.getElementById("cardView");
  const tableView = document.getElementById("tableView");

  if (!cardView || !tableView) {
    console.error("View containers not found!");
    return;
  }

  if (allToggleButtons.length === 0) {
    console.error("Toggle buttons not found!");
    return;
  }

  console.log(
    `Found ${allToggleButtons.length} toggle buttons (desktop + mobile)`
  );

  // Get saved view preference from localStorage (optional)
  const savedView = localStorage.getItem("employeeViewPreference") || "card";
  console.log("Saved view preference:", savedView);

  // Set initial view
  setActiveView(savedView, cardView, tableView, allToggleButtons);

  // Add click handlers to ALL toggle buttons (desktop + mobile)
  allToggleButtons.forEach((button, index) => {
    console.log(`Setting up button ${index + 1}:`, {
      dataView: button.getAttribute("data-view"),
      parent:
        button.closest(".view-toggle")?.parentElement?.className || "unknown",
    });

    button.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const viewType = this.getAttribute("data-view");
      console.log(`Toggle clicked (Button ${index + 1}):`, viewType);

      // Switch view
      switchView(viewType, cardView, tableView, allToggleButtons);

      // Save preference
      localStorage.setItem("employeeViewPreference", viewType);
    });

    // Ensure button is always clickable
    button.style.pointerEvents = "auto";
    button.style.cursor = "pointer";
  });

  console.log("✅ View toggle initialized successfully");
}

/**
 * Switch between views
 * Updates ALL toggle buttons (desktop + mobile)
 * @param {string} viewType - 'card' or 'table'
 * @param {HTMLElement} cardView
 * @param {HTMLElement} tableView
 * @param {NodeList} allToggleButtons
 */
function switchView(viewType, cardView, tableView, allToggleButtons) {
  console.log("Switching to view:", viewType);

  // Remove active class from ALL buttons (desktop + mobile)
  allToggleButtons.forEach((btn) => {
    btn.classList.remove("active");
    console.log("Removed active from:", btn.getAttribute("data-view"));
  });

  // Add active class to ALL buttons with matching viewType
  const matchingButtons = document.querySelectorAll(
    `.toggle-btn[data-view="${viewType}"]`
  );

  matchingButtons.forEach((btn) => {
    btn.classList.add("active");
    console.log("Added active to:", btn.getAttribute("data-view"));
  });

  // Hide both views first
  cardView.classList.remove("active");
  tableView.classList.remove("active");

  // Show selected view with animation
  setTimeout(() => {
    if (viewType === "card") {
      cardView.classList.add("active");
      console.log("✅ Card view activated");
    } else if (viewType === "table") {
      tableView.classList.add("active");
      console.log("✅ Table view activated");
    }
  }, 50);

  // Trigger view change event (optional)
  const viewChangeEvent = new CustomEvent("viewChanged", {
    detail: { viewType: viewType },
  });
  document.dispatchEvent(viewChangeEvent);
}

/**
 * Set initial active view on page load
 * @param {string} viewType - 'card' or 'table'
 * @param {HTMLElement} cardView
 * @param {HTMLElement} tableView
 * @param {NodeList} allToggleButtons
 */
function setActiveView(viewType, cardView, tableView, allToggleButtons) {
  console.log("Setting initial view:", viewType);

  // Remove active from all buttons
  allToggleButtons.forEach((btn) => btn.classList.remove("active"));

  // Remove active from all views
  cardView.classList.remove("active");
  tableView.classList.remove("active");

  // Set active on ALL matching buttons (desktop + mobile)
  const matchingButtons = document.querySelectorAll(
    `.toggle-btn[data-view="${viewType}"]`
  );

  matchingButtons.forEach((btn) => {
    btn.classList.add("active");
  });

  // Set active view
  if (viewType === "card") {
    cardView.classList.add("active");
  } else {
    tableView.classList.add("active");
  }

  console.log(
    `Initial view set to: ${viewType} (${matchingButtons.length} buttons activated)`
  );
}

/**
 * Get current active view
 * @returns {string} 'card' or 'table'
 */
function getCurrentView() {
  const cardView = document.getElementById("cardView");
  const tableView = document.getElementById("tableView");

  if (cardView && cardView.classList.contains("active")) {
    return "card";
  } else if (tableView && tableView.classList.contains("active")) {
    return "table";
  }

  return "card"; // default
}

/**
 * Programmatically switch to a specific view
 * @param {string} viewType - 'card' or 'table'
 */
function switchToView(viewType) {
  const allToggleButtons = document.querySelectorAll(".toggle-btn");
  const cardView = document.getElementById("cardView");
  const tableView = document.getElementById("tableView");

  if (!cardView || !tableView) {
    console.error("View containers not found!");
    return;
  }

  switchView(viewType, cardView, tableView, allToggleButtons);
  localStorage.setItem("employeeViewPreference", viewType);
}

/**
 * Add keyboard shortcuts for view switching
 */
function addViewKeyboardShortcuts() {
  document.addEventListener("keydown", function (e) {
    // Ctrl+1 = Card view
    if (e.ctrlKey && e.key === "1") {
      e.preventDefault();
      switchToView("card");
      console.log("Keyboard shortcut: Switched to Card view");
    }

    // Ctrl+2 = Table view
    if (e.ctrlKey && e.key === "2") {
      e.preventDefault();
      switchToView("table");
      console.log("Keyboard shortcut: Switched to Table view");
    }
  });

  console.log("✅ View keyboard shortcuts added (Ctrl+1: Card, Ctrl+2: Table)");
}

/**
 * Listen for view change events (optional)
 */
function setupViewChangeListeners() {
  document.addEventListener("viewChanged", function (e) {
    console.log("View changed to:", e.detail.viewType);

    // You can trigger other actions here
    // For example: update URL, analytics, etc.

    // Reinitialize interactions if needed
    if (typeof reinitializeInteractions === "function") {
      setTimeout(() => {
        reinitializeInteractions();
      }, 100);
    }
  });
}

/**
 * Detect if we're on mobile and log info
 */
function detectDeviceType() {
  const isMobile = window.innerWidth <= 768;
  const desktopToggle = document.querySelector(".header-controls .view-toggle");
  const mobileToggle = document.querySelector(".filter-section .view-toggle");

  console.log("Device Detection:", {
    screenWidth: window.innerWidth,
    isMobile: isMobile,
    desktopToggleVisible: desktopToggle
      ? window.getComputedStyle(desktopToggle).display !== "none"
      : false,
    mobileToggleVisible: mobileToggle
      ? window.getComputedStyle(mobileToggle).display !== "none"
      : false,
  });
}

/**
 * Debug function to check view toggle status
 */
function debugViewToggle() {
  console.log("🔍 Debugging view toggle...");

  const allToggleButtons = document.querySelectorAll(".toggle-btn");
  const cardView = document.getElementById("cardView");
  const tableView = document.getElementById("tableView");
  const desktopToggle = document.querySelector(".header-controls .view-toggle");
  const mobileToggle = document.querySelector(".filter-section .view-toggle");

  console.log("Toggle buttons:", allToggleButtons.length);
  console.log("Card view:", cardView ? "✅ Found" : "❌ Missing");
  console.log("Table view:", tableView ? "✅ Found" : "❌ Missing");
  console.log("Current view:", getCurrentView());
  console.log(
    "Desktop toggle container:",
    desktopToggle ? "✅ Found" : "❌ Missing"
  );
  console.log(
    "Mobile toggle container:",
    mobileToggle ? "✅ Found" : "❌ Missing"
  );

  if (desktopToggle) {
    console.log(
      "Desktop toggle display:",
      window.getComputedStyle(desktopToggle).display
    );
  }

  if (mobileToggle) {
    console.log(
      "Mobile toggle display:",
      window.getComputedStyle(mobileToggle).display
    );
  }

  allToggleButtons.forEach((btn, index) => {
    const viewType = btn.getAttribute("data-view");
    const isActive = btn.classList.contains("active");
    const computedStyle = window.getComputedStyle(btn);
    const parentClass =
      btn.closest(".view-toggle")?.parentElement?.className || "unknown";

    console.log(`Button ${index + 1} (${viewType}) in ${parentClass}:`, {
      active: isActive ? "✅ Yes" : "❌ No",
      dataView: viewType,
      pointerEvents: computedStyle.pointerEvents,
      cursor: computedStyle.cursor,
      display: computedStyle.display,
      visibility: computedStyle.visibility,
    });
  });

  console.log("Card view classes:", cardView ? cardView.className : "N/A");
  console.log("Table view classes:", tableView ? tableView.className : "N/A");
}

/**
 * Handle window resize to detect device changes
 */
function handleResize() {
  detectDeviceType();

  // Reinitialize to ensure proper button binding
  setTimeout(() => {
    const allToggleButtons = document.querySelectorAll(".toggle-btn");
    const cardView = document.getElementById("cardView");
    const tableView = document.getElementById("tableView");
    const currentView = getCurrentView();

    if (cardView && tableView && allToggleButtons.length > 0) {
      setActiveView(currentView, cardView, tableView, allToggleButtons);
    }
  }, 100);
}

// ================================================
// AUTO-INITIALIZE ON PAGE LOAD
// ================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("View Toggle: DOMContentLoaded");

  setTimeout(() => {
    detectDeviceType();
    initializeViewToggle();
    addViewKeyboardShortcuts();
    setupViewChangeListeners();

    console.log("✅ View toggle module loaded successfully");
  }, 50);
});

// Handle window resize
let resizeTimer;
window.addEventListener("resize", function () {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(handleResize, 250);
});

// Expose functions to global scope
window.initializeViewToggle = initializeViewToggle;
window.switchToView = switchToView;
window.getCurrentView = getCurrentView;
window.debugViewToggle = debugViewToggle;
window.detectDeviceType = detectDeviceType;

console.log("✅ View toggle module loaded");
