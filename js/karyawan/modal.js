// ===========================
// MODAL MANAGEMENT - COMPLETE FIX
// File: js/karyawan/modal.js
// ===========================

let currentEmployee = null;

/**
 * Prevent body scroll
 */
function preventBodyScroll(prevent) {
  if (prevent) {
    document.body.style.overflow = "hidden";
    document.body.classList.add("modal-open");
  } else {
    document.body.style.overflow = "";
    document.body.classList.remove("modal-open");
  }
}

/**
 * Open Add Modal - FIXED VERSION
 */
function openAddModal() {
  console.log("openAddModal called");

  const modal = document.getElementById("modalAdd");

  if (!modal) {
    console.error("Modal Add not found!");
    alert(
      "Error: Modal tidak ditemukan. Pastikan file add.php sudah di-include."
    );
    return;
  }

  console.log("Opening modal...");

  // Force modal to show as overlay
  modal.style.display = "flex";
  modal.style.position = "fixed";
  modal.style.top = "0";
  modal.style.left = "0";
  modal.style.width = "100vw";
  modal.style.height = "100vh";
  modal.style.zIndex = "999999";

  // Prevent body scroll
  preventBodyScroll(true);

  // Reset form
  const form = modal.querySelector("form");
  if (form) {
    form.reset();

    // Reset photo preview
    const preview = document.getElementById("add_photo_preview");
    if (preview) preview.style.display = "none";
  }

  // Focus first input after a delay
  setTimeout(() => {
    const firstInput = modal.querySelector('input[type="text"]');
    if (firstInput) firstInput.focus();
  }, 100);

  console.log("Modal opened successfully");
}

/**
 * Close Add Modal
 */
function closeAddModal() {
  console.log("closeAddModal called");

  const modal = document.getElementById("modalAdd");
  if (modal) {
    modal.style.display = "none";
    preventBodyScroll(false);
  }
}

/**
 * Open Edit Modal - FIXED VERSION
 */
function openEditModal(data) {
  console.log("openEditModal called");

  const modal = document.getElementById("modalEdit");

  if (!modal) {
    console.error("Modal Edit not found!");
    alert("Error: Modal Edit tidak ditemukan.");
    return;
  }

  // Parse data
  const obj = typeof data === "string" ? JSON.parse(data) : data;
  console.log("Employee data:", obj);

  // Populate basic fields
  const fields = {
    edit_id: obj.id_employee,
    edit_employee_code: obj.employee_code || "",
    edit_name: obj.name || "",
    edit_phone: obj.phone || "",
    edit_join_date: obj.join_date || "",
    edit_status: obj.status || "Aktif",
  };

  for (const [fieldId, value] of Object.entries(fields)) {
    const element = document.getElementById(fieldId);
    if (element) {
      element.value = value;
    } else {
      console.warn(`Field ${fieldId} not found`);
    }
  }

  // Handle roles checkboxes
  if (obj.roles) {
    const roles = obj.roles.split(",").map((r) => r.trim());
    console.log("Roles:", roles);

    ["cleaning", "reglue", "repaint", "kasir"].forEach((role) => {
      const checkbox = document.getElementById(`edit_role_${role}`);
      if (checkbox) {
        checkbox.checked = roles.includes(role);
      }
    });
  }

  // Show current photo if exists
  if (obj.photo) {
    const photoPath = `../uploads/employee/${obj.photo}`;
    const currentPhotoImg = document.getElementById("edit_current_photo_img");
    const currentPhotoDiv = document.getElementById("edit_current_photo");

    if (currentPhotoImg && currentPhotoDiv) {
      currentPhotoImg.src = photoPath;
      currentPhotoDiv.style.display = "block";
    }
  }

  // Show modal as overlay
  modal.style.display = "flex";
  modal.style.position = "fixed";
  modal.style.top = "0";
  modal.style.left = "0";
  modal.style.width = "100vw";
  modal.style.height = "100vh";
  modal.style.zIndex = "999999";

  preventBodyScroll(true);

  console.log("Modal Edit opened successfully");
}

