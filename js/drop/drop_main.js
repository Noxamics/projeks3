// File: /js/drop/drop_main.js
// Main functionality untuk Drop Management

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Drop main module loaded");

  // ===== SUCCESS MESSAGE HANDLER =====
  if (sessionStorage.getItem("showSuccess") === "true") {
    const message =
      sessionStorage.getItem("successMessage") || "✅ Operasi berhasil!";

    const alertDiv = document.createElement("div");
    alertDiv.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      padding: 16px 24px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 10000;
      font-weight: 600;
      animation: slideIn 0.3s ease-out;
    `;
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
      alertDiv.style.animation = "slideOut 0.3s ease-out";
      setTimeout(() => alertDiv.remove(), 300);
    }, 3000);

    sessionStorage.removeItem("showSuccess");
    sessionStorage.removeItem("successMessage");

    console.log("✅ Success message displayed:", message);
  }

  // ===== CUSTOMER CHECKBOX =====
  document.querySelectorAll(".customer-checkbox").forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      const customerId = this.getAttribute("data-customer-id");
      const isChecked = this.checked;

      document
        .querySelectorAll(`.item-checkbox[data-customer-id="${customerId}"]`)
        .forEach((itemCb) => {
          itemCb.checked = isChecked;
        });

      console.log(
        `${isChecked ? "☑️" : "☐"} Customer ${customerId} - ${
          isChecked ? "selected" : "unselected"
        }`
      );
    });
  });

  // ===== ITEM CHECKBOX =====
  document.querySelectorAll(".item-checkbox").forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      const customerId = this.getAttribute("data-customer-id");
      const customerCheckbox = document.querySelector(
        `.customer-checkbox[data-customer-id="${customerId}"]`
      );

      const allItemsForCustomer = document.querySelectorAll(
        `.item-checkbox[data-customer-id="${customerId}"]`
      );
      const checkedItems = Array.from(allItemsForCustomer).filter(
        (cb) => cb.checked
      );

      if (customerCheckbox) {
        customerCheckbox.checked =
          checkedItems.length === allItemsForCustomer.length;
      }
    });
  });

  // ===== PRINT SELECTED BUTTON =====
  const printBtn = document.getElementById("printSelectedBtn");
  if (printBtn) {
    printBtn.addEventListener("click", function () {
      console.log("🖨️ Print button clicked!");
      const checked = document.querySelectorAll(".item-checkbox:checked");

      if (checked.length === 0) {
        showNotification(
          "⚠️ Pilih setidaknya satu item untuk dicetak struknya.",
          "error"
        );
        return;
      }

      const dropIds = [
        ...new Set(
          Array.from(checked).map((cb) => cb.getAttribute("data-drop-id"))
        ),
      ];

      console.log("Selected Drop IDs:", dropIds);

      const url = "cetak_struk.php?id=" + dropIds.join(",");
      console.log("Opening URL:", url);
      window.open(url, "_blank", "width=800,height=600");
    });
  }

  // ===== PRINT ALL BUTTON =====
  document.querySelectorAll(".print-all-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const customerId = this.getAttribute("data-customer-id");

      console.log(`🖨️ Printing all orders for customer ${customerId}`);

      const itemsForCustomer = document.querySelectorAll(
        `.item-checkbox[data-customer-id="${customerId}"]`
      );
      const dropIds = [
        ...new Set(
          Array.from(itemsForCustomer).map((cb) =>
            cb.getAttribute("data-drop-id")
          )
        ),
      ];

      if (dropIds.length === 0) {
        showNotification("⚠️ Tidak ada pesanan untuk dicetak.", "error");
        return;
      }

      const url = "cetak_struk.php?id=" + dropIds.join(",");
      window.open(url, "_blank", "width=800,height=600");
    });
  });

  // ===== DELETE BUTTON - FULL ORDER (MULTI-SELECT) =====
  const deleteBtn = document.getElementById("deleteBtn");
  if (deleteBtn) {
    deleteBtn.addEventListener("click", function (e) {
      e.preventDefault();
      console.log("🗑️ Delete button clicked!");

      const checked = document.querySelectorAll(".item-checkbox:checked");

      if (checked.length === 0) {
        showNotification(
          "⚠️ Pilih setidaknya satu pesanan untuk dihapus.",
          "error"
        );
        return;
      }

      const dropIds = [
        ...new Set(
          Array.from(checked).map((cb) => cb.getAttribute("data-drop-id"))
        ),
      ];

      const totalItems = checked.length;
      const totalOrders = dropIds.length;

      const confirmModal = document.getElementById("confirmDeleteModal");
      if (confirmModal) {
        // Update modal message
        const modalMessage = confirmModal.querySelector(".modal-message");
        if (modalMessage) {
          modalMessage.innerHTML = `
            <p style="margin-bottom: 12px;">Anda akan menghapus:</p>
            <ul style="list-style: none; padding: 0; margin: 0 0 16px 0;">
              <li>📦 <strong>${totalOrders}</strong> Pesanan</li>
              <li>📋 <strong>${totalItems}</strong> Item</li>
            </ul>
            <p style="color: #dc2626; font-weight: 600;">⚠️ Data yang dihapus tidak dapat dikembalikan!</p>
          `;
        }

        showModal("confirmDeleteModal");

        // Store data untuk bulk delete
        confirmModal.dataset.deleteType = "bulk";
        confirmModal.dataset.dropIds = dropIds.join(",");
        confirmModal.dataset.itemCount = totalItems;
      }
    });
  }

  // ===== CONFIRM DELETE - CANCEL =====
  const confirmCancelBtn = document.getElementById("confirmCancel");
  if (confirmCancelBtn) {
    confirmCancelBtn.addEventListener("click", function (e) {
      e.preventDefault();
      const confirmModal = document.getElementById("confirmDeleteModal");
      hideModal("confirmDeleteModal");
      if (confirmModal) {
        delete confirmModal.dataset.deleteType;
        delete confirmModal.dataset.dropIds;
        delete confirmModal.dataset.itemCount;
      }
    });
  }

  // ===== CONFIRM DELETE - OK (UNTUK BULK DELETE) =====
  const confirmOkBtn = document.getElementById("confirmOk");
  if (confirmOkBtn) {
    // Remove existing listener first (jika ada)
    const newBtn = confirmOkBtn.cloneNode(true);
    confirmOkBtn.parentNode.replaceChild(newBtn, confirmOkBtn);

    newBtn.addEventListener("click", function (e) {
      e.preventDefault();

      const confirmModal = document.getElementById("confirmDeleteModal");
      const deleteType = confirmModal.dataset.deleteType;

      // Jika ini bukan bulk delete, biarkan drop_delete.js handle
      if (deleteType !== "bulk") {
        console.log("⚠️ Not bulk delete, handled by drop_delete.js");
        return;
      }

      const dropIds = confirmModal.dataset.dropIds;
      const itemCount = confirmModal.dataset.itemCount || "0";

      if (!dropIds) {
        console.error("⚠️ No drop IDs found");
        hideModal("confirmDeleteModal");
        return;
      }

      hideModal("confirmDeleteModal");

      console.log("Deleting Drop IDs (bulk):", dropIds);

      this.disabled = true;
      this.textContent = "⏳ Menghapus...";

      fetch("../actions/drop/drop_delete.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "ids=" + dropIds,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            sessionStorage.setItem("showSuccess", "true");
            sessionStorage.setItem(
              "successMessage",
              data.message || "✅ Data berhasil dihapus."
            );
            window.location.reload();
          } else {
            showNotification(
              "❌ Gagal menghapus: " + (data.message || "Unknown error"),
              "error"
            );
            this.disabled = false;
            this.textContent = "✓ Hapus";
          }
        })
        .catch((error) => {
          showNotification("❌ Error: " + error.message, "error");
          this.disabled = false;
          this.textContent = "✓ Hapus";
        });
    });
  }

  // ===== STATUS DROPDOWN (PER ITEM) =====
  document.querySelectorAll(".status-dropdown").forEach((select) => {
    select.addEventListener("change", function () {
      const itemId = this.getAttribute("data-item-id");
      const dropId = this.getAttribute("data-drop-id");
      const newStatusId = this.value;

      if (!confirm("⚠️ Apakah Anda yakin ingin mengubah status item ini?")) {
        this.value = this.dataset.oldValue || this.value;
        return;
      }

      const oldValue = this.dataset.oldValue || this.value;
      this.dataset.oldValue = oldValue;
      this.disabled = true;

      fetch("../actions/drop/drop_update_item_status.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `item_id=${itemId}&status_id=${newStatusId}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            sessionStorage.setItem("showSuccess", "true");
            sessionStorage.setItem(
              "successMessage",
              data.message || "✅ Status item berhasil diubah."
            );
            window.location.reload();
          } else {
            showNotification(
              "❌ Gagal mengubah status: " + (data.message || "Unknown error"),
              "error"
            );
            this.value = oldValue;
            this.disabled = false;
          }
        })
        .catch((error) => {
          showNotification("❌ Error: " + error.message, "error");
          this.value = oldValue;
          this.disabled = false;
        });
    });

    select.dataset.oldValue = select.value;
  });

  // ===== KEYBOARD SHORTCUTS =====
  document.addEventListener("keydown", function (e) {
    // ESC to close modals
    if (e.key === "Escape") {
      const modals = document.querySelectorAll(
        ".modal, .note-modal, .item-edit-modal"
      );
      modals.forEach((modal) => {
        if (modal.style.display === "flex" || modal.style.display === "block") {
          if (modal.id) {
            hideModal(modal.id);
          } else {
            modal.style.display = "none";
            document.body.style.overflow = "";
          }
        }
      });

      const confirmModal = document.getElementById("confirmDeleteModal");
      if (confirmModal && confirmModal.style.display === "flex") {
        hideModal("confirmDeleteModal");
      }
    }

    // Ctrl+N for new order
    if (e.ctrlKey && e.key === "n") {
      e.preventDefault();
      const openAddBtn = document.getElementById("openAddModal");
      if (openAddBtn) openAddBtn.click();
    }

    // Ctrl+P for print selected
    if (e.ctrlKey && e.key === "p") {
      e.preventDefault();
      const printBtn = document.getElementById("printSelectedBtn");
      if (printBtn) printBtn.click();
    }

    // Delete key for delete selected
    if (e.key === "Delete") {
      const deleteBtn = document.getElementById("deleteBtn");
      if (
        deleteBtn &&
        document.querySelectorAll(".item-checkbox:checked").length > 0
      ) {
        e.preventDefault();
        deleteBtn.click();
      }
    }
  });

  // ===== NOTIFICATION HELPER =====
  function showNotification(message, type = "success") {
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 25px;
      background: ${type === "success" ? "#059669" : "#dc2626"};
      color: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      z-index: 10000;
      font-weight: 600;
      animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
      notification.style.animation = "slideOut 0.3s ease";
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  // ===== ADD CSS ANIMATIONS =====
  const style = document.createElement("style");
  style.textContent = `
    @keyframes slideIn {
      from {
        transform: translateX(100%);
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
        transform: translateX(100%);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(style);

  console.log("✅ All event listeners initialized");
  console.log("📋 Keyboard shortcuts:");
  console.log("  - ESC: Close modal");
  console.log("  - Ctrl+N: New order");
  console.log("  - Ctrl+P: Print selected");
  console.log("  - Delete: Delete selected");
  console.log("🖱️ Double-click row to edit order");
  console.log("📝 Click note cell to edit note");
});
