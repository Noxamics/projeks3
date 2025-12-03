// ===========================
// EMPLOYEE MANAGEMENT - MAIN
// File: js/karyawan/main.js
// Merged with view-toggle functionality
// ===========================

/**
 * Switch between Card and Table views
 * @param {string} viewType - 'card' or 'table'
 */
function switchView(viewType) {
  console.log("Switching to view:", viewType);

  const toggleButtons = document.querySelectorAll(".toggle-btn");
  const cardView = document.getElementById("cardView");
  const tableView = document.getElementById("tableView");

  // Validation
  if (!cardView || !tableView) {
    console.error("View containers not found!");
    return;
  }

  // Remove active class from all buttons
  toggleButtons.forEach((btn) => btn.classList.remove("active"));

  // Add active class to selected button
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

  // Save preference
  localStorage.setItem("employeeViewPreference", viewType);

  // Trigger custom event
  const viewChangeEvent = new CustomEvent("viewChanged", {
    detail: { viewType: viewType },
  });
  document.dispatchEvent(viewChangeEvent);
}

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

  // Get saved view preference
  const savedView = localStorage.getItem("employeeViewPreference") || "card";
  console.log("Saved view preference:", savedView);

  // Set initial view
  switchView(savedView);

  // Add click handlers to toggle buttons
  toggleButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const viewType = this.getAttribute("data-view");
      console.log("Toggle clicked:", viewType);

      // Switch view
      switchView(viewType);
    });

    // Ensure button is always clickable
    button.style.pointerEvents = "auto";
    button.style.cursor = "pointer";
  });

  console.log("✅ View toggle initialized successfully");
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
 * Add keyboard shortcuts for view switching
 */
function addViewKeyboardShortcuts() {
  document.addEventListener("keydown", function (e) {
    // Ctrl+1 = Card view
    if (e.ctrlKey && e.key === "1") {
      e.preventDefault();
      switchView("card");
      console.log("Keyboard shortcut: Switched to Card view");
    }

    // Ctrl+2 = Table view
    if (e.ctrlKey && e.key === "2") {
      e.preventDefault();
      switchView("table");
      console.log("Keyboard shortcut: Switched to Table view");
    }
  });

  console.log("✅ Keyboard shortcuts added (Ctrl+1: Card, Ctrl+2: Table)");
}

/**
 * Listen for view change events
 */
function setupViewChangeListeners() {
  document.addEventListener("viewChanged", function (e) {
    console.log("View changed to:", e.detail.viewType);

    // Reinitialize interactions if needed
    if (typeof reinitializeInteractions === "function") {
      setTimeout(() => {
        reinitializeInteractions();
      }, 100);
    }
  });
}

/**
 * Format date helper
 */
function formatDate(dateStr) {
  if (!dateStr) return "-";
  const date = new Date(dateStr);
  const options = { year: "numeric", month: "long", day: "numeric" };
  return date.toLocaleDateString("id-ID", options);
}

/**
 * Show loading state
 */
function showLoading(show = true) {
  const loadingElement = document.getElementById("loadingState");
  if (loadingElement) {
    loadingElement.classList.toggle("active", show);
  }
}

/**
 * Show toast notification
 */
function showToast(message, type = "info") {
  let toast = document.getElementById("toast-notification");

  if (!toast) {
    toast = document.createElement("div");
    toast.id = "toast-notification";
    toast.style.cssText = `
      position: fixed;
      bottom: 30px;
      right: 30px;
      padding: 16px 24px;
      border-radius: 12px;
      font-weight: 600;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      z-index: 10000;
      display: none;
      animation: slideInRight 0.3s ease;
      min-width: 250px;
      max-width: 400px;
    `;
    document.body.appendChild(toast);
  }

  const colors = {
    success: { bg: "#e8f5e9", text: "#2e7d32", border: "#c8e6c9" },
    error: { bg: "#ffebee", text: "#c62828", border: "#ffcdd2" },
    warning: { bg: "#fff3e0", text: "#e65100", border: "#ffe0b2" },
    info: { bg: "#e3f2fd", text: "#0066cc", border: "#bbdefb" },
  };

  const color = colors[type] || colors.info;

  toast.style.background = color.bg;
  toast.style.color = color.text;
  toast.style.border = `2px solid ${color.border}`;
  toast.textContent = message;
  toast.style.display = "block";

  setTimeout(() => {
    toast.style.display = "none";
  }, 4000);
}

