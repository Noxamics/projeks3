// =====================================================================
// File: /js/drop/drop_status_change.js
// Item Status Change Handler with Custom Confirmation Modal
// Version: 3.0 - Optimized & Clean
// Database: mifmyho2_sengkuclean
// =====================================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("🔄 Status Change Handler Initialized");

  // ==================== PAGE DETECTION ====================
  const currentPath = window.location.pathname;
  const isTimelinePage = currentPath.includes("timeline_pesanan.php");
  const isDropPage = currentPath.includes("drop.php");

  console.log(
    `🔍 Current page: ${
      isDropPage ? "Drop" : isTimelinePage ? "Timeline" : "Other"
    }`
  );

  // ==================== MODAL CREATION ====================

  /**
   * Create custom confirmation modal
   * Modal hanya dibuat sekali jika belum ada
   */
  function createConfirmModal() {
    if (document.getElementById("statusConfirmModal")) {
      return;
    }

    const modalHTML = `
      <div id="statusConfirmModal" class="status-confirm-overlay" style="display: none;">
        <div class="status-confirm-modal">
          <div class="status-confirm-header">
            <span class="status-confirm-icon">⚠️</span>
            <h3 class="status-confirm-title">Konfirmasi Perubahan Status</h3>
          </div>
          <div class="status-confirm-body">
            <p id="statusConfirmMessage">Ubah status item ini?</p>
          </div>
          <div class="status-confirm-footer">
            <button class="status-btn status-btn-cancel" id="statusConfirmCancel">
              ✕ Batal
            </button>
            <button class="status-btn status-btn-confirm" id="statusConfirmOK">
              ✓ Ubah Status
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
    injectModalStyles();
  }

  /**
   * Inject modal styles
   * Hanya inject sekali jika belum ada
   */
  function injectModalStyles() {
    if (document.getElementById("statusConfirmStyles")) {
      return;
    }

    const styles = `
      <style id="statusConfirmStyles">
        .status-confirm-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.65);
          backdrop-filter: blur(4px);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 10000;
          animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }

        @keyframes slideIn {
          from {
            transform: scale(0.9) translateY(-20px);
            opacity: 0;
          }
          to {
            transform: scale(1) translateY(0);
            opacity: 1;
          }
        }

        .status-confirm-modal {
          background: white;
          border-radius: 16px;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
          max-width: 500px;
          width: 90%;
          animation: slideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
          overflow: hidden;
        }

        .status-confirm-header {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          padding: 24px;
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .status-confirm-icon {
          font-size: 32px;
          line-height: 1;
          animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.1); }
        }

        .status-confirm-title {
          margin: 0;
          font-size: 20px;
          font-weight: 700;
          letter-spacing: -0.02em;
        }

        .status-confirm-body {
          padding: 28px;
        }

        .status-confirm-body p {
          margin: 0;
          font-size: 16px;
          line-height: 1.7;
          color: #334155;
        }

        .status-confirm-body strong {
          color: #0369a1;
          font-weight: 700;
        }

        .status-confirm-footer {
          padding: 16px 24px 24px;
          display: flex;
          gap: 12px;
          justify-content: flex-end;
          background: #f8fafc;
        }

        .status-btn {
          padding: 12px 28px;
          border: none;
          border-radius: 10px;
          font-size: 15px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
          display: inline-flex;
          align-items: center;
          gap: 8px;
          letter-spacing: -0.01em;
        }

        .status-btn-cancel {
          background: #e2e8f0;
          color: #475569;
        }

        .status-btn-cancel:hover {
          background: #cbd5e1;
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(71, 85, 105, 0.2);
        }

        .status-btn-confirm {
          background: linear-gradient(135deg, #10b981 0%, #059669 100%);
          color: white;
          box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
        }

        .status-btn-confirm:hover {
          background: linear-gradient(135deg, #059669 0%, #047857 100%);
          transform: translateY(-2px);
          box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .status-btn:active {
          transform: scale(0.96) !important;
        }

        .status-btn:focus {
          outline: none;
          box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        }
      </style>
    `;

    document.head.insertAdjacentHTML("beforeend", styles);
  }

  /**
   * Show custom confirm dialog
   * @param {string} message - HTML message to display
   * @returns {Promise<boolean>} - True jika OK, false jika Cancel
   */
  function customConfirm(message) {
    return new Promise((resolve) => {
      createConfirmModal();

      const modal = document.getElementById("statusConfirmModal");
      const messageEl = document.getElementById("statusConfirmMessage");
      const btnOK = document.getElementById("statusConfirmOK");
      const btnCancel = document.getElementById("statusConfirmCancel");

      if (!modal || !messageEl || !btnOK || !btnCancel) {
        console.error("❌ Modal elements not found");
        resolve(false);
        return;
      }

      // Set message
      messageEl.innerHTML = message;

      // Show modal with animation
      modal.style.display = "flex";

      // Event handler functions
      const handleOK = () => {
        closeModal();
        resolve(true);
      };

      const handleCancel = () => {
        closeModal();
        resolve(false);
      };

      const handleOverlayClick = (e) => {
        if (e.target === modal) {
          handleCancel();
        }
      };

      const handleEscape = (e) => {
        if (e.key === "Escape") {
          handleCancel();
        }
      };

      const closeModal = () => {
        modal.style.display = "none";
        cleanup();
      };

      const cleanup = () => {
        btnOK.removeEventListener("click", handleOK);
        btnCancel.removeEventListener("click", handleCancel);
        modal.removeEventListener("click", handleOverlayClick);
        document.removeEventListener("keydown", handleEscape);
      };

      // Attach event listeners
      btnOK.addEventListener("click", handleOK);
      btnCancel.addEventListener("click", handleCancel);
      modal.addEventListener("click", handleOverlayClick);
      document.addEventListener("keydown", handleEscape);

      // Focus OK button
      setTimeout(() => btnOK.focus(), 100);
    });
  }

  // ==================== STATUS UPDATE ====================

  /**
   * Update item status via API
   * @param {HTMLSelectElement} selectElement - Select element
   * @param {number} itemId - Item ID
   * @param {number} newStatusId - New status ID
   * @param {number} oldStatusId - Old status ID
   * @param {string} itemBrand - Item brand/name
   */
  async function updateItemStatus(
    selectElement,
    itemId,
    newStatusId,
    oldStatusId,
    itemBrand
  ) {
    // Save original styles
    const originalStyles = {
      bg: selectElement.style.backgroundColor,
      color: selectElement.style.color,
      opacity: selectElement.style.opacity,
      cursor: selectElement.style.cursor,
    };

    // Set loading state
    setLoadingState(selectElement, true);

    const formData = new FormData();
    formData.append("item_id", itemId);
    formData.append("status_id", newStatusId);

    console.log("📤 Sending status update request...");

    try {
      const response = await fetch(
        "../actions/drop/drop_update_item_status.php",
        {
          method: "POST",
          body: formData,
        }
      );

      const text = await response.text();
      console.log("📥 Response received:", text.substring(0, 200));

      const data = parseJsonResponse(text);

      if (data.success) {
        console.log("✅ Status updated successfully");

        // Update dataset
        selectElement.dataset.oldStatus = newStatusId;
        selectElement.dataset.currentStatus = newStatusId;

        // Visual feedback - green flash
        showSuccessFeedback(selectElement);

        // Save notification to session storage
        saveNotificationToSession(data.message);

        // Reload page after delay
        setTimeout(() => {
          console.log("🔄 Reloading page to refresh data...");
          window.location.reload();
        }, 700);
      } else {
        throw new Error(data.message || "Gagal update status");
      }
    } catch (error) {
      console.error("❌ Error updating status:", error);
      handleUpdateError(error, selectElement, oldStatusId, originalStyles);
    }
  }

  /**
   * Set loading state for select element
   * @param {HTMLSelectElement} element - Select element
   * @param {boolean} isLoading - Loading state
   */
  function setLoadingState(element, isLoading) {
    element.disabled = isLoading;
    element.style.opacity = isLoading ? "0.6" : "1";
    element.style.cursor = isLoading ? "wait" : "";
  }

  /**
   * Parse JSON response with error handling
   * @param {string} text - Response text
   * @returns {Object} - Parsed JSON object
   */
  function parseJsonResponse(text) {
    try {
      return JSON.parse(text);
    } catch (error) {
      console.error("❌ Invalid JSON response:", text);
      throw new Error("Server mengembalikan response tidak valid");
    }
  }

  /**
   * Show success feedback animation
   * @param {HTMLSelectElement} element - Select element
   */
  function showSuccessFeedback(element) {
    element.style.backgroundColor = "#10b981";
    element.style.color = "white";
    element.style.transition = "all 0.3s ease";

    setTimeout(() => {
      element.style.backgroundColor = "";
      element.style.color = "";
      element.style.opacity = "1";
      element.style.cursor = "";
      element.disabled = false;
    }, 300);
  }

  /**
   * Handle update error
   * @param {Error} error - Error object
   * @param {HTMLSelectElement} element - Select element
   * @param {number} oldStatusId - Old status ID to revert
   * @param {Object} originalStyles - Original element styles
   */
  function handleUpdateError(error, element, oldStatusId, originalStyles) {
    // Show error notification
    const errorMessage = `❌ ${error.message}`;

    if (typeof showNotification === "function") {
      showNotification(errorMessage, "error");
    } else if (typeof showFallbackNotification === "function") {
      showFallbackNotification(errorMessage, "error");
    } else {
      alert(errorMessage);
    }

    // Revert changes
    element.value = oldStatusId;
    element.style.backgroundColor = originalStyles.bg;
    element.style.color = originalStyles.color;
    element.style.opacity = originalStyles.opacity;
    element.style.cursor = originalStyles.cursor;
    element.disabled = false;
  }

  /**
   * Save notification to session storage based on current page
   * @param {string} message - Notification message
   */
  function saveNotificationToSession(message) {
    const notificationData = [
      {
        condition: isDropPage,
        key: "dropNotification",
        typeKey: "dropNotificationType",
      },
      {
        condition: isTimelinePage,
        key: "timelineNotification",
        typeKey: "timelineNotificationType",
      },
    ];

    const activeNotification = notificationData.find((n) => n.condition);

    if (activeNotification) {
      sessionStorage.setItem(activeNotification.key, `✅ ${message}`);
      sessionStorage.setItem(activeNotification.typeKey, "success");
      console.log(
        `💾 Saved notification for ${isDropPage ? "Drop" : "Timeline"} page`
      );
    }
  }

  // ==================== STATUS CHANGE HANDLER ====================

  /**
   * Attach status change listener ke select element
   * @param {HTMLSelectElement} select - Status select element
   */
  function attachStatusChangeListener(select) {
    // Check jika listener sudah diattach
    if (select.dataset.listenerAttached === "true") {
      return;
    }

    select.addEventListener("change", async function (e) {
      e.stopPropagation(); // Prevent row click events

      // Get data attributes
      const itemId = this.dataset.itemId;
      const dropId = this.dataset.dropId;
      const newStatusId = this.value;
      const oldStatusId = this.dataset.currentStatus || this.dataset.oldStatus;
      const itemBrand = this.dataset.itemBrand || "Item";
      const newStatusText =
        this.options[this.selectedIndex]?.text || "Status Baru";

      console.log(`📊 Status change request:`, {
        itemId,
        itemBrand,
        oldStatus: oldStatusId,
        newStatus: newStatusId,
        newStatusText,
      });

      // Show confirmation dialog
      const confirmed = await customConfirm(
        `Ubah status <strong>"${itemBrand}"</strong> menjadi <strong>"${newStatusText}"</strong>?`
      );

      if (!confirmed) {
        console.log("❌ Status change cancelled by user");
        this.value = oldStatusId; // Revert
        return;
      }

      // Update status via API
      await updateItemStatus(this, itemId, newStatusId, oldStatusId, itemBrand);
    });

    // Mark as attached
    select.dataset.listenerAttached = "true";
  }

  // ==================== INITIALIZATION ====================

  /**
   * Initialize status change handlers on all existing selects
   */
  function initializeStatusSelects() {
    const existingSelects = document.querySelectorAll(".item-status-select");
    console.log(`🔍 Found ${existingSelects.length} status select element(s)`);

    existingSelects.forEach((select, index) => {
      console.log(`  ✓ Attaching listener to select #${index + 1}`, {
        itemId: select.dataset.itemId,
        currentStatus: select.dataset.currentStatus,
      });
      attachStatusChangeListener(select);
    });
  }

  // Initialize existing selects
  initializeStatusSelects();

  // ==================== MUTATION OBSERVER ====================

  /**
   * Observer untuk mendeteksi status select yang ditambahkan secara dinamis
   */
  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      mutation.addedNodes.forEach(function (node) {
        if (node.nodeType !== Node.ELEMENT_NODE) return;

        // Check node itself
        if (node.classList?.contains("item-status-select")) {
          console.log("🆕 New status select detected, attaching listener");
          attachStatusChangeListener(node);
        }

        // Check children
        const selects = node.querySelectorAll?.(".item-status-select");
        if (selects?.length > 0) {
          console.log(`🆕 ${selects.length} new status select(s) detected`);
          selects.forEach((select) => {
            attachStatusChangeListener(select);
          });
        }
      });
    });
  });

  // Start observing
  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });

  console.log("✅ Status Change Handler Ready with Custom Modal");
  console.log("📦 Database: mifmyho2_sengkuclean");
});

