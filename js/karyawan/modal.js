/**
 * =============================================
 * FILE: js/karyawan/modal.js
 * DESKRIPSI: Complete Modal Management System
 * UPDATED: Auto-switch check-in/check-out based on status
 * ============================================= */

// ============================================
// GLOBAL VARIABLES
// ============================================
let currentEmployee = null;

// ============================================
// MODAL DISPLAY MANAGEMENT
// ============================================
function preventBodyScroll(prevent) {
  if (prevent) {
    document.body.style.overflow = "hidden";
    document.body.classList.add("modal-open");
  } else {
    document.body.style.overflow = "";
    document.body.classList.remove("modal-open");
  }
}

function showModal(modal) {
  if (!modal) return;

  modal.style.display = "flex";
  modal.style.top = "0";
  modal.style.left = "0";
  modal.style.width = "100vw";
  modal.style.height = "100vh";
  modal.style.zIndex = "9999";

  preventBodyScroll(true);
}

function hideModal(modal) {
  if (!modal) return;
  modal.style.display = "none";
  preventBodyScroll(false);
}

// ============================================
// EMPLOYEE CODE GENERATOR
// ============================================
function generateEmployeeCode() {
  const apiPath = "../actions/karyawan/generate_code.php";

  fetch(apiPath)
    .then((res) => {
      if (!res.ok) {
        throw new Error("HTTP error! status: " + res.status);
      }
      return res.json();
    })
    .then((data) => {
      console.log("Generate code response:", data);

      if (data.success && data.code) {
        document.getElementById("add_employee_code").value = data.code;
      } else {
        console.error("Generate code failed:", data.message);
        document.getElementById("add_employee_code").value =
          data.code || "EMP001";

        if (typeof showNotification === "function") {
          showNotification(
            "warning",
            "Perhatian",
            "Menggunakan kode default. " + (data.message || ""),
            null
          );
        }
      }
    })
    .catch((err) => {
      console.error("Error generating code:", err);
      document.getElementById("add_employee_code").value = "EMP001";

      if (typeof showNotification === "function") {
        showNotification(
          "error",
          "Gagal Generate Kode",
          "Gagal membuat kode otomatis! Menggunakan kode default.",
          null
        );
      }
    });
}

// ============================================
// PASSWORD TOGGLE
// ============================================
function togglePassword(inputId, button) {
  const input = document.getElementById(inputId);
  const svg = button.querySelector("svg");

  if (!input || !svg) return;

  const isPassword = input.type === "password";
  input.type = isPassword ? "text" : "password";

  if (isPassword) {
    svg.innerHTML = `
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
      <circle cx="12" cy="12" r="3"></circle>
      <line x1="1" y1="1" x2="23" y2="23"></line>
    `;
  } else {
    svg.innerHTML = `
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
      <circle cx="12" cy="12" r="3"></circle>
    `;
  }

  svg.style.transform = "scale(0.85)";
  setTimeout(() => (svg.style.transform = "scale(1)"), 100);
}

// ============================================
// IMAGE PREVIEW WITH EDITOR
// ============================================
function previewImageWithEditor(input, previewId) {
  const preview = document.getElementById(previewId);
  if (!preview) {
    console.warn(`Preview element ${previewId} not found`);
    return;
  }

  const previewImg = preview.querySelector(".preview-image");
  if (!previewImg) {
    console.warn("Preview image element not found");
    return;
  }

  if (input.files && input.files[0]) {
    const file = input.files[0];

    // Validate file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
      if (typeof showNotification === "function") {
        showNotification(
          "error",
          "File Terlalu Besar",
          "Ukuran file maksimal 2MB",
          null
        );
      }
      input.value = "";
      return;
    }

    // Validate file type
    const allowedTypes = ["image/jpeg", "image/jpg", "image/png"];
    if (!allowedTypes.includes(file.type)) {
      if (typeof showNotification === "function") {
        showNotification(
          "error",
          "Format Tidak Valid",
          "Gunakan format JPG, JPEG, atau PNG",
          null
        );
      }
      input.value = "";
      return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      previewImg.src = e.target.result;
      previewImg.dataset.rotation = "0";
      previewImg.dataset.scale = "1";
      previewImg.dataset.flipH = "1";
      previewImg.dataset.flipV = "1";
      updateImageTransform(previewImg);
      preview.style.display = "block";
    };
    reader.readAsDataURL(file);
  }
}

