// ================================================
// CARD & TABLE INTERACTIONS JAVASCRIPT
// File: js/karyawan/card-interactions.js
// ================================================

/**
 * Initialize card and table double-click interactions
 */
function initializeCardTableInteractions() {
  console.log("Initializing card and table interactions...");

  // Initialize card double-click
  initializeCardDoubleClick();

  // Initialize table row double-click
  initializeTableDoubleClick();

  console.log("✅ Card and table interactions initialized");
}

/**
 * Initialize double-click functionality for cards
 */
function initializeCardDoubleClick() {
  const cards = document.querySelectorAll(".employee-card");

  if (cards.length === 0) {
    console.warn("No employee cards found");
    return;
  }

  console.log(`Found ${cards.length} employee cards`);

  cards.forEach((card) => {
    let clickCount = 0;
    let clickTimer = null;

    card.addEventListener("click", function (e) {
      // Don't trigger if clicking on action buttons
      if (e.target.closest(".btn-card-mini")) {
        console.log("Button clicked, ignoring card click");
        return;
      }

      clickCount++;

      if (clickCount === 1) {
        // First click - create ripple effect
        createRippleEffect(e, this);

        // Reset counter after 300ms
        clickTimer = setTimeout(() => {
          clickCount = 0;
        }, 300);
      } else if (clickCount === 2) {
        // Second click (double-click) - open detail modal
        clearTimeout(clickTimer);
        clickCount = 0;

        console.log("Card double-clicked");
        openCardDetail(this);
      }
    });

    // Alternative: Native dblclick event (backup)
    card.addEventListener("dblclick", function (e) {
      if (e.target.closest(".btn-card-mini")) return;

      console.log("Native double-click detected");
      openCardDetail(this);
    });
  });
}

/**
 * Initialize double-click functionality for table rows
 */
function initializeTableDoubleClick() {
  const tableRows = document.querySelectorAll(".table-row-interactive");

  if (tableRows.length === 0) {
    console.warn("No table rows found");
    return;
  }

  console.log(`Found ${tableRows.length} table rows`);

  tableRows.forEach((row) => {
    let clickCount = 0;
    let clickTimer = null;

    row.addEventListener("click", function (e) {
      // Don't trigger if clicking on action buttons
      if (e.target.closest(".btn-table")) {
        console.log("Button clicked, ignoring row click");
        return;
      }

      clickCount++;

      if (clickCount === 1) {
        // First click - add highlight
        this.style.background = "rgba(0, 102, 204, 0.08)";

        // Reset after 300ms
        clickTimer = setTimeout(() => {
          this.style.background = "";
          clickCount = 0;
        }, 300);
      } else if (clickCount === 2) {
        // Second click (double-click) - open detail modal
        clearTimeout(clickTimer);
        clickCount = 0;
        this.style.background = "";

        console.log("Row double-clicked");
        openRowDetail(this);
      }
    });

    // Alternative: Native dblclick event (backup)
    row.addEventListener("dblclick", function (e) {
      if (e.target.closest(".btn-table")) return;

      console.log("Native double-click detected on row");
      this.style.background = "";
      openRowDetail(this);
    });
  });
}

/**
 * Open detail modal from card
 * @param {HTMLElement} cardElement - The card element
 */
function openCardDetail(cardElement) {
  const employeeData = cardElement.getAttribute("data-employee");

  if (!employeeData) {
    console.error("No employee data found on card");
    return;
  }

  try {
    const employee = JSON.parse(employeeData);
    console.log("Opening detail for employee:", employee.name);

    // Check if openDetailModal function exists
    if (typeof openDetailModal === "function") {
      openDetailModal(employee);
    } else {
      console.error("openDetailModal function not found");
      alert("Error: Modal function not available");
    }
  } catch (error) {
    console.error("Failed to parse employee data:", error);
    alert("Error: Invalid employee data");
  }
}

/**
 * Open detail modal from table row
 * @param {HTMLElement} rowElement - The table row element
 */
