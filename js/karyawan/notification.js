/**
 * =============================================
 * FILE: js/karyawan/notification.js
 * DESKRIPSI: Custom Notification System with Auto-Reload
 * ============================================= */

/**
 * Show Custom Notification
 * @param {string} type - Type of notification: 'success', 'error', 'warning', 'info'
 * @param {string} title - Notification title
 * @param {string} message - Notification message
 * @param {function|null} callback - Callback function to execute after OK button clicked (null = no callback)
 */
function showNotification(type, title, message, callback) {
  const overlay = document.getElementById("customNotification");
  const icon = document.getElementById("notificationIcon");
  const titleEl = document.getElementById("notificationTitle");
  const messageEl = document.getElementById("notificationMessage");
  const btn = document.getElementById("notificationBtn");

  if (!overlay) {
    console.error("Notification element not found!");
    return;
  }

  // Icon mapping
  const icons = {
    success: "✓",
    error: "✕",
    warning: "⚠",
    info: "i",
  };

  // Reset classes
  icon.className = "notification-icon";
  btn.className = "notification-btn";

  // Add type-specific classes
  icon.classList.add(type);
  btn.classList.add(type);

  // Set content
  icon.textContent = icons[type] || icons.success;
  titleEl.textContent = title;
  messageEl.textContent = message;

  // Show notification
  overlay.classList.add("show");

  // Store callback if provided
  if (callback && typeof callback === "function") {
    overlay.dataset.callback = "pending";
    window.notificationCallback = callback;
  } else {
    delete overlay.dataset.callback;
    window.notificationCallback = null;
  }

  console.log(`Notification shown: [${type}] ${title} - ${message}`);
}

/**
 * Close Notification and Execute Callback
 */
function closeNotification() {
  const overlay = document.getElementById("customNotification");

  if (!overlay) return;

  overlay.classList.remove("show");

  // Execute callback after animation
  if (overlay.dataset.callback === "pending" && window.notificationCallback) {
    setTimeout(() => {
      console.log("Executing notification callback...");
      window.notificationCallback();
      window.notificationCallback = null;
      delete overlay.dataset.callback;
    }, 300); // Wait for fade-out animation
  }
}

// ============================================
// EVENT LISTENERS
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  const overlay = document.getElementById("customNotification");

  if (!overlay) {
    console.warn("Custom notification element not found in DOM!");
    return;
  }

  // Click outside to close
  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) {
      closeNotification();
    }
  });

  // ESC key to close
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && overlay && overlay.classList.contains("show")) {
      closeNotification();
    }
  });

  console.log("Notification system initialized");
});

// ============================================
// EXPOSE TO GLOBAL SCOPE
// ============================================
window.showNotification = showNotification;
window.closeNotification = closeNotification;

console.log("Notification.js loaded successfully");
