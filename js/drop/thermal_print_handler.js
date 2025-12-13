// =====================================================================
// File: /js/drop/thermal_print_handler.js
// ESC/POS Thermal Printer Handler for 80mm Printer
// Supports: QZ Tray (Desktop) & Android Print Service
// Version: 1.0 - Production Ready - Full Integration
// =====================================================================

/**
 * THERMAL PRINTER CONFIGURATION
 */
const THERMAL_CONFIG = {
  paperWidth: 80, // mm
  charsPerLine: 48, // Safe for all 80mm printers
  encoding: "CP437", // Standard ESC/POS encoding
  qzTrayUrl: "https://cdn.jsdelivr.net/npm/qz-tray@2.2.2/qz-tray.js",
};

/**
 * ESC/POS COMMANDS (Universal)
 */
const ESC = "\x1B";
const GS = "\x1D";

const ESCPOS = {
  INIT: ESC + "@", // Initialize printer
  ALIGN_LEFT: ESC + "a" + "\x00",
  ALIGN_CENTER: ESC + "a" + "\x01",
  ALIGN_RIGHT: ESC + "a" + "\x02",
  BOLD_ON: ESC + "E" + "\x01",
  BOLD_OFF: ESC + "E" + "\x00",
  FONT_A: ESC + "M" + "\x00", // Normal font
  FONT_B: ESC + "M" + "\x01", // Small font
  LINE_FEED: "\n",
  FEED_3: "\n\n\n",
  CUT_PARTIAL: GS + "V" + "\x00", // Partial cut (optional)
  CUT_FULL: GS + "V" + "\x01", // Full cut (optional)
  DOUBLE_HEIGHT_ON: ESC + "!" + "\x10",
  DOUBLE_HEIGHT_OFF: ESC + "!" + "\x00",
  UNDERLINE_ON: ESC + "-" + "\x01",
  UNDERLINE_OFF: ESC + "-" + "\x00",
};

/**
 * UTILITY: Truncate or pad text to fit character limit
 */
function formatLine(
  text,
  maxChars = THERMAL_CONFIG.charsPerLine,
  align = "left"
) {
  text = String(text || "");

  if (text.length > maxChars) {
    return text.substring(0, maxChars);
  }

  if (align === "center") {
    const padding = Math.floor((maxChars - text.length) / 2);
    return (
      " ".repeat(padding) + text + " ".repeat(maxChars - text.length - padding)
    );
  } else if (align === "right") {
    return " ".repeat(maxChars - text.length) + text;
  }

  return text + " ".repeat(maxChars - text.length);
}

/**
 * UTILITY: Create separator line
 */
function separator(char = "-", maxChars = THERMAL_CONFIG.charsPerLine) {
  return char.repeat(maxChars);
}

/**
 * UTILITY: Format price (2 columns)
 */
function formatPrice(label, price, maxChars = THERMAL_CONFIG.charsPerLine) {
  const priceStr = "Rp" + price.toLocaleString("id-ID");
  const spaces = maxChars - label.length - priceStr.length;
  return label + " ".repeat(Math.max(1, spaces)) + priceStr;
}

/**
 * UTILITY: Format date from "2023-12-13 14:30:00" to "13 Des 2023 14:30"
 */
function formatDate(dateStr) {
  if (!dateStr) return "";

  const months = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Agu",
    "Sep",
    "Okt",
    "Nov",
    "Des",
  ];
  const date = new Date(dateStr.replace(" ", "T"));

  const day = date.getDate();
  const month = months[date.getMonth()];
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");

  return `${day} ${month} ${year} ${hours}:${minutes}`;
}

/**
 * BUILD ESC/POS RECEIPT DATA
 */