/**
 * Close Edit Modal
 */
function closeEditModal() {
  const modal = document.getElementById("modalEdit");
  if (modal) {
    modal.style.display = "none";
    preventBodyScroll(false);

    // Hide photo preview
    const preview = document.getElementById("edit_photo_preview");
    if (preview) preview.style.display = "none";
  }
}

/**
 * Open Delete Modal - FIXED VERSION
 */
function openDeleteModal(id, name) {
  console.log("openDeleteModal called", { id, name });

  const modal = document.getElementById("modalDelete");

  if (!modal) {
    console.error("Modal Delete not found!");
    alert("Error: Modal Delete tidak ditemukan.");
    return;
  }

  // Set employee ID
  const idField = document.getElementById("delete_id");
  if (idField) {
    idField.value = id;
  }

  // Set confirmation text
  const textElement = document.getElementById("delete_text");
  if (textElement) {
    textElement.textContent = `Yakin menghapus: ${name}?`;
  }

  // Show modal as overlay
  modal.style.display = "flex";
  modal.style.position = "fixed";
  modal.style.top = "0";
  modal.style.left = "0";
  modal.style.width = "100vw";
  modal.style.height = "100vh";
  modal.style.zIndex = "999999";

  preventBodyScroll(true);

  console.log("Modal Delete opened successfully");
}

/**
 * Close Delete Modal
 */
function closeDeleteModal() {
  const modal = document.getElementById("modalDelete");
  if (modal) {
    modal.style.display = "none";
    preventBodyScroll(false);
  }
}

/**
 * Open Detail Modal - FIXED VERSION
 */
function openDetailModal(data) {
  console.log("openDetailModal called");

  const modal = document.getElementById("modalDetail");

  if (!modal) {
    console.error("Modal Detail not found!");
    alert("Error: Modal Detail tidak ditemukan.");
    return;
  }

  // Parse data
  const obj = typeof data === "string" ? JSON.parse(data) : data;
  currentEmployee = obj;
  console.log("Employee data:", obj);

  // Set photo
  const photoElement = document.getElementById("detail_photo");
  if (photoElement) {
    let photoPath;
    if (obj.photo) {
      photoPath = `../uploads/employee/${obj.photo}`;
    } else {
      const initial = obj.name.substring(0, 2).toUpperCase();
      photoPath = `https://ui-avatars.com/api/?name=${encodeURIComponent(
        initial
      )}&size=300&background=6c5ce7&color=fff&bold=true&rounded=true`;
    }
    photoElement.src = photoPath;
  }

  // Set employee info
  setElementText("detail_name", obj.name);
  setElementText("detail_code", `Kode: ${obj.employee_code}`);
  setElementText("detail_phone", `HP: ${obj.phone}`);
  setElementText("detail_join", `Bergabung: ${formatDate(obj.join_date)}`);

  // Set status badge
  const statusElement = document.getElementById("detail_status");
  if (statusElement) {
    statusElement.textContent = obj.status;
    statusElement.className = `status-badge status-${obj.status.toLowerCase()}`;
  }

  // Set roles
  const rolesElement = document.getElementById("detail_roles");
  if (rolesElement && obj.roles) {
    const roles = obj.roles.split(",");
    rolesElement.innerHTML = roles
      .map(
        (role) =>
          `<span class="role-badge role-${role
            .trim()
            .toLowerCase()}">${role.trim()}</span>`
      )
      .join("");
  }

  // Update info tab
  if (typeof updateDetailModalContent === "function") {
    updateDetailModalContent(obj);
  }

  // Set employee ID for attendance
  const attendanceIdField = document.getElementById("attendance_employee_id");
  if (attendanceIdField) {
    attendanceIdField.value = obj.id_employee;
  }

  // Load attendance history
  if (typeof loadAttendanceHistory === "function") {
    loadAttendanceHistory(obj.id_employee);
  }

  // Update time display
  if (typeof updateTimeDisplay === "function") {
    updateTimeDisplay();
    const timeInterval = setInterval(updateTimeDisplay, 1000);
    modal.dataset.timeInterval = timeInterval;
  }

  // Reset to first tab
  switchTab("info");

  // Show modal as overlay
  modal.style.display = "flex";
  modal.style.position = "fixed";
  modal.style.top = "0";
  modal.style.left = "0";
  modal.style.width = "100vw";
  modal.style.height = "100vh";
  modal.style.zIndex = "999999";

  preventBodyScroll(true);

  console.log("Modal Detail opened successfully");
}