/**
 * Handle form submission with auto-reload
 */
function handleFormSubmit(form, url, successMessage, onSuccess) {
  const submitBtn = form.querySelector('button[type="submit"]');
  if (!submitBtn) return;

  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = "⏳ Memproses...";
  submitBtn.disabled = true;

  fetch(url, {
    method: "POST",
    body: new FormData(form),
  })
    .then((r) => r.text())
    .then((response) => {
      const trimmedResponse = response.trim();
      console.log("Server response:", trimmedResponse);

      if (trimmedResponse === "success") {
        showToast(successMessage || "✅ Operasi berhasil!", "success");

        // Call success callback if provided
        if (onSuccess && typeof onSuccess === "function") {
          onSuccess();
        }

        // Reload page after 1 second
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        handleFormError(trimmedResponse);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }
    })
    .catch((err) => {
      showToast("❌ Error: " + err.message, "error");
      console.error("Form submission error:", err);
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    });
}

/**
 * Handle form errors with user-friendly messages
 */
function handleFormError(error) {
  const errorMessages = {
    "error:duplicate": "⚠️ Data sudah terdaftar!",
    "error:duplicate_code_or_phone":
      "⚠️ Kode karyawan atau nomor HP sudah terdaftar!",
    "error:invalid_file_type":
      "⚠️ Tipe file tidak valid! Gunakan JPG, JPEG, atau PNG.",
    "error:file_too_large": "⚠️ Ukuran file terlalu besar! Maksimal 5MB.",
    "error:password": "❌ Password salah!",
    "error:password_too_short":
      "⚠️ Password terlalu pendek! Minimal 6 karakter.",
    "error:already": "⚠️ Operasi sudah dilakukan!",
    "error:no_checkin": "⚠️ Belum check in!",
    "error:missing_roles": "⚠️ Pilih minimal 1 role!",
    "error:invalid_employee_code_format":
      "⚠️ Format kode karyawan salah! Gunakan huruf kapital dan angka.",
    "error:invalid_phone_format":
      "⚠️ Format nomor HP salah! Gunakan 10-15 digit angka.",
  };

  let message = "❌ Gagal memproses data!";

  for (const [key, msg] of Object.entries(errorMessages)) {
    if (error.includes(key)) {
      message = msg;
      break;
    }
  }

  showToast(message, "error");
}

/**
 * Refresh statistics
 */
function refreshStats() {
  fetch("../actions/karyawan/get_stats.php")
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        updateStatElement("totalEmployees", data.totalEmployees);
        updateStatElement("todayAttendance", data.todayAttendance);

        if (data.todayKasir) {
          updateStatElement("todayKasir", data.todayKasir.code);
        }
      }
    })
    .catch((err) => {
      console.error("Error refreshing stats:", err);
    });
}

/**
 * Update stat element value
 */
function updateStatElement(id, value) {
  const element = document.getElementById(id);
  if (element) {
    element.textContent = value;
  }
}

/**
 * Add new employee card dynamically
 */