function buildThermalReceipt(orderData) {
  let escpos = "";

  // Initialize printer
  escpos += ESCPOS.INIT;

  // ==================== HEADER ====================
  escpos += ESCPOS.ALIGN_CENTER;
  escpos += ESCPOS.BOLD_ON;
  escpos += ESCPOS.DOUBLE_HEIGHT_ON;
  escpos += "SENGKUCLEAN" + ESCPOS.LINE_FEED;
  escpos += ESCPOS.DOUBLE_HEIGHT_OFF;
  escpos += ESCPOS.BOLD_OFF;

  escpos += ESCPOS.FONT_B;
  escpos += "Laundry Premium Service" + ESCPOS.LINE_FEED;
  escpos += ESCPOS.FONT_A;
  escpos += separator("=") + ESCPOS.LINE_FEED;

  // ==================== ORDER INFO ====================
  escpos += ESCPOS.ALIGN_LEFT;
  escpos += ESCPOS.BOLD_ON;
  escpos += formatLine("Order: " + orderData.order_code) + ESCPOS.LINE_FEED;
  escpos += ESCPOS.BOLD_OFF;

  escpos +=
    formatLine("Tanggal: " + formatDate(orderData.trans_date)) +
    ESCPOS.LINE_FEED;

  if (orderData.est_finish_date) {
    escpos +=
      formatLine("Selesai: " + formatDate(orderData.est_finish_date)) +
      ESCPOS.LINE_FEED;
  }

  escpos +=
    formatLine("Customer: " + orderData.customer.name) + ESCPOS.LINE_FEED;
  escpos += formatLine("Phone: " + orderData.customer.phone) + ESCPOS.LINE_FEED;

  if (orderData.employee_name) {
    escpos +=
      formatLine("Kasir: " + orderData.employee_name) + ESCPOS.LINE_FEED;
  }

  escpos += separator("=") + ESCPOS.LINE_FEED;

  // ==================== ITEMS ====================
  escpos += ESCPOS.BOLD_ON;
  escpos += formatLine("DETAIL PESANAN") + ESCPOS.LINE_FEED;
  escpos += ESCPOS.BOLD_OFF;
  escpos += separator("-") + ESCPOS.LINE_FEED;

  orderData.items.forEach((item, index) => {
    const num = index + 1 + ".";

    // Item number and brand
    escpos += formatLine(num + " " + item.brand) + ESCPOS.LINE_FEED;

    // Service (indented)
    escpos += formatLine("  " + item.service_name) + ESCPOS.LINE_FEED;

    // Price
    const priceStr = "Rp" + item.price.toLocaleString("id-ID");
    escpos +=
      formatLine("  " + priceStr, THERMAL_CONFIG.charsPerLine, "left") +
      ESCPOS.LINE_FEED;

    // Item notes (if any)
    if (item.notes && item.notes.trim()) {
      escpos += formatLine("  Catatan: " + item.notes) + ESCPOS.LINE_FEED;
    }

    escpos += ESCPOS.LINE_FEED;
  });

  escpos += separator("-") + ESCPOS.LINE_FEED;

  // ==================== TOTALS ====================
  const totalPrice = orderData.total_amount || 0;
  const amountPaid = orderData.payment.amount_paid || 0;
  const change = Math.max(0, amountPaid - totalPrice);

  escpos += formatPrice("TOTAL", totalPrice) + ESCPOS.LINE_FEED;
  escpos += formatPrice("Dibayar", amountPaid) + ESCPOS.LINE_FEED;

  escpos += ESCPOS.BOLD_ON;
  escpos += formatPrice("KEMBALIAN", change) + ESCPOS.LINE_FEED;
  escpos += ESCPOS.BOLD_OFF;

  escpos += separator("=") + ESCPOS.LINE_FEED;

  // ==================== PAYMENT INFO ====================
  escpos +=
    formatLine("Metode: " + orderData.payment.method) + ESCPOS.LINE_FEED;
  escpos +=
    formatLine("Status: " + orderData.payment.status.toUpperCase()) +
    ESCPOS.LINE_FEED;

  // Order notes
  if (orderData.note && orderData.note.trim()) {
    escpos += separator("-") + ESCPOS.LINE_FEED;
    escpos += ESCPOS.BOLD_ON;
    escpos += formatLine("CATATAN:") + ESCPOS.LINE_FEED;
    escpos += ESCPOS.BOLD_OFF;
    escpos += formatLine(orderData.note) + ESCPOS.LINE_FEED;
  }

  // ==================== FOOTER ====================
  escpos += separator("=") + ESCPOS.LINE_FEED;
  escpos += ESCPOS.ALIGN_CENTER;
  escpos +=
    formatLine(
      "Terima kasih atas kepercayaan Anda",
      THERMAL_CONFIG.charsPerLine,
      "center"
    ) + ESCPOS.LINE_FEED;
  escpos +=
    formatLine("www.sengkuclean.com", THERMAL_CONFIG.charsPerLine, "center") +
    ESCPOS.LINE_FEED;

  // Feed and cut
  escpos += ESCPOS.FEED_3;
  escpos += ESCPOS.CUT_PARTIAL; // Optional: cut paper

  return escpos;
}