/**
 * Close Detail Modal
 */
function closeDetailModal() {
  const modal = document.getElementById("modalDetail");
  if (modal) {
    // Clear time interval
    if (modal.dataset.timeInterval) {
      clearInterval(parseInt(modal.dataset.timeInterval));
    }

    modal.style.display = "none";
    preventBodyScroll(false);
    currentEmployee = null;
  }
}

/**
 * Open Attendance Modal (alias for detail modal with attendance tab)
 */
function openAttendanceModal(data) {
  openDetailModal(data);

  // Switch to attendance tab after modal opens
  setTimeout(() => {
    switchTab("attendance");
  }, 100);
}

/**
 * Switch tabs in detail modal
 */
function switchTab(tabName) {
  console.log("Switching to tab:", tabName);

  // Hide all tab contents
  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.classList.remove("active");
  });

  // Remove active from all buttons
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.classList.remove("active");
  });

  // Show selected tab
  const selectedTab = document.getElementById(`tab-${tabName}`);
  if (selectedTab) {
    selectedTab.classList.add("active");
  }

  // Set button active
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    const btnText = btn.textContent.toLowerCase();
    if (btnText.includes(tabName)) {
      btn.classList.add("active");
    }
  });
}

/**
 * Helper: Set element text safely
 */
function setElementText(id, text) {
  const element = document.getElementById(id);
  if (element) {
    element.textContent = text;
  } else {
    console.warn(`Element ${id} not found`);
  }
}

/**
 * Helper: Format date to Indonesian format
 */
function formatDate(dateString) {
  if (!dateString) return "-";
  const date = new Date(dateString);
  const options = { year: "numeric", month: "long", day: "numeric" };
  return date.toLocaleDateString("id-ID", options);
}

// ===========================
// FORM SUBMISSION HANDLERS - AUTO RELOAD
// ===========================

/**
 * Initialize all form submission handlers
 */
