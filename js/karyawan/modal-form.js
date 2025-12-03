/**
 * =============================================
 * FILE: js/karyawan/modal-form.js
 * DESKRIPSI: Form Handler untuk Add, Edit, Delete Employee
 * ============================================= */

// ============================================
// FORM INITIALIZATION
// ============================================
function initializeFormHandlers() {
  console.log("Initializing form handlers...");

  initializeAddForm();
  initializeEditForm();
  initializeDeleteForm();

  console.log("All form handlers initialized");
}

// ============================================
// ADD FORM HANDLER
// ============================================
function initializeAddForm() {
  const addForm = document.querySelector("#modalAdd form");

  if (!addForm) {
    console.warn("Add form not found");
    return;
  }

  // Remove existing listeners
  const newAddForm = addForm.cloneNode(true);
  addForm.parentNode.replaceChild(newAddForm, addForm);

  newAddForm.addEventListener("submit", function (e) {
    e.preventDefault();
    console.log("Add form submitted");

    // Validate roles
    const roles = this.querySelectorAll('input[name="roles[]"]:checked');
    if (roles.length === 0) {
      showNotification(
        "warning",
        "Peringatan!",
        "Pilih minimal 1 role untuk karyawan!",
        null // No callback, just close notification
      );
      return;
    }

    // Validate password
    const password = this.querySelector('input[name="password"]');
    if (!password || !password.value || password.value.trim() === "") {
      showNotification(
        "warning",
        "Password Kosong",
        "Password wajib diisi!",
        null
      );
      return;
    }

    if (password.value.length < 6) {
      showNotification(
        "warning",
        "Password Tidak Valid",
        "Password minimal 6 karakter!",
        null
      );
      return;
    }

    // Button loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : "";

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = "Menyimpan...";
    }

    const formData = new FormData(this);

    fetch("../actions/karyawan/add.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          console.log("Add success:", data);

          // Show success notification with auto-reload callback
          showNotification(
            "success",
            "Berhasil!",
            data.message || "Karyawan berhasil ditambahkan!",
            function () {
              closeAddModal();
              window.location.reload();
            }
          );
        } else {
          console.error("Add failed:", data.message);

          // Show error notification with auto-reload callback
          showNotification(
            "error",
            "Gagal!",
            data.message || "Gagal menambahkan karyawan",
            function () {
              window.location.reload();
            }
          );

          // Reset button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);

        // Show error notification with auto-reload callback
        showNotification(
          "error",
          "Terjadi Kesalahan",
          "Terjadi kesalahan saat menambahkan karyawan",
          function () {
            window.location.reload();
          }
        );

        // Reset button
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      });
  });

  console.log("Add form handler initialized");
}

// ============================================
// EDIT FORM HANDLER
// ============================================
function initializeEditForm() {
  const editForm = document.querySelector("#modalEdit form");

  if (!editForm) {
    console.warn("Edit form not found");
    return;
  }

  // Remove existing listeners
  const newEditForm = editForm.cloneNode(true);
  editForm.parentNode.replaceChild(newEditForm, editForm);

  newEditForm.addEventListener("submit", function (e) {
    e.preventDefault();
    console.log("Edit form submitted");

    // Validate roles
    const roles = this.querySelectorAll('input[name="roles[]"]:checked');
    if (roles.length === 0) {
      showNotification(
        "warning",
        "Peringatan!",
        "Pilih minimal 1 role untuk karyawan!",
        null
      );
      return;
    }

    // Button loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : "";

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = "Menyimpan...";
    }

    const formData = new FormData(this);

    fetch("../actions/karyawan/edit.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          console.log("Edit success:", data);

          // Show success notification with auto-reload callback
          showNotification(
            "success",
            "Berhasil!",
            data.message || "Data karyawan berhasil diupdate!",
            function () {
              closeEditModal();
              window.location.reload();
            }
          );
        } else {
          console.error("Edit failed:", data.message);

          // Show error notification with auto-reload callback
          showNotification(
            "error",
            "Gagal!",
            data.message || "Gagal mengupdate karyawan",
            function () {
              window.location.reload();
            }
          );

          // Reset button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);

        // Show error notification with auto-reload callback
        showNotification(
          "error",
          "Terjadi Kesalahan",
          "Terjadi kesalahan saat mengupdate karyawan",
          function () {
            window.location.reload();
          }
        );

        // Reset button
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      });
  });

  console.log("Edit form handler initialized");
}

