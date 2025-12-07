// ================================================
// ULTIMATE FIX - EVENT DELEGATION PATTERN
// File: js/karyawan/card-interactions.js
// ================================================

/**
 * Initialize card and table interactions
 */
function initializeCardTableInteractions() {
  console.log("🔄 Initializing card and table interactions...");

  initializeCardDoubleClick();
  initializeTableDoubleClick();

  // CRITICAL: Use event delegation instead of direct binding
  initializeTableButtonsDelegation();

  console.log("✅ Card and table interactions initialized");
}

/**
 * ULTIMATE FIX: Use event delegation on table container
 * This ensures buttons work even if DOM changes
 */
function initializeTableButtonsDelegation() {
  const tableWrapper = document.querySelector(".table-wrapper");
  const tableView = document.getElementById("tableView");

  if (!tableWrapper && !tableView) {
    console.warn("⚠️ Table container not found");
    return;
  }

  const container = tableWrapper || tableView;

  // Remove any existing listeners
  const newContainer = container.cloneNode(true);
  container.parentNode.replaceChild(newContainer, container);

  console.log("🎯 Setting up event delegation on table container");

  // Single event listener on parent handles all button clicks
  newContainer.addEventListener(
    "click",
    function (e) {
      // Find if clicked element or parent is a button
      const button = e.target.closest(".btn-table");

      if (!button) return;

      // Stop propagation immediately
      e.stopPropagation();
      e.stopImmediatePropagation();
      e.preventDefault();

      console.log("🔘 Button clicked via delegation:", button.className);

      // Get employee data from parent row
      const row = button.closest("tr");
      if (!row) {
        console.error("❌ No parent row found");
        return;
      }

      const employeeData = row.getAttribute("data-employee");
      if (!employeeData) {
        console.error("❌ No employee data on row");
        alert("Error: Data karyawan tidak ditemukan");
        return;
      }

      try {
        const employee = JSON.parse(employeeData);
        console.log("✅ Employee parsed:", employee.name);

        // Handle based on button type
        if (button.classList.contains("btn-edit")) {
          console.log("🔧 Calling openEditModal");
          handleEditClick(employee);
        } else if (button.classList.contains("btn-delete")) {
          console.log("🗑️ Calling openDeleteModal");
          handleDeleteClick(employee);
        }
      } catch (error) {
        console.error("❌ Parse error:", error);
        alert("Error: Data tidak valid");
      }
    },
    true
  ); // Use capture phase

  console.log("✅ Event delegation setup complete");

  // Also setup for card buttons
  setupCardButtonDelegation();
}

/**
 * Setup event delegation for card buttons
 */
function setupCardButtonDelegation() {
  const cardView = document.getElementById("cardView");
  if (!cardView) return;

  // Remove existing listeners
  const newCardView = cardView.cloneNode(true);
  cardView.parentNode.replaceChild(newCardView, cardView);

  newCardView.addEventListener(
    "click",
    function (e) {
      const button = e.target.closest(".btn-card-mini");

      if (!button) return;

      e.stopPropagation();
      e.preventDefault();

      const card = button.closest(".employee-card");
      if (!card) return;

      const employeeData = card.getAttribute("data-employee");
      if (!employeeData) return;

      try {
        const employee = JSON.parse(employeeData);

        if (button.classList.contains("btn-edit")) {
          handleEditClick(employee);
        } else if (button.classList.contains("btn-delete")) {
          handleDeleteClick(employee);
        } else if (button.classList.contains("btn-attendance")) {
          handleAttendanceClick(employee);
        }
      } catch (error) {
        console.error("Parse error:", error);
      }
    },
    true
  );

  console.log("✅ Card button delegation setup complete");
}

/**
 * Handle edit button click
 */
function handleEditClick(employee) {
  if (typeof window.openEditModal === "function") {
    console.log("✅ Opening edit modal for:", employee.name);
    window.openEditModal(employee);
  } else {
    console.error("❌ openEditModal not found");
    alert("Error: Fungsi edit tidak tersedia");
  }
}

/**
 * Handle delete button click
 */
function handleDeleteClick(employee) {
  if (typeof window.openDeleteModal === "function") {
    console.log("✅ Opening delete modal for:", employee.name);
    window.openDeleteModal(employee.id_employee, employee.name);
  } else {
    console.error("❌ openDeleteModal not found");
    alert("Error: Fungsi hapus tidak tersedia");
  }
}

/**
 * Handle attendance button click
 */
function handleAttendanceClick(employee) {
  if (typeof window.openAttendanceModal === "function") {
    console.log("✅ Opening attendance modal for:", employee.name);
    window.openAttendanceModal(employee);
  } else {
    console.error("❌ openAttendanceModal not found");
    alert("Error: Fungsi absensi tidak tersedia");
  }
}

/**
 * Initialize double-click for cards
 */
