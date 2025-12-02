// ===========================
// EMPLOYEE MANAGEMENT - MAIN
// File: js/karyawan/main.js
// WITH AUTO RELOAD AFTER SUCCESS
// ===========================

/**
 * View Switching between Card and Table
 */
function switchView(viewType) {
  document.querySelectorAll(".toggle-btn").forEach((btn) => {
    btn.classList.remove("active");
  });

  const targetBtn = document.querySelector(`[data-view="${viewType}"]`);
  if (targetBtn) {
    targetBtn.classList.add("active");
  }

  document.querySelectorAll(".view-content").forEach((content) => {
    content.classList.remove("active");
  });

  const viewElement = document.getElementById(
    viewType === "card" ? "cardView" : "tableView"
  );
  if (viewElement) {
    viewElement.classList.add("active");
  }

  localStorage.setItem("preferredView", viewType);
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
 * Refresh statistics (for auto-refresh)
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
 * Add new employee card dynamically (without reload)
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
 * Initialize page
 */
document.addEventListener("DOMContentLoaded", function () {
  console.log("Employee Management System Loaded");

  // Load saved view preference
  const savedView = localStorage.getItem("preferredView") || "card";
  switchView(savedView);

  // Auto-refresh stats every 5 minutes
  setInterval(refreshStats, 300000);

  console.log("✅ Page initialized successfully");
});

// Expose functions to global scope
window.switchView = switchView;
window.formatDate = formatDate;
window.showLoading = showLoading;
window.showToast = showToast;
window.handleFormSubmit = handleFormSubmit;
window.handleFormError = handleFormError;
window.refreshStats = refreshStats;
window.addEmployeeCard = addEmployeeCard;
