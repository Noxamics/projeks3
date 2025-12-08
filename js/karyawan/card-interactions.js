/**
 * =============================================
 * FILE: js/karyawan/card-interactions.js
 * DESKRIPSI: Handle card and table interactions
 * VERSION: 3.0 - CLEANED & FIXED
 * ============================================= */

(function () {
  "use strict";

  console.log("📦 Card/Table Interactions Module Loading...");

  // ============================================
  // CONFIGURATION
  // ============================================
  const CONFIG = {
    DOUBLE_CLICK_DELAY: 300,
    INIT_DELAY: 500,
    RETRY_DELAY: 1000,
  };

  // ============================================
  // UTILITY FUNCTIONS
  // ============================================

  /**
   * Debug modal functions availability
   */
  function debugModalStatus() {
    console.log("═══════════════════════════════════════");
    console.log("🔍 MODAL FUNCTIONS STATUS CHECK");
    console.log("═══════════════════════════════════════");

    const modalFunctions = {
      openDetailModal: window.openDetailModal,
      openEditModal: window.openEditModal,
      openDeleteModal: window.openDeleteModal,
      openAttendanceModal: window.openAttendanceModal,
      openAddModal: window.openAddModal,
      closeDetailModal: window.closeDetailModal,
    };

    let allReady = true;
    for (const [name, func] of Object.entries(modalFunctions)) {
      const status = typeof func === "function";
      console.log(`${status ? "✅" : "❌"} ${name}: ${typeof func}`);
      if (!status) allReady = false;
    }

    console.log("═══════════════════════════════════════");
    console.log(
      allReady ? "✅ ALL MODAL FUNCTIONS READY" : "⚠️ SOME FUNCTIONS MISSING"
    );
    console.log("═══════════════════════════════════════");

    return allReady;
  }

  /**
   * Check if clicking on button
   */
  function isClickingButton(e, buttonSelectors) {
    return buttonSelectors.some((selector) => e.target.closest(selector));
  }

  /**
   * Create ripple effect on click
   */
  function createRippleEffect(event, element) {
    const wrapper = element.querySelector(".card-image-wrapper");
    if (!wrapper) return;

    const ripple = document.createElement("div");
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

  // ============================================
  // MODAL HANDLERS
  // ============================================

  /**
   * Open detail modal from card or row
   */
  function openDetailFromElement(element, elementType) {
    console.log(`📂 Opening detail from ${elementType}...`);

    const employeeData = element.getAttribute("data-employee");

    if (!employeeData) {
      console.error("❌ No employee data attribute found");
      alert(`Error: Data karyawan tidak ditemukan pada ${elementType}`);
      return;
    }

    try {
      const employee = JSON.parse(employeeData);
      console.log("✅ Employee data parsed:", employee.name);

      if (typeof window.openDetailModal === "function") {
        console.log("✅ Calling openDetailModal...");
        window.openDetailModal(employee);
      } else {
        console.error("❌ openDetailModal function not found");
        console.log(
          "Available modal functions:",
          Object.keys(window).filter((k) => k.includes("Modal"))
        );
        alert(
          "Error: Modal detail tidak tersedia. Refresh halaman dan coba lagi."
        );
      }
    } catch (error) {
      console.error("❌ Parse error:", error);
      alert("Error: Data tidak valid - " + error.message);
    }
  }

  /**
   * Handle edit button click
   */
  function handleEditClick(employee) {
    console.log("🔧 Handle edit for:", employee.name);

    if (typeof window.openEditModal === "function") {
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
    console.log("🗑️ Handle delete for:", employee.name);

    if (typeof window.openDeleteModal === "function") {
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
    console.log("⏰ Handle attendance for:", employee.name);

    if (typeof window.openAttendanceModal === "function") {
      window.openAttendanceModal(employee);
    } else {
      console.error("❌ openAttendanceModal not found");
      alert("Error: Fungsi absensi tidak tersedia");
    }
  }

  // ============================================
  // CARD INTERACTIONS
  // ============================================

  /**
   * Initialize card double-click listeners
   */
  function initializeCardDoubleClick() {
    const cards = document.querySelectorAll(".employee-card");

    if (cards.length === 0) {
      console.warn("⚠️ No employee cards found");
      return;
    }

    console.log(`✅ Initializing ${cards.length} employee cards`);

    cards.forEach((card, index) => {
      // Double-click listener - HIGH PRIORITY
      card.addEventListener(
        "dblclick",
        function (e) {
          const buttonSelectors = [".card-action-buttons", ".btn-card-mini"];

          if (isClickingButton(e, buttonSelectors)) {
            console.log("🚫 Double-click on button - ignored");
            return;
          }

          console.log("🖱️ CARD DOUBLE-CLICKED!");
          e.stopPropagation();
          e.preventDefault();

          openDetailFromElement(this, "card");
        },
        { passive: false }
      );

      // Single click for visual feedback
      card.addEventListener("click", function (e) {
        const buttonSelectors = [".card-action-buttons", ".btn-card-mini"];

        if (isClickingButton(e, buttonSelectors)) {
          return;
        }

        // Visual feedback
        createRippleEffect(e, this);
        this.style.transform = "scale(0.98)";
        setTimeout(() => {
          this.style.transform = "";
        }, 150);
      });

      // Hover effect
      card.style.cursor = "pointer";
    });

    console.log("✅ Card double-click listeners attached");
  }

  // ============================================
  // TABLE INTERACTIONS
  // ============================================

  /**
   * Initialize table row double-click listeners
   */
  function initializeTableDoubleClick() {
    const tableRows = document.querySelectorAll(".table-row-interactive");

    if (tableRows.length === 0) {
      console.warn("⚠️ No table rows found");
      return;
    }

    console.log(`✅ Initializing ${tableRows.length} table rows`);

    tableRows.forEach((row, index) => {
      // Double-click listener
      row.addEventListener(
        "dblclick",
        function (e) {
          const buttonSelectors = [".table-actions", ".btn-table"];

          if (isClickingButton(e, buttonSelectors)) {
            console.log("🚫 Double-click on button - ignored");
            return;
          }

          console.log("🖱️ TABLE ROW DOUBLE-CLICKED!");
          e.stopPropagation();
          e.preventDefault();

          openDetailFromElement(this, "table row");
        },
        { passive: false }
      );

      // Hover effects
      row.addEventListener("mouseenter", function () {
        this.style.backgroundColor = "#f8fbff";
        this.style.cursor = "pointer";
      });

      row.addEventListener("mouseleave", function () {
        this.style.backgroundColor = "";
      });
    });

    console.log("✅ Table double-click listeners attached");
  }

  // ============================================
  // BUTTON DELEGATIONS (NON-BLOCKING)
  // ============================================

  /**
   * Setup button click delegation for cards
   */
  function setupCardButtonDelegation() {
    const cardView = document.getElementById("cardView");

    if (!cardView) {
      console.warn("⚠️ Card view not found");
      return;
    }

    console.log("🎯 Setting up card button delegation");

    // Use normal event phase (not capture)
    cardView.addEventListener(
      "click",
      function (e) {
        const button = e.target.closest(".btn-card-mini");

        if (!button) return;

        e.stopPropagation();
        e.preventDefault();

        console.log("🔘 Card button clicked:", button.className);

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
          console.error("❌ Parse error:", error);
        }
      },
      false
    ); // Bubble phase

    console.log("✅ Card button delegation setup");
  }

  /**
   * Setup button click delegation for table
   */
  function setupTableButtonDelegation() {
    const tableView = document.getElementById("tableView");

    if (!tableView) {
      console.warn("⚠️ Table view not found");
      return;
    }

    console.log("🎯 Setting up table button delegation");

    tableView.addEventListener(
      "click",
      function (e) {
        const button = e.target.closest(".btn-table");

        if (!button) return;

        e.stopPropagation();
        e.preventDefault();

        console.log("🔘 Table button clicked:", button.className);

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

          if (button.classList.contains("btn-edit")) {
            handleEditClick(employee);
          } else if (button.classList.contains("btn-delete")) {
            handleDeleteClick(employee);
          }
        } catch (error) {
          console.error("❌ Parse error:", error);
          alert("Error: Data tidak valid");
        }
      },
      false
    ); // Bubble phase

    console.log("✅ Table button delegation setup");
  }

  // ============================================
  // MAIN INITIALIZATION
  // ============================================

  /**
   * Initialize all interactions
   */
  function initializeAllInteractions() {
    console.log("🔄 Initializing all card and table interactions...");

    // Initialize double-click listeners FIRST
    initializeCardDoubleClick();
    initializeTableDoubleClick();

    // Then setup button delegations (won't interfere with double-click)
    setupCardButtonDelegation();
    setupTableButtonDelegation();

    console.log("✅ All interactions initialized successfully!");
  }

  /**
   * Test function for manual testing
   */
  function testDoubleClick() {
    console.log("🧪 Testing double-click...");

    const firstCard = document.querySelector(".employee-card");
    const firstRow = document.querySelector(".table-row-interactive");

    if (firstCard) {
      console.log("✅ Found card, triggering double-click");
      const event = new MouseEvent("dblclick", {
        bubbles: true,
        cancelable: true,
        view: window,
      });
      firstCard.dispatchEvent(event);
    } else {
      console.warn("⚠️ No card found");
    }

    if (firstRow) {
      console.log("✅ Found row, triggering double-click");
      setTimeout(() => {
        const event = new MouseEvent("dblclick", {
          bubbles: true,
          cancelable: true,
          view: window,
        });
        firstRow.dispatchEvent(event);
      }, 1000);
    } else {
      console.warn("⚠️ No row found");
    }
  }

  // ============================================
  // AUTO-INITIALIZE ON DOM READY
  // ============================================

  document.addEventListener("DOMContentLoaded", function () {
    console.log("🚀 Card/Table Interactions: DOMContentLoaded fired");

    // Wait for modal.js to be ready
    setTimeout(() => {
      console.log("⏳ Checking modal functions availability...");
      const modalsReady = debugModalStatus();

      if (modalsReady) {
        console.log("✅ All modals ready, initializing...");
        initializeAllInteractions();

        console.log("");
        console.log(
          "💡 TIP: Double-click pada card atau table row untuk membuka detail"
        );
        console.log(
          "💡 TIP: Gunakan testDoubleClick() di console untuk test manual"
        );
        console.log("");
      } else {
        console.warn("⚠️ Some modals not ready, retrying in 1 second...");
        setTimeout(() => {
          console.log("🔄 Retrying initialization...");
          debugModalStatus();
          initializeAllInteractions();
        }, CONFIG.RETRY_DELAY);
      }
    }, CONFIG.INIT_DELAY);
  });

  // ============================================
  // EXPOSE FUNCTIONS TO GLOBAL SCOPE
  // ============================================

  window.initializeAllInteractions = initializeAllInteractions;
  window.debugModalStatus = debugModalStatus;
  window.testDoubleClick = testDoubleClick;
  window.handleEditClick = handleEditClick;
  window.handleDeleteClick = handleDeleteClick;
  window.handleAttendanceClick = handleAttendanceClick;

  console.log("✅ Card/Table interactions module loaded successfully");
})();
