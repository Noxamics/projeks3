// =====================================================================
// File: /js/drop/drop_main.js
// Main Drop Management - FIXED: All Print Opens in New Window
// Version: 3.1 - Fixed Print Behavior
// =====================================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Drop main module loaded");

  // ==================== HELPER FUNCTIONS ====================

  function getBaseUrl() {
    const protocol = window.location.protocol;
    const host = window.location.host;
    return `${protocol}//${host}`;
  }

  // ==================== SUCCESS MESSAGE HANDLER ====================

  if (sessionStorage.getItem("drop_showSuccess") === "true") {
    const message =
      sessionStorage.getItem("drop_successMessage") || "✅ Operasi berhasil!";

    customSuccess(message).then(() => {
      sessionStorage.removeItem("drop_showSuccess");
      sessionStorage.removeItem("drop_successMessage");
    });
  }

  // ==================== CHECKBOX MANAGEMENT ====================

  // Customer checkbox - select all items for customer
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

  // Item checkbox - update customer checkbox if all items selected
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

  // ==================== PRINT HANDLERS ====================

  // Print selected items
  const printBtn = document.getElementById("printSelectedBtn");
  if (printBtn) {
    printBtn.addEventListener("click", async function () {
      const checked = document.querySelectorAll(".item-checkbox:checked");

      if (checked.length === 0) {
        await customAlert(
          "Pilih setidaknya satu item untuk dicetak",
          "Tidak Ada Item Dipilih",
          "⚠️"
        );
        return;
      }

      const dropIds = [
        ...new Set(
          Array.from(checked).map((cb) => cb.getAttribute("data-drop-id"))
        ),
      ];

      if (dropIds.length === 0) {
        await customAlert("Tidak ada pesanan yang dipilih", "Peringatan", "⚠️");
        return;
      }

      const confirmed = await customConfirm(
        `Akan membuka <strong>${dropIds.length}</strong> halaman cetak.\n\nLanjutkan?`,
        "Cetak Struk Terpilih",
        "🖨️"
      );

      if (!confirmed) return;

      const baseUrl = getBaseUrl();
      showLoading(`Membuka ${dropIds.length} halaman cetak...`);

      let delay = 0;
      let successCount = 0;
      let failedCount = 0;

      dropIds.forEach((dropId, index) => {
        setTimeout(() => {
          const url = `${baseUrl}/actions/drop/cetak_struk.php?id=${dropId}`;

          try {
            const printWindow = window.open(
              url,
              `CetakStruk_${dropId}`,
              "width=800,height=900,scrollbars=yes,resizable=yes"
            );

            if (
              !printWindow ||
              printWindow.closed ||
              typeof printWindow.closed == "undefined"
            ) {
              failedCount++;
            } else {
              successCount++;
            }
          } catch (error) {
            failedCount++;
          }

          // Show result after last window
          if (index === dropIds.length - 1) {
            setTimeout(async () => {
              hideLoading();

              if (failedCount > 0) {
                await customAlert(
                  `✅ Berhasil: ${successCount} halaman\n❌ Gagal: ${failedCount} halaman\n\nJika ada yang terblokir, izinkan popup untuk situs ini.`,
                  "Cetak Selesai",
                  "📊"
                );
              } else {
                await customSuccess(
                  `Berhasil membuka ${successCount} halaman cetak!`
                );
              }
            }, 500);
          }
        }, delay);
        delay += 500;
      });
    });
  }

  // Print all items for customer
  document.querySelectorAll(".print-all-btn").forEach((btn) => {
    btn.addEventListener("click", async function (e) {
      e.preventDefault();
      e.stopPropagation();

      const customerId = this.getAttribute("data-customer-id");
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
        await customAlert(
          "Tidak ada pesanan untuk dicetak",
          "Peringatan",
          "⚠️"
        );
        return;
      }

      const confirmed = await customConfirm(
        `Akan membuka <strong>${dropIds.length}</strong> halaman cetak untuk customer ini.\n\nLanjutkan?`,
        "Cetak Semua Struk",
        "🖨️"
      );

      if (!confirmed) return;

      const baseUrl = getBaseUrl();
      showLoading(`Membuka ${dropIds.length} halaman cetak...`);

      let delay = 0;
      let successCount = 0;
      let failedCount = 0;

      dropIds.forEach((dropId, index) => {
        setTimeout(() => {
          const url = `${baseUrl}/actions/drop/cetak_struk.php?id=${dropId}`;

          try {
            const printWindow = window.open(
              url,
              `CetakStruk_${dropId}`,
              "width=800,height=900,scrollbars=yes,resizable=yes"
            );

            if (
              !printWindow ||
              printWindow.closed ||
              typeof printWindow.closed == "undefined"
            ) {
              failedCount++;
            } else {
              successCount++;
            }
          } catch (error) {
            failedCount++;
          }

          if (index === dropIds.length - 1) {
            setTimeout(async () => {
              hideLoading();

              if (failedCount > 0) {
                await customAlert(
                  `✅ Berhasil: ${successCount} halaman\n❌ Gagal: ${failedCount} halaman\n\nJika ada yang terblokir, izinkan popup untuk situs ini.`,
                  "Cetak Selesai",
                  "📊"
                );
              } else {
                await customSuccess(
                  `Berhasil membuka ${successCount} halaman cetak!`
                );
              }
            }, 500);
          }
        }, delay);
        delay += 500;
      });
    });
  });

  // Print single item - FIXED: Use window.open instead of window.location.href
  document.addEventListener("click", async function (e) {
    const printItemBtn = e.target.closest(".print-item-btn");

    if (printItemBtn) {
      e.preventDefault();
      e.stopPropagation();

      const dropId = printItemBtn.getAttribute("data-drop-id");

      if (!dropId) {
        await customError("ID pesanan tidak ditemukan");
        return;
      }

      const baseUrl = getBaseUrl();
      const url = `${baseUrl}/actions/drop/cetak_struk.php?id=${dropId}`;

      // FIXED: Open in new window instead of redirecting
      try {
        const printWindow = window.open(
          url,
          `CetakStruk_${dropId}`,
          "width=800,height=900,scrollbars=yes,resizable=yes,menubar=no,toolbar=no,location=no,status=no"
        );

        if (
          !printWindow ||
          printWindow.closed ||
          typeof printWindow.closed == "undefined"
        ) {
          console.error("❌ Popup blocked");
          await customError(
            "Popup diblokir oleh browser!\n\nSilakan izinkan popup untuk situs ini dan coba lagi.",
            "Popup Diblokir"
          );
        } else {
          console.log("✅ Print window opened successfully");
        }
      } catch (error) {
        console.error("❌ Error opening window:", error);
        await customError(`Error membuka halaman cetak: ${error.message}`);
      }
    }
  });

  // ==================== DELETE HANDLERS ====================

  // Delete button - bulk delete
  const deleteBtn = document.getElementById("deleteBtn");
  if (deleteBtn) {
    deleteBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      const checked = document.querySelectorAll(".item-checkbox:checked");

      if (checked.length === 0) {
        await customAlert(
          "Pilih setidaknya satu pesanan untuk dihapus",
          "Tidak Ada Item Dipilih",
          "⚠️"
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

      const confirmed = await customConfirm(
        `Anda akan menghapus:\n\n📦 <strong>${totalOrders}</strong> Pesanan\n📋 <strong>${totalItems}</strong> Item\n\n<span style="color: #dc2626; font-weight: 600;">⚠️ Data yang dihapus tidak dapat dikembalikan!</span>`,
        "Konfirmasi Hapus",
        "🗑️"
      );

      if (!confirmed) {
        console.log("❌ User cancelled bulk delete");
        return;
      }

      showLoading("Menghapus data...");

      try {
        const response = await fetch("../actions/drop/drop_delete.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "ids=" + dropIds.join(","),
        });

        const data = await response.json();

        hideLoading();

        if (data.success) {
          sessionStorage.setItem("drop_showSuccess", "true");
          sessionStorage.setItem(
            "drop_successMessage",
            data.message || "✅ Data berhasil dihapus."
          );
          window.location.reload();
        } else {
          await customError(
            data.message || "Terjadi kesalahan saat menghapus data",
            "Gagal Menghapus"
          );
        }
      } catch (error) {
        hideLoading();
        await customError(error.message, "Terjadi Kesalahan");
      }
    });
  }

  // ==================== STATUS UPDATE HANDLER ====================

  document.querySelectorAll(".status-dropdown").forEach((select) => {
    select.addEventListener("change", async function () {
      const itemId = this.getAttribute("data-item-id");
      const newStatusId = this.value;
      const oldValue = this.dataset.oldValue || this.value;

      const confirmed = await customConfirm(
        "Ubah status item ini?",
        "Konfirmasi Perubahan",
        "⚠️"
      );

      if (!confirmed) {
        this.value = oldValue;
        return;
      }

      this.dataset.oldValue = oldValue;
      this.disabled = true;

      showLoading("Mengubah status...");

      try {
        const response = await fetch(
          "../actions/drop/drop_update_item_status.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `item_id=${itemId}&status_id=${newStatusId}`,
          }
        );

        const data = await response.json();

        hideLoading();

        if (data.success) {
          sessionStorage.setItem("drop_showSuccess", "true");
          sessionStorage.setItem(
            "drop_successMessage",
            data.message || "✅ Status item berhasil diubah."
          );
          window.location.reload();
        } else {
          await customError(
            data.message || "Gagal mengubah status",
            "Gagal Update Status"
          );
          this.value = oldValue;
          this.disabled = false;
        }
      } catch (error) {
        hideLoading();
        await customError(error.message, "Terjadi Kesalahan");
        this.value = oldValue;
        this.disabled = false;
      }
    });

    select.dataset.oldValue = select.value;
  });

  // ==================== KEYBOARD SHORTCUTS ====================

  document.addEventListener("keydown", function (e) {
    // ESC - Close modals (handled by modal_system.js)

    // Ctrl+N - New order
    if (e.ctrlKey && e.key === "n") {
      e.preventDefault();
      const openAddBtn = document.getElementById("openAddModal");
      if (openAddBtn) openAddBtn.click();
    }

    // Ctrl+P - Print selected
    if (e.ctrlKey && e.key === "p") {
      e.preventDefault();
      const printBtn = document.getElementById("printSelectedBtn");
      if (printBtn) printBtn.click();
    }

    // Delete - Delete selected
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

  // ==================== INITIALIZATION COMPLETE ====================

  console.log("✅ All event listeners initialized");
  console.log("📋 Keyboard shortcuts:");
  console.log("  - ESC: Close modal");
  console.log("  - Ctrl+N: New order");
  console.log("  - Ctrl+P: Print selected");
  console.log("  - Delete: Delete selected");
  console.log("🖱️ Double-click row to edit order");
  console.log("📝 Click note cell to edit note");
  console.log("✅ FIXED: All print buttons now open in NEW WINDOW");
});
