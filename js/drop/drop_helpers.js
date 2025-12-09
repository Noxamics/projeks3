// =====================================================================
// File: /js/drop/drop_helpers.js
// Helper Functions untuk Drop Management System - IMPROVED VERSION
// Version: 3.2 - Enhanced Error Handling & Performance
// Database: mifmyho2_sengkuclean
// =====================================================================

(function () {
  "use strict";

  // ==================== CONSTANTS ====================

  const CONSTANTS = {
    MOBILE_BREAKPOINT: 768,
    DEBOUNCE_DELAY: 250,
    NOTIFICATION_DURATION: 3000,
    NOTIFICATION_FADE_DURATION: 300,
    API_TIMEOUT: 30000,
    MUTATION_OBSERVER_DELAY: 100,
  };

  const NOTIFICATION_COLORS = {
    success: "#059669",
    error: "#dc2626",
    warning: "#f59e0b",
    info: "#0284c7",
  };

  // ==================== FORMAT CURRENCY ====================

  /**
   * Format number ke format Rupiah
   * @param {number} amount - Jumlah uang
   * @returns {string} - Format Rp X.XXX
   */
  const formatRupiah = (amount) => {
    if (typeof amount !== "number" || isNaN(amount)) {
      console.warn("⚠️ formatRupiah received invalid input:", amount);
      return "Rp 0";
    }
    return "Rp " + amount.toLocaleString("id-ID");
  };

  /**
   * Parse string Rupiah ke number
   * @param {string} rupiah - String format Rupiah
   * @returns {number} - Angka bersih
   */
  const parseRupiah = (rupiah) => {
    if (!rupiah) return 0;
    const cleaned = String(rupiah).replace(/[^0-9]/g, "");
    return parseInt(cleaned || 0, 10);
  };

  // ==================== MODAL MANAGEMENT ====================

  /**
   * Tampilkan modal dengan validasi
   * @param {string} modalId - ID modal element
   * @returns {boolean} - True jika berhasil
   */
  function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
      console.error(`❌ Modal '${modalId}' tidak ditemukan`);
      return false;
    }

    modal.style.display = "flex";
    modal.classList.add("show");
    document.body.style.overflow = "hidden";

    // Add ARIA attributes
    modal.setAttribute("aria-hidden", "false");

    console.log(`✅ Modal '${modalId}' dibuka`);
    return true;
  }

  /**
   * Sembunyikan modal dengan validasi
   * @param {string} modalId - ID modal element
   * @returns {boolean} - True jika berhasil
   */
  function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
      console.error(`❌ Modal '${modalId}' tidak ditemukan`);
      return false;
    }

    modal.style.display = "none";
    modal.classList.remove("show");
    document.body.style.overflow = "";

    // Add ARIA attributes
    modal.setAttribute("aria-hidden", "true");

    console.log(`✅ Modal '${modalId}' ditutup`);
    return true;
  }

  /**
   * Tutup modal edit item
   */
  function closeItemEditModal() {
    return hideModal("itemEditModal");
  }

  // ==================== DATE CONVERSION ====================

  /**
   * Convert tanggal input ke format database (YYYY-MM-DD)
   * @param {string} inputDate - Format DD/MM/YYYY atau DD-MM-YYYY
   * @returns {string|null} - Format YYYY-MM-DD atau null
   */
  function convertDateToDbFormat(inputDate) {
    if (
      !inputDate ||
      typeof inputDate !== "string" ||
      inputDate.trim() === ""
    ) {
      return null;
    }

    try {
      const cleaned = inputDate.trim().replace(/\//g, "-");

      // Try DD-MM-YYYY format
      const parts = cleaned.split("-");
      if (parts.length === 3 && parts[0].length <= 2) {
        const [day, month, year] = parts;
        const dateObj = new Date(`${year}-${month}-${day}`);

        if (!isNaN(dateObj.getTime())) {
          return dateObj.toISOString().split("T")[0];
        }
      }

      // Try YYYY-MM-DD format
      const dateYmd = new Date(cleaned);
      if (!isNaN(dateYmd.getTime())) {
        return dateYmd.toISOString().split("T")[0];
      }

      console.warn("⚠️ Invalid date format:", inputDate);
      return null;
    } catch (error) {
      console.error("❌ Date conversion error:", error);
      return null;
    }
  }

  // ==================== DYNAMIC ESTIMATE FETCH ====================

  /**
   * Fetch estimasi dinamis dari server dengan timeout
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

      const controller = new AbortController();
      const timeoutId = setTimeout(
        () => controller.abort(),
        CONSTANTS.API_TIMEOUT
      );

      const response = await fetch("../actions/drop/get_dynamic_estimate.php", {
        method: "POST",
        body: formData,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      return result;
    } catch (error) {
      if (error.name === "AbortError") {
        console.error("❌ Fetch dynamic estimate timeout");
      } else {
        console.error("❌ Error fetching dynamic estimate:", error);
      }
      return null;
    }
  };

  // ==================== PAYMENT DATE HANDLING ====================

  /**
   * Set payment date pada field display dan hidden
   * @param {string} displayId - ID input display
   * @param {string} hiddenId - ID input hidden
   * @param {string} date - Tanggal format YYYY-MM-DD
   * @returns {boolean} - Success status
   */
  function setPaymentDate(displayId, hiddenId, date) {
    const displayField = document.getElementById(displayId);
    const hiddenField = document.getElementById(hiddenId);

    if (!displayField || !hiddenField) {
      console.warn(
        `⚠️ Payment date fields not found: ${displayId}, ${hiddenId}`
      );
      return false;
    }

    const validDate = date || "";
    displayField.value = validDate;
    hiddenField.value = validDate;
    hiddenField.dataset.originalDate = validDate;

    return true;
  }

  /**
   * Handle perubahan status pembayaran
   * @param {string} statusValue - Status pembayaran (Lunas/Belum Lunas)
   * @param {string} displayId - ID input display
   * @param {string} hiddenId - ID input hidden
   * @param {string|null} existingDate - Tanggal existing (untuk edit)
   * @returns {boolean} - Success status
   */
  function handlePaymentStatusChange(
    statusValue,
    displayId,
    hiddenId,
    existingDate = null
  ) {
    if (!statusValue) {
      console.warn("⚠️ No payment status value provided");
      return false;
    }

    const hiddenField = document.getElementById(hiddenId);

    if (!hiddenField) {
      console.warn(`⚠️ Hidden field not found: ${hiddenId}`);
      return false;
    }

    if (statusValue === "Lunas") {
      if (existingDate) {
        return setPaymentDate(displayId, hiddenId, existingDate);
      } else {
        const today = new Date().toISOString().split("T")[0];
        return setPaymentDate(displayId, hiddenId, today);
      }
    } else {
      hiddenField.dataset.originalDate = "";
      return setPaymentDate(displayId, hiddenId, "");
    }
  }

  // ==================== RUPIAH INPUT SETUP ====================

  /**
   * Setup input field untuk format Rupiah
   * @param {string} displayInputId - ID input yang ditampilkan
   * @param {string} hiddenInputId - ID input hidden untuk value asli
   * @returns {boolean} - Success status
   */
  const setupRupiahInput = (displayInputId, hiddenInputId) => {
    const displayInput = document.getElementById(displayInputId);
    const hiddenInput = document.getElementById(hiddenInputId);

    if (!displayInput || !hiddenInput) {
      console.warn(`⚠️ Rupiah input tidak ditemukan: ${displayInputId}`);
      return false;
    }

    // Initialize display value
    const initialValue = parseFloat(hiddenInput.value);
    if (initialValue && initialValue > 0) {
      displayInput.value = formatRupiah(initialValue);
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
      const numericValue = parseRupiah(e.target.value);
      e.target.value = numericValue > 0 ? formatRupiah(numericValue) : "";
    });

    // Handle paste event
    displayInput.addEventListener("paste", function (e) {
      setTimeout(() => {
        const numericValue = parseRupiah(e.target.value);
        hiddenInput.value = numericValue;
        e.target.value = formatRupiah(numericValue);
      }, 0);
    });

    return true;
  };

  // ==================== NOTIFICATION SYSTEM ====================

  /**
   * Tampilkan notifikasi toast
   * @param {string} message - Pesan notifikasi
   * @param {string} type - Tipe notifikasi (success/error/warning/info)
   * @returns {HTMLElement} - Notification element
   */
  function showNotification(message, type = "success") {
    if (!message) {
      console.warn("⚠️ No message provided for notification");
      return null;
    }

    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.setAttribute("role", "alert");
    notification.setAttribute("aria-live", "polite");

    const bgColor = NOTIFICATION_COLORS[type] || NOTIFICATION_COLORS.success;

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
      max-width: 400px;
      word-wrap: break-word;
      animation: slideInRight 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
      notification.style.animation = "slideOutRight 0.3s ease";
      setTimeout(() => {
        if (notification.parentElement) {
          notification.remove();
        }
      }, CONSTANTS.NOTIFICATION_FADE_DURATION);
    }, CONSTANTS.NOTIFICATION_DURATION);

    return notification;
  }

  // ==================== ORDER SUMMARY CALCULATION ====================

  /**
   * Hitung ringkasan order (total item, harga, estimasi)
   * @returns {Object} - Summary data
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
      const duration = parseInt(durationInput?.value || 0, 10);
      const transDate = transDateInput?.value || "";

      totalPrice += price;

      if (duration > maxDuration) {
        maxDuration = duration;
      }

      // Hitung tanggal estimasi selesai
      if (transDate && duration > 0) {
        try {
          const date = new Date(transDate);
          date.setDate(date.getDate() + duration);
          const estDate = date.toISOString().split("T")[0];

          if (!maxEstDate || estDate > maxEstDate) {
            maxEstDate = estDate;
          }
        } catch (error) {
          console.warn("⚠️ Error calculating estimate date:", error);
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

    if (elements.totalItems) {
      elements.totalItems.value = `${items.length} Item`;
    }
    if (elements.totalPriceDisplay) {
      elements.totalPriceDisplay.value = formatRupiah(totalPrice);
    }
    if (elements.totalPrice) {
      elements.totalPrice.value = totalPrice;
    }
    if (elements.maxEstimate) {
      elements.maxEstimate.value =
        maxDuration > 0 ? `${maxDuration} Hari` : "-";
    }
    if (elements.maxDuration) {
      elements.maxDuration.value = maxDuration;
    }
    if (elements.finalEstDate) {
      elements.finalEstDate.value = maxEstDate || "-";
    }

    return {
      itemCount: items.length,
      totalPrice,
      maxDuration,
      maxEstDate,
    };
  }

  // ==================== UPDATE ITEM SERVICE DATA ====================

  /**
   * Update data service item (harga & estimasi)
   * @param {HTMLElement} itemGroup - Element item group
   * @returns {Promise<boolean>} - Success status
   */
  const updateItemServiceData = async (itemGroup) => {
    if (!itemGroup) {
      console.error("❌ No item group provided");
      return false;
    }

    const serviceSelect = itemGroup.querySelector(".item-service");
    const priceDisplay = itemGroup.querySelector(".item-price-display");
    const priceInput = itemGroup.querySelector(".item-price");
    const estimateDisplay = itemGroup.querySelector(".item-estimate");
    const durationInput = itemGroup.querySelector(".item-duration");
    const transDateInput = itemGroup.querySelector(".item-trans-date");
    const estDateDisplay = itemGroup.querySelector(".item-est-date-display");

    if (!serviceSelect) {
      console.error("❌ Service select not found in item group");
      return false;
    }

    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

    if (!selectedOption || !selectedOption.value) {
      // Reset jika tidak ada service dipilih
      if (priceDisplay) priceDisplay.value = "Rp 0";
      if (priceInput) priceInput.value = "0";
      if (durationInput) durationInput.value = "0";
      if (estimateDisplay) estimateDisplay.value = "-";
      if (estDateDisplay) estDateDisplay.value = "-";
      calculateOrderSummary();
      return true;
    }

    const serviceId = selectedOption.value;
    const transDate = transDateInput?.value;

    if (!transDate) {
      console.warn("⚠️ No transaction date provided");
      return false;
    }

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
      if (estimateDisplay) estimateDisplay.value = result.estimate_desc || "-";
      if (estDateDisplay) estDateDisplay.value = result.estimate_date || "-";

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
      const priceMin = parseFloat(
        selectedOption.getAttribute("data-price") || 0
      );
      const duration = parseInt(
        selectedOption.getAttribute("data-duration") || 0,
        10
      );

      if (priceDisplay) priceDisplay.value = formatRupiah(priceMin);
      if (priceInput) priceInput.value = priceMin;
      if (durationInput) durationInput.value = duration;
      if (estimateDisplay) {
        estimateDisplay.value = duration > 0 ? `${duration} Hari` : "-";
      }

      // Hitung tanggal estimasi manual
      if (transDate && duration > 0) {
        try {
          const date = new Date(transDate);
          date.setDate(date.getDate() + duration);
          const estDate = date.toISOString().split("T")[0];
          if (estDateDisplay) estDateDisplay.value = estDate;
        } catch (error) {
          console.warn("⚠️ Error calculating manual estimate:", error);
          if (estDateDisplay) estDateDisplay.value = "-";
        }
      } else {
        if (estDateDisplay) estDateDisplay.value = "-";
      }
    }

    calculateOrderSummary();
    return true;
  };

  // ==================== DELETE MODAL HANDLER ====================

  // Global variable untuk pending delete
  window.pendingDelete = null;

  /**
   * Buka modal konfirmasi delete
   * @param {Object} data - Data yang akan dihapus
   * @returns {boolean} - Success status
   */
  function openDeleteModal(data) {
    if (!data) {
      console.error("❌ No data provided for delete modal");
      return false;
    }

    window.pendingDelete = data;
    console.log("🗑️ Delete modal opened:", data);
    return showModal("confirmDeleteModal");
  }

  /**
   * Tutup modal konfirmasi delete
   * @returns {boolean} - Success status
   */
  function closeDeleteModal() {
    const result = hideModal("confirmDeleteModal");
    window.pendingDelete = null;
    console.log("✅ Delete modal closed");
    return result;
  }

  // ==================== SESSION STORAGE NOTIFICATIONS ====================

  /**
   * Handle session storage notifications
   */
  function handleSessionNotifications() {
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
      const notificationType = sessionStorage.getItem(
        "timelineNotificationType"
      );

      if (notification) {
        showNotification(notification, notificationType || "success");
        sessionStorage.removeItem("timelineNotification");
        sessionStorage.removeItem("timelineNotificationType");
      }
    }
  }

  // ==================== CSS ANIMATIONS ====================

  /**
   * Add required CSS animations
   */
  function addRequiredStyles() {
    if (document.getElementById("drop-helpers-styles")) {
      return; // Already added
    }

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

  // =========================================================
  // MOBILE CARD VIEW SYSTEM
  // =========================================================

  /**
   * Initialize mobile card view labels
   */
  function initMobileCardView() {
    const columnLabels = [
      "", // Checkbox column
      "ID ORDER",
      "BRAND",
      "LAYANAN",
      "HARGA",
      "TGL",
      "STATUS",
      "BAYAR",
      "KARYAWAN",
      "AKSI",
    ];

    const orderItemGrids = document.querySelectorAll(".order-item-grid");
    let processedCount = 0;

    orderItemGrids.forEach((grid) => {
      const cells = grid.querySelectorAll(".order-item-cell");

      cells.forEach((cell, index) => {
        if (cell.hasAttribute("data-label")) return;

        if (index < columnLabels.length && columnLabels[index]) {
          cell.setAttribute("data-label", columnLabels[index]);
          processedCount++;
        }
      });
    });

    console.log(
      `✅ Mobile card view labels initialized - ${orderItemGrids.length} grids, ${processedCount} cells processed`
    );
  }

  /**
   * Add data-label to dynamically added items
   * @param {HTMLElement} itemGrid - Order item grid element
   * @returns {boolean} - Success status
   */
  function addDataLabelsToNewItem(itemGrid) {
    if (!itemGrid) {
      console.warn("⚠️ No item grid provided");
      return false;
    }

    const columnLabels = [
      "",
      "ID ORDER",
      "BRAND",
      "LAYANAN",
      "HARGA",
      "TGL",
      "STATUS",
      "BAYAR",
      "KARYAWAN",
      "AKSI",
    ];

    const cells = itemGrid.querySelectorAll(".order-item-cell");

    cells.forEach((cell, index) => {
      if (index < columnLabels.length && columnLabels[index]) {
        cell.setAttribute("data-label", columnLabels[index]);
      }
    });

    return true;
  }

  // =========================================================
  // EXPAND/COLLAPSE FUNCTIONALITY
  // =========================================================

  /**
   * Check if mobile view
   * @returns {boolean}
   */
  function isMobileView() {
    return window.innerWidth <= CONSTANTS.MOBILE_BREAKPOINT;
  }

  /**
   * Initialize expand/collapse functionality
   */
  function initExpandCollapse() {
    if (!isMobileView()) {
      console.log("⚠️ Not mobile view, skipping collapse init");
      return;
    }

    const customerHeaders = document.querySelectorAll(".customer-group-header");
    console.log(`🔍 Found ${customerHeaders.length} customer headers`);

    customerHeaders.forEach((header, idx) => {
      if (header.hasAttribute("data-collapse-initialized")) {
        return;
      }

      header.setAttribute("data-collapse-initialized", "true");

      const ordersContainer = header.nextElementSibling;

      if (
        !ordersContainer ||
        !ordersContainer.classList.contains("customer-orders-container")
      ) {
        console.warn(`⚠️ Header ${idx}: No matching orders container`);
        return;
      }

      header.addEventListener("click", function (e) {
        // Don't trigger if clicking on action buttons
        if (
          e.target.closest(".customer-action-btn") ||
          e.target.closest(".customer-checkbox")
        ) {
          return;
        }

        const isCollapsed = ordersContainer.classList.contains("collapsed");
        const customerId = header.dataset.customerId || idx;

        if (isCollapsed) {
          ordersContainer.classList.remove("collapsed");
          header.classList.remove("collapsed");
          localStorage.setItem(`customer-${customerId}-collapsed`, "false");
        } else {
          ordersContainer.classList.add("collapsed");
          header.classList.add("collapsed");
          localStorage.setItem(`customer-${customerId}-collapsed`, "true");
        }
      });

      // Restore previous state
      const customerId = header.dataset.customerId || idx;
      const wasCollapsed =
        localStorage.getItem(`customer-${customerId}-collapsed`) === "true";

      if (wasCollapsed) {
        ordersContainer.classList.add("collapsed");
        header.classList.add("collapsed");
      }
    });

    console.log("✅ Expand/Collapse initialized");
  }

  /**
   * Add customer IDs to headers
   */
  function addCustomerIds() {
    const customerHeaders = document.querySelectorAll(".customer-group-header");

    customerHeaders.forEach((header, index) => {
      if (!header.hasAttribute("data-customer-id")) {
        const nameElement = header.querySelector(".customer-name");
        const customerId = nameElement
          ? nameElement.textContent.trim().replace(/\s+/g, "-").toLowerCase()
          : `customer-${index}`;

        header.setAttribute("data-customer-id", customerId);
      }
    });
  }

  /**
   * Expand all customers
   */
  function expandAllCustomers() {
    if (!isMobileView()) return;

    document
      .querySelectorAll(".customer-orders-container")
      .forEach((container) => {
        container.classList.remove("collapsed");
      });

    document.querySelectorAll(".customer-group-header").forEach((header) => {
      header.classList.remove("collapsed");
    });

    console.log("✅ All customers expanded");
  }

  /**
   * Collapse all customers
   */
  function collapseAllCustomers() {
    if (!isMobileView()) return;

    document
      .querySelectorAll(".customer-orders-container")
      .forEach((container) => {
        container.classList.add("collapsed");
      });

    document.querySelectorAll(".customer-group-header").forEach((header) => {
      header.classList.add("collapsed");
    });

    console.log("✅ All customers collapsed");
  }

  // =========================================================
  // UTILITY FUNCTIONS
  // =========================================================

  /**
   * Debounce function
   * @param {Function} func - Function to debounce
   * @param {number} wait - Wait time in ms
   * @returns {Function} - Debounced function
   */
  function debounce(func, wait = CONSTANTS.DEBOUNCE_DELAY) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  /**
   * Debug function for mobile view
   */
  function debugMobileView() {
    console.log("=== MOBILE VIEW DEBUG ===");
    console.log(`Window width: ${window.innerWidth}px`);
    console.log(`Is mobile: ${isMobileView()}`);
    console.log(
      `Customer headers: ${
        document.querySelectorAll(".customer-group-header").length
      }`
    );
    console.log(
      `Order grids: ${document.querySelectorAll(".order-item-grid").length}`
    );
    console.log(
      `Order cells: ${document.querySelectorAll(".order-item-cell").length}`
    );
    console.log(
      `Cells with data-label: ${
        document.querySelectorAll(".order-item-cell[data-label]").length
      }`
    );
  }

  /**
   * Force re-initialization
   */
  function forceReinit() {
    console.log("🔧 Forcing re-initialization...");
    initMobileCardView();
    addCustomerIds();
    initExpandCollapse();
    console.log("✅ Done! Run mobileCardView.debug() to check");
  }

  // =========================================================
  // CONTENT CHANGE OBSERVER (Detailed Version)
  // =========================================================

  /**
   * Observe content changes for dynamic content
   * Monitors table container for new items and reinitializes mobile features
   */
  function observeContentChanges() {
    const tableContainer = document.querySelector(".table-container");

    if (!tableContainer) {
      console.warn("⚠️ Table container not found for observer");
      return;
    }

    const observer = new MutationObserver(
      debounce((mutations) => {
        let shouldReinitialize = false;

        mutations.forEach((mutation) => {
          if (mutation.addedNodes.length) {
            mutation.addedNodes.forEach((node) => {
              if (node.nodeType === 1) {
                // Element node

                // Check if it's an order item grid
                if (node.classList?.contains("order-item-grid")) {
                  addDataLabelsToNewItem(node);
                } else if (node.querySelectorAll) {
                  // Check for grids inside the node
                  const newGrids = node.querySelectorAll(".order-item-grid");
                  newGrids.forEach((grid) => addDataLabelsToNewItem(grid));
                }

                // Check if customer headers were added
                if (node.classList?.contains("customer-group-header")) {
                  shouldReinitialize = true;
                } else if (node.querySelectorAll) {
                  const newHeaders = node.querySelectorAll(
                    ".customer-group-header"
                  );
                  if (newHeaders.length > 0) {
                    shouldReinitialize = true;
                  }
                }
              }
            });
          }
        });

        // Reinitialize collapse functionality if new headers were added
        if (shouldReinitialize && isMobileView()) {
          setTimeout(() => {
            addCustomerIds();
            initExpandCollapse();
          }, CONSTANTS.MUTATION_OBSERVER_DELAY);
        }
      }, CONSTANTS.DEBOUNCE_DELAY)
    );

    observer.observe(tableContainer, {
      childList: true,
      subtree: true,
    });

    console.log("✅ Content change observer initialized");
  }

  // =========================================================
  // SESSION STORAGE NOTIFICATIONS (Detailed Version)
  // =========================================================

  /**
   * Handle session storage notifications
   * Checks for pending notifications on page load
   */
  function handleSessionNotifications() {
    const currentPath = window.location.pathname;

    // Define notification configurations for different pages
    const notificationConfigs = [
      {
        page: "drop.php",
        storageKey: "dropNotification",
        typeKey: "dropNotificationType",
      },
      {
        page: "timeline_pesanan.php",
        storageKey: "timelineNotification",
        typeKey: "timelineNotificationType",
      },
    ];

    // Check each configuration
    notificationConfigs.forEach(({ page, storageKey, typeKey }) => {
      if (currentPath.includes(page)) {
        const message = sessionStorage.getItem(storageKey);
        const type = sessionStorage.getItem(typeKey);

        if (message) {
          showNotification(message, type || "success");

          // Clean up session storage
          sessionStorage.removeItem(storageKey);
          sessionStorage.removeItem(typeKey);

          console.log(`✅ Notification displayed for ${page}`);
        }
      }
    });
  }

  // =========================================================
  // DEBUG & UTILITY FUNCTIONS
  // =========================================================

  /**
   * Debug function for mobile view
   * Displays comprehensive information about the current state
   */
  function debugMobileView() {
    console.group("=== MOBILE VIEW DEBUG ===");

    // Window info
    console.log(`Window width: ${window.innerWidth}px`);
    console.log(`Is mobile view: ${isMobileView()}`);
    console.log(`Mobile breakpoint: ${CONSTANTS.MOBILE_BREAKPOINT}px`);

    // Element counts
    const headers = document.querySelectorAll(".customer-group-header");
    const grids = document.querySelectorAll(".order-item-grid");
    const cells = document.querySelectorAll(".order-item-cell");
    const labeledCells = document.querySelectorAll(
      ".order-item-cell[data-label]"
    );

    console.log(`Customer headers: ${headers.length}`);
    console.log(`Order grids: ${grids.length}`);
    console.log(`Order cells: ${cells.length}`);
    console.log(`Cells with data-label: ${labeledCells.length}`);

    // Sample cells
    if (cells.length > 0) {
      console.group("First 5 cells:");
      for (let i = 0; i < Math.min(5, cells.length); i++) {
        const cell = cells[i];
        console.log(`Cell ${i}:`, {
          label: cell.getAttribute("data-label"),
          text: cell.textContent.trim().substring(0, 50),
          classes: cell.className,
        });
      }
      console.groupEnd();
    }

    // Customer headers info
    if (headers.length > 0) {
      console.group(`Customer Headers (${headers.length}):`);
      headers.forEach((header, idx) => {
        const customerId = header.dataset.customerId;
        const initialized = header.hasAttribute("data-collapse-initialized");
        const collapsed = header.classList.contains("collapsed");

        console.log(`Header ${idx}:`, {
          customerId,
          initialized,
          collapsed,
          hasAfterPseudo:
            window.getComputedStyle(header, "::after").content !== "none",
        });
      });
      console.groupEnd();
    }

    console.groupEnd();
  }

  /**
   * Force re-initialization of all mobile features
   */
  function forceReinitialize() {
    console.log("🔧 Forcing complete re-initialization...");

    try {
      initMobileCardView();
      addCustomerIds();
      initExpandCollapse();

      console.log("✅ Re-initialization complete!");
      console.log("💡 Run mobileCardView.debug() to verify");
    } catch (error) {
      console.error("❌ Re-initialization failed:", error);
    }
  }

  // =========================================================
  // INITIALIZATION & EVENT LISTENERS
  // =========================================================

  /**
   * Initialize all features on DOM ready
   */
  document.addEventListener("DOMContentLoaded", function () {
    console.log("🚀 Drop Helpers v3.2 - Initializing...");
    console.log(`📱 Window width: ${window.innerWidth}px`);
    console.log(`📱 Is mobile: ${isMobileView()}`);

    try {
      // Add required styles
      addRequiredStyles();

      // Handle session notifications
      handleSessionNotifications();

      // Initialize mobile features
      initMobileCardView();
      addCustomerIds();
      initExpandCollapse();

      // Start observing content changes
      observeContentChanges();

      // Log statistics
      console.log("📊 Page statistics:");
      console.log(
        `  - Customer headers: ${
          document.querySelectorAll(".customer-group-header").length
        }`
      );
      console.log(
        `  - Order grids: ${
          document.querySelectorAll(".order-item-grid").length
        }`
      );
      console.log(
        `  - Order cells: ${
          document.querySelectorAll(".order-item-cell").length
        }`
      );

      console.log("✅ Drop Helpers v3.2 - Initialization complete!");
    } catch (error) {
      console.error("❌ Initialization error:", error);
    }
  });

  /**
   * Re-initialize on window resize (debounced)
   * Only triggers on mobile view
   */
  let resizeTimer;
  window.addEventListener("resize", function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      if (isMobileView()) {
        console.log("📱 Window resized to mobile view - re-initializing...");
        addCustomerIds();
        initExpandCollapse();
      }
    }, CONSTANTS.DEBOUNCE_DELAY);
  });

  // =========================================================
  // GLOBAL API EXPORTS
  // =========================================================

  /**
   * Global API untuk mobile card view
   * Accessible via: window.mobileCardView
   */
  window.mobileCardView = {
    /**
     * Initialize mobile card view labels
     */
    init: initMobileCardView,

    /**
     * Add data labels to a specific item
     * @param {HTMLElement} itemGrid - Item grid element
     */
    addLabelsToItem: addDataLabelsToNewItem,

    /**
     * Initialize expand/collapse functionality
     */
    initCollapse: initExpandCollapse,

    /**
     * Expand all customer sections
     */
    expandAll: expandAllCustomers,

    /**
     * Collapse all customer sections
     */
    collapseAll: collapseAllCustomers,

    /**
     * Display debug information
     */
    debug: debugMobileView,

    /**
     * Force re-initialization of all features
     */
    fixAll: forceReinitialize,

    /**
     * Check if currently in mobile view
     * @returns {boolean}
     */
    isMobile: isMobileView,

    /**
     * Get current constants
     * @returns {Object}
     */
    getConstants: () => ({ ...CONSTANTS }),
  };

  /**
   * Export all helper functions globally
   * Makes them accessible throughout the application
   */
  window.formatRupiah = formatRupiah;
  window.parseRupiah = parseRupiah;
  window.showModal = showModal;
  window.hideModal = hideModal;
  window.closeItemEditModal = closeItemEditModal;
  window.convertDateToDbFormat = convertDateToDbFormat;
  window.fetchDynamicEstimate = fetchDynamicEstimate;
  window.setPaymentDate = setPaymentDate;
  window.handlePaymentStatusChange = handlePaymentStatusChange;
  window.setupRupiahInput = setupRupiahInput;
  window.showNotification = showNotification;
  window.calculateOrderSummary = calculateOrderSummary;
  window.updateItemServiceData = updateItemServiceData;
  window.openDeleteModal = openDeleteModal;
  window.closeDeleteModal = closeDeleteModal;

  // =========================================================
  // ERROR HANDLING
  // =========================================================

  /**
   * Global error handler for unhandled promise rejections
   */
  window.addEventListener("unhandledrejection", function (event) {
    console.error(
      "❌ Unhandled promise rejection in drop helpers:",
      event.reason
    );

    // Show user-friendly notification for critical errors
    if (event.reason && event.reason.message) {
      showNotification(
        "Terjadi kesalahan sistem. Silakan refresh halaman.",
        "error"
      );
    }

    event.preventDefault();
  });

  /**
   * Global error handler for uncaught errors
   */
  window.addEventListener("error", function (event) {
    console.error("❌ Uncaught error in drop helpers:", event.error);
    event.preventDefault();
  });

  // =========================================================
  // INITIALIZATION COMPLETE LOG
  // =========================================================

  console.log("✅ Drop Management Helpers v3.2 loaded successfully");
  console.log("📦 Database: mifmyho2_sengkuclean");
  console.log("🕐 Timezone: Asia/Jakarta");
  console.log("📱 Mobile Expand/Collapse: ENABLED");
  console.log("🔧 Debug API: window.mobileCardView.debug()");
  console.log("🛠️ Available commands:");
  console.log("  - mobileCardView.init()       : Initialize mobile labels");
  console.log("  - mobileCardView.initCollapse(): Initialize collapse");
  console.log("  - mobileCardView.expandAll()  : Expand all customers");
  console.log("  - mobileCardView.collapseAll(): Collapse all customers");
  console.log("  - mobileCardView.debug()      : Show debug info");
  console.log("  - mobileCardView.fixAll()     : Force re-init");
  console.log("  - mobileCardView.isMobile()   : Check mobile view");

  // IIFE closing bracket
})();