function addEmployeeCard(employee) {
  const cardsGrid = document.querySelector(".cards-grid");
  if (!cardsGrid) return;

  // Remove empty state if exists
  const emptyState = cardsGrid.querySelector(".empty-state");
  if (emptyState) {
    emptyState.remove();
  }

  // Create card HTML
  const cardHTML = createEmployeeCardHTML(employee);

  // Insert at the beginning
  cardsGrid.insertAdjacentHTML("afterbegin", cardHTML);

  // Animate the new card
  const newCard = cardsGrid.firstElementChild;
  if (newCard) {
    newCard.style.opacity = "0";
    newCard.style.transform = "scale(0.8)";

    setTimeout(() => {
      newCard.style.transition = "all 0.5s ease";
      newCard.style.opacity = "1";
      newCard.style.transform = "scale(1)";
    }, 100);
  }
}

/**
 * Create employee card HTML
 */
function createEmployeeCardHTML(employee) {
  const photoPath = employee.photo
    ? `../uploads/employee/${employee.photo}`
    : `https://ui-avatars.com/api/?name=${encodeURIComponent(
        employee.name.substring(0, 2)
      )}&size=400&background=6c5ce7&color=fff&bold=true`;

  const roles = employee.roles ? employee.roles.split(",") : ["cleaning"];
  const rolesHTML = roles
    .map(
      (role) =>
        `<span class="role-badge role-${role
          .trim()
          .toLowerCase()}">${role.trim()}</span>`
    )
    .join("");

  const json = JSON.stringify(employee).replace(/"/g, "&quot;");

  return `
    <div class="employee-card" 
         data-status="${employee.status}" 
         data-roles="${employee.roles || "cleaning"}"
         data-name="${employee.name}"
         data-code="${employee.employee_code}"
         data-phone="${employee.phone}">
      
      <div class="card-image-wrapper">
        <img src="${photoPath}" alt="${
    employee.name
  }" class="card-image" loading="lazy">
        
        <div class="card-badges">
          <span class="badge-number">#${String(employee.id_employee).padStart(
            3,
            "0"
          )}</span>
          <span class="badge-code">${employee.employee_code}</span>
        </div>
        
        <div class="card-gradient">
          <h3 class="card-name">${employee.name}</h3>
          <p class="card-phone">📞 ${employee.phone}</p>
          
          <div class="card-roles">
            ${rolesHTML}
          </div>
          
          <span class="status-badge status-${employee.status.toLowerCase()}">
            ${employee.status}
          </span>
        </div>
      </div>
      
      <div class="card-actions">
        <button class="btn-card btn-edit" onclick='openEditModal(${json})' title="Edit">✏️</button>
        <button class="btn-card btn-detail" onclick='openDetailModal(${json})' title="Detail">👁️</button>
        <button class="btn-card btn-delete" onclick="openDeleteModal(${
          employee.id_employee
        }, '${employee.name}')" title="Hapus">🗑️</button>
      </div>
    </div>
  `;
}

/**
 * Debug view toggle status
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

  if (cardView) console.log("Card view classes:", cardView.className);
  if (tableView) console.log("Table view classes:", tableView.className);
}

// ================================================
// INITIALIZE ON PAGE LOAD
// ================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("🚀 Employee Management System Loading...");

  // Small delay to ensure DOM is fully ready
  setTimeout(() => {
    // Initialize view toggle
    initializeViewToggle();

    // Add keyboard shortcuts
    addViewKeyboardShortcuts();

    // Setup event listeners
    setupViewChangeListeners();

    // Auto-refresh stats every 5 minutes
    setInterval(refreshStats, 300000);

    console.log("✅ Employee Management System Loaded Successfully");
  }, 100);
});

// ================================================
// EXPOSE FUNCTIONS TO GLOBAL SCOPE
// ================================================

window.switchView = switchView;
window.initializeViewToggle = initializeViewToggle;
window.getCurrentView = getCurrentView;
window.formatDate = formatDate;
window.showLoading = showLoading;
window.showToast = showToast;
window.handleFormSubmit = handleFormSubmit;
window.handleFormError = handleFormError;
window.refreshStats = refreshStats;
window.addEmployeeCard = addEmployeeCard;
window.debugViewToggle = debugViewToggle;

console.log("✅ Main.js module loaded");
