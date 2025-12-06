// File: /js/drop/print_handler.js
// Handler untuk tombol cetak struk per item - DEBUG VERSION

console.log("🔄 print_handler.js is loading...");

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Print handler loaded - DOM ready");

  // Test if buttons exist
  const printButtons = document.querySelectorAll(".print-item-btn");
  const printAllButtons = document.querySelectorAll(".print-all-btn");

  console.log(`📊 Found ${printButtons.length} print item buttons`);
  console.log(`📊 Found ${printAllButtons.length} print all buttons`);

  // ===== CETAK STRUK PER ITEM =====
  document.addEventListener("click", function (e) {
    console.log("👆 Click detected on:", e.target);

    // Check if clicked element or its parent has print-item-btn class
    const printBtn = e.target.closest(".print-item-btn");

    if (printBtn) {
      console.log("🖨️ Print button clicked!");
      e.preventDefault();
      e.stopPropagation();

      const dropId = printBtn.getAttribute("data-drop-id");
      console.log("📦 Drop ID:", dropId);

      if (!dropId) {
        console.error("❌ Drop ID not found");
        alert("❌ ID pesanan tidak ditemukan");
        return;
      }

      console.log("🖨️ Printing struk for drop_id:", dropId);

      // Build absolute URL
      const currentUrl = window.location.href;
      console.log("🌐 Current URL:", currentUrl);

      // Detect base path dynamically
      const pathArray = window.location.pathname.split("/");
      const projectIndex = pathArray.indexOf("PROJEKS3");

      let baseUrl;
      if (projectIndex !== -1) {
        const basePath = pathArray.slice(0, projectIndex + 1).join("/");
        baseUrl = window.location.origin + basePath;
      } else {
        // Fallback
        baseUrl = window.location.origin + "/PROJEKS3";
      }

      const printUrl = `${baseUrl}/actions/drop/cetak_struk.php?id=${dropId}`;

      console.log("📄 Base URL:", baseUrl);
      console.log("📄 Full Print URL:", printUrl);

      // Open print window
      try {
        const printWindow = window.open(
          printUrl,
          "CetakStruk_" + dropId,
          "width=800,height=900,scrollbars=yes,resizable=yes"
        );

        if (
          !printWindow ||
          printWindow.closed ||
          typeof printWindow.closed == "undefined"
        ) {
          alert(
            "❌ Popup diblokir oleh browser!\n\nSilakan izinkan popup untuk situs ini."
          );
          console.error("❌ Popup blocked");
        } else {
          console.log("✅ Print window opened successfully");
        }
      } catch (error) {
        console.error("❌ Error opening window:", error);
        alert("❌ Error: " + error.message);
      }
    }
  });

  // ===== CETAK SEMUA STRUK CUSTOMER =====
  document.addEventListener("click", function (e) {
    const printAllBtn = e.target.closest(".print-all-btn");

    if (printAllBtn) {
      console.log("🖨️ Print All button clicked!");
      e.preventDefault();
      e.stopPropagation();

      const customerId = printAllBtn.getAttribute("data-customer-id");
      console.log("👤 Customer ID:", customerId);

      if (!customerId) {
        console.error("❌ Customer ID not found");
        alert("❌ ID customer tidak ditemukan");
        return;
      }

      console.log("🖨️ Printing all struks for customer:", customerId);

      // Get all drop IDs for this customer
      const customerContainer = document.querySelector(
        `.customer-orders-container[data-customer-id="${customerId}"]`
      );

      if (!customerContainer) {
        console.error("❌ Customer container not found");
        alert("❌ Data pesanan tidak ditemukan");
        return;
      }

      const orderRows = customerContainer.querySelectorAll(".order-item-row");
      const dropIds = new Set(); // Use Set to avoid duplicates

      orderRows.forEach((row) => {
        const dropId = row.getAttribute("data-drop-id");
        if (dropId) {
          dropIds.add(dropId);
        }
      });

      console.log("📦 Drop IDs found:", Array.from(dropIds));

      if (dropIds.size === 0) {
        alert("❌ Tidak ada pesanan untuk dicetak");
        return;
      }

      // Confirm before printing multiple
      const confirmMsg = `Akan mencetak ${dropIds.size} struk untuk customer ini.\n\nLanjutkan?`;
      if (!confirm(confirmMsg)) {
        console.log("❌ User cancelled print all");
        return;
      }

      console.log(`📄 Opening ${dropIds.size} print windows...`);

      // Detect base path dynamically
      const pathArray = window.location.pathname.split("/");
      const projectIndex = pathArray.indexOf("PROJEKS3");

      let baseUrl;
      if (projectIndex !== -1) {
        const basePath = pathArray.slice(0, projectIndex + 1).join("/");
        baseUrl = window.location.origin + basePath;
      } else {
        // Fallback
        baseUrl = window.location.origin + "/PROJEKS3";
      }

      console.log("📄 Base URL for all:", baseUrl);

      // Open each struk with delay to prevent browser blocking
      let delay = 0;
      let successCount = 0;

      dropIds.forEach((dropId) => {
        setTimeout(() => {
          const printUrl = `${baseUrl}/actions/drop/cetak_struk.php?id=${dropId}`;
          console.log(`📄 Opening [${delay / 500 + 1}]:`, printUrl);

          try {
            const printWindow = window.open(
              printUrl,
              "CetakStruk_" + dropId,
              "width=800,height=900,scrollbars=yes,resizable=yes"
            );

            if (
              !printWindow ||
              printWindow.closed ||
              typeof printWindow.closed == "undefined"
            ) {
              console.error(`❌ Popup blocked for drop_id: ${dropId}`);
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
          }
        }, delay);

        delay += 500; // 500ms delay between each window
      });

      setTimeout(() => {
        alert(
          `✅ Membuka ${dropIds.size} jendela cetak...\n\nJika ada yang terblokir, izinkan popup untuk situs ini.`
        );
      }, 100);
    }
  });

  // ===== HOVER EFFECT FOR PRINT BUTTONS =====
  const style = document.createElement("style");
  style.textContent = `
    .print-item-btn {
      transition: all 0.3s ease !important;
    }
    
    .print-item-btn:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4) !important;
    }
    
    .print-item-btn:active {
      transform: translateY(0) !important;
    }
    
    .print-all-btn:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4) !important;
    }
    
    .delete-item-btn:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
    }
    
    .delete-item-btn:disabled,
    .print-item-btn:disabled {
      opacity: 0.5 !important;
      cursor: not-allowed !important;
      transform: none !important;
    }
  `;
  document.head.appendChild(style);

  console.log("✅ Print handlers initialized");
  console.log("✅ Hover styles applied");
});

// Log when script is fully loaded
console.log("✅ print_handler.js loaded completely");
