// ===========================
// ATTENDANCE MANAGEMENT
// File: js/karyawan/attendance.js
// ===========================

/**
 * Load attendance history for employee
 */
function loadAttendanceHistory(employeeId) {
  const tbody = document.getElementById("attendance_history_body");
  if (!tbody) return;

  // Show loading
  tbody.innerHTML =
    '<tr><td colspan="4" style="text-align:center; color:#0066cc;">⏳ Memuat data...</td></tr>';

  fetch(`../actions/karyawan/get_attendance.php?id=${employeeId}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      tbody.innerHTML = "";

      if (!data || data.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="4" style="text-align:center; color:#636e72;">📭 Belum ada data absensi</td></tr>';
        return;
      }

      data.forEach((row) => {
        const tr = document.createElement("tr");

        // Format date
        const date = new Date(row.date);
        const formattedDate = date.toLocaleDateString("id-ID", {
          weekday: "short",
          year: "numeric",
          month: "short",
          day: "numeric",
        });

        // Create status badge
        const statusClass = `status-badge status-${row.status.toLowerCase()}`;

        tr.innerHTML = `
          <td><strong>${formattedDate}</strong></td>
          <td><strong>${row.check_in || "-"}</strong></td>
          <td><strong>${row.check_out || "-"}</strong></td>
          <td><span class="${statusClass}">${row.status}</span></td>
        `;

        // Add hover effect
        tr.style.cursor = "pointer";
        tr.addEventListener("mouseenter", function () {
          this.style.background = "#f0f8ff";
        });
        tr.addEventListener("mouseleave", function () {
          this.style.background = "";
        });

        tbody.appendChild(tr);
      });
    })
    .catch((err) => {
      console.error("Error loading attendance:", err);
      tbody.innerHTML =
        '<tr><td colspan="4" style="text-align:center; color:#e74c3c;">⚠️ Error memuat data absensi</td></tr>';
    });
}

/**
 * Check In
 */
function checkIn() {
  const password = document.getElementById("checkin_password");
  const employeeId = document.getElementById("attendance_employee_id");

  if (!password || !employeeId) {
    showAlert("Form tidak lengkap", "error");
    return;
  }

  if (!password.value.trim()) {
    showAlert("Masukkan password untuk check in", "error");
    password.focus();
    return;
  }

  // Disable button during submission
  const submitBtn = event.target;
  const originalText = submitBtn.textContent;
  submitBtn.textContent = "⏳ Memproses...";
  submitBtn.disabled = true;

  const formData = new FormData();
  formData.append("employee_id", employeeId.value);
  formData.append("password", password.value);
  formData.append("action", "checkin");

  fetch("../actions/karyawan/attendance.php", {
    method: "POST",
    body: formData,
  })
    .then((r) => r.text())
    .then((response) => {
      const trimmed = response.trim();

      switch (trimmed) {
        case "success":
          showAlert("✅ Check in berhasil!", "success");
          password.value = "";
          loadAttendanceHistory(employeeId.value);
          break;
        case "error:password":
          showAlert("❌ Password salah!", "error");
          password.focus();
          break;
        case "error:already":
          showAlert("⚠️ Anda sudah check in hari ini!", "warning");
          break;
        case "error:no_record":
          showAlert("❌ Data karyawan tidak ditemukan!", "error");
          break;
        default:
          showAlert("❌ Gagal check in: " + trimmed, "error");
      }
    })
    .catch((err) => {
      showAlert("❌ Error: " + err.message, "error");
      console.error("Check in error:", err);
    })
    .finally(() => {
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    });
}

/**
 * Check Out
 */
function checkOut() {
  const password = document.getElementById("checkout_password");
  const employeeId = document.getElementById("attendance_employee_id");

  if (!password || !employeeId) {
    showAlert("Form tidak lengkap", "error");
    return;
  }

  if (!password.value.trim()) {
    showAlert("Masukkan password untuk check out", "error");
    password.focus();
    return;
  }

  // Disable button during submission
  const submitBtn = event.target;
  const originalText = submitBtn.textContent;
  submitBtn.textContent = "⏳ Memproses...";
  submitBtn.disabled = true;

  const formData = new FormData();
  formData.append("employee_id", employeeId.value);
  formData.append("password", password.value);
  formData.append("action", "checkout");

  fetch("../actions/karyawan/attendance.php", {
    method: "POST",
    body: formData,
  })
    .then((r) => r.text())
    .then((response) => {
      const trimmed = response.trim();

      switch (trimmed) {
        case "success":
          showAlert("✅ Check out berhasil!", "success");
          password.value = "";
          loadAttendanceHistory(employeeId.value);
          break;
        case "error:password":
          showAlert("❌ Password salah!", "error");
          password.focus();
          break;
        case "error:no_checkin":
          showAlert("⚠️ Anda belum check in hari ini!", "warning");
          break;
        case "error:already":
          showAlert("⚠️ Anda sudah check out hari ini!", "warning");
          break;
        case "error:no_record":
          showAlert("❌ Data karyawan tidak ditemukan!", "error");
          break;
        default:
          showAlert("❌ Gagal check out: " + trimmed, "error");
      }
    })
    .catch((err) => {
      showAlert("❌ Error: " + err.message, "error");
      console.error("Check out error:", err);
    })
    .finally(() => {
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    });
}

/**
 * Show alert message in attendance modal
 */
function showAlert(message, type = "info") {
  const alertDiv = document.getElementById("attendance_alert");

  if (!alertDiv) {
    console.warn("Alert element not found");
    // Fallback to toast
    showToast(message, type);
    return;
  }

  const icons = {
    success: "✅",
    error: "❌",
    warning: "⚠️",
    info: "ℹ️",
  };

  alertDiv.className = `alert alert-${type}`;
  alertDiv.innerHTML = `${icons[type] || ""} ${message}`;
  alertDiv.style.display = "block";

  // Auto hide after 5 seconds
  setTimeout(() => {
    alertDiv.style.display = "none";
  }, 5000);
}

/**
 * Get attendance status for today
 */
function getTodayAttendanceStatus(employeeId) {
  return fetch(`../actions/karyawan/get_today_attendance.php?id=${employeeId}`)
    .then((r) => r.json())
    .then((data) => {
      return data;
    })
    .catch((err) => {
      console.error("Error getting attendance status:", err);
      return null;
    });
}

/**
 * Update attendance UI based on status
 */
async function updateAttendanceUI(employeeId) {
  const status = await getTodayAttendanceStatus(employeeId);

  if (!status) return;

  const checkinSection = document.querySelector(".checkin-section");
  const checkoutSection = document.querySelector(".checkout-section");

  if (checkinSection && checkoutSection) {
    if (status.checkedIn && !status.checkedOut) {
      // Already checked in, show checkout form
      checkinSection.style.display = "none";
      checkoutSection.style.display = "block";
    } else if (status.checkedIn && status.checkedOut) {
      // Already completed for today
      checkinSection.style.display = "none";
      checkoutSection.style.display = "none";

      const alertDiv = document.getElementById("attendance_alert");
      if (alertDiv) {
        alertDiv.className = "alert alert-success";
        alertDiv.innerHTML = "✅ Absensi hari ini sudah lengkap";
        alertDiv.style.display = "block";
      }
    }
  }
}

/**
 * Export attendance to CSV
 */
function exportAttendance(employeeId) {
  showLoading(true);

  fetch(`../actions/karyawan/export_attendance.php?id=${employeeId}`)
    .then((r) => r.blob())
    .then((blob) => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `attendance_${employeeId}_${new Date().getTime()}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);

      showToast("✅ Data absensi berhasil diekspor!", "success");
    })
    .catch((err) => {
      showToast("❌ Gagal mengekspor data absensi", "error");
      console.error("Export error:", err);
    })
    .finally(() => {
      showLoading(false);
    });
}

/**
 * Initialize attendance listeners
 */
document.addEventListener("DOMContentLoaded", function () {
  // Setup password fields to accept Enter key
  const passwordFields = [
    document.getElementById("checkin_password"),
    document.getElementById("checkout_password"),
  ];

  passwordFields.forEach((field) => {
    if (field) {
      field.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          const isCheckin = this.id === "checkin_password";
          if (isCheckin) {
            checkIn();
          } else {
            checkOut();
          }
        }
      });
    }
  });
});

// Expose functions to global scope
window.loadAttendanceHistory = loadAttendanceHistory;
window.checkIn = checkIn;
window.checkOut = checkOut;
window.showAlert = showAlert;
window.getTodayAttendanceStatus = getTodayAttendanceStatus;
window.exportAttendance = exportAttendance;
