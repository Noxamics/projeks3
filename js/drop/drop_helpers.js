// =====================================================================
// File: /js/drop/drop_helpers.js
// Helper Functions untuk Drop Management System
// Version: 3.0 - Optimized & Clean
// Database: mifmyho2_sengkuclean
// =====================================================================

// ==================== FORMAT CURRENCY ====================

/**
 * Format number ke format Rupiah
 * @param {number} amount - Jumlah uang
 * @returns {string} - Format Rp X.XXX
 */
const formatRupiah = (amount) => {
  if (typeof amount !== "number" || isNaN(amount)) return "Rp 0";
  return "Rp " + amount.toLocaleString("id-ID");
};

/**
 * Parse string Rupiah ke number
 * @param {string} rupiah - String format Rupiah
 * @returns {number} - Angka bersih
 */
const parseRupiah = (rupiah) => {
  return parseInt(rupiah.replace(/[^0-9]/g, "") || 0);
};

// ==================== MODAL MANAGEMENT ====================

/**
 * Tampilkan modal
 * @param {string} modalId - ID modal element
 */
function showModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) {
    console.error(`❌ Modal '${modalId}' tidak ditemukan`);
    return;
  }

  modal.style.display = "flex";
  modal.classList.add("show");
  document.body.style.overflow = "hidden";
  console.log(`✅ Modal '${modalId}' dibuka`);
}

/**
 * Sembunyikan modal
 * @param {string} modalId - ID modal element
 */
function hideModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) {
    console.error(`❌ Modal '${modalId}' tidak ditemukan`);
    return;
  }

  modal.style.display = "none";
  modal.classList.remove("show");
  document.body.style.overflow = "";
  console.log(`✅ Modal '${modalId}' ditutup`);
}

/**
 * Tutup modal edit item
 */
function closeItemEditModal() {
  hideModal("itemEditModal");
}

// ==================== DATE CONVERSION ====================

/**
 * Convert tanggal input ke format database (YYYY-MM-DD)
 * @param {string} inputDate - Format DD/MM/YYYY atau DD-MM-YYYY
 * @returns {string|null} - Format YYYY-MM-DD atau null
 */
function convertDateToDbFormat(inputDate) {
  if (!inputDate || inputDate.trim() === "") return null;

  const cleaned = inputDate.replace(/\//g, "-");

  // Try DD-MM-YYYY format
  const parts = cleaned.split("-");
  if (parts.length === 3) {
    const dateObj = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);
    if (!isNaN(dateObj.getTime())) {
      return dateObj.toISOString().split("T")[0];
    }
  }

  // Try YYYY-MM-DD format
  const dateYmd = new Date(cleaned);
  if (!isNaN(dateYmd.getTime())) {
    return dateYmd.toISOString().split("T")[0];
  }

  return null;
}

// ==================== DYNAMIC ESTIMATE FETCH ====================

/**
 * Fetch estimasi dinamis dari server
 * @param {number} serviceId - ID layanan
 * @param {string} transDate - Tanggal transaksi
 * @param {number} excludeDropId - ID drop yang dikecualikan (untuk edit)
 * @returns {Promise<Object|null>} - Data estimasi atau null
 */