/**
 * DETECT DEVICE TYPE
 */
function detectDevice() {
  const ua = navigator.userAgent.toLowerCase();
  const isAndroid = /android/.test(ua);
  const isIOS = /iphone|ipad|ipod/.test(ua);
  const isMobile = isAndroid || isIOS || /mobile/.test(ua);

  return {
    isAndroid,
    isIOS,
    isMobile,
    isDesktop: !isMobile,
  };
}

/**
 * PRINT VIA QZ TRAY (Desktop)
 */
async function printViaQZTray(escposData) {
  try {
    // Load QZ Tray library if not loaded
    if (typeof qz === "undefined") {
      await loadQZTray();
    }

    // Connect to QZ Tray
    if (!qz.websocket.isActive()) {
      await qz.websocket.connect();
    }

    // Find printers
    const printers = await qz.printers.find();

    if (printers.length === 0) {
      throw new Error(
        "Tidak ada printer yang terdeteksi.\n\nPastikan printer thermal sudah terhubung."
      );
    }

    // Use first printer or let user choose
    let selectedPrinter = printers[0];

    // Try to find thermal printer by name
    const thermalKeywords = [
      "thermal",
      "pos",
      "receipt",
      "tm-",
      "rp",
      "xprinter",
      "zj",
      "epson",
    ];
    const thermalPrinter = printers.find((p) =>
      thermalKeywords.some((keyword) => p.toLowerCase().includes(keyword))
    );

    if (thermalPrinter) {
      selectedPrinter = thermalPrinter;
    }

    console.log("Printing to:", selectedPrinter);
    console.log("Available printers:", printers);

    // Create print config
    const config = qz.configs.create(selectedPrinter, {
      encoding: THERMAL_CONFIG.encoding,
    });

    // Print raw ESC/POS data
    await qz.print(config, [escposData]);

    return { success: true, printer: selectedPrinter };
  } catch (error) {
    console.error("QZ Tray print error:", error);
    throw error;
  }
}

/**
 * LOAD QZ TRAY LIBRARY
 */
function loadQZTray() {
  return new Promise((resolve, reject) => {
    if (typeof qz !== "undefined") {
      resolve();
      return;
    }

    const script = document.createElement("script");
    script.src = THERMAL_CONFIG.qzTrayUrl;
    script.onload = resolve;
    script.onerror = () => reject(new Error("Failed to load QZ Tray"));
    document.head.appendChild(script);
  });
}

/**
 * PRINT VIA ANDROID (Web Print API / Intent)
 */
async function printViaAndroid(escposData, orderData) {
  try {
    // Method 1: Try Android Print Service Intent
    if (typeof Android !== "undefined" && Android.print) {
      // If WebView with print method exposed
      Android.print(escposData);
      return { success: true, method: "android_webview" };
    }

    // Method 2: Create invisible iframe with plain text
    // This triggers Android's print dialog which can route to Print Service
    const printFrame = document.createElement("iframe");
    printFrame.style.position = "absolute";
    printFrame.style.width = "0";
    printFrame.style.height = "0";
    printFrame.style.border = "none";

    document.body.appendChild(printFrame);

    const frameDoc = printFrame.contentWindow.document;
    frameDoc.open();
    frameDoc.write("<html><head><title>Print</title></head><body>");
    frameDoc.write(
      '<pre style="font-family: monospace; font-size: 12px; white-space: pre-wrap;">'
    );

    // Convert ESC/POS to plain text (remove control chars for preview)
    const plainText = escposData.replace(/[\x00-\x1F\x7F-\x9F]/g, "");
    frameDoc.write(plainText);

    frameDoc.write("</pre></body></html>");
    frameDoc.close();

    // Wait for content to load
    await new Promise((resolve) => setTimeout(resolve, 500));

    // Trigger print dialog
    printFrame.contentWindow.print();

    // Clean up after delay
    setTimeout(() => {
      document.body.removeChild(printFrame);
    }, 1000);

    return { success: true, method: "android_print_dialog" };
  } catch (error) {
    console.error("Android print error:", error);
    throw error;
  }
}

/**
 * MAIN PRINT FUNCTION
 */