function initializeCardDoubleClick() {
  const cards = document.querySelectorAll(".employee-card");
  if (cards.length === 0) return;

  console.log(`Found ${cards.length} employee cards`);

  cards.forEach((card) => {
    let clickCount = 0;
    let clickTimer = null;

    card.addEventListener("click", function (e) {
      if (e.target.closest(".btn-card-mini")) return;

      clickCount++;

      if (clickCount === 1) {
        createRippleEffect(e, this);
        clickTimer = setTimeout(() => {
          clickCount = 0;
        }, 300);
      } else if (clickCount === 2) {
        clearTimeout(clickTimer);
        clickCount = 0;
        openCardDetail(this);
      }
    });

    card.addEventListener("dblclick", function (e) {
      if (e.target.closest(".btn-card-mini")) return;
      openCardDetail(this);
    });
  });
}

/**
 * Initialize double-click for table rows
 */
function initializeTableDoubleClick() {
  const tableRows = document.querySelectorAll(".table-row-interactive");
  if (tableRows.length === 0) return;

  console.log(`Found ${tableRows.length} table rows`);

  tableRows.forEach((row) => {
    let clickCount = 0;
    let clickTimer = null;

    row.addEventListener("click", function (e) {
      if (
        e.target.closest(".btn-table") ||
        e.target.closest(".table-actions")
      ) {
        return;
      }

      clickCount++;

      if (clickCount === 1) {
        this.style.background = "rgba(0, 102, 204, 0.08)";
        clickTimer = setTimeout(() => {
          this.style.background = "";
          clickCount = 0;
        }, 300);
      } else if (clickCount === 2) {
        clearTimeout(clickTimer);
        clickCount = 0;
        this.style.background = "";
        openRowDetail(this);
      }
    });

    row.addEventListener("dblclick", function (e) {
      if (e.target.closest(".btn-table")) return;
      this.style.background = "";
      openRowDetail(this);
    });
  });
}

/**
 * Open detail modal from card
 */
function openCardDetail(cardElement) {
  const employeeData = cardElement.getAttribute("data-employee");
  if (!employeeData) {
    alert("Error: Data karyawan tidak ditemukan");
    return;
  }

  try {
    const employee = JSON.parse(employeeData);
    if (typeof window.openDetailModal === "function") {
      window.openDetailModal(employee);
    } else {
      alert("Modal detail tidak tersedia");
    }
  } catch (error) {
    alert("Error: Data tidak valid");
  }
}

/**
 * Open detail modal from table row
 */
function openRowDetail(rowElement) {
  const employeeData = rowElement.getAttribute("data-employee");
  if (!employeeData) {
    alert("Error: Data karyawan tidak ditemukan");
    return;
  }

  try {
    const employee = JSON.parse(employeeData);
    if (typeof window.openDetailModal === "function") {
      window.openDetailModal(employee);
    } else {
      alert("Modal detail tidak tersedia");
    }
  } catch (error) {
    alert("Error: Data tidak valid");
  }
}

/**
 * Create ripple effect
 */
function createRippleEffect(event, element) {
  const ripple = document.createElement("div");
  const wrapper = element.querySelector(".card-image-wrapper");
  if (!wrapper) return;

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
  setTimeout(() => ripple.remove(), 600);
}

/**
 * Debug modal status
 */
function debugModalStatus() {
  console.log("═══════════════════════════════════════");
  console.log("🔍 MODAL STATUS CHECK");
  console.log("═══════════════════════════════════════");

  const modalFunctions = {
    openDetailModal: window.openDetailModal,
    openEditModal: window.openEditModal,
    openDeleteModal: window.openDeleteModal,
    openAttendanceModal: window.openAttendanceModal,
    openAddModal: window.openAddModal,
  };

  let allReady = true;
  for (const [name, func] of Object.entries(modalFunctions)) {
    const status = typeof func === "function";
    console.log(`${status ? "✅" : "❌"} ${name}: ${typeof func}`);
    if (!status) allReady = false;
  }

  console.log("═══════════════════════════════════════");
  console.log(
    allReady ? "✅ All modal functions READY" : "⚠️ Some functions MISSING"
  );
  console.log("═══════════════════════════════════════");
  return allReady;
}

/**
 * Test button functionality
 */
function testTableButtons() {
  console.log("🧪 Testing table buttons...");

  const editBtn = document.querySelector(".btn-table.btn-edit");
  const deleteBtn = document.querySelector(".btn-table.btn-delete");

  if (editBtn) {
    console.log("🔍 Edit button found, simulating click...");
    editBtn.click();
  } else {
    console.error("❌ Edit button not found");
  }
}

// ================================================
// AUTO-INITIALIZE
// ================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("🚀 Card/Table Interactions: DOMContentLoaded");

  setTimeout(() => {
    const modalsReady = debugModalStatus();

    if (modalsReady) {
      initializeCardTableInteractions();
      console.log("✅ Initialization complete");

      // Test after 1 second
      setTimeout(() => {
        console.log("💡 You can now click table buttons");
        console.log("💡 Or run: testTableButtons()");
      }, 1000);
    } else {
      console.warn("⚠️ Waiting for modals...");
      setTimeout(() => {
        debugModalStatus();
        initializeCardTableInteractions();
      }, 1000);
    }
  }, 300);
});

// Expose functions globally
window.initializeCardTableInteractions = initializeCardTableInteractions;
window.debugModalStatus = debugModalStatus;
window.testTableButtons = testTableButtons;
window.handleEditClick = handleEditClick;
window.handleDeleteClick = handleDeleteClick;

console.log("✅ Card/Table interactions module loaded with EVENT DELEGATION");
