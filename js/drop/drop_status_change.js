// File: /js/drop/drop_status_change.js
// Handler untuk perubahan status per item dengan CUSTOM POPUP

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Status change handler loaded with custom popup");

  // ===== CREATE CUSTOM CONFIRM MODAL =====
  function createConfirmModal() {
    // Check if modal already exists
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

    // Add CSS if not exists
    if (!document.getElementById("statusConfirmStyles")) {
      const styleHTML = `
        <style id="statusConfirmStyles">
          .status-confirm-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 480px;
            width: 90%;
            animation: slideIn 0.3s ease;
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
          }

          .status-confirm-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
          }

          .status-confirm-body {
            padding: 24px;
          }

          .status-confirm-body p {
            margin: 0;
            font-size: 16px;
            line-height: 1.6;
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
          }

          .status-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
          }

          .status-btn-cancel {
            background: #e2e8f0;
            color: #475569;
          }

          .status-btn-cancel:hover {
            background: #cbd5e1;
            transform: translateY(-1px);
          }

          .status-btn-confirm {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
          }

          .status-btn-confirm:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
          }

          .status-btn:active {
            transform: scale(0.98);
          }
        </style>
      `;
      document.head.insertAdjacentHTML("beforeend", styleHTML);
    }
  }

  // ===== CUSTOM CONFIRM FUNCTION =====
  function customConfirm(message) {
    return new Promise((resolve) => {
      createConfirmModal();

      const modal = document.getElementById("statusConfirmModal");
      const messageEl = document.getElementById("statusConfirmMessage");
      const btnOK = document.getElementById("statusConfirmOK");
      const btnCancel = document.getElementById("statusConfirmCancel");

      // Set message
      messageEl.innerHTML = message;

      // Show modal
      modal.style.display = "flex";

      // Handle OK button
      const handleOK = () => {
        modal.style.display = "none";
        cleanup();
        resolve(true);
      };

      // Handle Cancel button
      const handleCancel = () => {
        modal.style.display = "none";
        cleanup();
        resolve(false);
      };

      // Handle click outside modal
      const handleOverlayClick = (e) => {
        if (e.target === modal) {
          handleCancel();
        }
      };

      // Handle Escape key
      const handleEscape = (e) => {
        if (e.key === "Escape") {
          handleCancel();
        }
      };

      // Cleanup function
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
    });
  }

  // ===== ATTACH STATUS CHANGE LISTENER =====
  function attachStatusChangeListener(select) {
    // Cek jika sudah attach listener
    if (select.dataset.listenerAttached === "true") {
      return;
    }

    select.addEventListener("change", async function (e) {
      e.stopPropagation(); // Prevent row click events

      const itemId = this.dataset.itemId;
      const dropId = this.dataset.dropId;
      const newStatusId = this.value;
      const oldStatusId = this.dataset.currentStatus || this.dataset.oldStatus;
      const itemBrand = this.dataset.itemBrand || "Item";

      console.log(
        `📊 Status change: Item #${itemId} (${itemBrand}) from status ${oldStatusId} to ${newStatusId}`
      );

      // Get new status text
      const newStatusText = this.options[this.selectedIndex].text;

      // Show custom confirm dialog
      const confirmed = await customConfirm(
        `Ubah status <strong>"${itemBrand}"</strong> menjadi <strong>"${newStatusText}"</strong>?`
      );

      if (!confirmed) {
        this.value = oldStatusId; // Revert
        return;
      }

      // Disable select while updating
      const originalBg = this.style.backgroundColor;
      const originalColor = this.style.color;
      this.disabled = true;
      this.style.opacity = "0.6";
      this.style.cursor = "wait";

      const formData = new FormData();
      formData.append("item_id", itemId);
      formData.append("status_id", newStatusId);

      console.log("📤 Sending request to update status...");

      try {
        const response = await fetch(
          "../actions/drop/drop_update_item_status.php",
          {
            method: "POST",
            body: formData,
          }
        );

        const text = await response.text();
        console.log("📥 Response:", text);

        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error("❌ Invalid JSON:", text);
          throw new Error("Server mengembalikan response tidak valid");
        }

        if (data.success) {
          console.log("✅ Status updated successfully:", data);

          // Update dataset
          this.dataset.oldStatus = newStatusId;
          this.dataset.currentStatus = newStatusId;

          // Visual feedback - green flash
          this.style.backgroundColor = "#10b981";
          this.style.color = "white";

          // Show notification
          if (typeof showNotification === "function") {
            showNotification(`✅ ${data.message}`, "success");
          }

          setTimeout(() => {
            this.style.backgroundColor = originalBg;
            this.style.color = originalColor;
            this.style.opacity = "1";
            this.style.cursor = "";
            this.disabled = false;

            // Reload after short delay to update statistics
            setTimeout(() => {
              console.log("🔄 Reloading page to refresh data...");
              window.location.reload();
            }, 800);
          }, 500);
        } else {
          throw new Error(data.message || "Gagal update status");
        }
      } catch (error) {
        console.error("❌ Error updating status:", error);

        // Show error notification
        if (typeof showNotification === "function") {
          showNotification(`❌ ${error.message}`, "error");
        } else {
          alert("❌ Gagal update status: " + error.message);
        }

        // Revert changes
        this.value = oldStatusId;
        this.style.backgroundColor = originalBg;
        this.style.color = originalColor;
        this.style.opacity = "1";
        this.style.cursor = "";
        this.disabled = false;
      }
    });

    // Mark as attached
    select.dataset.listenerAttached = "true";
  }

  // Attach to all existing status selects
  const existingSelects = document.querySelectorAll(".item-status-select");
  console.log(`🔍 Found ${existingSelects.length} status selects`);

  existingSelects.forEach((select, index) => {
    console.log(`  Attaching listener to select #${index + 1}`, {
      itemId: select.dataset.itemId,
      currentStatus: select.dataset.currentStatus,
    });
    attachStatusChangeListener(select);
  });

  // Observer untuk element yang ditambahkan secara dinamis
  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      mutation.addedNodes.forEach(function (node) {
        if (node.nodeType === 1) {
          // Check the node itself
          if (node.classList && node.classList.contains("item-status-select")) {
            attachStatusChangeListener(node);
          }

          // Check children
          const selects = node.querySelectorAll(".item-status-select");
          selects.forEach((select) => {
            attachStatusChangeListener(select);
          });
        }
      });
    });
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });

  console.log("✅ Status change handler initialized with custom popup");
});

// Simple notification function (jika belum ada)
if (typeof showNotification === "undefined") {
  window.showNotification = function (message, type = "info") {
    let container = document.getElementById("notification-container");
    if (!container) {
      container = document.createElement("div");
      container.id = "notification-container";
      container.style.cssText =
        "position: fixed; top: 20px; right: 20px; z-index: 9999;";
      document.body.appendChild(container);
    }

    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
            background: ${
              type === "success"
                ? "#10b981"
                : type === "error"
                ? "#ef4444"
                : "#3b82f6"
            };
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease-out;
            min-width: 250px;
            font-weight: 500;
        `;
    notification.textContent = message;

    container.appendChild(notification);

    setTimeout(() => {
      notification.style.animation = "slideOut 0.3s ease-in";
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  };

  // Add CSS animations
  if (!document.getElementById("notification-styles")) {
    const style = document.createElement("style");
    style.id = "notification-styles";
    style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
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
