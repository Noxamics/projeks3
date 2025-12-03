/**
 * =============================================
 * FILE: js/karyawan/attendance.js
 * DESKRIPSI: Attendance System with Real-time Clock
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

// Start clock interval
let clockInterval = null;

function startAttendanceClock() {
  updateAttendanceClock(); // Update immediately
  if (clockInterval) clearInterval(clockInterval);
  clockInterval = setInterval(updateAttendanceClock, 1000);
  console.log("Attendance clock started");
}

// Stop clock when modal closes
function stopAttendanceClock() {
  if (clockInterval) {
    clearInterval(clockInterval);
    clockInterval = null;
    console.log("Attendance clock stopped");
  }
}

// ============================================
// CHECK IN FORM HANDLER (UPDATED WITH DEBUG)
// ============================================
document.addEventListener("DOMContentLoaded", function () {
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

      console.log("=== CHECK IN DEBUG ===");
      console.log("Employee ID:", formData.get('employee_id'));
      console.log("Role Today:", formData.get('role_today'));
      console.log("Has Password:", formData.get('password') ? 'Yes' : 'No');

      fetch("../actions/attendance/checkin.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          console.log("Response status:", response.status);
          return response.text(); // Get as text first
        })
        .then((text) => {
          console.log("Raw response:", text);
          
          try {
            const data = JSON.parse(text);
            console.log("Parsed response:", data);

            // Show debug info if available
            if (data.debug && data.debug.length > 0) {
              console.log("=== DEBUG INFO ===");
              data.debug.forEach(msg => console.log(msg));
            }

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
                // Info tambahan jika mendapat role kasir otomatis
                if (data.data.kasir_auto_added) {
                  message += "\n\n✨ Anda mendapatkan role KASIR (pasif) karena check in pertama kali!";
                }
              }

              showNotification("success", "Check In Berhasil!", message, function () {
                closeAttendanceModal();
                window.location.reload();
              });
            } else {
              // Show error with debug info if available
              let errorMsg = data.message;
              if (data.debug && data.debug.length > 0) {
                errorMsg += "\n\nDebug Info:\n" + data.debug.join("\n");
              }
              
              showNotification("error", "Check In Gagal", errorMsg, null);
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalText;
            }
          } catch (parseError) {
            console.error("JSON Parse Error:", parseError);
            console.error("Response was:", text);
            
            showNotification(
              "error",
              "Terjadi Kesalahan",
              "Server response tidak valid. Periksa console untuk detail.",
              null
            );
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        })
        .catch((error) => {
          console.error("Fetch error:", error);
          showNotification(
            "error",
            "Terjadi Kesalahan",
            "Tidak dapat terhubung ke server: " + error.message,
            null
          );
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        });
    });

    console.log("Check-in form handler initialized");
  }
});

// ============================================
// CHECK OUT FORM HANDLER
// ============================================
document.addEventListener("DOMContentLoaded", function () {
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

            showNotification(
              "success",
              "Check Out Berhasil!",
              message,
              function () {
                closeAttendanceModal();
                window.location.reload();
              }
            );
          } else {
            showNotification("error", "Check Out Gagal", data.message, null);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        })
        .catch((error) => {
          console.error("Check-out error:", error);
          showNotification(
            "error",
            "Terjadi Kesalahan",
            "Terjadi kesalahan saat check out. Silakan coba lagi.",
            null
          );
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        });
    });

    console.log("Check-out form handler initialized");
  }
});

// ============================================
// EXPOSE TO GLOBAL SCOPE
// ============================================
window.startAttendanceClock = startAttendanceClock;
window.stopAttendanceClock = stopAttendanceClock;
window.updateAttendanceClock = updateAttendanceClock;

console.log("Attendance.js loaded successfully");