function openRowDetail(rowElement) {
  const employeeData = rowElement.getAttribute("data-employee");

  if (!employeeData) {
    console.error("No employee data found on row");
    return;
  }

  try {
    const employee = JSON.parse(employeeData);
    console.log("Opening detail for employee:", employee.name);

    // Check if openDetailModal function exists
    if (typeof openDetailModal === "function") {
      openDetailModal(employee);
    } else {
      console.error("openDetailModal function not found");
      alert("Error: Modal function not available");
    }
  } catch (error) {
    console.error("Failed to parse employee data:", error);
    alert("Error: Invalid employee data");
  }
}

/**
 * Create ripple effect on card click
 * @param {Event} event - Click event
 * @param {HTMLElement} element - Card element
 */
function createRippleEffect(event, element) {
  const ripple = document.createElement("div");
  const wrapper = element.querySelector(".card-image-wrapper");

  if (!wrapper) {
    console.warn("Card image wrapper not found");
    return;
  }

  const rect = wrapper.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  const x = event.clientX - rect.left - size / 2;
  const y = event.clientY - rect.top - size / 2;

  ripple.className = "ripple-effect";
  ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        left: ${x}px;
        top: ${y}px;
        pointer-events: none;
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        z-index: 5;
    `;

  wrapper.appendChild(ripple);

  // Remove ripple after animation
  setTimeout(() => {
    if (ripple.parentNode) {
      ripple.parentNode.removeChild(ripple);
    }
  }, 600);
}

/**
 * Add keyboard support for accessibility
 */
function addKeyboardSupport() {
  // Cards keyboard support
  document.querySelectorAll(".employee-card").forEach((card) => {
    card.setAttribute("tabindex", "0");

    card.addEventListener("keydown", function (e) {
      // Enter key opens detail
      if (e.key === "Enter") {
        e.preventDefault();
        openCardDetail(this);
      }
    });
  });

  // Table rows keyboard support
  document.querySelectorAll(".table-row-interactive").forEach((row) => {
    row.setAttribute("tabindex", "0");

    row.addEventListener("keydown", function (e) {
      // Enter key opens detail
      if (e.key === "Enter") {
        e.preventDefault();
        openRowDetail(this);
      }
    });
  });
}

/**
 * Handle mobile touch events
 */
function addMobileTouchSupport() {
  // Detect if device is mobile
  const isMobile =
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    );

  if (!isMobile) {
    console.log("Desktop device detected, skipping mobile touch support");
    return;
  }

  console.log("Mobile device detected, adding touch support");

  // For mobile, use tap instead of double-click for better UX
  // But keep double-click functionality

  // Optional: Add long-press for detail on mobile
  let pressTimer;

  document.querySelectorAll(".employee-card").forEach((card) => {
    card.addEventListener("touchstart", function () {
      pressTimer = setTimeout(() => {
        console.log("Long press detected on card");
        openCardDetail(this);
      }, 500);
    });

    card.addEventListener("touchend", function () {
      clearTimeout(pressTimer);
    });

    card.addEventListener("touchmove", function () {
      clearTimeout(pressTimer);
    });
  });
}

/**
 * Reinitialize interactions (for dynamic content)
 */
function reinitializeInteractions() {
  console.log("Reinitializing interactions...");
  initializeCardTableInteractions();
  addKeyboardSupport();
  addMobileTouchSupport();
}

// ================================================
// AUTO-INITIALIZE ON PAGE LOAD
// ================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("Card/Table Interactions: DOMContentLoaded");

  // Wait a bit for other scripts to load
  setTimeout(() => {
    initializeCardTableInteractions();
    addKeyboardSupport();
    addMobileTouchSupport();
  }, 100);
});

// Expose functions to global scope
window.initializeCardTableInteractions = initializeCardTableInteractions;
window.reinitializeInteractions = reinitializeInteractions;
window.createRippleEffect = createRippleEffect;

console.log("✅ Card/Table interactions module loaded");
