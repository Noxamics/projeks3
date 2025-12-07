// =====================================================================
// File: /js/drop/print_handler.js
// Print Handler with Fixed URL Path for New Database
// Version: 2.0 - Fixed for mifmyho2_sengkuclean
// =====================================================================

console.log("🔄 print_handler.js loading...");

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Print handler loaded - DOM ready");

  // ==================== URL BUILDER FOR NEW DATABASE ====================

  /**
   * Build correct print URL for hosting environment
   * Database: mifmyho2_sengkuclean
   * Host: sengkuclean.mif.myhost.id
   */
  function buildPrintUrl(dropId) {
    const protocol = window.location.protocol; // http: or https:
    const host = window.location.host; // sengkuclean.mif.myhost.id

    // Correct path for new database structure
    const printUrl = `${protocol}//${host}/actions/drop/cetak_struk.php?id=${dropId}`;

    console.log("🔗 Built Print URL:", printUrl);
    return printUrl;
  }

  // ==================== PRINT SINGLE ITEM ====================

  document.addEventListener("click", async function (e) {
    const printBtn = e.target.closest(".print-item-btn");

    if (printBtn) {
      console.log("🖨️ Print button clicked!");
      e.preventDefault();
      e.stopPropagation();

      const dropId = printBtn.getAttribute("data-drop-id");
      console.log("📦 Drop ID:", dropId);

      if (!dropId) {
        console.error("❌ Drop ID not found");
        await customError("ID pesanan tidak ditemukan");
        return;
      }

      const printUrl = buildPrintUrl(dropId);
      console.log("📄 Opening print window:", printUrl);

      try {
        // Open in new window
        const printWindow = window.open(
          printUrl,
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

  // ==================== PRINT ALL CUSTOMER ORDERS ====================

  document.addEventListener("click", async function (e) {
    const printAllBtn = e.target.closest(".print-all-btn");

    if (printAllBtn) {
      console.log("🖨️ Print All button clicked!");
      e.preventDefault();
      e.stopPropagation();

      const customerId = printAllBtn.getAttribute("data-customer-id");
      console.log("👤 Customer ID:", customerId);

      if (!customerId) {
        console.error("❌ Customer ID not found");
        await customError("ID customer tidak ditemukan");
        return;
      }

      // Find all drop IDs for this customer
      const customerContainer = document.querySelector(
        `.customer-orders-container[data-customer-id="${customerId}"]`
      );

      if (!customerContainer) {
        console.error("❌ Customer container not found");
        await customError("Data pesanan tidak ditemukan");
        return;
      }

      const orderRows = customerContainer.querySelectorAll(".order-item-row");
      const dropIds = new Set();

      orderRows.forEach((row) => {
        const dropId = row.getAttribute("data-drop-id");
        if (dropId) {
          dropIds.add(dropId);
        }
      });

      console.log("📦 Drop IDs found:", Array.from(dropIds));

      if (dropIds.size === 0) {
        await customAlert(
          "Tidak ada pesanan untuk dicetak",
          "Peringatan",
          "⚠️"
        );
        return;
      }

      // Confirm before printing multiple
      const confirmed = await customConfirm(
        `Akan membuka <strong>${dropIds.size}</strong> halaman cetak untuk customer ini.\n\nLanjutkan?`,
        "Cetak Semua Struk",
        "🖨️"
      );

      if (!confirmed) {
        console.log("❌ User cancelled print all");
        return;
      }

      console.log(`📄 Opening ${dropIds.size} print windows...`);

      showLoading(`Membuka ${dropIds.size} halaman cetak...`);

      let delay = 0;
      let successCount = 0;
      let failedCount = 0;

      dropIds.forEach((dropId, index) => {
        setTimeout(() => {
          const printUrl = buildPrintUrl(dropId);
          console.log(`📄 Opening [${index + 1}/${dropIds.size}]:`, printUrl);

          try {
            const printWindow = window.open(
              printUrl,
              `CetakStruk_${dropId}`,
              "width=800,height=900,scrollbars=yes,resizable=yes"
            );

            if (
              !printWindow ||
              printWindow.closed ||
              typeof printWindow.closed == "undefined"
            ) {
              console.error(`❌ Popup blocked for drop_id: ${dropId}`);
              failedCount++;
            } else {
              successCount++;
              console.log(
                `✅ [${successCount}/${dropIds.size}] Opened print window for drop_id: ${dropId}`
              );
            }
          } catch (error) {
            console.error(
              `❌ Error opening window for drop_id ${dropId}:`,
              error
            );
            failedCount++;
          }

          // Hide loading after last window
          if (index === dropIds.size - 1) {
            setTimeout(async () => {
              hideLoading();

              if (failedCount > 0) {
                await customAlert(
                  `✅ Berhasil membuka ${successCount} halaman cetak\n❌ Gagal membuka ${failedCount} halaman\n\nJika ada yang terblokir, izinkan popup untuk situs ini.`,
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

        delay += 500; // 500ms delay between windows
      });
    }
  });

  // ==================== PRINT SELECTED ORDERS ====================

  const printSelectedBtn = document.getElementById("printSelectedBtn");
  if (printSelectedBtn) {
    printSelectedBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      const checkedItems = document.querySelectorAll(".item-checkbox:checked");

      if (checkedItems.length === 0) {
        await customAlert(
          "Pilih setidaknya satu item untuk dicetak",
          "Tidak Ada Item Dipilih",
          "⚠️"
        );
        return;
      }

      // Get unique drop IDs
      const dropIds = [
        ...new Set(
          Array.from(checkedItems).map((cb) => cb.getAttribute("data-drop-id"))
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

      if (!confirmed) {
        console.log("❌ User cancelled print selected");
        return;
      }

      showLoading(`Membuka ${dropIds.length} halaman cetak...`);

      let delay = 0;
      let successCount = 0;
      let failedCount = 0;

      dropIds.forEach((dropId, index) => {
        setTimeout(() => {
          const printUrl = buildPrintUrl(dropId);
          console.log(`📄 Opening [${index + 1}/${dropIds.length}]:`, printUrl);

          try {
            const printWindow = window.open(
              printUrl,
              `CetakStruk_${dropId}`,
              "width=800,height=900,scrollbars=yes,resizable=yes"
            );

            if (
              !printWindow ||
              printWindow.closed ||
              typeof printWindow.closed == "undefined"
            ) {
              console.error(`❌ Popup blocked for drop_id: ${dropId}`);
              failedCount++;
            } else {
              successCount++;
              console.log(
                `✅ [${successCount}/${dropIds.length}] Opened successfully`
              );
            }
          } catch (error) {
            console.error(`❌ Error:`, error);
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

  // ==================== HOVER EFFECTS ====================

  const style = document.createElement("style");
  style.textContent = `
    .print-item-btn,
    .print-all-btn {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .print-item-btn:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4) !important;
    }
    
    .print-all-btn:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4) !important;
    }
    
    .print-item-btn:active,
    .print-all-btn:active {
      transform: translateY(0) !important;
    }
    
    .print-item-btn:disabled,
    .print-all-btn:disabled {
      opacity: 0.5 !important;
      cursor: not-allowed !important;
      transform: none !important;
    }
  `;
  document.head.appendChild(style);

  console.log("✅ Print handlers initialized");
  console.log("📍 Database: mifmyho2_sengkuclean");
  console.log("🌐 Host: sengkuclean.mif.myhost.id");
});

console.log("✅ print_handler.js loaded completely");
