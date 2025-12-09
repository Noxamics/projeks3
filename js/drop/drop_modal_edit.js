// =====================================================================
// File: /js/drop/drop_modal_edit.js
// Edit Modal Handler - FIXED for Timeline Page
// Version: 3.2 - Fixed Template Loading
// =====================================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Drop edit modal module loaded");

  // ==================== PAGE DETECTION ====================

  const currentPage = window.location.pathname;
  const isTimelinePage = currentPage.includes("timeline_pesanan.php");
  const isDropPage = currentPage.includes("drop.php");

  const API_BASE_PATH = "../actions/drop/";

  console.log(
    `🔍 Current page: ${
      isTimelinePage ? "Timeline" : isDropPage ? "Drop" : "Unknown"
    }`
  );

  let editItemCounter = 1;

  // ==================== HELPER FUNCTIONS ====================

  function formatRupiah(amount) {
    const number = parseFloat(amount) || 0;
    return `Rp ${number.toLocaleString("id-ID")}`;
  }

  function buildApiUrl(endpoint) {
    const protocol = window.location.protocol;
    const host = window.location.host;
    return `${protocol}//${host}${endpoint}`;
  }

  // ==================== TEMPLATE FUNCTIONS - FIXED ====================

  function getServicesOptionsHTML() {
    // Try to get from hidden div (timeline page)
    const timelineTemplate = document.getElementById(
      "timeline_services_template"
    );
    if (timelineTemplate) {
      const select = timelineTemplate.querySelector("select");
      if (select) {
        console.log("✅ Services loaded from timeline template");
        return select.innerHTML;
      }
    }

    // Fallback: Get from first item in itemsContainer (drop page)
    const firstItem = document.querySelector("#itemsContainer .item-group");
    if (firstItem) {
      const serviceSelect = firstItem.querySelector(".item-service");
      if (serviceSelect) {
        console.log("✅ Services loaded from drop page");
        return serviceSelect.innerHTML;
      }
    }

    // Fallback: Get from edit modal if already loaded
    const editFirstItem = document.querySelector(
      "#editItemsContainer .item-group"
    );
    if (editFirstItem) {
      const serviceSelect = editFirstItem.querySelector(".item-service");
      if (serviceSelect) {
        console.log("✅ Services loaded from existing edit item");
        return serviceSelect.innerHTML;
      }
    }

    console.error("❌ No services template found!");
    return '<option value="">-- Pilih Layanan --</option>';
  }

  function getStatusesOptionsHTML() {
    // Try to get from hidden div (timeline page)
    const timelineTemplate = document.getElementById(
      "timeline_statuses_template"
    );
    if (timelineTemplate) {
      const select = timelineTemplate.querySelector("select");
      if (select) {
        console.log("✅ Statuses loaded from timeline template");
        return select.innerHTML;
      }
    }

    // Fallback: Get from first item in itemsContainer (drop page)
    const firstItem = document.querySelector("#itemsContainer .item-group");
    if (firstItem) {
      const statusSelect = firstItem.querySelector(".item-status");
      if (statusSelect) {
        console.log("✅ Statuses loaded from drop page");
        return statusSelect.innerHTML;
      }
    }

    // Fallback: Get from edit modal if already loaded
    const editFirstItem = document.querySelector(
      "#editItemsContainer .item-group"
    );
    if (editFirstItem) {
      const statusSelect = editFirstItem.querySelector(".item-status");
      if (statusSelect) {
        console.log("✅ Statuses loaded from existing edit item");
        return statusSelect.innerHTML;
      }
    }

    console.error("❌ No statuses template found!");
    return '<option value="">Pilih Status</option>';
  }

  // ==================== ITEM MANAGEMENT ====================

  function updateEditRemoveButtons() {
    const items = document.querySelectorAll("#editItemsContainer .item-group");
    items.forEach((item) => {
      const removeBtn = item.querySelector(".remove-item-btn");
      if (removeBtn) {
        removeBtn.style.display = items.length > 1 ? "inline-block" : "none";
      }
    });
  }

  function createEditItemHTML(itemIndex, itemData = {}) {
    const servicesOptions = getServicesOptionsHTML();
    const statusOptions = getStatusesOptionsHTML();

    const priceFormatted = itemData.price
      ? Number(itemData.price).toLocaleString("id-ID")
      : "0";

    const durationText = itemData.duration ? `${itemData.duration} Hari` : "-";

    return `
      <div class="item-group" data-item-index="${itemIndex}">
        <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 10px;">
          <span class="item-badge">Item #${itemIndex}</span>
          <button type="button" class="remove-item-btn" data-item="${itemIndex}">
            <i class="bi bi-x-lg"></i> Hapus
          </button>
        </div>

        <input type="hidden" name="items[${itemIndex}][item_id]" value="${itemData.item_id || ""}">

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 40px;">
          <div>
            <label><i class="bi bi-tag"></i> Brand / Merk</label>
            <input type="text" name="items[${itemIndex}][brand]" class="item-brand" required
              placeholder="Contoh: Nike, Adidas" value="${
                itemData.brand || ""
              }">
          </div>
          <div>
            <label><i class="bi bi-tools"></i> Layanan</label>
            <select name="items[${itemIndex}][service_id]" class="item-service" required>
              ${servicesOptions}
            </select>
          </div>
          <div>
            <label><i class="bi bi-cash-coin"></i> Harga</label>
            <input type="text" class="item-price-display" readonly value="Rp ${priceFormatted}" 
              style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
            <input type="hidden" name="items[${itemIndex}][price]" class="item-price" value="${itemData.price || "0"}">
          </div>
          <div>
            <label><i class="bi bi-clock-history"></i> Estimasi Selesai</label>
            <input type="text" class="item-estimate" readonly value="${durationText}" 
              style="background: #f1f5f9; font-weight: 600;">
            <input type="hidden" name="items[${itemIndex}][duration]" class="item-duration" value="${itemData.duration || "0"}">
          </div>
          <div>
            <label><i class="bi bi-calendar-event"></i> Tgl. Transaksi</label>
            <input type="date" name="items[${itemIndex}][trans_date]" class="item-trans-date"
              value="${
                itemData.trans_date || new Date().toISOString().split("T")[0]
              }">
          </div>
          <div>
            <label><i class="bi bi-calendar-check"></i> Tanggal Estimasi Selesai</label>
            <input type="date" name="items[${itemIndex}][est_finish_date]" class="item-est-date" readonly
              style="background: #f1f5f9;" value="${
                itemData.est_finish_date || ""
              }">
          </div>
          <div style="grid-column: 1 / -1;">
            <label><i class="bi bi-bar-chart-steps"></i> Status</label>
            <select name="items[${itemIndex}][status_id]" class="item-status" required>
              ${statusOptions}
            </select>
          </div>
          <div style="grid-column: 1 / -1;">
            <label><i class="bi bi-pencil-square"></i> Catatan Item</label>
            <textarea name="items[${itemIndex}][notes]" class="item-notes" rows="2"
              placeholder="Catatan khusus untuk item ini..."
              style="width:100%; padding:10px; border-radius:6px; border:1px solid #d6dee9; resize: vertical;">${
                itemData.notes || ""
              }</textarea>
          </div>
        </div>
      </div>
    `;
  }

  function attachEditItemListeners(itemElement) {
    const serviceSelect = itemElement.querySelector(".item-service");
    const transDateInput = itemElement.querySelector(".item-trans-date");
    const removeBtn = itemElement.querySelector(".remove-item-btn");

    if (serviceSelect) {
      serviceSelect.addEventListener("change", () =>
        updateEditItemServiceData(itemElement)
      );
    }

    if (transDateInput) {
      transDateInput.addEventListener("change", () => {
        if (serviceSelect?.value) {
          updateEditItemServiceData(itemElement);
        }
      });
    }

    if (removeBtn) {
      removeBtn.addEventListener("click", async function () {
        const items = document.querySelectorAll(
          "#editItemsContainer .item-group"
        );

        if (items.length > 1) {
          const confirmed = await customConfirm(
            "Hapus item ini dari pesanan?",
            "Konfirmasi Hapus",
            "🗑️"
          );

          if (confirmed) {
            itemElement.remove();
            calculateEditOrderSummary();
            updateEditRemoveButtons();
            reindexEditItems();
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

  async function updateEditItemServiceData(itemGroup) {
    const serviceSelect = itemGroup.querySelector(".item-service");
    const priceDisplay = itemGroup.querySelector(".item-price-display");
    const priceInput = itemGroup.querySelector(".item-price");
    const estimateDisplay = itemGroup.querySelector(".item-estimate");
    const durationInput = itemGroup.querySelector(".item-duration");
    const transDateInput = itemGroup.querySelector(".item-trans-date");
    const estDateInput = itemGroup.querySelector(".item-est-date");

    if (!serviceSelect) return;

    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

    if (selectedOption?.value) {
      const serviceId = selectedOption.value;
      const transDate = transDateInput?.value;
      const dropId = document.getElementById("edit_drop_id")?.value;

      if (estimateDisplay) estimateDisplay.value = "⏳ Menghitung...";
      if (durationInput) durationInput.value = "0";
      if (estDateInput) estDateInput.value = "";

      if (typeof fetchDynamicEstimate === "function" && transDate && dropId) {
        const result = await fetchDynamicEstimate(serviceId, transDate, dropId);

        if (result?.success) {
          if (priceDisplay) priceDisplay.value = formatRupiah(result.price_min);
          if (priceInput) priceInput.value = result.price_min;
          if (durationInput) durationInput.value = result.duration;
          if (estimateDisplay) estimateDisplay.value = result.estimate_desc;
          if (estDateInput) estDateInput.value = result.estimate_date;
        } else {
          setDefaultServiceValues(
            selectedOption,
            priceDisplay,
            priceInput,
            durationInput,
            estimateDisplay,
            estDateInput,
            transDate
          );
        }
      } else {
        setDefaultServiceValues(
          selectedOption,
          priceDisplay,
          priceInput,
          durationInput,
          estimateDisplay,
          estDateInput,
          transDate
        );
      }
    } else {
      if (priceDisplay) priceDisplay.value = "Rp 0";
      if (priceInput) priceInput.value = "0";
      if (durationInput) durationInput.value = "0";
      if (estimateDisplay) estimateDisplay.value = "-";
      if (estDateInput) estDateInput.value = "";
    }

    calculateEditOrderSummary();
  }

  function setDefaultServiceValues(
    selectedOption,
    priceDisplay,
    priceInput,
    durationInput,
    estimateDisplay,
    estDateInput,
    transDate
  ) {
    const priceMin = parseFloat(selectedOption.getAttribute("data-price") || 0);
    const duration = parseInt(
      selectedOption.getAttribute("data-duration") || 0
    );

    if (priceDisplay) priceDisplay.value = formatRupiah(priceMin);
    if (priceInput) priceInput.value = priceMin;
    if (durationInput) durationInput.value = duration;
    if (estimateDisplay)
      estimateDisplay.value = duration > 0 ? `${duration} Hari` : "-";

    if (transDate && estDateInput) {
      const date = new Date(transDate);
      date.setDate(date.getDate() + duration);
      estDateInput.value = date.toISOString().split("T")[0];
    }
  }

  function calculateEditOrderSummary() {
    const items = document.querySelectorAll("#editItemsContainer .item-group");
    let totalPrice = 0;
    let maxDuration = 0;
    let maxEstDate = "";

    items.forEach((item) => {
      const priceInput = item.querySelector(".item-price");
      const durationInput = item.querySelector(".item-duration");
      const estDateInput = item.querySelector(".item-est-date");

      const price = parseFloat(priceInput?.value || 0);
      const duration = parseInt(durationInput?.value || 0);
      const estDate = estDateInput?.value;

      totalPrice += price;
      if (duration > maxDuration) maxDuration = duration;
      if (estDate && (!maxEstDate || estDate > maxEstDate))
        maxEstDate = estDate;
    });

    const totalItemsEl = document.getElementById("editTotalItems");
    const totalPriceDisplayEl = document.getElementById(
      "editTotalPriceDisplay"
    );
    const totalPriceEl = document.getElementById("editTotalPrice");
    const maxEstimateEl = document.getElementById("editMaxEstimate");
    const maxDurationEl = document.getElementById("editMaxDuration");
    const finalEstDateEl = document.getElementById("editFinalEstDate");

    if (totalItemsEl) totalItemsEl.value = `${items.length} Item`;
    if (totalPriceDisplayEl)
      totalPriceDisplayEl.value = formatRupiah(totalPrice);
    if (totalPriceEl) totalPriceEl.value = totalPrice;
    if (maxEstimateEl)
      maxEstimateEl.value = maxDuration > 0 ? `${maxDuration} Hari` : "-";
    if (maxDurationEl) maxDurationEl.value = maxDuration;
    if (finalEstDateEl) finalEstDateEl.value = maxEstDate;
  }

  function reindexEditItems() {
    const container = document.getElementById("editItemsContainer");
    if (!container) return;

    const items = container.querySelectorAll(".item-group");

    items.forEach((item, idx) => {
      const newIndex = idx + 1;
      item.setAttribute("data-item-index", newIndex);

      const badge = item.querySelector(".item-badge");
      if (badge) badge.textContent = `Item #${newIndex}`;

      item.querySelectorAll('[name^="items["]').forEach((input) => {
        const name = input.getAttribute("name");
        const newName = name.replace(/items\[\d+\]/, `items[${newIndex}]`);
        input.setAttribute("name", newName);
      });

      const removeBtn = item.querySelector(".remove-item-btn");
      if (removeBtn) {
        removeBtn.style.display = newIndex > 1 ? "inline-block" : "none";
      }
    });

    editItemCounter = items.length;
  }

  // ==================== LOAD EDIT MODAL ====================

  window.loadEditModal = async function (dropId) {
    const apiUrl = `${API_BASE_PATH}get_order_detail.php?drop_id=${dropId}`;

    showLoading("Memuat data pesanan...");

    try {
      const response = await fetch(apiUrl);

      const contentType = response.headers.get("content-type");
      if (!contentType?.includes("application/json")) {
        throw new Error("Server mengembalikan bukan JSON. Cek path file PHP!");
      }

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      hideLoading();

      if (data.success) {
        populateEditForm(data, dropId);
      } else {
        await customError(data.message || "Gagal memuat data", "Error");
      }
    } catch (error) {
      hideLoading();
      console.error("❌ Error:", error);
      await customError(
        `Gagal memuat data pesanan:\n\n${error.message}`,
        "Error"
      );
    }
  };

  function populateEditForm(data, dropId) {
    // Set customer info
    const customerNameEl = document.getElementById("edit_customer_name");
    const customerPhoneEl = document.getElementById("edit_customer_phone");
    const customerIdEl = document.getElementById("edit_customer_id");
    const dropIdEl = document.getElementById("edit_drop_id");

    if (customerNameEl) customerNameEl.value = data.customer.name;
    if (customerPhoneEl) customerPhoneEl.value = data.customer.phone;
    if (customerIdEl) customerIdEl.value = data.customer.id;
    if (dropIdEl) dropIdEl.value = dropId;

    // Set employee
    const empSelect = document.getElementById("edit_employee_id");
    if (empSelect?.tagName === "SELECT") {
      empSelect.value = data.employee_id;
    }

    // Set note
    const noteEl = document.getElementById("edit_note");
    if (noteEl) noteEl.value = data.note || "";

    // Set payment info
    populatePaymentInfo(data.payment);

    // Load items
    populateEditItems(data.items);

    editItemCounter = data.items.length;
    calculateEditOrderSummary();
    updateEditRemoveButtons();

    if (typeof showModal === "function") {
      showModal("editModal");
    }
  }

  function populatePaymentInfo(payment) {
    const statusEl = document.getElementById("edit_payment_status");
    const methodEl = document.getElementById("edit_payment_method");
    const amountPaidEl = document.getElementById("edit_amount_paid");
    const amountPaidDisplayEl = document.getElementById(
      "edit_amount_paid_display"
    );
    const dateDisplayEl = document.getElementById("edit_payment_date_display");
    const dateHiddenEl = document.getElementById("edit_payment_date_hidden");

    if (statusEl) statusEl.value = payment.status;
    if (methodEl) methodEl.value = payment.method;
    if (amountPaidEl) amountPaidEl.value = payment.amount_paid;
    if (amountPaidDisplayEl) {
      amountPaidDisplayEl.value = `Rp ${Number(
        payment.amount_paid
      ).toLocaleString("id-ID")}`;
    }

    if (payment.date) {
      if (dateDisplayEl) {
        dateDisplayEl.value = payment.date;
        dateDisplayEl.disabled = false;
      }
      if (dateHiddenEl) {
        dateHiddenEl.value = payment.date;
        dateHiddenEl.dataset.originalDate = payment.date;
      }
    } else {
      if (dateDisplayEl) {
        dateDisplayEl.value = "";
        dateDisplayEl.disabled = true;
      }
      if (dateHiddenEl) {
        dateHiddenEl.value = "";
        dateHiddenEl.dataset.originalDate = "";
      }
    }
  }

  function populateEditItems(items) {
    const container = document.getElementById("editItemsContainer");
    if (!container) return;

    container.innerHTML = "";

    items.forEach((item, idx) => {
      const itemHTML = createEditItemHTML(idx + 1, item);
      container.insertAdjacentHTML("beforeend", itemHTML);

      const itemElement = container.querySelector(
        `[data-item-index="${idx + 1}"]`
      );
      if (!itemElement) return;

      const serviceSelect = itemElement.querySelector(".item-service");
      if (serviceSelect) serviceSelect.value = item.service_id;

      const statusSelect = itemElement.querySelector(".item-status");
      if (statusSelect) statusSelect.value = item.status_id;

      attachEditItemListeners(itemElement);
    });
  }

  // ==================== EVENT LISTENERS ====================

  function setupEditRowListeners() {
    // Double-click to edit (drop.php only)
    if (!isTimelinePage && isDropPage) {
      document.querySelectorAll(".order-item-row").forEach((row) => {
        row.addEventListener("dblclick", function (e) {
          if (e.target.type === "checkbox" || e.target.tagName === "SELECT")
            return;
          const dropId = this.getAttribute("data-drop-id");
          if (dropId) loadEditModal(dropId);
        });
      });
    }

    // Click note cell to edit
    if (!isTimelinePage && isDropPage) {
      document.querySelectorAll(".note-cell").forEach((noteCell) => {
        noteCell.addEventListener("click", function (e) {
          e.stopPropagation();
          const dropId = this.getAttribute("data-drop-id");
          if (!dropId) return;

          loadEditModal(dropId);
          setTimeout(() => {
            const notesField = document.getElementById("edit_note");
            if (notesField) notesField.focus();
          }, 300);
        });
      });
    }
  }

  setupEditRowListeners();

  // Add item button
  const editAddItemBtn = document.getElementById("editAddItemBtn");
  if (editAddItemBtn) {
    editAddItemBtn.addEventListener("click", function () {
      editItemCounter++;
      const container = document.getElementById("editItemsContainer");
      if (!container) return;

      const itemHTML = createEditItemHTML(editItemCounter);
      container.insertAdjacentHTML("beforeend", itemHTML);

      const newItem = container.querySelector(
        `[data-item-index="${editItemCounter}"]`
      );
      if (newItem) {
        attachEditItemListeners(newItem);
      }

      updateEditRemoveButtons();
      calculateEditOrderSummary();
    });
  }

  // Rupiah input setup
  if (typeof setupRupiahInput === "function") {
    setupRupiahInput("edit_amount_paid_display", "edit_amount_paid");
  }

  // Payment status change
  const editPayStatus = document.getElementById("edit_payment_status");
  if (editPayStatus && typeof handlePaymentStatusChange === "function") {
    editPayStatus.addEventListener("change", function () {
      const dateHiddenEl = document.getElementById("edit_payment_date_hidden");
      const originalDate = dateHiddenEl?.dataset.originalDate || null;

      handlePaymentStatusChange(
        this.value,
        "edit_payment_date_display",
        "edit_payment_date_hidden",
        originalDate
      );
    });
  }

  // ==================== FORM SUBMISSION ====================

  async function submitEditForm(shouldPrint = false) {
    const form = document.getElementById("editForm");
    if (!form) return;

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const formData = new FormData(form);
    const dropId = document.getElementById("edit_drop_id")?.value;

    try {
      const response = await fetch(`${API_BASE_PATH}drop_edit.php`, {
        method: "POST",
        body: formData,
      });

      const contentType = response.headers.get("content-type");
      if (!contentType?.includes("application/json")) {
        throw new Error("Server mengembalikan HTML/Error bukan JSON.");
      }

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      hideLoading();

      if (data.success) {
        if (typeof hideModal === "function") {
          hideModal("editModal");
        }

        if (shouldPrint && dropId) {
          await customSuccess(
            "Pesanan berhasil diperbarui!\n\nMembuka halaman cetak..."
          );

          const cetakUrl = buildApiUrl(
            `/actions/drop/cetak_struk.php?id=${dropId}`
          );

          const printWindow = window.open(
            cetakUrl,
            `CetakStruk_${dropId}`,
            "width=800,height=900,scrollbars=yes,resizable=yes,menubar=no,toolbar=no,location=no,status=no"
          );

          if (
            !printWindow ||
            printWindow.closed ||
            typeof printWindow.closed === "undefined"
          ) {
            console.error("❌ Popup blocked");
            await customError(
              "Popup diblokir oleh browser!\n\nSilakan izinkan popup untuk situs ini dan coba lagi.",
              "Popup Diblokir"
            );
          } else {
            console.log("✅ Print window opened successfully");
            setTimeout(() => window.location.reload(), 1000);
          }
        } else {
          const storageKey = isTimelinePage
            ? "timeline_showSuccess"
            : "drop_showSuccess";
          const messageKey = isTimelinePage
            ? "timeline_successMessage"
            : "drop_successMessage";

          sessionStorage.setItem(storageKey, "true");
          sessionStorage.setItem(
            messageKey,
            data.message || "✅ Pesanan berhasil diperbarui!"
          );

          setTimeout(() => window.location.reload(), 500);
        }
      } else {
        throw new Error(data.message || "Gagal menyimpan perubahan");
      }
    } catch (error) {
      hideLoading();
      await customError(
        `Terjadi kesalahan:\n\n${error.message}`,
        error.message.includes("JSON")
          ? "Format Response Error"
          : "Gagal Menyimpan"
      );
      throw error;
    }
  }

  // Save only button
  const editSaveOnlyBtn = document.getElementById("editSaveOnlyBtn");
  if (editSaveOnlyBtn) {
    editSaveOnlyBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";
      showLoading("Menyimpan perubahan...");

      try {
        await submitEditForm(false);
      } catch (error) {
        this.disabled = false;
        this.innerHTML = "<i class='bi bi-save'></i> Simpan";
      }
    });
  }

  // Save & print button
  const editSaveAndPrintBtn = document.getElementById("editSaveAndPrintBtn");
  if (editSaveAndPrintBtn) {
    editSaveAndPrintBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";
      showLoading("Menyimpan perubahan...");

      try {
        await submitEditForm(true);
      } catch (error) {
        this.disabled = false;
        this.innerHTML = "<i class='bi bi-printer'></i> Simpan & Cetak";
      }
    });
  }

  // Close modal
  window.closeEditModal = function () {
    if (typeof hideModal === "function") {
      hideModal("editModal");
    }
  };

  console.log("✅ Edit modal event listeners initialized");
  console.log("✅ FIXED: Template loading from hidden divs");

  if (!isTimelinePage && isDropPage) {
    console.log("🖱️ Double-click row to edit full order");
    console.log("📝 Click note cell to edit order");
  }
});
