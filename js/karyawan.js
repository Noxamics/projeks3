// ===========================
// EMPLOYEE DUAL MODE MANAGEMENT
// ===========================

// View Switching
function switchView(viewType) {
  // Update toggle buttons
  document.querySelectorAll(".toggle-btn").forEach((btn) => {
    btn.classList.remove("active");
  });
  document.querySelector(`[data-view="${viewType}"]`).classList.add("active");

  // Update view content
  document.querySelectorAll(".view-content").forEach((content) => {
    content.classList.remove("active");
  });

  if (viewType === "card") {
    document.getElementById("cardView").classList.add("active");
  } else {
    document.getElementById("tableView").classList.add("active");
  }

  // Save preference to localStorage
  localStorage.setItem("preferredView", viewType);
}

// Load saved view preference
document.addEventListener("DOMContentLoaded", function () {
  const savedView = localStorage.getItem("preferredView") || "card";
  switchView(savedView);
});

// Modal Controls
function openAddModal() {
  document.getElementById("modalAdd").style.display = "flex";
}

function closeAddModal() {
  document.getElementById("modalAdd").style.display = "none";
}

function openEditModal(data) {
  let obj = typeof data === "string" ? JSON.parse(data) : data;
  document.getElementById("edit_id").value = obj.id_employee;
  document.getElementById("edit_employee_code").value = obj.employee_code || "";
  document.getElementById("edit_name").value = obj.name || "";
  document.getElementById("edit_phone").value = obj.phone || "";
  document.getElementById("edit_join_date").value = obj.join_date || "";
  document.getElementById("edit_status").value = obj.status || "Aktif";
  document.getElementById("modalEdit").style.display = "flex";
}

function closeEditModal() {
  document.getElementById("modalEdit").style.display = "none";
}

function openDeleteModal(id, name) {
  document.getElementById("delete_id").value = id;
  document.getElementById("delete_text").innerText =
    "Yakin menghapus: " + name + " ?";
  document.getElementById("modalDelete").style.display = "flex";
}

function closeDeleteModal() {
  document.getElementById("modalDelete").style.display = "none";
}

// Detail Modal with Attendance
let currentEmployee = null;

function openDetailModal(data) {
  let obj = typeof data === "string" ? JSON.parse(data) : data;
  currentEmployee = obj;

  // Set employee info with fallback
  let photoPath;
  if (obj.photo) {
    photoPath = `../uploads/employee/${obj.photo}`;
  } else {
    const initial = obj.name.substring(0, 2).toUpperCase();
    photoPath = `https://ui-avatars.com/api/?name=${encodeURIComponent(
      initial
    )}&size=300&background=6c5ce7&color=fff&bold=true&rounded=true`;
  }
  document.getElementById("detail_photo").src = photoPath;
  document.getElementById("detail_name").innerText = obj.name;
  document.getElementById(
    "detail_code"
  ).innerText = `Kode: ${obj.employee_code}`;
  document.getElementById("detail_phone").innerText = `HP: ${obj.phone}`;
  document.getElementById("detail_join").innerText = `Bergabung: ${formatDate(
    obj.join_date
  )}`;
  document.getElementById("detail_status").innerText = obj.status;
  document.getElementById(
    "detail_status"
  ).className = `status-badge status-${obj.status.toLowerCase()}`;

  // Set employee ID for attendance form
  document.getElementById("attendance_employee_id").value = obj.id_employee;

  // Load attendance history
  loadAttendanceHistory(obj.id_employee);

  // Update time display
  updateTimeDisplay();
  setInterval(updateTimeDisplay, 1000);

  // Reset to first tab
  switchTab("info");

  document.getElementById("modalDetail").style.display = "flex";
}

function closeDetailModal() {
  document.getElementById("modalDetail").style.display = "none";
  currentEmployee = null;
}

// Tab switching
function switchTab(tabName) {
  // Hide all tabs
  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.classList.remove("active");
  });

  // Remove active from all buttons
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.classList.remove("active");
  });

  // Show selected tab
  document.getElementById(`tab-${tabName}`).classList.add("active");
  event.target.classList.add("active");
}

