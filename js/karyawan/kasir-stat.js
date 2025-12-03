/**
 * =============================================
 * FILE: js/karyawan/kasir-stat.js
 * DESKRIPSI: Real-time Active Kasir Stat Card Updater
 * ============================================= */

// ============================================
// UPDATE ACTIVE KASIR STAT CARD
// ============================================
function updateKasirStat() {
  console.log("Updating active kasir stat...");

  fetch("../actions/karyawan/get_active_kasir.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Active kasir data:", data);

      if (data.success) {
        // Find the kasir stat card elements
        const kasirCodeElement = document.querySelector(
          ".stat-card.stat-warning .stat-number"
        );
        const kasirLabelElement = document.querySelector(
          ".stat-card.stat-warning .stat-label"
        );
        const kasirBadgeElement = document.querySelector(
          ".stat-card.stat-warning .stat-badge"
        );
        const kasirFooterElement = document.querySelector(
          ".stat-card.stat-warning .stat-info"
        );

        if (data.has_active_kasir && data.kasir_data) {
          // Has active kasir - show data
          const kasir = data.kasir_data;

          if (kasirCodeElement) {
            kasirCodeElement.textContent = kasir.code;
          }

          if (kasirLabelElement) {
            kasirLabelElement.textContent = "Karyawan Check-in";
          }

          if (kasirBadgeElement) {
            kasirBadgeElement.textContent = "On Duty";
            kasirBadgeElement.classList.remove("badge-standby");
            kasirBadgeElement.classList.add("badge-active");
          }

          if (kasirFooterElement) {
            kasirFooterElement.innerHTML = `
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" 
                  stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" 
                  stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              ${kasir.name} <span style="font-size: 11px; opacity: 0.8;">(${kasir.role})</span>
            `;
          }

          // Add pulsing animation to indicate active
          const kasirCard = document.querySelector(".stat-card.stat-warning");
          if (kasirCard) {
            kasirCard.classList.add("stat-active");
          }
        } else {
          // No active kasir - show default state
          if (kasirCodeElement) {
            kasirCodeElement.textContent = "-";
          }

          if (kasirLabelElement) {
            kasirLabelElement.textContent = "Belum Ada Check-in";
          }

          if (kasirBadgeElement) {
            kasirBadgeElement.textContent = "Standby";
            kasirBadgeElement.classList.remove("badge-active");
            kasirBadgeElement.classList.add("badge-standby");
          }

          if (kasirFooterElement) {
            kasirFooterElement.innerHTML = `
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" 
                  stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" 
                  stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Belum ada karyawan check-in
            `;
          }

          // Remove pulsing animation
          const kasirCard = document.querySelector(".stat-card.stat-warning");
          if (kasirCard) {
            kasirCard.classList.remove("stat-active");
          }
        }

        console.log("Kasir stat updated successfully");
      } else {
        console.error("Failed to get kasir data:", data.message);
      }
    })
    .catch((error) => {
      console.error("Error updating kasir stat:", error);
    });
}

// ============================================
// AUTO-INITIALIZE
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  console.log("Initializing kasir stat updater...");

  // Initial update
  updateKasirStat();

  // Auto-refresh every 15 seconds
  setInterval(updateKasirStat, 15000);

  console.log("Kasir stat updater initialized");
});

// ============================================
// EXPOSE TO GLOBAL SCOPE
// ============================================
window.updateKasirStat = updateKasirStat;

console.log("Kasir-stat.js loaded successfully");