const fetchDynamicEstimate = async (
  serviceId,
  transDate,
  excludeDropId = 0
) => {
  try {
    const formData = new FormData();
    formData.append("service_id", serviceId);
    formData.append("trans_date", transDate);
    formData.append("exclude_drop_id", excludeDropId);

    const response = await fetch("../actions/drop/get_dynamic_estimate.php", {
      method: "POST",
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    return result;
  } catch (error) {
    console.error("❌ Error fetching dynamic estimate:", error);
    return null;
  }
};

// ==================== PAYMENT DATE HANDLING ====================

/**
 * Set payment date pada field display dan hidden
 * @param {string} displayId - ID input display
 * @param {string} hiddenId - ID input hidden
 * @param {string} date - Tanggal format YYYY-MM-DD
 */
function setPaymentDate(displayId, hiddenId, date) {
  const displayField = document.getElementById(displayId);
  const hiddenField = document.getElementById(hiddenId);

  if (displayField && hiddenField) {
    displayField.value = date || "";
    hiddenField.value = date || "";
    hiddenField.dataset.originalDate = date || "";
  }
}

/**
 * Handle perubahan status pembayaran
 * @param {string} statusValue - Status pembayaran (Lunas/Belum Lunas)
 * @param {string} displayId - ID input display
 * @param {string} hiddenId - ID input hidden
 * @param {string|null} existingDate - Tanggal existing (untuk edit)
 */
function handlePaymentStatusChange(
  statusValue,
  displayId,
  hiddenId,
  existingDate = null
) {
  const hiddenField = document.getElementById(hiddenId);

  if (statusValue === "Lunas") {
    if (existingDate) {
      setPaymentDate(displayId, hiddenId, existingDate);
    } else {
      const today = new Date().toISOString().split("T")[0];
      setPaymentDate(displayId, hiddenId, today);
    }
  } else {
    setPaymentDate(displayId, hiddenId, "");
    if (hiddenField) {
      hiddenField.dataset.originalDate = "";
    }
  }
}

// ==================== RUPIAH INPUT SETUP ====================

/**
 * Setup input field untuk format Rupiah
 * @param {string} displayInputId - ID input yang ditampilkan
 * @param {string} hiddenInputId - ID input hidden untuk value asli
 */
const setupRupiahInput = (displayInputId, hiddenInputId) => {
  const displayInput = document.getElementById(displayInputId);
  const hiddenInput = document.getElementById(hiddenInputId);

  if (!displayInput || !hiddenInput) {
    console.warn(`⚠️ Rupiah input tidak ditemukan: ${displayInputId}`);
    return;
  }

  // Initialize display value
  if (hiddenInput.value && parseFloat(hiddenInput.value) > 0) {
    displayInput.value = formatRupiah(parseFloat(hiddenInput.value));
  } else {
    displayInput.value = "";
  }

  // Handle input event
  displayInput.addEventListener("input", function (e) {
    const numericValue = parseRupiah(e.target.value);
    hiddenInput.value = numericValue;
    e.target.value = formatRupiah(numericValue);
  });

  // Handle blur event (format ulang)
  displayInput.addEventListener("blur", function (e) {
    e.target.value = formatRupiah(parseRupiah(e.target.value));
  });
};

// ==================== NOTIFICATION SYSTEM ====================

/**
 * Tampilkan notifikasi toast
 * @param {string} message - Pesan notifikasi
 * @param {string} type - Tipe notifikasi (success/error/warning)
 */
function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.textContent = message;

  const bgColor =
    {
      success: "#059669",
      error: "#dc2626",
      warning: "#f59e0b",
    }[type] || "#059669";

  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    background: ${bgColor};
    color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 10000;
    font-weight: 500;
    animation: slideInRight 0.3s ease;
  `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = "slideOutRight 0.3s ease";
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// ==================== ORDER SUMMARY CALCULATION ====================

/**
 * Hitung ringkasan order (total item, harga, estimasi)
 */
function calculateOrderSummary() {
  const items = document.querySelectorAll(".item-group");
  let totalPrice = 0;
  let maxDuration = 0;
  let maxEstDate = "";

  items.forEach((item) => {
    const priceInput = item.querySelector(".item-price");
    const durationInput = item.querySelector(".item-duration");
    const transDateInput = item.querySelector(".item-trans-date");

    const price = parseFloat(priceInput?.value || 0);
    const duration = parseInt(durationInput?.value || 0);
    const transDate = transDateInput?.value || "";

    totalPrice += price;

    if (duration > maxDuration) {
      maxDuration = duration;
    }

    // Hitung tanggal estimasi selesai
    if (transDate && duration > 0) {
      const date = new Date(transDate);
      date.setDate(date.getDate() + duration);
      const estDate = date.toISOString().split("T")[0];

      if (!maxEstDate || estDate > maxEstDate) {
        maxEstDate = estDate;
      }
    }
  });

  // Update summary fields
  const elements = {
    totalItems: document.getElementById("totalItems"),
    totalPriceDisplay: document.getElementById("totalPriceDisplay"),
    totalPrice: document.getElementById("totalPrice"),
    maxEstimate: document.getElementById("maxEstimate"),
    maxDuration: document.getElementById("maxDuration"),
    finalEstDate: document.getElementById("finalEstDate"),
  };

  if (elements.totalItems) elements.totalItems.value = `${items.length} Item`;
  if (elements.totalPriceDisplay)
    elements.totalPriceDisplay.value = formatRupiah(totalPrice);
  if (elements.totalPrice) elements.totalPrice.value = totalPrice;
  if (elements.maxEstimate)
    elements.maxEstimate.value = maxDuration > 0 ? `${maxDuration} Hari` : "-";
  if (elements.maxDuration) elements.maxDuration.value = maxDuration;
  if (elements.finalEstDate) elements.finalEstDate.value = maxEstDate || "-";
}

// ==================== UPDATE ITEM SERVICE DATA ====================

/**
 * Update data service item (harga & estimasi)
 * @param {HTMLElement} itemGroup - Element item group
 */
const updateItemServiceData = async (itemGroup) => {
  const serviceSelect = itemGroup.querySelector(".item-service");
  const priceDisplay = itemGroup.querySelector(".item-price-display");
  const priceInput = itemGroup.querySelector(".item-price");
  const estimateDisplay = itemGroup.querySelector(".item-estimate");
  const durationInput = itemGroup.querySelector(".item-duration");
  const transDateInput = itemGroup.querySelector(".item-trans-date");
  const estDateDisplay = itemGroup.querySelector(".item-est-date-display");

  const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

  if (!selectedOption || !selectedOption.value) {
    // Reset jika tidak ada service dipilih
    if (priceDisplay) priceDisplay.value = "Rp 0";
    if (priceInput) priceInput.value = "0";
    if (durationInput) durationInput.value = "0";
    if (estimateDisplay) estimateDisplay.value = "-";
    if (estDateDisplay) estDateDisplay.value = "-";
    calculateOrderSummary();
    return;
  }

  const serviceId = selectedOption.value;
  const transDate = transDateInput.value;

  // Set loading state
  if (estimateDisplay) estimateDisplay.value = "⏳ Menghitung...";
  if (durationInput) durationInput.value = "0";
  if (estDateDisplay) estDateDisplay.value = "-";

  // Fetch dynamic estimate
  const result = await fetchDynamicEstimate(serviceId, transDate, 0);

  if (result && result.success) {
    // Update dengan data dari server
    if (priceDisplay) priceDisplay.value = formatRupiah(result.price_min);
    if (priceInput) priceInput.value = result.price_min;
    if (durationInput) durationInput.value = result.duration;
    if (estimateDisplay) estimateDisplay.value = result.estimate_desc;
    if (estDateDisplay) estDateDisplay.value = result.estimate_date;

    // Log info antrian
    if (result.queue_info) {
      console.log(`📋 ${result.queue_info}`);
    }
    if (result.has_overdue) {
      console.warn(
        `⚠️ Ada ${result.overdue_count} pesanan overdue - queue direset`
      );
    }
  } else {
    // Fallback ke data static dari option
    const priceMin = parseFloat(selectedOption.getAttribute("data-price") || 0);
    const duration = parseInt(
      selectedOption.getAttribute("data-duration") || 0
    );

    if (priceDisplay) priceDisplay.value = formatRupiah(priceMin);
    if (priceInput) priceInput.value = priceMin;
    if (durationInput) durationInput.value = duration;
    if (estimateDisplay)
      estimateDisplay.value = duration > 0 ? `${duration} Hari` : "-";

    // Hitung tanggal estimasi manual
    if (transDate && duration > 0) {
      const date = new Date(transDate);
      date.setDate(date.getDate() + duration);
      const estDate = date.toISOString().split("T")[0];
      if (estDateDisplay) estDateDisplay.value = estDate;
    } else {
      if (estDateDisplay) estDateDisplay.value = "-";
    }
  }

  calculateOrderSummary();
};

// ==================== DELETE MODAL HANDLER ====================

// Global variable untuk pending delete
window.pendingDelete = null;

/**
 * Buka modal konfirmasi delete
 * @param {Object} data - Data yang akan dihapus
 */
function openDeleteModal(data) {
  window.pendingDelete = data;
  console.log("🗑️ Delete modal opened:", data);
  showModal("confirmDeleteModal");
}

/**
 * Tutup modal konfirmasi delete
 */
function closeDeleteModal() {
  hideModal("confirmDeleteModal");
  window.pendingDelete = null;
  console.log("✅ Delete modal closed");
}

// ==================== SESSION STORAGE NOTIFICATIONS ====================

document.addEventListener("DOMContentLoaded", function () {
  // Detect halaman saat ini
  const currentPath = window.location.pathname;
  const isTimelinePage = currentPath.includes("timeline_pesanan.php");
  const isDropPage = currentPath.includes("drop.php");

  // Check notification untuk halaman drop
  if (isDropPage) {
    const notification = sessionStorage.getItem("dropNotification");
    const notificationType = sessionStorage.getItem("dropNotificationType");

    if (notification) {
      showNotification(notification, notificationType || "success");
      sessionStorage.removeItem("dropNotification");
      sessionStorage.removeItem("dropNotificationType");
    }
  }

  // Check notification untuk halaman timeline
  if (isTimelinePage) {
    const notification = sessionStorage.getItem("timelineNotification");
    const notificationType = sessionStorage.getItem("timelineNotificationType");

    if (notification) {
      showNotification(notification, notificationType || "success");
      sessionStorage.removeItem("timelineNotification");
      sessionStorage.removeItem("timelineNotificationType");
    }
  }
});

// ==================== CSS ANIMATIONS (OPTIONAL) ====================

// Tambahkan style untuk animasi jika belum ada
if (!document.getElementById("drop-helpers-styles")) {
  const style = document.createElement("style");
  style.id = "drop-helpers-styles";
  style.textContent = `
    @keyframes slideInRight {
      from {
        transform: translateX(100%);
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
        transform: translateX(100%);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(style);
}

// ==================== INITIALIZATION COMPLETE ====================
console.log("✅ Drop Management Helpers v3.0 loaded successfully");
console.log("📦 Database: mifmyho2_sengkuclean");
console.log("🕐 Timezone: Asia/Jakarta");