function initializeFormHandlers() {
  console.log("Initializing form handlers...");

  // ========== ADD FORM HANDLER ==========
  const addForm = document.querySelector("#modalAdd form");
  if (addForm) {
    // Remove existing listeners
    const newAddForm = addForm.cloneNode(true);
    addForm.parentNode.replaceChild(newAddForm, addForm);

    newAddForm.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("Add form submitted");

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn ? submitBtn.innerHTML : "";

      // Show loading
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = "⏳ Menyimpan...";
      }

      const formData = new FormData(this);

      fetch("../actions/karyawan/add.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            console.log("✅ Add success");
            alert("✅ Karyawan berhasil ditambahkan!");
            closeAddModal();

            // CRITICAL: Reload page to show new data
            window.location.reload();
          } else {
            console.error("❌ Add failed:", data.message);
            alert(
              "❌ Error: " + (data.message || "Gagal menambahkan karyawan")
            );

            // Restore button
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalText;
            }
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("❌ Terjadi kesalahan saat menambahkan karyawan");

          // Restore button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        });
    });

    console.log("✅ Add form handler initialized");
  } else {
    console.warn("⚠️ Add form not found");
  }

  // ========== EDIT FORM HANDLER ==========
  const editForm = document.querySelector("#modalEdit form");
  if (editForm) {
    // Remove existing listeners
    const newEditForm = editForm.cloneNode(true);
    editForm.parentNode.replaceChild(newEditForm, editForm);

    newEditForm.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("Edit form submitted");

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn ? submitBtn.innerHTML : "";

      // Show loading
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = "⏳ Menyimpan...";
      }

      const formData = new FormData(this);

      fetch("../actions/karyawan/edit.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            console.log("✅ Edit success");
            alert("✅ Data karyawan berhasil diupdate!");
            closeEditModal();

            // CRITICAL: Reload page to show updated data
            window.location.reload();
          } else {
            console.error("❌ Edit failed:", data.message);
            alert("❌ Error: " + (data.message || "Gagal mengupdate karyawan"));

            // Restore button
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalText;
            }
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("❌ Terjadi kesalahan saat mengupdate karyawan");

          // Restore button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        });
    });

    console.log("✅ Edit form handler initialized");
  } else {
    console.warn("⚠️ Edit form not found");
  }

  // ========== DELETE FORM HANDLER ==========
  const deleteForm = document.querySelector("#modalDelete form");
  if (deleteForm) {
    // Remove existing listeners
    const newDeleteForm = deleteForm.cloneNode(true);
    deleteForm.parentNode.replaceChild(newDeleteForm, deleteForm);

    newDeleteForm.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("Delete form submitted");

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn ? submitBtn.innerHTML : "";

      // Show loading
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = "⏳ Menghapus...";
      }

      const formData = new FormData(this);

      fetch("../actions/karyawan/delete.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            console.log("✅ Delete success");
            alert("✅ Karyawan berhasil dihapus!");
            closeDeleteModal();

            // CRITICAL: Reload page to remove deleted data
            window.location.reload();
          } else {
            console.error("❌ Delete failed:", data.message);
            alert("❌ Error: " + (data.message || "Gagal menghapus karyawan"));

            // Restore button
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalText;
            }
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("❌ Terjadi kesalahan saat menghapus karyawan");

          // Restore button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        });
    });

    console.log("✅ Delete form handler initialized");
  } else {
    console.warn("⚠️ Delete form not found");
  }
}

/**
 * Initialize modal event listeners
 */
document.addEventListener("DOMContentLoaded", function () {
  console.log("Initializing modal event listeners...");

  // Initialize form handlers
  initializeFormHandlers();

  // Close modal on background click
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("click", function (e) {
      // Only close if clicking the backdrop (not the modal-box)
      if (e.target === this) {
        console.log("Backdrop clicked, closing modal");
        this.style.display = "none";
        preventBodyScroll(false);

        // Clear time interval if detail modal
        if (this.id === "modalDetail" && this.dataset.timeInterval) {
          clearInterval(parseInt(this.dataset.timeInterval));
        }
      }
    });
  });

  // Close modal on Escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      const openModals = document.querySelectorAll(
        '.modal[style*="display: flex"], .modal[style*="display:flex"]'
      );
      openModals.forEach((modal) => {
        console.log("Escape pressed, closing modal");
        modal.style.display = "none";
        preventBodyScroll(false);

        // Clear time interval if detail modal
        if (modal.id === "modalDetail" && modal.dataset.timeInterval) {
          clearInterval(parseInt(modal.dataset.timeInterval));
        }
      });
    }
  });

  console.log("Modal event listeners initialized");
});

// Expose functions to global scope
window.openAddModal = openAddModal;
window.closeAddModal = closeAddModal;
window.openEditModal = openEditModal;
window.closeEditModal = closeEditModal;
window.openDeleteModal = openDeleteModal;
window.closeDeleteModal = closeDeleteModal;
window.openDetailModal = openDetailModal;
window.closeDetailModal = closeDetailModal;
window.openAttendanceModal = openAttendanceModal;
window.switchTab = switchTab;
window.initializeFormHandlers = initializeFormHandlers;

console.log("✅ Modal functions loaded and exposed to window");