// ==================== FALLBACK NOTIFICATION SYSTEM ====================

/**
 * Fallback notification function jika showNotification belum ada
 * Integrated dengan drop_helpers.js notification system
 */
if (typeof showNotification === "undefined") {
  window.showNotification = function (message, type = "success") {
    console.log(`📢 Showing notification: ${message} (${type})`);

    let container = document.getElementById("notification-container");
    if (!container) {
      container = document.createElement("div");
      container.id = "notification-container";
      container.style.cssText =
        "position: fixed; top: 20px; right: 20px; z-index: 10000;";
      document.body.appendChild(container);
    }

    const colors = {
      success: "#10b981",
      error: "#ef4444",
      warning: "#f59e0b",
      info: "#3b82f6",
    };

    const icons = {
      success: "✓",
      error: "✕",
      warning: "⚠",
      info: "ℹ",
    };

    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
      background: ${colors[type] || colors.info};
      color: white;
      padding: 16px 24px;
      border-radius: 10px;
      margin-bottom: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      animation: slideInRight 0.3s ease-out;
      min-width: 280px;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    `;

    notification.innerHTML = `
      <span style="font-size: 18px;">${icons[type] || icons.info}</span>
      <span>${message}</span>
    `;

    container.appendChild(notification);

    setTimeout(() => {
      notification.style.animation = "slideOutRight 0.3s ease-in";
      setTimeout(() => notification.remove(), 300);
    }, 3500);
  };

  // Inject notification styles
  if (!document.getElementById("notification-styles")) {
    const style = document.createElement("style");
    style.id = "notification-styles";
    style.textContent = `
      @keyframes slideInRight {
        from {
          transform: translateX(400px);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }
      @keyframes slideOutRight {
        from {
          transform: translateX(0);
          opacity: 1;
        }
        to {
          transform: translateX(400px);
          opacity: 0;
        }
      }
    `;
    document.head.appendChild(style);
  }
}

/**
 * Alternative fallback notification (inline version)
 * @param {string} message - Message to show
 * @param {string} type - Type of notification
 */
function showFallbackNotification(message, type = "success") {
  if (typeof showNotification === "function") {
    showNotification(message, type);
  } else {
    alert(message);
  }
}
