// =====================================================================
// File: /js/drop/drop_modal.js
// Modal Handler - IMPROVED VERSION
// Version: 3.2 - Enhanced Error Handling & Code Quality
// =====================================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Drop modal module loaded");

  let itemCounter = 1;
  const BUTTON_TEXTS = {
    saveAndPrint: {
      default: '<i class="bi bi-printer"></i> Simpan & Cetak',
      loading: "⏳ Menyimpan...",
    },
    saveOnly: {
      default: '<i class="bi bi-save"></i> Simpan',
      loading: "⏳ Menyimpan...",
    },
  };

  // ==================== HELPER FUNCTIONS ====================

  /**
   * Build full API URL from endpoint
   * @param {string} endpoint - API endpoint path
   * @returns {string} Full URL
   */
  function buildApiUrl(endpoint) {
    const protocol = window.location.protocol;
    const host = window.location.host;
    return `${protocol}//${host}${endpoint}`;
  }

  /**
   * Format date to DD/MM/YYYY
   * @param {Date|string} date - Date to format
   * @returns {string} Formatted date
   */
  function formatDateToDDMMYYYY(date) {
    const d = new Date(date);
    if (isNaN(d.getTime())) {
      console.error("Invalid date:", date);
      return "";
    }
    const day = String(d.getDate()).padStart(2, "0");
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
  }

  /**
   * Add days to date and format to DD/MM/YYYY
   * @param {string} dateStr - Date in YYYY-MM-DD format
   * @param {number} days - Number of days to add
   * @returns {string} Formatted date DD/MM/YYYY
   */
  function addDaysToDate(dateStr, days) {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) {
      console.error("Invalid date string:", dateStr);
      return "";
    }
    d.setDate(d.getDate() + days);
    return formatDateToDDMMYYYY(d);
  }

  /**
   * Compare two dates in DD/MM/YYYY format
   * @param {string} date1 - First date
   * @param {string} date2 - Second date
   * @returns {number} Difference in milliseconds
   */
  function compareDDMMYYYY(date1, date2) {
    const d1 = date1.split("/");
    const d2 = date2.split("/");
    const dateObj1 = new Date(d1[2], d1[1] - 1, d1[0]);
    const dateObj2 = new Date(d2[2], d2[1] - 1, d2[0]);
    return dateObj1 - dateObj2;
  }

  /**
   * Restore button to default state
   * @param {HTMLElement} button - Button element
   * @param {string} defaultHTML - Default button HTML
   */
  function restoreButton(button, defaultHTML) {
    if (button) {
      button.disabled = false;
      button.innerHTML = defaultHTML;
    }
  }

  /**
   * Safe JSON parse with error handling
   * @param {string} text - JSON string to parse
   * @returns {Object} Parsed object or error result
   */
  function safeJSONParse(text) {
    try {
      return { success: true, data: JSON.parse(text) };
    } catch (error) {
      console.error("JSON Parse Error:", error);
      return {
        success: false,
        error: `Invalid JSON response: ${text.substring(0, 200)}...`,
      };
    }
  }

  // ==================== VALIDATION ====================

  /**
   * Validate entire form before submission
   * @returns {Promise<boolean>} True if valid
   */
  async function validateForm() {
    // Check employee
    const employeeInput = document.querySelector('input[name="employee_id"]');
    const employeeSelect = document.querySelector('select[name="employee_id"]');

    if (
      (!employeeInput || !employeeInput.value) &&
      (!employeeSelect || !employeeSelect.value)
    ) {
      await customError(
        "Tidak ada karyawan kasir yang aktif!\n\nPastikan ada karyawan yang sudah check-in hari ini.",
        "Karyawan Tidak Ditemukan"
      );
      return false;
    }

    // Check items
    const items = document.querySelectorAll(".item-group");

    if (items.length === 0) {
      await customError(
        "Tidak ada item dalam pesanan!",
        "Item Tidak Ditemukan"
      );
      return false;
    }

    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      const itemNum = i + 1;

      const serviceSelect = item.querySelector(".item-service");
      if (!serviceSelect || !serviceSelect.value) {
        await customError(
          `Item #${itemNum}: Pilih layanan terlebih dahulu!`,
          "Layanan Belum Dipilih"
        );
        if (serviceSelect) serviceSelect.focus();
        return false;
      }

      const brandInput = item.querySelector(".item-brand");
      if (!brandInput || !brandInput.value.trim()) {
        await customError(
          `Item #${itemNum}: Brand/Merk harus diisi!`,
          "Brand Kosong"
        );
        if (brandInput) brandInput.focus();
        return false;
      }

      const transDate = item.querySelector(".item-trans-date");
      if (!transDate || !transDate.value) {
        await customError(
          `Item #${itemNum}: Tanggal transaksi harus diisi!`,
          "Tanggal Kosong"
        );
        if (transDate) transDate.focus();
        return false;
      }

      const statusSelect = item.querySelector(".item-status");
      if (!statusSelect || !statusSelect.value) {
        await customError(
          `Item #${itemNum}: Pilih status item!`,
          "Status Belum Dipilih"
        );
        if (statusSelect) statusSelect.focus();
        return false;
      }
    }

    return true;
  }

  // ==================== ITEM MANAGEMENT ====================

  /**
   * Update visibility of remove buttons based on item count
   */
  function updateRemoveButtons() {
    const items = document.querySelectorAll(".item-group");
    items.forEach((item) => {
      const removeBtn = item.querySelector(".remove-item-btn");
      if (removeBtn) {
        removeBtn.style.display = items.length > 1 ? "inline-block" : "none";
      }
    });
  }

  /**
   * Create HTML for new item
   * @param {number} itemIndex - Item index number
   * @returns {string} HTML string
   */
  function createNewItemHTML(itemIndex) {
    const firstService = document.querySelector(".item-service");
    const firstStatus = document.querySelector(".item-status");

    if (!firstService || !firstStatus) {
      console.error("Template selects not found!");
      return "";
    }

    const servicesOptions = firstService.innerHTML;
    const statusOptions = firstStatus.innerHTML;
    const todayYYYYMMDD = new Date().toISOString().split("T")[0];

    return `
      <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 10px;">
        <span class="item-badge">Item #${itemIndex}</span>
        <button type="button" class="remove-item-btn" data-item="${itemIndex}">✕ Hapus</button>
      </div>

      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 40px;">
        <div>
          <label>🏷️ Brand / Merk</label>
          <input type="text" name="items[${itemIndex}][brand]" class="item-brand" required placeholder="Contoh: Nike, Adidas">
        </div>
        <div>
          <label>🛠️ Layanan</label>
          <select name="items[${itemIndex}][service_id]" class="item-service" required>
            ${servicesOptions}
          </select>
        </div>
        <div>
          <label>💰 Harga</label>
          <input type="text" class="item-price-display" readonly value="Rp 0" style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
          <input type="hidden" name="items[${itemIndex}][price]" class="item-price" value="0">
        </div>
        <div>
          <label>⏱️ Estimasi Selesai</label>
          <input type="text" class="item-estimate" readonly value="-" style="background: #f1f5f9; font-weight: 600;">
          <input type="hidden" name="items[${itemIndex}][duration]" class="item-duration" value="0">
        </div>
        <div>
          <label>📅 Tgl. Transaksi</label>
          <input type="date" name="items[${itemIndex}][trans_date]" class="item-trans-date" value="${todayYYYYMMDD}" required>
        </div>
        <div>
          <label>📆 Tanggal Estimasi Selesai</label>
          <input type="text" class="item-est-date-display" readonly value="-" style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
          <input type="hidden" name="items[${itemIndex}][est_finish_date]" class="item-est-date-hidden" value="">
        </div>
        <div style="grid-column: 1 / -1;">
          <label>📊 Status</label>
          <select name="items[${itemIndex}][status_id]" class="item-status" required>
            ${statusOptions}
          </select>
        </div>
        <div style="grid-column: 1 / -1;">
          <label>📝 Catatan Item</label>
          <textarea name="items[${itemIndex}][notes]" class="item-notes" rows="2" placeholder="Catatan khusus untuk item ini..." style="width:100%; padding:10px; border-radius:6px; border:1px solid #d6dee9; resize: vertical;"></textarea>
        </div>
      </div>
    `;
  }

  /**
   * Update item data when service changes
   * @param {HTMLElement} itemElement - Item container element
   */
  function updateItemServiceData(itemElement) {
    const serviceSelect = itemElement.querySelector(".item-service");
    const priceDisplay = itemElement.querySelector(".item-price-display");
    const priceHidden = itemElement.querySelector(".item-price");
    const estimateDisplay = itemElement.querySelector(".item-estimate");
    const durationHidden = itemElement.querySelector(".item-duration");
    const transDateInput = itemElement.querySelector(".item-trans-date");
    const estDateDisplay = itemElement.querySelector(".item-est-date-display");
    const estDateHidden = itemElement.querySelector(".item-est-date-hidden");

    if (!serviceSelect) {
      console.error("Service select not found in item");
      return;
    }

    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

    if (selectedOption && selectedOption.value) {
      const price = parseFloat(selectedOption.dataset.price || 0);
      const duration = parseInt(selectedOption.dataset.duration || 0);

      if (priceDisplay)
        priceDisplay.value = `Rp ${price.toLocaleString("id-ID")}`;
      if (priceHidden) priceHidden.value = price;
      if (estimateDisplay)
        estimateDisplay.value = duration > 0 ? `${duration} Hari` : "-";
      if (durationHidden) durationHidden.value = duration;

      if (transDateInput && transDateInput.value && duration > 0) {
        const estDateDDMMYYYY = addDaysToDate(transDateInput.value, duration);
        if (estDateDisplay) estDateDisplay.value = estDateDDMMYYYY;
        if (estDateHidden) estDateHidden.value = estDateDDMMYYYY;
      } else {
        if (estDateDisplay) estDateDisplay.value = "-";
        if (estDateHidden) estDateHidden.value = "";
      }
    } else {
      // Reset to default values
      if (priceDisplay) priceDisplay.value = "Rp 0";
      if (priceHidden) priceHidden.value = "0";
      if (estimateDisplay) estimateDisplay.value = "-";
      if (durationHidden) durationHidden.value = "0";
      if (estDateDisplay) estDateDisplay.value = "-";
      if (estDateHidden) estDateHidden.value = "";
    }

    calculateOrderSummary();
  }

  /**
   * Calculate and update order summary
   */
  function calculateOrderSummary() {
    const items = document.querySelectorAll(".item-group");
    let totalAmount = 0;
    let maxDuration = 0;
    let maxDurationText = "-";
    let latestEstDate = "-";

    items.forEach((item) => {
      const priceInput = item.querySelector(".item-price");
      const durationInput = item.querySelector(".item-duration");
      const estDateDisplay = item.querySelector(".item-est-date-display");

      const price = parseFloat(priceInput?.value || 0);
      const duration = parseInt(durationInput?.value || 0);

      totalAmount += price;

      if (duration > maxDuration) {
        maxDuration = duration;
        maxDurationText = duration > 0 ? `${duration} Hari` : "-";
      }

      if (
        estDateDisplay &&
        estDateDisplay.value &&
        estDateDisplay.value !== "-"
      ) {
        if (
          latestEstDate === "-" ||
          compareDDMMYYYY(estDateDisplay.value, latestEstDate) > 0
        ) {
          latestEstDate = estDateDisplay.value;
        }
      }
    });

    // Update summary fields
    const totalPriceInput = document.getElementById("totalPrice");
    if (totalPriceInput) totalPriceInput.value = totalAmount;

    const totalPriceDisplay = document.getElementById("totalPriceDisplay");
    if (totalPriceDisplay)
      totalPriceDisplay.value = `Rp ${totalAmount.toLocaleString("id-ID")}`;

    const maxEstimate = document.getElementById("maxEstimate");
    if (maxEstimate) maxEstimate.value = maxDurationText;

    const maxDurationInput = document.getElementById("maxDuration");
    if (maxDurationInput) maxDurationInput.value = maxDuration;

    const summaryEstDateDisplays = document.querySelectorAll(
      ".order-summary .item-est-date-display"
    );
    summaryEstDateDisplays.forEach((display) => {
      display.value = latestEstDate;
    });

    const totalItemsInput = document.getElementById("totalItems");
    if (totalItemsInput) totalItemsInput.value = items.length;
  }

  /**
   * Setup item event listeners
   * @param {HTMLElement} itemElement - Item container element
   */
  function setupItemListeners(itemElement) {
    const serviceSelect = itemElement.querySelector(".item-service");
    const transDateInput = itemElement.querySelector(".item-trans-date");
    const removeBtn = itemElement.querySelector(".remove-item-btn");

    if (serviceSelect) {
      serviceSelect.addEventListener("change", () =>
        updateItemServiceData(itemElement)
      );
    }

    if (transDateInput) {
      transDateInput.addEventListener("change", () => {
        if (serviceSelect && serviceSelect.value) {
          updateItemServiceData(itemElement);
        }
      });
    }

    if (removeBtn) {
      removeBtn.addEventListener("click", async function () {
        const allItems = document.querySelectorAll(".item-group");

        if (allItems.length > 1) {
          const confirmed = await customConfirm(
            "Hapus item ini dari pesanan?",
            "Konfirmasi Hapus",
            "🗑️"
          );

          if (confirmed) {
            itemElement.remove();
            calculateOrderSummary();
            updateRemoveButtons();
          }
        } else {
          await customAlert(
            "Minimal harus ada 1 item dalam pesanan!",
            "Peringatan",
            "⚠️"
          );
        }
      });
    }
  }

  /**
   * Reset form to initial state
   */
  function resetFormAndItems() {
    const form = document.getElementById("addForm");
    if (form) form.reset();

    const itemsContainer = document.getElementById("itemsContainer");
    if (!itemsContainer) return;

    // Remove all items except first
    const allItems = itemsContainer.querySelectorAll(".item-group");
    allItems.forEach((item, index) => {
      if (index > 0) item.remove();
    });

    itemCounter = 1;

    // Reset first item
    const firstItem = itemsContainer.querySelector(".item-group");
    if (firstItem) {
      const todayYYYYMMDD = new Date().toISOString().split("T")[0];

      const transDate = firstItem.querySelector(".item-trans-date");
      if (transDate) transDate.value = todayYYYYMMDD;

      const priceDisplay = firstItem.querySelector(".item-price-display");
      if (priceDisplay) priceDisplay.value = "Rp 0";

      const priceHidden = firstItem.querySelector(".item-price");
      if (priceHidden) priceHidden.value = "0";

      const estimate = firstItem.querySelector(".item-estimate");
      if (estimate) estimate.value = "-";

      const duration = firstItem.querySelector(".item-duration");
      if (duration) duration.value = "0";

      const estDateDisplay = firstItem.querySelector(".item-est-date-display");
      if (estDateDisplay) estDateDisplay.value = "-";

      const estDateHidden = firstItem.querySelector(".item-est-date-hidden");
      if (estDateHidden) estDateHidden.value = "";
    }

    updateRemoveButtons();
    calculateOrderSummary();
  }

  // ==================== EVENT LISTENERS ====================

  // Add Item Button
  const addItemBtn = document.getElementById("addItemBtn");
  if (addItemBtn) {
    addItemBtn.addEventListener("click", function () {
      itemCounter++;
      const itemsContainer = document.getElementById("itemsContainer");

      if (!itemsContainer) {
        console.error("Items container not found!");
        return;
      }

      const newItem = document.createElement("div");
      newItem.className = "item-group";
      newItem.setAttribute("data-item-index", itemCounter);
      newItem.innerHTML = createNewItemHTML(itemCounter);
      itemsContainer.appendChild(newItem);

      setupItemListeners(newItem);
      updateRemoveButtons();
      calculateOrderSummary();

      // Scroll to new item
      newItem.scrollIntoView({ behavior: "smooth", block: "nearest" });
    });
  }

  // Setup first item listeners
  const firstItem = document.querySelector(".item-group");
  if (firstItem) {
    setupItemListeners(firstItem);
  }

  // Open Add Modal Button
  const openAddBtn = document.getElementById("openAddModal");
  if (openAddBtn) {
    openAddBtn.addEventListener("click", function () {
      resetFormAndItems();

      if (typeof setPaymentDate === "function") {
        setPaymentDate("payment_date_display", "payment_date_hidden", "");
      }

      if (typeof showModal === "function") {
        showModal("addModal");
      }

      const payStatus = document.getElementById("payment_status");
      if (payStatus && typeof handlePaymentStatusChange === "function") {
        handlePaymentStatusChange(
          payStatus.value,
          "payment_date_display",
          "payment_date_hidden"
        );
      }
    });
  }

  // Close Modal Buttons
  document.querySelectorAll(".close").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const target = btn.getAttribute("data-target");
      if (target && typeof hideModal === "function") {
        hideModal(target);
      }
    });
  });

  // Rupiah Input Setup
  if (typeof setupRupiahInput === "function") {
    setupRupiahInput("amount_paid_display", "amount_paid");
  }

  // Payment Status Change
  const payStatusAdd = document.getElementById("payment_status");
  if (payStatusAdd && typeof handlePaymentStatusChange === "function") {
    payStatusAdd.addEventListener("change", function () {
      handlePaymentStatusChange(
        this.value,
        "payment_date_display",
        "payment_date_hidden"
      );
    });
  }

  // ==================== FORM SUBMISSION ====================

  /**
   * Handle form submission to API
   * @param {FormData} formData - Form data to submit
   * @returns {Promise<Object>} API response
   */
  async function submitFormToAPI(formData) {
    const apiUrl = buildApiUrl("/actions/drop/drop_add.php");

    const response = await fetch(apiUrl, {
      method: "POST",
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
    }

    const responseText = await response.text();
    const parseResult = safeJSONParse(responseText);

    if (!parseResult.success) {
      throw new Error(parseResult.error);
    }

    return parseResult.data;
  }

  /**
   * Open print window with proper error handling
   * @param {string} dropId - Drop ID to print
   * @returns {Promise<boolean>} Success status
   */
  async function openPrintWindow(dropId) {
    const printUrl = buildApiUrl(`/actions/drop/cetak_struk.php?id=${dropId}`);

    try {
      const printWindow = window.open(
        printUrl,
        `CetakStruk_${dropId}`,
        "width=800,height=900,scrollbars=yes,resizable=yes,menubar=no,toolbar=no,location=no,status=no"
      );

      if (
        !printWindow ||
        printWindow.closed ||
        typeof printWindow.closed === "undefined"
      ) {
        await customError(
          "Popup diblokir oleh browser!\n\nSilakan izinkan popup untuk situs ini dan coba lagi.\n\nAnda bisa mencetak ulang dari menu utama.",
          "Popup Diblokir"
        );
        return false;
      }

      console.log("✅ Print window opened successfully");
      return true;
    } catch (error) {
      console.error("❌ Error opening print window:", error);
      await customError(
        `Error membuka halaman cetak: ${error.message}\n\nAnda bisa mencetak ulang dari menu utama.`,
        "Gagal Membuka Cetak"
      );
      return false;
    }
  }

  // SAVE & PRINT BUTTON
  const saveAndPrintBtn = document.getElementById("saveAndPrintBtn");
  if (saveAndPrintBtn) {
    saveAndPrintBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      // Validation
      if (!(await validateForm())) return;

      const form = document.getElementById("addForm");
      if (!form || !form.checkValidity()) {
        if (form) form.reportValidity();
        return;
      }

      // Disable button
      this.disabled = true;
      this.innerHTML = BUTTON_TEXTS.saveAndPrint.loading;
      showLoading("Menyimpan pesanan...");

      const formData = new FormData(form);

      try {
        const data = await submitFormToAPI(formData);

        hideLoading();

        if (data.success && data.drop_id) {
          if (typeof hideModal === "function") {
            hideModal("addModal");
          }

          await customSuccess(
            "Pesanan berhasil disimpan!\n\nMembuka halaman cetak..."
          );

          // Open print window
          await openPrintWindow(data.drop_id);

          // Reload after delay
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          throw new Error(data.message || "Response tidak valid dari server");
        }
      } catch (error) {
        hideLoading();
        console.error("❌ Save & Print Error:", error);
        await customError(
          `Terjadi kesalahan:\n\n${error.message}`,
          "Gagal Menyimpan"
        );
      } finally {
        restoreButton(this, BUTTON_TEXTS.saveAndPrint.default);
      }
    });
  }

  // SAVE ONLY BUTTON
  const saveOnlyBtn = document.getElementById("saveOnlyBtn");
  if (saveOnlyBtn) {
    saveOnlyBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      // Validation
      if (!(await validateForm())) return;

      const form = document.getElementById("addForm");
      if (!form || !form.checkValidity()) {
        if (form) form.reportValidity();
        return;
      }

      // Disable button
      this.disabled = true;
      this.innerHTML = BUTTON_TEXTS.saveOnly.loading;
      showLoading("Menyimpan pesanan...");

      const formData = new FormData(form);

      try {
        const data = await submitFormToAPI(formData);

        hideLoading();

        if (data.success) {
          if (typeof hideModal === "function") {
            hideModal("addModal");
          }

          // Store success message
          sessionStorage.setItem("drop_showSuccess", "true");
          sessionStorage.setItem(
            "drop_successMessage",
            "Pesanan berhasil ditambahkan"
          );
          sessionStorage.setItem("drop_orderCode", data.order_code || "");

          // Reload page
          setTimeout(() => window.location.reload(), 300);
        } else {
          throw new Error(data.message || "Response tidak valid dari server");
        }
      } catch (error) {
        hideLoading();
        console.error("❌ Save Only Error:", error);
        await customError(
          `Terjadi kesalahan:\n\n${error.message}`,
          "Gagal Menyimpan"
        );
      } finally {
        restoreButton(this, BUTTON_TEXTS.saveOnly.default);
      }
    });
  }

  // ==================== SUCCESS MODAL ====================

  // Check session storage for success message
  if (sessionStorage.getItem("drop_showSuccess") === "true") {
    const successMessage =
      sessionStorage.getItem("drop_successMessage") ||
      "Pesanan berhasil ditambahkan";
    const orderCode = sessionStorage.getItem("drop_orderCode") || "";

    customSuccess(
      `${successMessage}\n\n${orderCode ? `Kode Order: ${orderCode}` : ""}`
    );

    // Clear session storage
    sessionStorage.removeItem("drop_showSuccess");
    sessionStorage.removeItem("drop_successMessage");
    sessionStorage.removeItem("drop_orderCode");
  }

  console.log("✅ Modal event listeners initialized");
  console.log("✅ Version 3.2: Enhanced error handling & code quality");
});
