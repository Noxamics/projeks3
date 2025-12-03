/**
 * =============================================
 * FILE: js/karyawan/modal-form.js
 * DESKRIPSI: Form Handler untuk Add, Edit, Delete Employee
 * ============================================= */

// ============================================
// ADD FORM HANDLER
// ============================================
function initializeAddForm() {
  const addForm = document.querySelector("#modalAdd form");

  if (!addForm) {
    console.warn("Add form not found");
    return;
  }

  // Remove existing listeners by cloning
  const newAddForm = addForm.cloneNode(true);
  addForm.parentNode.replaceChild(newAddForm, addForm);

  newAddForm.addEventListener("submit", function (e) {
    e.preventDefault();
    console.log("Add form submitted");

    // Validate roles
    const roles = this.querySelectorAll('input[name="roles[]"]:checked');
    if (roles.length === 0) {
      if (typeof showNotification === "function") {
        showNotification(
          "warning",
          "Peringatan!",
          "Pilih minimal 1 role untuk karyawan!",
          null
        );
      } else {
        alert("Pilih minimal 1 role untuk karyawan!");
      }
      return;
    }

    // Validate password
    const password = this.querySelector('input[name="password"]');
    if (!password || !password.value || password.value.trim() === "") {
      if (typeof showNotification === "function") {
        showNotification(
          "warning",
          "Password Kosong",
          "Password wajib diisi!",
          null
        );
      } else {
        alert("Password wajib diisi!");
      }
      return;
    }

    if (password.value.length < 6) {
      if (typeof showNotification === "function") {
        showNotification(
          "warning",
          "Password Tidak Valid",
          "Password minimal 6 karakter!",
          null
        );
      } else {
        alert("Password minimal 6 karakter!");
      }
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

          if (typeof showNotification === "function") {
            showNotification(
              "success",
              "Berhasil!",
              data.message || "Karyawan berhasil ditambahkan!",
              function () {
                if (typeof closeAddModal === "function") {
                  closeAddModal();
                }
                window.location.reload();
              }
            );
          } else {
            alert(data.message || "Karyawan berhasil ditambahkan!");
            window.location.reload();
          }
        } else {
          console.error("Add failed:", data.message);

          if (typeof showNotification === "function") {
            showNotification(
              "error",
              "Gagal!",
              data.message || "Gagal menambahkan karyawan",
              null
            );
          } else {
            alert(data.message || "Gagal menambahkan karyawan");
          }

          // Reset button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);

        if (typeof showNotification === "function") {
          showNotification(
            "error",
            "Terjadi Kesalahan",
            "Terjadi kesalahan saat menambahkan karyawan",
            null
          );
        } else {
          alert("Terjadi kesalahan saat menambahkan karyawan");
        }

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

  // Remove existing listeners by cloning
  const newEditForm = editForm.cloneNode(true);
  editForm.parentNode.replaceChild(newEditForm, editForm);

  newEditForm.addEventListener("submit", function (e) {
    e.preventDefault();
    console.log("Edit form submitted");

    // Validate roles
    const roles = this.querySelectorAll('input[name="roles[]"]:checked');
    if (roles.length === 0) {
      if (typeof showNotification === "function") {
        showNotification(
          "warning",
          "Peringatan!",
          "Pilih minimal 1 role untuk karyawan!",
          null
        );
      } else {
        alert("Pilih minimal 1 role untuk karyawan!");
      }
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

          if (typeof showNotification === "function") {
            showNotification(
              "success",
              "Berhasil!",
              data.message || "Data karyawan berhasil diupdate!",
              function () {
                if (typeof closeEditModal === "function") {
                  closeEditModal();
                }
                window.location.reload();
              }
            );
          } else {
            alert(data.message || "Data karyawan berhasil diupdate!");
            window.location.reload();
          }
        } else {
          console.error("Edit failed:", data.message);

          if (typeof showNotification === "function") {
            showNotification(
              "error",
              "Gagal!",
              data.message || "Gagal mengupdate karyawan",
              null
            );
          } else {
            alert(data.message || "Gagal mengupdate karyawan");
          }

          // Reset button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);

        if (typeof showNotification === "function") {
          showNotification(
            "error",
            "Terjadi Kesalahan",
            "Terjadi kesalahan saat mengupdate karyawan",
            null
          );
        } else {
          alert("Terjadi kesalahan saat mengupdate karyawan");
        }

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

  // Remove existing listeners by cloning
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

          if (typeof showNotification === "function") {
            showNotification(
              "success",
              "Berhasil!",
              data.message || "Karyawan berhasil dihapus!",
              function () {
                if (typeof closeDeleteModal === "function") {
                  closeDeleteModal();
                }
                window.location.reload();
              }
            );
          } else {
            alert(data.message || "Karyawan berhasil dihapus!");
            window.location.reload();
          }
        } else {
          console.error("Delete failed:", data.message);

          if (typeof showNotification === "function") {
            showNotification(
              "error",
              "Gagal!",
              data.message || "Gagal menghapus karyawan",
              null
            );
          } else {
            alert(data.message || "Gagal menghapus karyawan");
          }

          // Reset button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);

        if (typeof showNotification === "function") {
          showNotification(
            "error",
            "Terjadi Kesalahan",
            "Terjadi kesalahan saat menghapus karyawan",
            null
          );
        } else {
          alert("Terjadi kesalahan saat menghapus karyawan");
        }

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
// INITIALIZE ALL FORM HANDLERS
// ============================================
function initializeFormHandlers() {
  console.log("Initializing form handlers...");

  initializeAddForm();
  initializeEditForm();
  initializeDeleteForm();

  console.log("All form handlers initialized");
}

// ============================================
// AUTO-INITIALIZE ON DOM LOAD
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  console.log("Initializing modal-form.js...");
  initializeFormHandlers();
  console.log("Modal-form.js initialized successfully");
});

// ============================================
// EXPOSE TO GLOBAL SCOPE
// ============================================
window.initializeFormHandlers = initializeFormHandlers;

console.log("Modal-form.js loaded successfully");