async function printThermalReceipt(orderData) {
  try {
    showLoading("Mempersiapkan struk thermal...");

    // Build ESC/POS data
    const escposData = buildThermalReceipt(orderData);

    console.log("ESC/POS Data Length:", escposData.length);
    console.log("ESC/POS Preview:", escposData.substring(0, 100));

    // Detect device
    const device = detectDevice();
    console.log("Device:", device);

    let result;

    if (device.isDesktop) {
      // Desktop: Use QZ Tray
      showLoading("Menghubungkan ke printer thermal...");
      result = await printViaQZTray(escposData);

      hideLoading();
      await customSuccess(
        `Struk thermal berhasil dicetak!\n\nPrinter: ${result.printer}`
      );
    } else if (device.isAndroid) {
      // Android: Use Print Service
      showLoading("Membuka layanan cetak Android...");
      result = await printViaAndroid(escposData, orderData);

      hideLoading();
      await customSuccess(
        "Silakan pilih printer thermal Anda di dialog cetak Android yang muncul"
      );
    } else {
      // iOS or other mobile: Fallback to browser print
      hideLoading();
      await customAlert(
        "Untuk mencetak thermal di perangkat ini, gunakan:\n\n• Laptop/PC dengan QZ Tray\n• Android dengan ESC/POS Print Service",
        "Cetak Thermal Tidak Didukung",
        "warning"
      );
    }

    return result;
  } catch (error) {
    hideLoading();
    console.error("Print error:", error);

    let errorMessage = "Gagal mencetak struk thermal:\n\n" + error.message;

    if (error.message.includes("QZ Tray") || error.message.includes("qz")) {
      errorMessage +=
        "\n\n📥 Pastikan QZ Tray sudah terinstall dan berjalan.\n\nDownload: https://qz.io/download/";
    }

    if (error.message.includes("printer")) {
      errorMessage +=
        "\n\n🖨️ Pastikan printer thermal sudah:\n• Terhubung via USB/Bluetooth\n• Driver terinstall\n• Power ON";
    }

    await customError(errorMessage);
    throw error;
  }
}

/**
 * FETCH ORDER DATA FROM SERVER (Using existing endpoint)
 */
async function fetchOrderDataForThermal(dropId) {
  try {
    const response = await fetch(
      `/actions/drop/get_order_detail.php?drop_id=${dropId}`
    );

    if (!response.ok) {
      throw new Error("Network response was not ok");
    }

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.message || "Failed to get order data");
    }

    console.log("Order data fetched:", data);

    return data;
  } catch (error) {
    console.error("Fetch error:", error);
    throw new Error("Gagal mengambil data pesanan: " + error.message);
  }
}

/**
 * EVENT HANDLERS
 */
