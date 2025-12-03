// ================================================
// VIEW TOGGLE FUNCTIONALITY
// File: js/karyawan/view-toggle.js
// Switch between Card and Table view
// ================================================

/**
 * Initialize view toggle functionality
 */
function initializeViewToggle() {
  console.log("Initializing view toggle...");

  const toggleButtons = document.querySelectorAll(".toggle-btn");
  const cardView = document.getElementById("cardView");
  const tableView = document.getElementById("tableView");

  if (!cardView || !tableView) {
    console.error("View containers not found!");
    return;
  }

  if (toggleButtons.length === 0) {
    console.error("Toggle buttons not found!");
    return;
  }

  console.log(`Found ${toggleButtons.length} toggle buttons`);

  // Get saved view preference from localStorage (optional)
  const savedView = localStorage.getItem("employeeViewPreference") || "card";
  console.log("Saved view preference:", savedView);

  // Set initial view
  setActiveView(savedView, cardView, tableView, toggleButtons);

  // Add click handlers to toggle buttons
  toggleButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const viewType = this.getAttribute("data-view");
      console.log("Toggle clicked:", viewType);

      // Switch view
      switchView(viewType, cardView, tableView, toggleButtons);

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
 * @param {string} viewType - 'card' or 'table'
 * @param {HTMLElement} cardView
 * @param {HTMLElement} tableView
 * @param {NodeList} toggleButtons
 */
function switchView(viewType, cardView, tableView, toggleButtons) {
  console.log("Switching to view:", viewType);

  // Remove active class from all buttons
  toggleButtons.forEach((btn) => btn.classList.remove("active"));

  // Add active class to clicked button
  const activeButton = document.querySelector(
    `.toggle-btn[data-view="${viewType}"]`
  );
  if (activeButton) {
    activeButton.classList.add("active");
  }

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
 * @param {NodeList} toggleButtons
 */
function setActiveView(viewType, cardView, tableView, toggleButtons) {
  // Remove active from all
  toggleButtons.forEach((btn) => btn.classList.remove("active"));
  cardView.classList.remove("active");
  tableView.classList.remove("active");

  // Set active
  const activeButton = document.querySelector(
    `.toggle-btn[data-view="${viewType}"]`
  );
  if (activeButton) {
    activeButton.classList.add("active");
  }

  if (viewType === "card") {
    cardView.classList.add("active");
  } else {
    tableView.classList.add("active");
  }

  console.log("Initial view set to:", viewType);
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
  const toggleButtons = document.querySelectorAll(".toggle-btn");
  const cardView = document.getElementById("cardView");
  const tableView = document.getElementById("tableView");

  if (!cardView || !tableView) {
    console.error("View containers not found!");
    return;
  }

  switchView(viewType, cardView, tableView, toggleButtons);
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
 * Debug function to check view toggle status
 */
function debugViewToggle() {
  console.log("🔍 Debugging view toggle...");

  const toggleButtons = document.querySelectorAll(".toggle-btn");
  const cardView = document.getElementById("cardView");
  const tableView = document.getElementById("tableView");

  console.log("Toggle buttons:", toggleButtons.length);
  console.log("Card view:", cardView ? "✅ Found" : "❌ Missing");
  console.log("Table view:", tableView ? "✅ Found" : "❌ Missing");
  console.log("Current view:", getCurrentView());

  toggleButtons.forEach((btn, index) => {
    const viewType = btn.getAttribute("data-view");
    const isActive = btn.classList.contains("active");
    const computedStyle = window.getComputedStyle(btn);

    console.log(`Button ${index + 1} (${viewType}):`, {
      active: isActive ? "✅ Yes" : "❌ No",
      dataView: viewType,
      pointerEvents: computedStyle.pointerEvents,
      cursor: computedStyle.cursor,
      display: computedStyle.display,
    });
  });

  console.log("Card view classes:", cardView ? cardView.className : "N/A");
  console.log("Table view classes:", tableView ? tableView.className : "N/A");
}

// ================================================
// AUTO-INITIALIZE ON PAGE LOAD
// ================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("View Toggle: DOMContentLoaded");

  setTimeout(() => {
    initializeViewToggle();
    addViewKeyboardShortcuts();
    setupViewChangeListeners();

    console.log("✅ View toggle module loaded successfully");
  }, 50);
});

// Expose functions to global scope
window.initializeViewToggle = initializeViewToggle;
window.switchToView = switchToView;
window.getCurrentView = getCurrentView;
window.debugViewToggle = debugViewToggle;

console.log("✅ View toggle module loaded");