// ============================================
// DELETE FORM HANDLER
// ============================================
function initializeDeleteForm() {
  const deleteForm = document.querySelector("#modalDelete form");

  if (!deleteForm) {
    console.warn("Delete form not found");
    return;
  }

  // Remove existing listeners
  const newDeleteForm = deleteForm.cloneNode(true);
  deleteForm.parentNode.replaceChild(newDeleteForm, deleteForm);

  newDeleteForm.addEventListener("submit", function (e) {
    e.preventDefault();
    console.log("Delete form submitted");

    // Button loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : "";

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = "Menghapus...";
    }

    const formData = new FormData(this);

    fetch("../actions/karyawan/delete.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          console.log("Delete success:", data);

          // Show success notification with auto-reload callback
          showNotification(
            "success",
            "Berhasil!",
            data.message || "Karyawan berhasil dihapus!",
            function () {
              closeDeleteModal();
              window.location.reload();
            }
          );
        } else {
          console.error("Delete failed:", data.message);

          // Show error notification with auto-reload callback
          showNotification(
            "error",
            "Gagal!",
            data.message || "Gagal menghapus karyawan",
            function () {
              window.location.reload();
            }
          );

          // Reset button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);

        // Show error notification with auto-reload callback
        showNotification(
          "error",
          "Terjadi Kesalahan",
          "Terjadi kesalahan saat menghapus karyawan",
          function () {
            window.location.reload();
          }
        );

        // Reset button
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      });
  });

  console.log("Delete form handler initialized");
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function togglePassword(inputId, button) {
  const input = document.getElementById(inputId);
  const svg = button.querySelector("svg");

  if (!input || !svg) return;

  const isPassword = input.type === "password";
  input.type = isPassword ? "text" : "password";

  // Update SVG icon
  if (isPassword) {
    // Eye-off (with slash)
    svg.innerHTML = `
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
      <circle cx="12" cy="12" r="3"></circle>
      <line x1="1" y1="1" x2="23" y2="23"></line>
    `;
  } else {
    // Eye-on
    svg.innerHTML = `
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
      <circle cx="12" cy="12" r="3"></circle>
    `;
  }

  svg.style.transform = "scale(0.85)";
  setTimeout(() => (svg.style.transform = "scale(1)"), 100);
}

function previewImage(input, previewId) {
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

    // Validate file size
    if (file.size > 2 * 1024 * 1024) {
      showNotification(
        "error",
        "File Terlalu Besar",
        "Ukuran file maksimal 2MB",
        null
      );
      input.value = "";
      return;
    }

    // Validate file type
    const allowedTypes = ["image/jpeg", "image/jpg", "image/png"];
    if (!allowedTypes.includes(file.type)) {
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

    reader.readAsDataURL(file);
  }
}

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

  // Limit zoom range
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

function removePreview(inputId, previewId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);

  if (input) input.value = "";
  if (preview) preview.style.display = "none";
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

// ============================================
// INITIALIZE ON DOM LOAD
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  console.log("Initializing modal-form.js...");
  initializeFormHandlers();
  console.log("Modal-form.js initialized successfully");
});

// ============================================
// EXPOSE TO GLOBAL SCOPE
// ============================================
window.togglePassword = togglePassword;
window.previewImage = previewImage;
window.removePreview = removePreview;
window.formatCurrency = formatCurrency;
window.initializeFormHandlers = initializeFormHandlers;
window.updateImageTransform = updateImageTransform;
window.rotateImage = rotateImage;
window.flipImage = flipImage;
window.zoomImage = zoomImage;
window.resetImage = resetImage;