// ============================================
// IMAGE TRANSFORM FUNCTIONS
// ============================================
function updateImageTransform(img) {
  const rotation = img.dataset.rotation || 0;
  const scale = img.dataset.scale || 1;
  const flipH = img.dataset.flipH || 1;
  const flipV = img.dataset.flipV || 1;

  img.style.transform = `rotate(${rotation}deg) scale(${scale * flipH}, ${
    scale * flipV
  })`;
}

function rotateImage(previewId, degrees) {
  const preview = document.getElementById(previewId);
  if (!preview) return;

  const img = preview.querySelector(".preview-image");
  if (!img) return;

  let currentRotation = parseInt(img.dataset.rotation) || 0;
  currentRotation += degrees;
  img.dataset.rotation = currentRotation;

  updateImageTransform(img);
}

function flipImage(previewId, direction) {
  const preview = document.getElementById(previewId);
  if (!preview) return;

  const img = preview.querySelector(".preview-image");
  if (!img) return;

  if (direction === "horizontal") {
    let flipH = parseInt(img.dataset.flipH) || 1;
    img.dataset.flipH = flipH * -1;
  } else if (direction === "vertical") {
    let flipV = parseInt(img.dataset.flipV) || 1;
    img.dataset.flipV = flipV * -1;
  }

  updateImageTransform(img);
}

function zoomImage(previewId, delta) {
  const preview = document.getElementById(previewId);
  if (!preview) return;

  const img = preview.querySelector(".preview-image");
  if (!img) return;

  let currentScale = parseFloat(img.dataset.scale) || 1;
  currentScale += delta;

  if (currentScale < 0.5) currentScale = 0.5;
  if (currentScale > 3) currentScale = 3;

  img.dataset.scale = currentScale;
  updateImageTransform(img);
}

function resetImage(previewId) {
  const preview = document.getElementById(previewId);
  if (!preview) return;

  const img = preview.querySelector(".preview-image");
  if (!img) return;

  img.dataset.rotation = "0";
  img.dataset.scale = "1";
  img.dataset.flipH = "1";
  img.dataset.flipV = "1";

  updateImageTransform(img);
}

function removePreviewEditor(inputId, previewId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);

  if (input) input.value = "";
  if (preview) preview.style.display = "none";
}

// ============================================
// ADD MODAL
// ============================================
function openAddModal() {
  console.log("Opening Add Modal...");

  const modal = document.getElementById("modalAdd");

  if (!modal) {
    console.error("Modal Add not found!");
    alert(
      "Error: Modal tidak ditemukan. Pastikan file add.php sudah di-include."
    );
    return;
  }

  // Reset form
  const form = modal.querySelector("form");
  if (form) {
    form.reset();

    // Reset photo preview
    const preview = document.getElementById("add_photo_preview");
    if (preview) preview.style.display = "none";

    // Set default values
    const joinDateInput = document.getElementById("add_join_date");
    if (joinDateInput) {
      joinDateInput.value = new Date().toISOString().split("T")[0];
    }

    const statusInput = document.getElementById("add_status");
    if (statusInput) {
      statusInput.value = "Non-Aktif";
    }

    // Reset password field
    const passwordInput = document.getElementById("add_password");
    if (passwordInput) {
      passwordInput.value = "";
      passwordInput.type = "password";
    }
  }

  // Generate employee code
  generateEmployeeCode();

  showModal(modal);

  setTimeout(() => {
    const firstInput = modal.querySelector("#add_name");
    if (firstInput) firstInput.focus();
  }, 100);

  console.log("Add Modal opened");
}

function closeAddModal() {
  console.log("Closing Add Modal...");
  const modal = document.getElementById("modalAdd");
  hideModal(modal);
}

