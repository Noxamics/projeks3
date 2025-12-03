/**
 * =============================================
 * FILE: js/karyawan/attendance.js
 * DESKRIPSI: Attendance System with Real-time Clock & Form Handlers
 * UPDATED: Added kasir stat refresh after check-in/check-out
 * ============================================= */

// ============================================
// REAL-TIME CLOCK
// ============================================
function updateAttendanceClock() {
  const now = new Date();

  // Format time
  const hours = String(now.getHours()).padStart(2, "0");
  const minutes = String(now.getMinutes()).padStart(2, "0");
  const seconds = String(now.getSeconds()).padStart(2, "0");
  const timeString = `${hours}:${minutes}:${seconds}`;

  // Format date
  const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
  const months = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember",
  ];

  const dayName = days[now.getDay()];
  const date = now.getDate();
  const monthName = months[now.getMonth()];
  const year = now.getFullYear();
  const dateString = `${dayName}, ${date} ${monthName} ${year}`;

  // Update displays
  const timeEl = document.getElementById("current_time");
  const dateEl = document.getElementById("current_date");

  if (timeEl) timeEl.textContent = timeString;
  if (dateEl) dateEl.textContent = dateString;
}

// Clock interval
let clockInterval = null;

function startAttendanceClock() {
  updateAttendanceClock(); // Update immediately
  if (clockInterval) clearInterval(clockInterval);
  clockInterval = setInterval(updateAttendanceClock, 1000);
  console.log("Attendance clock started");
}

function stopAttendanceClock() {
  if (clockInterval) {
    clearInterval(clockInterval);
    clockInterval = null;
    console.log("Attendance clock stopped");
  }
}

// ============================================
// CHECK IN FORM HANDLER
// ============================================
function initCheckInHandler() {
  const formCheckIn = document.getElementById("formCheckIn");

  if (formCheckIn) {
    formCheckIn.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;

      // Disable button
      submitBtn.disabled = true;
      submitBtn.innerHTML = "Memproses...";

      console.log("Submitting check-in...");

      fetch("../actions/attendance/checkin.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          console.log("Check-in response:", data);

          if (data.success) {
            // Build success message
            let message = data.message;

            if (data.data) {
              if (data.data.new_status) {
                message += "\n\nStatus: " + data.data.new_status;
              }
              if (data.data.check_in_time) {
                message += "\nWaktu Check In: " + data.data.check_in_time;
              }
              if (data.data.role_today) {
                message += "\nRole Hari Ini: " + data.data.role_today;
              }
              if (data.data.kasir_auto_added) {
                message +=
                  "\n\n✨ Anda mendapatkan role KASIR (pasif) karena check in pertama kali!";
              }
            }

            if (typeof showNotification === "function") {
              showNotification(
                "success",
                "Check In Berhasil!",
                message,
                function () {
                  if (typeof closeAttendanceModal === "function") {
                    closeAttendanceModal();
                  }

                  // Update stat cards
                  if (typeof updateStatCards === "function") {
                    updateStatCards();
                  }

                  // Update kasir stat (IMPORTANT!)
                  if (typeof updateKasirStat === "function") {
                    updateKasirStat();
                  }

                  // Reload page to reflect new status
                  window.location.reload();
                }
              );
            } else {
              alert(message);

              // Update kasir stat even without notification
              if (typeof updateKasirStat === "function") {
                updateKasirStat();
              }

              window.location.reload();
            }
          } else {
            if (typeof showNotification === "function") {
              showNotification("error", "Check In Gagal", data.message, null);
            } else {
              alert("Check In Gagal: " + data.message);
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        })
        .catch((error) => {
          console.error("Check-in error:", error);

          if (typeof showNotification === "function") {
            showNotification(
              "error",
              "Terjadi Kesalahan",
              "Terjadi kesalahan saat check in. Silakan coba lagi.",
              null
            );
          } else {
            alert("Terjadi kesalahan saat check in. Silakan coba lagi.");
          }

          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        });
    });

    console.log("Check-in form handler initialized");
  }
}

// ============================================
// CHECK OUT FORM HANDLER
// ============================================
function initCheckOutHandler() {
  const formCheckOut = document.getElementById("formCheckOut");

  if (formCheckOut) {
    formCheckOut.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;

      // Disable button
      submitBtn.disabled = true;
      submitBtn.innerHTML = "Memproses...";

      console.log("Submitting check-out...");

      fetch("../actions/attendance/checkout.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          console.log("Check-out response:", data);

          if (data.success) {
            // Build success message
            let message = data.message;

            if (data.data) {
              if (data.data.work_hours) {
                message += "\n\nJam Kerja: " + data.data.work_hours + " jam";
              }
              if (data.data.check_out_time) {
                message += "\nWaktu Check Out: " + data.data.check_out_time;
              }
              if (data.data.new_status) {
                message += "\nStatus: " + data.data.new_status;
              }
            }

            if (typeof showNotification === "function") {
              showNotification(
                "success",
                "Check Out Berhasil!",
                message,
                function () {
                  if (typeof closeAttendanceModal === "function") {
                    closeAttendanceModal();
                  }

                  // Update stat cards
                  if (typeof updateStatCards === "function") {
                    updateStatCards();
                  }

                  // Update kasir stat (IMPORTANT!)
                  if (typeof updateKasirStat === "function") {
                    updateKasirStat();
                  }

                  // Reload page to reflect new status
                  window.location.reload();
                }
              );
            } else {
              alert(message);

              // Update kasir stat even without notification
              if (typeof updateKasirStat === "function") {
                updateKasirStat();
              }

              window.location.reload();
            }
          } else {
            if (typeof showNotification === "function") {
              showNotification("error", "Check Out Gagal", data.message, null);
            } else {
              alert("Check Out Gagal: " + data.message);
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        })
        .catch((error) => {
          console.error("Check-out error:", error);

          if (typeof showNotification === "function") {
            showNotification(
              "error",
              "Terjadi Kesalahan",
              "Terjadi kesalahan saat check out. Silakan coba lagi.",
              null
            );
          } else {
            alert("Terjadi kesalahan saat check out. Silakan coba lagi.");
          }

          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        });
    });

    console.log("Check-out form handler initialized");
  }
}

// ============================================
// INITIALIZE ON DOM READY
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  console.log("Initializing attendance handlers...");

  initCheckInHandler();
  initCheckOutHandler();

  // Initial stat cards load if function exists
  if (typeof updateStatCards === "function") {
    updateStatCards();

    // Auto-refresh stat cards every 30 seconds
    setInterval(updateStatCards, 30000);
  }

  console.log("Attendance handlers initialized");
});

// ============================================
// EXPOSE TO GLOBAL SCOPE
// ============================================
window.startAttendanceClock = startAttendanceClock;
window.stopAttendanceClock = stopAttendanceClock;
window.updateAttendanceClock = updateAttendanceClock;
window.updateStatCards = updateStatCards;

console.log("Attendance.js loaded successfully");
