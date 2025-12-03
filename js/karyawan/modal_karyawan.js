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