// ============================================
// EDIT MODAL
// ============================================
function openEditModal(data) {
  console.log("Opening Edit Modal...");

  const modal = document.getElementById("modalEdit");

  if (!modal) {
    console.error("Modal Edit not found!");
    alert("Error: Modal Edit tidak ditemukan.");
    return;
  }

  const obj = typeof data === "string" ? JSON.parse(data) : data;
  console.log("Employee data:", obj);

  const fields = {
    edit_id: obj.id_employee,
    edit_employee_code: obj.employee_code || "",
    edit_name: obj.name || "",
    edit_phone: obj.phone || "",
    edit_email: obj.email || "",
    edit_address: obj.address || "",
    edit_birth_date: obj.birth_date || "",
    edit_emergency_contact: obj.emergency_contact || "",
    edit_base_salary: obj.base_salary || "0.00",
    edit_join_date: obj.join_date || "",
    edit_status: obj.status || "Aktif",
  };

  for (const [fieldId, value] of Object.entries(fields)) {
    const element = document.getElementById(fieldId);
    if (element) {
      element.value = value;
    }
  }

  // Handle roles
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

  // Show current photo
  if (obj.photo) {
    const photoPath = `../uploads/employee/${obj.photo}`;
    const currentPhotoImg = document.getElementById("edit_current_photo_img");
    const currentPhotoDiv = document.getElementById("edit_current_photo");

    if (currentPhotoImg && currentPhotoDiv) {
      currentPhotoImg.src = photoPath;
      currentPhotoDiv.style.display = "block";
    }
  }

  showModal(modal);
  console.log("Edit Modal opened");
}

function closeEditModal() {
  console.log("Closing Edit Modal...");
  const modal = document.getElementById("modalEdit");
  hideModal(modal);

  const preview = document.getElementById("edit_photo_preview");
  if (preview) preview.style.display = "none";
}

// ============================================
// DELETE MODAL
// ============================================
function openDeleteModal(id, name) {
  console.log("Opening Delete Modal...", { id, name });

  const modal = document.getElementById("modalDelete");

  if (!modal) {
    console.error("Modal Delete not found!");
    alert("Error: Modal Delete tidak ditemukan.");
    return;
  }

  const idField = document.getElementById("delete_id");
  if (idField) {
    idField.value = id;
  }

  const textElement = document.getElementById("delete_text");
  if (textElement) {
    textElement.textContent = `Yakin menghapus karyawan: ${name}?`;
  }

  showModal(modal);
  console.log("Delete Modal opened");
}

function closeDeleteModal() {
  console.log("Closing Delete Modal...");
  const modal = document.getElementById("modalDelete");
  hideModal(modal);
}

// ============================================
// DETAIL MODAL
// ============================================
function openDetailModal(data) {
  console.log("Opening Detail Modal...");

  const modal = document.getElementById("modalDetail");

  if (!modal) {
    console.error("Modal Detail not found!");
    alert("Error: Modal Detail tidak ditemukan.");
    return;
  }

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

  setElementText("detail_name", obj.name);
  setElementText("detail_code", `Kode: ${obj.employee_code}`);
  setElementText("detail_phone", `HP: ${obj.phone}`);
  setElementText("detail_join", `Bergabung: ${formatDate(obj.join_date)}`);

  const statusElement = document.getElementById("detail_status");
  if (statusElement) {
    statusElement.textContent = obj.status;
    statusElement.className = `status-badge status-${obj.status.toLowerCase()}`;
  }

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

  // Update detail content if function exists
  updateDetailModalContent(obj);

  switchTab("info");

  showModal(modal);
  console.log("Detail Modal opened");
}

function closeDetailModal() {
  console.log("Closing Detail Modal...");
  const modal = document.getElementById("modalDetail");
  hideModal(modal);
  currentEmployee = null;
}

