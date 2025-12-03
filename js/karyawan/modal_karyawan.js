/**
 * =============================================
 * FILE: js/karyawan/modal_karyawan.js
 * DESKRIPSI: Modal Utilities & Employee Code Generator
 * ============================================= */

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

        showNotification(
          "warning",
          "Perhatian",
          "Menggunakan kode default. " + (data.message || ""),
          null
        );
      }
    })
    .catch((err) => {
      console.error("Error generating code:", err);
      document.getElementById("add_employee_code").value = "EMP001";

      showNotification(
        "error",
        "Gagal Generate Kode",
        "Gagal membuat kode otomatis! Menggunakan kode default.",
        null
      );
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
  const previewImg = preview.querySelector(".preview-image");

  if (input.files && input.files[0]) {
    if (input.files[0].size > 2 * 1024 * 1024) {
      showNotification(
        "error",
        "File Terlalu Besar",
        "Ukuran file maksimal 2MB",
        null
      );
      input.value = "";
      return;
    }

    const allowedTypes = ["image/jpeg", "image/jpg", "image/png"];
    if (!allowedTypes.includes(input.files[0].type)) {
      showNotification(
        "error",
        "Format Tidak Valid",
        "Gunakan format JPG, JPEG, atau PNG",
        null
      );
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
    reader.readAsDataURL(input.files[0]);
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
  const img = preview.querySelector(".preview-image");

  let currentRotation = parseInt(img.dataset.rotation) || 0;
  currentRotation += degrees;
  img.dataset.rotation = currentRotation;

  updateImageTransform(img);
}

function flipImage(previewId, direction) {
  const preview = document.getElementById(previewId);
  const img = preview.querySelector(".preview-image");

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
  const img = preview.querySelector(".preview-image");

  let currentScale = parseFloat(img.dataset.scale) || 1;
  currentScale += delta;

  if (currentScale < 0.5) currentScale = 0.5;
  if (currentScale > 3) currentScale = 3;

  img.dataset.scale = currentScale;
  updateImageTransform(img);
}

function resetImage(previewId) {
  const preview = document.getElementById(previewId);
  const img = preview.querySelector(".preview-image");

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

/**
 * =============================================
 * FILE: js/karyawan/modal_karyawan.js
 * DESKRIPSI: Modal Functions for Employee Management
 * ============================================= */

// ============================================
// OPEN ATTENDANCE MODAL - WITH CHECK-IN STATUS DETECTION
// ============================================
function openAttendanceModal(employeeData) {
  const modal = document.getElementById("modalAttendance");
  if (!modal) {
    console.error("Attendance modal not found");
    return;
  }

  console.log("Opening attendance modal for:", employeeData);

  // Populate employee info
  const photoEl = document.getElementById("attendance_photo");
  const nameEl = document.getElementById("attendance_name");
  const codeEl = document.getElementById("attendance_code");

  if (photoEl) {
    photoEl.src =
      employeeData.photo ||
      `https://ui-avatars.com/api/?name=${encodeURIComponent(
        employeeData.name.substring(0, 2)
      )}&size=400&background=6c5ce7&color=fff&bold=true`;
  }

  if (nameEl) nameEl.textContent = employeeData.name;
  if (codeEl) codeEl.textContent = employeeData.employee_code;

  // Check if employee has checked in today
  fetch(
    `../actions/attendance/check_status.php?employee_id=${employeeData.id_employee}`
  )
    .then((response) => response.json())
    .then((data) => {
      console.log("Check-in status:", data);

      const checkinSection = document.getElementById("checkin-section");
      const checkoutSection = document.getElementById("checkout-section");

      if (data.has_checked_in) {
        // Show check-out section
        if (checkinSection) checkinSection.style.display = "none";
        if (checkoutSection) checkoutSection.style.display = "block";

        // Populate work summary
        document.getElementById("summary_shoes").textContent =
          data.summary?.shoes_completed || 0;
        document.getElementById("summary_duration").textContent =
          data.summary?.work_hours || 0;
        document.getElementById("summary_role").textContent =
          data.summary?.role_today || "-";
        document.getElementById("summary_score").textContent =
          data.summary?.score || 0;

        // Set employee ID for checkout
        document.getElementById("checkout_employee_id").value =
          employeeData.id_employee;
      } else {
        // Show check-in section
        if (checkinSection) checkinSection.style.display = "block";
        if (checkoutSection) checkoutSection.style.display = "none";

        // Set employee ID for checkin
        document.getElementById("checkin_employee_id").value =
          employeeData.id_employee;

        // Populate roles dropdown
        populateRolesDropdown(employeeData);
      }

      // Show modal
      modal.style.display = "flex";

      // Start clock
      if (typeof startAttendanceClock === "function") {
        startAttendanceClock();
      }
    })
    .catch((error) => {
      console.error("Error checking attendance status:", error);
      showNotification(
        "error",
        "Error",
        "Gagal memeriksa status attendance",
        null
      );
    });
}

// ============================================
// POPULATE ROLES DROPDOWN
// ============================================
function populateRolesDropdown(employeeData) {
  const roleSelect = document.getElementById("checkin_role");
  if (!roleSelect) return;

  // Clear existing options except placeholder
  roleSelect.innerHTML = '<option value="">-- Pilih Role --</option>';

  // Get roles from employee data
  let roles = [];
  if (employeeData.roles) {
    roles = employeeData.roles.split(",").map((role) => role.trim());
  } else {
    roles = ["cleaning"]; // Default role
  }

  console.log("Available roles:", roles);

  // Add role options
  roles.forEach((role) => {
    const option = document.createElement("option");
    option.value = role.toLowerCase();
    option.textContent = role.charAt(0).toUpperCase() + role.slice(1);
    roleSelect.appendChild(option);
  });
}

// ============================================
// CLOSE ATTENDANCE MODAL
// ============================================
function closeAttendanceModal() {
  const modal = document.getElementById("modalAttendance");
  if (!modal) return;

  modal.style.display = "none";

  // Stop clock
  if (typeof stopAttendanceClock === "function") {
    stopAttendanceClock();
  }

  // Reset forms
  const checkinForm = document.getElementById("formCheckIn");
  const checkoutForm = document.getElementById("formCheckOut");

  if (checkinForm) checkinForm.reset();
  if (checkoutForm) checkoutForm.reset();
}

// ============================================
// CLOSE MODAL ON OUTSIDE CLICK
// ============================================
window.onclick = function (event) {
  const attendanceModal = document.getElementById("modalAttendance");

  if (event.target === attendanceModal) {
    closeAttendanceModal();
  }
};

// ============================================
// OTHER MODAL FUNCTIONS
// ============================================
function openAddModal() {
  const modal = document.getElementById("modalAdd");
  if (modal) {
    modal.style.display = "flex";
  }
}

function closeAddModal() {
  const modal = document.getElementById("modalAdd");
  if (modal) {
    modal.style.display = "none";
    const form = document.getElementById("formAddEmployee");
    if (form) form.reset();
  }
}

function openEditModal(employeeData) {
  console.log("Opening edit modal for:", employeeData);
  const modal = document.getElementById("modalEdit");
  if (!modal) return;

  // Populate form fields
  document.getElementById("edit_employee_id").value = employeeData.id_employee;
  document.getElementById("edit_name").value = employeeData.name;
  document.getElementById("edit_phone").value = employeeData.phone;
  document.getElementById("edit_address").value = employeeData.address || "";
  document.getElementById("edit_status").value = employeeData.status;

  // Handle roles checkboxes
  const roles = employeeData.roles ? employeeData.roles.split(",") : [];
  document.querySelectorAll('input[name="roles[]"]').forEach((checkbox) => {
    checkbox.checked = roles.includes(checkbox.value);
  });

  modal.style.display = "flex";
}

function closeEditModal() {
  const modal = document.getElementById("modalEdit");
  if (modal) {
    modal.style.display = "none";
  }
}

function openDeleteModal(employeeId, employeeName) {
  const modal = document.getElementById("modalDelete");
  if (!modal) return;

  document.getElementById("delete_employee_id").value = employeeId;
  document.getElementById("delete_employee_name").textContent = employeeName;

  modal.style.display = "flex";
}

function closeDeleteModal() {
  const modal = document.getElementById("modalDelete");
  if (modal) {
    modal.style.display = "none";
  }
}

function openDetailModal(employeeData) {
  console.log("Opening detail modal for:", employeeData);
  const modal = document.getElementById("modalDetail");
  if (!modal) return;

  // Populate detail fields
  document.getElementById("detail_photo").src =
    employeeData.photo ||
    `https://ui-avatars.com/api/?name=${encodeURIComponent(
      employeeData.name.substring(0, 2)
    )}&size=400`;
  document.getElementById("detail_name").textContent = employeeData.name;
  document.getElementById("detail_code").textContent =
    employeeData.employee_code;
  document.getElementById("detail_phone").textContent = employeeData.phone;
  document.getElementById("detail_address").textContent =
    employeeData.address || "-";
  document.getElementById("detail_join_date").textContent =
    employeeData.join_date;
  document.getElementById("detail_status").textContent = employeeData.status;

  const rolesContainer = document.getElementById("detail_roles");
  if (rolesContainer) {
    const roles = employeeData.roles
      ? employeeData.roles.split(",")
      : ["cleaning"];
    rolesContainer.innerHTML = roles
      .map(
        (role) =>
          `<span class="role-badge role-${role.trim().toLowerCase()}">${
            role.trim().charAt(0).toUpperCase() + role.trim().slice(1)
          }</span>`
      )
      .join("");
  }

  modal.style.display = "flex";
}

function closeDetailModal() {
  const modal = document.getElementById("modalDetail");
  if (modal) {
    modal.style.display = "none";
  }
}

// ============================================
// EXPOSE TO GLOBAL SCOPE
// ============================================
window.generateEmployeeCode = generateEmployeeCode;
window.togglePassword = togglePassword;
window.previewImageWithEditor = previewImageWithEditor;
window.updateImageTransform = updateImageTransform;
window.rotateImage = rotateImage;
window.flipImage = flipImage;
window.zoomImage = zoomImage;
window.resetImage = resetImage;
window.removePreviewEditor = removePreviewEditor;

console.log("Modal-karyawan.js loaded successfully");
