// =====================================================================
// File: /js/drop/modal_system.js
// Universal Modal System - Replace all browser alerts/confirms
// Version: 2.0 - Bootstrap SVG Icons Support
// =====================================================================

/**
 * UNIVERSAL MODAL SYSTEM
 * Menggantikan semua alert(), confirm(), dan prompt() browser
 * dengan modal kustom yang modern dan konsisten
 *
 * UPDATED: Support Bootstrap SVG Icons
 */

(function () {
  "use strict";

  // ==================== MODAL CONTAINER CREATION ====================

  function createModalContainer() {
    if (document.getElementById("universalModalSystem")) {
      return;
    }

    const modalHTML = `
      <!-- Universal Modal System Container -->
      <div id="universalModalSystem">
        
        <!-- Alert Modal -->
        <div id="customAlertModal" class="universal-modal-overlay" style="display: none;">
          <div class="universal-modal alert-modal">
            <div class="modal-icon-container">
              <div class="modal-icon" id="alertIcon">ℹ️</div>
            </div>
            <h3 class="modal-title" id="alertTitle">Pemberitahuan</h3>
            <p class="modal-message" id="alertMessage">Message here</p>
            <div class="modal-actions">
              <button class="modal-btn btn-primary" id="alertOkBtn">OK</button>
            </div>
          </div>
        </div>

        <!-- Confirm Modal -->
        <div id="customConfirmModal" class="universal-modal-overlay" style="display: none;">
          <div class="universal-modal confirm-modal">
            <div class="modal-icon-container">
              <div class="modal-icon" id="confirmIcon">⚠️</div>
            </div>
            <h3 class="modal-title" id="confirmTitle">Konfirmasi</h3>
            <p class="modal-message" id="confirmMessage">Are you sure?</p>
            <div class="modal-actions">
              <button class="modal-btn btn-secondary" id="confirmCancelBtn">Batal</button>
              <button class="modal-btn btn-primary" id="confirmOkBtn">OK</button>
            </div>
          </div>
        </div>

        <!-- Success Modal -->
        <div id="customSuccessModal" class="universal-modal-overlay" style="display: none;">
          <div class="universal-modal success-modal">
            <div class="modal-icon-container">
              <div class="modal-icon success-icon">
                <div class="success-checkmark">✓</div>
              </div>
            </div>
            <h3 class="modal-title">Berhasil!</h3>
            <p class="modal-message" id="successMessage">Operation successful</p>
            <div class="modal-actions">
              <button class="modal-btn btn-success" id="successOkBtn">OK, Mengerti</button>
            </div>
          </div>
        </div>

        <!-- Error Modal -->
        <div id="customErrorModal" class="universal-modal-overlay" style="display: none;">
          <div class="universal-modal error-modal">
            <div class="modal-icon-container">
              <div class="modal-icon error-icon">✕</div>
            </div>
            <h3 class="modal-title">Terjadi Kesalahan</h3>
            <p class="modal-message" id="errorMessage">An error occurred</p>
            <div class="modal-actions">
              <button class="modal-btn btn-danger" id="errorOkBtn">OK</button>
            </div>
          </div>
        </div>

        <!-- Loading Modal -->
        <div id="customLoadingModal" class="universal-modal-overlay" style="display: none;">
          <div class="universal-modal loading-modal">
            <div class="loader"></div>
            <p class="modal-message" id="loadingMessage">Memproses...</p>
          </div>
        </div>

      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
    injectModalStyles();
    attachModalEvents();
  }

  // ==================== MODAL STYLES ====================

  function injectModalStyles() {
    if (document.getElementById("universalModalStyles")) {
      return;
    }

    const styles = `
      <style id="universalModalStyles">
        .universal-modal-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.7);
          backdrop-filter: blur(8px);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 99999;
          animation: fadeIn 0.2s ease;
        }

        .universal-modal {
          background: white;
          border-radius: 20px;
          padding: 32px;
          max-width: 480px;
          width: 90%;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
          animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
          text-align: center;
        }

        .modal-icon-container {
          margin-bottom: 20px;
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 80px;
        }

        .modal-icon {
          width: 80px;
          height: 80px;
          margin: 0 auto;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 40px;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* CRITICAL: Support untuk Bootstrap SVG Icons */
        .modal-icon svg {
          display: block;
          margin: 0 auto;
          width: 48px;
          height: 48px;
        }

        /* Jika icon adalah SVG, tidak perlu background circle */
        .modal-icon:has(svg) {
          background: transparent;
          width: auto;
          height: auto;
          border-radius: 0;
        }

        .success-icon {
          background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .error-icon {
          background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .success-checkmark {
          font-size: 48px;
          font-weight: bold;
          animation: checkmarkPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
        }

        .modal-title {
          font-size: 24px;
          font-weight: 700;
          color: #1e293b;
          margin: 0 0 16px 0;
          letter-spacing: -0.02em;
        }

        .modal-message {
          font-size: 16px;
          color: #475569;
          line-height: 1.6;
          margin: 0 0 24px 0;
          white-space: pre-wrap;
        }

        .modal-actions {
          display: flex;
          gap: 12px;
          justify-content: center;
        }

        .modal-btn {
          padding: 12px 32px;
          border: none;
          border-radius: 12px;
          font-size: 15px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
          min-width: 120px;
        }

        .btn-primary {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
          background: #e2e8f0;
          color: #475569;
        }

        .btn-secondary:hover {
          background: #cbd5e1;
          transform: translateY(-2px);
        }

        .btn-success {
          background: linear-gradient(135deg, #10b981 0%, #059669 100%);
          color: white;
          box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
          background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
          color: white;
          box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .modal-btn:active {
          transform: scale(0.96) !important;
        }

        .modal-btn:focus {
          outline: none;
          box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        }

        /* Loading Modal */
        .loading-modal {
          padding: 40px;
        }

        .loader {
          width: 60px;
          height: 60px;
          margin: 0 auto 20px;
          border: 4px solid #f3f4f6;
          border-top: 4px solid #667eea;
          border-radius: 50%;
          animation: spin 0.8s linear infinite;
        }

        /* Animations */
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }

        @keyframes slideUp {
          from {
            transform: translateY(30px);
            opacity: 0;
          }
          to {
            transform: translateY(0);
            opacity: 1;
          }
        }

        @keyframes scaleIn {
          from {
            transform: scale(0);
            opacity: 0;
          }
          to {
            transform: scale(1);
            opacity: 1;
          }
        }

        @keyframes checkmarkPop {
          from {
            transform: scale(0);
            opacity: 0;
          }
          to {
            transform: scale(1);
            opacity: 1;
          }
        }

        @keyframes spin {
          to { transform: rotate(360deg); }
        }

        @keyframes pulse {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.05); }
        }

        /* Responsive */
        @media (max-width: 640px) {
          .universal-modal {
            max-width: 90%;
            padding: 24px;
          }

          .modal-title {
            font-size: 20px;
          }

          .modal-icon {
            width: 60px;
            height: 60px;
            font-size: 30px;
          }

          .modal-icon svg {
            width: 40px;
            height: 40px;
          }
        }
      </style>
    `;

    document.head.insertAdjacentHTML("beforeend", styles);
  }

  // ==================== EVENT HANDLERS ====================

  function attachModalEvents() {
    // ESC key to close modals
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        closeAllModals();
      }
    });

    // Click overlay to close
    document.querySelectorAll(".universal-modal-overlay").forEach((overlay) => {
      overlay.addEventListener("click", (e) => {
        if (e.target === overlay) {
          closeAllModals();
        }
      });
    });
  }

  function closeAllModals() {
    document.querySelectorAll(".universal-modal-overlay").forEach((modal) => {
      modal.style.display = "none";
    });
  }

  // ==================== HELPER: SET ICON ====================

  /**
   * Set icon ke element (support SVG dan emoji)
   * @param {HTMLElement} iconElement - Element icon
   * @param {string} icon - Icon (SVG HTML atau emoji)
   */
  function setIcon(iconElement, icon) {
    if (!iconElement) return;

    // Cek apakah icon adalah SVG
    const trimmedIcon = icon.trim();
    if (trimmedIcon.startsWith("<svg")) {
      // CRITICAL FIX: Use innerHTML for SVG
      iconElement.innerHTML = icon;
    } else {
      // Use textContent for emoji/text
      iconElement.textContent = icon;
    }
  }

  // ==================== PUBLIC API ====================

  /**
   * Custom Alert
   * @param {string} message - Message to display
   * @param {string} title - Optional title
   * @param {string} icon - Optional icon (emoji or SVG HTML)
   */
  window.customAlert = function (
    message,
    title = "Pemberitahuan",
    icon = "ℹ️"
  ) {
    return new Promise((resolve) => {
      createModalContainer();

      const modal = document.getElementById("customAlertModal");
      const titleEl = document.getElementById("alertTitle");
      const messageEl = document.getElementById("alertMessage");
      const iconEl = document.getElementById("alertIcon");
      const okBtn = document.getElementById("alertOkBtn");

      titleEl.textContent = title;
      messageEl.innerHTML = message;
      setIcon(iconEl, icon); // Use helper function

      modal.style.display = "flex";

      const handleOk = () => {
        modal.style.display = "none";
        cleanup();
        resolve(true);
      };

      const cleanup = () => {
        okBtn.removeEventListener("click", handleOk);
      };

      okBtn.addEventListener("click", handleOk);
      setTimeout(() => okBtn.focus(), 100);
    });
  };

  /**
   * Custom Confirm
   * @param {string} message - Message to display
   * @param {string} title - Optional title
   * @param {string} icon - Optional icon (emoji or SVG HTML)
   */
  window.customConfirm = function (message, title = "Konfirmasi", icon = "⚠️") {
    return new Promise((resolve) => {
      createModalContainer();

      const modal = document.getElementById("customConfirmModal");
      const titleEl = document.getElementById("confirmTitle");
      const messageEl = document.getElementById("confirmMessage");
      const iconEl = document.getElementById("confirmIcon");
      const okBtn = document.getElementById("confirmOkBtn");
      const cancelBtn = document.getElementById("confirmCancelBtn");

      titleEl.textContent = title;
      messageEl.innerHTML = message;
      setIcon(iconEl, icon); // Use helper function

      modal.style.display = "flex";

      const handleOk = () => {
        modal.style.display = "none";
        cleanup();
        resolve(true);
      };

      const handleCancel = () => {
        modal.style.display = "none";
        cleanup();
        resolve(false);
      };

      const cleanup = () => {
        okBtn.removeEventListener("click", handleOk);
        cancelBtn.removeEventListener("click", handleCancel);
      };

      okBtn.addEventListener("click", handleOk);
      cancelBtn.addEventListener("click", handleCancel);

      setTimeout(() => cancelBtn.focus(), 100);
    });
  };

  /**
   * Custom Success
   * @param {string} message - Success message
   * @param {string} title - Optional title
   */
  window.customSuccess = function (message, title = "Berhasil!") {
    return new Promise((resolve) => {
      createModalContainer();

      const modal = document.getElementById("customSuccessModal");
      const titleEl = modal.querySelector(".modal-title");
      const messageEl = document.getElementById("successMessage");
      const okBtn = document.getElementById("successOkBtn");

      titleEl.textContent = title;
      messageEl.innerHTML = message;

      modal.style.display = "flex";

      const handleOk = () => {
        modal.style.display = "none";
        cleanup();
        resolve(true);
      };

      const cleanup = () => {
        okBtn.removeEventListener("click", handleOk);
      };

      okBtn.addEventListener("click", handleOk);
      setTimeout(() => okBtn.focus(), 100);
    });
  };

  /**
   * Custom Error
   * @param {string} message - Error message
   * @param {string} title - Optional title
   */
  window.customError = function (message, title = "Terjadi Kesalahan") {
    return new Promise((resolve) => {
      createModalContainer();

      const modal = document.getElementById("customErrorModal");
      const titleEl = modal.querySelector(".modal-title");
      const messageEl = document.getElementById("errorMessage");
      const okBtn = document.getElementById("errorOkBtn");

      titleEl.textContent = title;
      messageEl.innerHTML = message;

      modal.style.display = "flex";

      const handleOk = () => {
        modal.style.display = "none";
        cleanup();
        resolve(true);
      };

      const cleanup = () => {
        okBtn.removeEventListener("click", handleOk);
      };

      okBtn.addEventListener("click", handleOk);
      setTimeout(() => okBtn.focus(), 100);
    });
  };

  /**
   * Show Loading
   * @param {string} message - Loading message
   */
  window.showLoading = function (message = "Memproses...") {
    createModalContainer();

    const modal = document.getElementById("customLoadingModal");
    const messageEl = document.getElementById("loadingMessage");

    messageEl.textContent = message;
    modal.style.display = "flex";
  };

  /**
   * Hide Loading
   */
  window.hideLoading = function () {
    const modal = document.getElementById("customLoadingModal");
    if (modal) {
      modal.style.display = "none";
    }
  };

  // ==================== INITIALIZATION ====================

  // Auto initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", createModalContainer);
  } else {
    createModalContainer();
  }

  console.log("✅ Universal Modal System v2.0 loaded - SVG Support Enabled");
})();