// ============================================
// ATTENDANCE MODAL - UPDATED WITH AUTO SWITCH
// ============================================
// ============================================
// ATTENDANCE MODAL - FIXED VERSION
// ============================================
function openAttendanceModal(data) {
  console.log("Opening Attendance Modal...");

  const modal = document.getElementById("modalAttendance");

  if (!modal) {
    console.error("Modal Attendance not found!");
    alert("Error: Modal Attendance tidak ditemukan.");
    return;
  }

  const obj = typeof data === "string" ? JSON.parse(data) : data;
  console.log("Employee data:", obj);

  setElementText("attendance_name", obj.name);
  setElementText("attendance_code", `Kode: ${obj.employee_code}`);

  const photo = document.getElementById("attendance_photo");
  if (photo) {
    if (obj.photo) {
      photo.src = `../uploads/employee/${obj.photo}`;
    } else {
      const initial = obj.name.substring(0, 2).toUpperCase();
      photo.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(
        initial
      )}&size=300&background=0066cc&color=fff&bold=true`;
    }
  }

  const roles = obj.roles ? obj.roles.split(",").map((r) => r.trim()) : [];
  console.log("Employee roles:", roles);

  if (typeof startAttendanceClock === "function") {
    startAttendanceClock();
  }

  // Check attendance status
  fetch("../actions/attendance/check_status.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "employee_id=" + obj.id_employee,
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Attendance status received:", data);
      console.log("Current Status:", data.current_status);
      console.log("Has Checked In:", data.has_checked_in);
      console.log("Has Checked Out:", data.has_checked_out);

      const checkinSection = document.getElementById("checkin-section");
      const checkoutSection = document.getElementById("checkout-section");

      // CRITICAL LOGIC: Check status based on these conditions
      // 1. If status = "Aktif" AND has_checked_in = true AND has_checked_out = false -> SHOW CHECKOUT
      // 2. If has_checked_out = true -> ALREADY DONE FOR TODAY
      // 3. Otherwise -> SHOW CHECKIN

      if (data.has_checked_out) {
        // ========================================
        // CASE 1: ALREADY CHECKED OUT TODAY
        // ========================================
        console.log("Employee already checked out today");

        if (typeof showNotification === "function") {
          showNotification(
            "info",
            "Sudah Check Out",
            `Anda sudah melakukan check out hari ini!\n\nCheck In: ${data.check_in_time}\nCheck Out: ${data.check_out_time}\n\nStatus: Non-Aktif`,
            null
          );
        } else {
          alert(
            "Anda sudah melakukan check out hari ini!\n\nCheck In: " +
              data.check_in_time +
              "\nCheck Out: " +
              data.check_out_time +
              "\n\nStatus: Non-Aktif"
          );
        }

        // Close modal
        if (typeof closeAttendanceModal === "function") {
          closeAttendanceModal();
        }
        return;
      } else if (
        data.current_status === "Aktif" &&
        data.has_checked_in === true &&
        data.has_checked_out === false
      ) {
        // ========================================
        // CASE 2: EMPLOYEE IS ACTIVE - SHOW CHECK OUT FORM
        // ========================================
        console.log("Employee is ACTIVE - Show CHECK OUT form");

        if (checkinSection) checkinSection.style.display = "none";
        if (checkoutSection) checkoutSection.style.display = "block";

        const checkoutEmployeeId = document.getElementById(
          "checkout_employee_id"
        );
        if (checkoutEmployeeId) {
          checkoutEmployeeId.value = obj.id_employee;
        }

        // Populate work summary
        setElementText("summary_shoes", data.today_shoes || "0");
        setElementText("summary_duration", data.work_hours || "0");
        setElementText("summary_role", data.role_today || "-");
        setElementText("summary_score", data.today_score || "0");
      } else {
        // ========================================
        // CASE 3: NOT ACTIVE - SHOW CHECK IN FORM
        // ========================================
        console.log("Employee is NOT ACTIVE - Show CHECK IN form");

        if (checkinSection) checkinSection.style.display = "block";
        if (checkoutSection) checkoutSection.style.display = "none";

        const checkinEmployeeId = document.getElementById(
          "checkin_employee_id"
        );
        if (checkinEmployeeId) {
          checkinEmployeeId.value = obj.id_employee;
        }

        // Populate role select
        const roleSelect = document.getElementById("checkin_role");
        if (roleSelect) {
          roleSelect.innerHTML = '<option value="">-- Pilih Role --</option>';

          roles.forEach((role) => {
            const option = document.createElement("option");
            option.value = role.toLowerCase();
            option.textContent = role.charAt(0).toUpperCase() + role.slice(1);
            roleSelect.appendChild(option);
          });

          if (roles.length === 1) {
            roleSelect.value = roles[0].toLowerCase();
            console.log("Auto-selected single role:", roles[0]);
          }
        }
      }

      showModal(modal);
      console.log("Attendance Modal opened");
    })
    .catch((error) => {
      console.error("Error checking attendance status:", error);
      alert("Gagal memeriksa status absensi. Coba lagi.");
    });
}

function closeAttendanceModal() {
  console.log("Closing Attendance Modal...");

  const modal = document.getElementById("modalAttendance");
  hideModal(modal);

  if (typeof stopAttendanceClock === "function") {
    stopAttendanceClock();
  }

  const formCheckIn = document.getElementById("formCheckIn");
  const formCheckOut = document.getElementById("formCheckOut");

  if (formCheckIn) formCheckIn.reset();
  if (formCheckOut) formCheckOut.reset();

  const checkinSection = document.getElementById("checkin-section");
  const checkoutSection = document.getElementById("checkout-section");

  if (checkinSection) checkinSection.style.display = "block";
  if (checkoutSection) checkoutSection.style.display = "none";

  const alertDiv = document.getElementById("attendance_alert");
  if (alertDiv) alertDiv.style.display = "none";

  console.log("Attendance Modal closed");
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function switchTab(tabName) {
  console.log("Switching to tab:", tabName);

  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.classList.remove("active");
  });

  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.classList.remove("active");
  });

  const selectedTab = document.getElementById(`tab-${tabName}`);
  if (selectedTab) {
    selectedTab.classList.add("active");
  }

  document.querySelectorAll(".tab-btn").forEach((btn) => {
    const btnText = btn.textContent.toLowerCase();
    if (btnText.includes(tabName)) {
      btn.classList.add("active");
    }
  });
}

function setElementText(id, text) {
  const element = document.getElementById(id);
  if (element) {
    element.textContent = text;
  } else {
    console.warn(`Element ${id} not found`);
  }
}

function formatDate(dateString) {
  if (!dateString) return "-";

  const date = new Date(dateString);
  const options = { year: "numeric", month: "long", day: "numeric" };

  return date.toLocaleDateString("id-ID", options);
}

function formatCurrency(amount) {
  return (
    "Rp " +
    parseFloat(amount).toLocaleString("id-ID", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}

function updateDetailModalContent(employee) {
  console.log("Updating detail modal with:", employee);

  // Info tab - Basic Info
  setElementText("info_id", employee.id_employee || "-");
  setElementText("info_code", employee.employee_code || "-");
  setElementText("info_name", employee.name || "-");
  setElementText("info_phone", employee.phone || "-");
  setElementText("info_email", employee.email || "-");

  // Status & Role
  setElementText("info_status", employee.status || "-");
  setElementText("info_join", formatDate(employee.join_date) || "-");
  setElementText(
    "info_birth",
    employee.birth_date ? formatDate(employee.birth_date) : "-"
  );
  setElementText(
    "info_roles",
    employee.roles ? employee.roles.replace(/,/g, ", ") : "-"
  );

  // Address & Contact
  setElementText("info_address", employee.address || "-");
  setElementText("info_emergency", employee.emergency_contact || "-");

  // Financial
  const salary = employee.base_salary || 0;
  setElementText("info_salary", formatCurrency(salary));

  // Performance tab
  setElementText("perf_today_shoes", employee.today_shoes || "0");
  setElementText("perf_today_score", (employee.today_score || "0") + "%");
}

// ============================================
// EVENT LISTENERS
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  console.log("Initializing modal event listeners...");

  // Close modal on backdrop click
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("click", function (e) {
      if (e.target === this) {
        console.log("Backdrop clicked, closing modal");
        hideModal(this);

        if (
          this.id === "modalAttendance" &&
          typeof stopAttendanceClock === "function"
        ) {
          stopAttendanceClock();
        }
      }
    });
  });

  // Close modal on ESC key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      const openModals = document.querySelectorAll(
        '.modal[style*="display: flex"], .modal[style*="display:flex"]'
      );

      openModals.forEach((modal) => {
        console.log("Escape pressed, closing modal");
        hideModal(modal);

        if (
          modal.id === "modalAttendance" &&
          typeof stopAttendanceClock === "function"
        ) {
          stopAttendanceClock();
        }
      });
    }
  });

  console.log("Modal event listeners initialized");
});

// ============================================
// EXPOSE TO GLOBAL SCOPE
// ============================================
window.openAddModal = openAddModal;
window.closeAddModal = closeAddModal;
window.openEditModal = openEditModal;
window.closeEditModal = closeEditModal;
window.openDeleteModal = openDeleteModal;
window.closeDeleteModal = closeDeleteModal;
window.openDetailModal = openDetailModal;
window.closeDetailModal = closeDetailModal;
window.openAttendanceModal = openAttendanceModal;
window.closeAttendanceModal = closeAttendanceModal;
window.switchTab = switchTab;
window.setElementText = setElementText;
window.formatDate = formatDate;
window.formatCurrency = formatCurrency;
window.generateEmployeeCode = generateEmployeeCode;
window.togglePassword = togglePassword;
window.previewImageWithEditor = previewImageWithEditor;
window.updateImageTransform = updateImageTransform;
window.rotateImage = rotateImage;
window.flipImage = flipImage;
window.zoomImage = zoomImage;
window.resetImage = resetImage;
window.removePreviewEditor = removePreviewEditor;
window.updateDetailModalContent = updateDetailModalContent;

console.log("Modal.js loaded successfully");