document.addEventListener("DOMContentLoaded", function () {
  console.log("🖨️ Thermal print handler loaded");

  // ==================== PRINT SINGLE ITEM THERMAL ====================
  document.addEventListener("click", async function (e) {
    const thermalBtn = e.target.closest(".thermal-print-btn");

    if (thermalBtn) {
      e.preventDefault();
      e.stopPropagation();

      console.log("Thermal print button clicked");

      const dropId = thermalBtn.getAttribute("data-drop-id");

      if (!dropId) {
        await customError("ID pesanan tidak ditemukan");
        return;
      }

      try {
        // Disable button
        thermalBtn.disabled = true;
        const originalHtml = thermalBtn.innerHTML;
        thermalBtn.innerHTML =
          '<i class="bi bi-hourglass-split"></i> Proses...';

        // Fetch order data
        showLoading("Mengambil data pesanan...");
        const orderData = await fetchOrderDataForThermal(dropId);
        hideLoading();

        console.log("Fetched order data:", orderData);

        // Print thermal receipt
        await printThermalReceipt(orderData);

        // Restore button
        thermalBtn.disabled = false;
        thermalBtn.innerHTML = originalHtml;
      } catch (error) {
        hideLoading();
        console.error("Thermal print error:", error);

        // Restore button
        if (thermalBtn) {
          thermalBtn.disabled = false;
          const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill" viewBox="0 0 16 16">
            <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/>
            <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
          </svg>`;
          thermalBtn.innerHTML = svg;
        }
      }
    }
  });

  // ==================== PRINT ALL CUSTOMER THERMAL ====================
  document.addEventListener("click", async function (e) {
    const thermalAllBtn = e.target.closest(".thermal-all-btn");

    if (thermalAllBtn) {
      e.preventDefault();
      e.stopPropagation();

      console.log("Thermal print all customer clicked");

      const customerId = thermalAllBtn.getAttribute("data-customer-id");

      if (!customerId) {
        await customError("ID customer tidak ditemukan");
        return;
      }

      // Find all drop IDs for this customer
      const customerContainer = document.querySelector(
        `.customer-orders-container[data-customer-id="${customerId}"]`
      );

      if (!customerContainer) {
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

      console.log("Drop IDs found:", Array.from(dropIds));

      if (dropIds.size === 0) {
        await customAlert(
          "Tidak ada pesanan untuk dicetak thermal",
          "Peringatan",
          "warning"
        );
        return;
      }

      const confirmed = await customConfirm(
        `Akan mencetak <strong>${dropIds.size}</strong> struk thermal untuk customer ini.\n\nLanjutkan?`,
        "Cetak Thermal Semua",
        "printer"
      );

      if (!confirmed) {
        console.log("User cancelled print all thermal");
        return;
      }

      showLoading(`Mencetak ${dropIds.size} struk thermal...`);

      let successCount = 0;
      let failedCount = 0;
      const dropIdsArray = Array.from(dropIds);

      for (let i = 0; i < dropIdsArray.length; i++) {
        const dropId = dropIdsArray[i];

        try {
          showLoading(
            `Mencetak struk thermal ${i + 1} dari ${dropIdsArray.length}...`
          );

          const orderData = await fetchOrderDataForThermal(dropId);
          await printThermalReceipt(orderData);

          successCount++;

          // Delay between prints
          if (i < dropIdsArray.length - 1) {
            await new Promise((resolve) => setTimeout(resolve, 2000));
          }
        } catch (error) {
          console.error(`Failed to print thermal drop_id ${dropId}:`, error);
          failedCount++;
        }
      }

      hideLoading();

      if (failedCount > 0) {
        await customAlert(
          `Berhasil: ${successCount} struk thermal\nGagal: ${failedCount} struk`,
          "Cetak Selesai",
          "info"
        );
      } else {
        await customSuccess(`Berhasil mencetak ${successCount} struk thermal!`);
      }
    }
  });

  // ==================== PRINT SELECTED THERMAL ====================
  const printSelectedThermalBtn = document.getElementById(
    "printSelectedThermalBtn"
  );

  if (printSelectedThermalBtn) {
    printSelectedThermalBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      const checkedItems = document.querySelectorAll(".item-checkbox:checked");

      if (checkedItems.length === 0) {
        await customAlert(
          "Pilih setidaknya satu item untuk dicetak thermal",
          "Tidak Ada Item Dipilih",
          "warning"
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
        await customAlert(
          "Tidak ada pesanan yang dipilih",
          "Peringatan",
          "warning"
        );
        return;
      }

      const confirmed = await customConfirm(
        `Akan mencetak <strong>${dropIds.length}</strong> struk thermal.\n\nLanjutkan?`,
        "Cetak Thermal Terpilih",
        "printer"
      );

      if (!confirmed) {
        console.log("User cancelled print selected thermal");
        return;
      }

      showLoading(`Mencetak ${dropIds.length} struk thermal...`);

      let successCount = 0;
      let failedCount = 0;

      for (let i = 0; i < dropIds.length; i++) {
        const dropId = dropIds[i];

        try {
          showLoading(
            `Mencetak struk thermal ${i + 1} dari ${dropIds.length}...`
          );

          const orderData = await fetchOrderDataForThermal(dropId);
          await printThermalReceipt(orderData);

          successCount++;

          // Delay between prints
          if (i < dropIds.length - 1) {
            await new Promise((resolve) => setTimeout(resolve, 2000));
          }
        } catch (error) {
          console.error(`Failed to print thermal drop_id ${dropId}:`, error);
          failedCount++;
        }
      }

      hideLoading();

      if (failedCount > 0) {
        await customAlert(
          `Berhasil: ${successCount} struk thermal\nGagal: ${failedCount} struk`,
          "Cetak Selesai",
          "info"
        );
      } else {
        await customSuccess(`Berhasil mencetak ${successCount} struk thermal!`);
      }
    });
  }

  console.log("✅ Thermal print handlers initialized");
});

/**
 * EXPORT FOR MANUAL USE
 */
window.ThermalPrinter = {
  print: printThermalReceipt,
  buildReceipt: buildThermalReceipt,
  detectDevice: detectDevice,
  fetchOrder: fetchOrderDataForThermal,
  config: THERMAL_CONFIG,
};

console.log("✅ thermal_print_handler.js loaded successfully");