// Format date helper
function formatDate(dateStr) {
  if (!dateStr) return "-";
  const date = new Date(dateStr);
  const options = { year: "numeric", month: "long", day: "numeric" };
  return date.toLocaleDateString("id-ID", options);
}

// Update time display
function updateTimeDisplay() {
  const now = new Date();
  const timeStr = now.toLocaleTimeString("id-ID");
  const timeDisplay = document.getElementById("current_time");
  if (timeDisplay) {
    timeDisplay.innerText = timeStr;
  }
}

// Load attendance history
function loadAttendanceHistory(employeeId) {
  fetch(`../actions/karyawan/get_attendance.php?id=${employeeId}`)
    .then((r) => r.json())
    .then((data) => {
      const tbody = document.getElementById("attendance_history_body");
      tbody.innerHTML = "";

      if (data.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="4" style="text-align:center; color:#636e72;">Belum ada data absensi</td></tr>';
        return;
      }

      data.forEach((row) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
                    <td>${row.date}</td>
                    <td><strong>${row.check_in || "-"}</strong></td>
                    <td><strong>${row.check_out || "-"}</strong></td>
                    <td><span class="status-badge status-${row.status.toLowerCase()}">${
          row.status
        }</span></td>
                `;
        tbody.appendChild(tr);
      });
    })
    .catch((err) => {
      console.error("Error loading attendance:", err);
      const tbody = document.getElementById("attendance_history_body");
      tbody.innerHTML =
        '<tr><td colspan="4" style="text-align:center; color:#e74c3c;">Error memuat data absensi</td></tr>';
    });
}

// Check In
function checkIn() {
  const password = document.getElementById("checkin_password").value;
  const employeeId = document.getElementById("attendance_employee_id").value;

  if (!password) {
    showAlert("Masukkan password untuk check in", "error");
    return;
  }

  const formData = new FormData();
  formData.append("employee_id", employeeId);
  formData.append("password", password);
  formData.append("action", "checkin");

  fetch("../actions/karyawan/attendance.php", {
    method: "POST",
    body: formData,
  })
    .then((r) => r.text())
    .then((txt) => {
      txt = txt.trim();
      if (txt === "success") {
        showAlert("✅ Check in berhasil!", "success");
        document.getElementById("checkin_password").value = "";
        loadAttendanceHistory(employeeId);
      } else if (txt === "error:password") {
        showAlert("❌ Password salah!", "error");
      } else if (txt === "error:already") {
        showAlert("⚠️ Anda sudah check in hari ini!", "error");
      } else {
        showAlert("❌ Gagal check in: " + txt, "error");
      }
    })
    .catch((err) => {
      showAlert("❌ Error: " + err.message, "error");
    });
}

// Check Out
function checkOut() {
  const password = document.getElementById("checkout_password").value;
  const employeeId = document.getElementById("attendance_employee_id").value;

  if (!password) {
    showAlert("Masukkan password untuk check out", "error");
    return;
  }

  const formData = new FormData();
  formData.append("employee_id", employeeId);
  formData.append("password", password);
  formData.append("action", "checkout");

  fetch("../actions/karyawan/attendance.php", {
    method: "POST",
    body: formData,
  })
    .then((r) => r.text())
    .then((txt) => {
      txt = txt.trim();
      if (txt === "success") {
        showAlert("✅ Check out berhasil!", "success");
        document.getElementById("checkout_password").value = "";
        loadAttendanceHistory(employeeId);
      } else if (txt === "error:password") {
        showAlert("❌ Password salah!", "error");
      } else if (txt === "error:no_checkin") {
        showAlert("⚠️ Anda belum check in hari ini!", "error");
      } else if (txt === "error:already") {
        showAlert("⚠️ Anda sudah check out hari ini!", "error");
      } else {
        showAlert("❌ Gagal check out: " + txt, "error");
      }
    })
    .catch((err) => {
      showAlert("❌ Error: " + err.message, "error");
    });
}

// Show alert message
function showAlert(message, type) {
  const alertDiv = document.getElementById("attendance_alert");
  alertDiv.className = `alert alert-${type}`;
  alertDiv.innerText = message;
  alertDiv.style.display = "block";

  setTimeout(() => {
    alertDiv.style.display = "none";
  }, 4000);
}

// Search employee
function searchEmployee() {
  const keyword = document.getElementById("searchInput").value.toLowerCase();

  // Search in card view
  const cards = document.querySelectorAll(".employee-card");
  cards.forEach((card) => {
    const name = card.querySelector(".card-name").innerText.toLowerCase();
    const info = card.querySelector(".card-info").innerText.toLowerCase();

    if (name.includes(keyword) || info.includes(keyword)) {
      card.style.display = "";
    } else {
      card.style.display = "none";
    }
  });

  // Search in table view
  const rows = document.querySelectorAll(".employee-table tbody tr");
  rows.forEach((row) => {
    const cells = row.querySelectorAll("td");
    let found = false;

    cells.forEach((cell) => {
      if (cell.innerText.toLowerCase().includes(keyword)) {
        found = true;
      }
    });

    row.style.display = found ? "" : "none";
  });
}

// AJAX handlers
document.addEventListener("DOMContentLoaded", function () {
  // Add Employee
  const fAdd = document.getElementById("formAdd");
  if (fAdd) {
    fAdd.addEventListener("submit", function (e) {
      e.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerText;
      submitBtn.innerText = "⏳ Menyimpan...";
      submitBtn.disabled = true;

      fetch("../actions/karyawan/add.php", {
        method: "POST",
        body: new FormData(this),
      })
        .then((r) => r.text())
        .then((txt) => {
          txt = txt.trim();
          if (txt === "success") {
            alert("✅ Karyawan berhasil ditambahkan!");
            location.reload();
          } else if (txt.startsWith("error:duplicate")) {
            alert("⚠️ Kode karyawan atau nomor HP sudah terdaftar!");
          } else if (txt.startsWith("error:invalid_file_type")) {
            alert("⚠️ Tipe file tidak valid! Hanya gambar yang diperbolehkan.");
          } else if (txt.startsWith("error:file_too_large")) {
            alert("⚠️ Ukuran file terlalu besar! Maksimal 5MB.");
          } else {
            alert("❌ Gagal menambah karyawan: " + txt);
          }

          submitBtn.innerText = originalText;
          submitBtn.disabled = false;
        })
        .catch((err) => {
          alert("❌ Error: " + err.message);
          submitBtn.innerText = originalText;
          submitBtn.disabled = false;
        });
    });
  }

  // Edit Employee
  const fEdit = document.getElementById("formEdit");
  if (fEdit) {
    fEdit.addEventListener("submit", function (e) {
      e.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerText;
      submitBtn.innerText = "⏳ Mengupdate...";
      submitBtn.disabled = true;

      fetch("../actions/karyawan/edit.php", {
        method: "POST",
        body: new FormData(this),
      })
        .then((r) => r.text())
        .then((txt) => {
          txt = txt.trim();
          if (txt === "success") {
            alert("✅ Data karyawan berhasil diupdate!");
            location.reload();
          } else if (txt.startsWith("error:duplicate")) {
            alert("⚠️ Kode karyawan atau nomor HP sudah terdaftar!");
          } else {
            alert("❌ Gagal update karyawan: " + txt);
          }

          submitBtn.innerText = originalText;
          submitBtn.disabled = false;
        })
        .catch((err) => {
          alert("❌ Error: " + err.message);
          submitBtn.innerText = originalText;
          submitBtn.disabled = false;
        });
    });
  }

  // Delete Employee
  const fDel = document.getElementById("formDelete");
  if (fDel) {
    fDel.addEventListener("submit", function (e) {
      e.preventDefault();

      fetch("../actions/karyawan/delete.php", {
        method: "POST",
        body: new FormData(this),
      })
        .then((r) => r.text())
        .then((txt) => {
          txt = txt.trim();
          if (txt === "success") {
            alert("✅ Karyawan berhasil dihapus!");
            location.reload();
          } else {
            alert("❌ Gagal menghapus karyawan.");
          }
        })
        .catch((err) => {
          alert("❌ Error: " + err.message);
        });
    });
  }

  // Search on Enter
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        searchEmployee();
      }
    });

    // Real-time search
    searchInput.addEventListener("input", function () {
      searchEmployee();
    });
  }

  // Close modal on background click
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("click", function (e) {
      if (e.target === this) {
        this.style.display = "none";
      }
    });
  });
});
