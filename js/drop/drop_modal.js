// =====================================================================
// File: /js/drop/drop_modal.js
// Modal Handler - FIXED: Save & Print Opens in New Window
// Version: 3.1 - Fixed Print Behavior
// =====================================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Drop modal module loaded");

  let itemCounter = 1;

  // ==================== HELPER FUNCTIONS ====================

  function buildApiUrl(endpoint) {
    const protocol = window.location.protocol;
    const host = window.location.host;
    return `${protocol}//${host}${endpoint}`;
  }

  function formatDateToDDMMYYYY(date) {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, "0");
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
  }

  function addDaysToDate(dateStr, days) {
    const d = new Date(dateStr);
    d.setDate(d.getDate() + days);
    return formatDateToDDMMYYYY(d);
  }

  function compareDDMMYYYY(date1, date2) {
    const d1 = date1.split("/");
    const d2 = date2.split("/");
    const dateObj1 = new Date(d1[2], d1[1] - 1, d1[0]);
    const dateObj2 = new Date(d2[2], d2[1] - 1, d2[0]);
    return dateObj1 - dateObj2;
  }

  // ==================== VALIDATION ====================

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
    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      const itemNum = i + 1;

      const serviceSelect = item.querySelector(".item-service");
      if (!serviceSelect.value) {
        await customError(
          `Item #${itemNum}: Pilih layanan terlebih dahulu!`,
          "Layanan Belum Dipilih"
        );
        serviceSelect.focus();
        return false;
      }

      const brandInput = item.querySelector(".item-brand");
      if (!brandInput.value.trim()) {
        await customError(
          `Item #${itemNum}: Brand/Merk harus diisi!`,
          "Brand Kosong"
        );
        brandInput.focus();
        return false;
      }

      const transDate = item.querySelector(".item-trans-date");
      if (!transDate.value) {
        await customError(
          `Item #${itemNum}: Tanggal transaksi harus diisi!`,
          "Tanggal Kosong"
        );
        transDate.focus();
        return false;
      }

      const statusSelect = item.querySelector(".item-status");
      if (!statusSelect.value) {
        await customError(
          `Item #${itemNum}: Pilih status item!`,
          "Status Belum Dipilih"
        );
        statusSelect.focus();
        return false;
      }
    }

    return true;
  }

  // ==================== ITEM MANAGEMENT ====================

  function updateRemoveButtons() {
    const items = document.querySelectorAll(".item-group");
    items.forEach((item) => {
      const removeBtn = item.querySelector(".remove-item-btn");
      if (removeBtn) {
        removeBtn.style.display = items.length > 1 ? "inline-block" : "none";
      }
    });
  }

  function createNewItemHTML(itemIndex) {
    const servicesOptions = document.querySelector(".item-service").innerHTML;
    const statusOptions = document.querySelector(".item-status").innerHTML;
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
          <input type="date" name="items[${itemIndex}][trans_date]" class="item-trans-date" value="${todayYYYYMMDD}">
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

  function updateItemServiceData(itemElement) {
    const serviceSelect = itemElement.querySelector(".item-service");
    const priceDisplay = itemElement.querySelector(".item-price-display");
    const priceHidden = itemElement.querySelector(".item-price");
    const estimateDisplay = itemElement.querySelector(".item-estimate");
    const durationHidden = itemElement.querySelector(".item-duration");
    const transDateInput = itemElement.querySelector(".item-trans-date");
    const estDateDisplay = itemElement.querySelector(".item-est-date-display");
    const estDateHidden = itemElement.querySelector(".item-est-date-hidden");

    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

    if (selectedOption && selectedOption.value) {
      const price = parseFloat(selectedOption.dataset.price || 0);
      const duration = parseInt(selectedOption.dataset.duration || 0);

      priceDisplay.value = `Rp ${price.toLocaleString("id-ID")}`;
      priceHidden.value = price;
      estimateDisplay.value = `${duration} Hari`;
      durationHidden.value = duration;

      if (transDateInput && transDateInput.value) {
        const estDateDDMMYYYY = addDaysToDate(transDateInput.value, duration);
        if (estDateDisplay) estDateDisplay.value = estDateDDMMYYYY;
        if (estDateHidden) estDateHidden.value = estDateDDMMYYYY;
      }

      calculateOrderSummary();
    } else {
      priceDisplay.value = "Rp 0";
      priceHidden.value = "0";
      estimateDisplay.value = "-";
      durationHidden.value = "0";
      if (estDateDisplay) estDateDisplay.value = "-";
      if (estDateHidden) estDateHidden.value = "";
      calculateOrderSummary();
    }
  }

  function calculateOrderSummary() {
    const items = document.querySelectorAll(".item-group");
    let totalAmount = 0;
    let maxDuration = 0;
    let maxDurationText = "-";
    let latestEstDate = "-";

    items.forEach((item) => {
      const price = parseFloat(item.querySelector(".item-price")?.value || 0);
      const duration = parseInt(
        item.querySelector(".item-duration")?.value || 0
      );
      totalAmount += price;

      if (duration > maxDuration) {
        maxDuration = duration;
        maxDurationText = duration > 0 ? `${duration} Hari` : "-";
      }

      const estDateDisplay = item.querySelector(".item-est-date-display");
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

  // ==================== EVENT LISTENERS ====================

  const addItemBtn = document.getElementById("addItemBtn");
  if (addItemBtn) {
    addItemBtn.addEventListener("click", function () {
      itemCounter++;
      const itemsContainer = document.getElementById("itemsContainer");
      const newItem = document.createElement("div");
      newItem.className = "item-group";
      newItem.setAttribute("data-item-index", itemCounter);
      newItem.innerHTML = createNewItemHTML(itemCounter);
      itemsContainer.appendChild(newItem);

      const serviceSelect = newItem.querySelector(".item-service");
      const transDateInput = newItem.querySelector(".item-trans-date");

      serviceSelect.addEventListener("change", () =>
        updateItemServiceData(newItem)
      );
      transDateInput.addEventListener("change", () => {
        if (serviceSelect.value) updateItemServiceData(newItem);
      });

      newItem
        .querySelector(".remove-item-btn")
        .addEventListener("click", async function () {
          if (document.querySelectorAll(".item-group").length > 1) {
            const confirmed = await customConfirm(
              "Hapus item ini dari pesanan?",
              "Konfirmasi Hapus",
              "🗑️"
            );
            if (confirmed) {
              newItem.remove();
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

      updateRemoveButtons();
      calculateOrderSummary();
    });
  }

  // First item listeners
  const firstItem = document.querySelector(".item-group");
  if (firstItem) {
    const serviceSelect = firstItem.querySelector(".item-service");
    const transDateInput = firstItem.querySelector(".item-trans-date");

    serviceSelect.addEventListener("change", () =>
      updateItemServiceData(firstItem)
    );
    transDateInput.addEventListener("change", () => {
      if (serviceSelect.value) updateItemServiceData(firstItem);
    });

    const removeBtn = firstItem.querySelector(".remove-item-btn");
    if (removeBtn) {
      removeBtn.addEventListener("click", async function () {
        if (document.querySelectorAll(".item-group").length > 1) {
          const confirmed = await customConfirm(
            "Hapus item ini dari pesanan?",
            "Konfirmasi Hapus",
            "🗑️"
          );
          if (confirmed) {
            firstItem.remove();
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

  // Open Add Modal
  const openAddBtn = document.getElementById("openAddModal");
  if (openAddBtn) {
    openAddBtn.addEventListener("click", function () {
      const form = document.getElementById("addForm");
      if (form) form.reset();

      const itemsContainer = document.getElementById("itemsContainer");
      const allItems = itemsContainer.querySelectorAll(".item-group");
      allItems.forEach((item, index) => {
        if (index > 0) item.remove();
      });
      itemCounter = 1;

      const firstItemReset = itemsContainer.querySelector(".item-group");
      if (firstItemReset) {
        firstItemReset.querySelector(".item-trans-date").value = new Date()
          .toISOString()
          .split("T")[0];
        firstItemReset.querySelector(".item-price-display").value = "Rp 0";
        firstItemReset.querySelector(".item-price").value = "0";
        firstItemReset.querySelector(".item-estimate").value = "-";
        firstItemReset.querySelector(".item-duration").value = "0";

        const estDateDisplay = firstItemReset.querySelector(
          ".item-est-date-display"
        );
        if (estDateDisplay) estDateDisplay.value = "-";
        const estDateHidden = firstItemReset.querySelector(
          ".item-est-date-hidden"
        );
        if (estDateHidden) estDateHidden.value = "";
      }

      updateRemoveButtons();
      calculateOrderSummary();

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

  // Close buttons
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

  // SAVE & PRINT - FIXED: Open in new window instead of redirect
  const saveAndPrintBtn = document.getElementById("saveAndPrintBtn");
  if (saveAndPrintBtn) {
    saveAndPrintBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      if (!(await validateForm())) return;

      const form = document.getElementById("addForm");
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";
      showLoading("Menyimpan pesanan...");

      const formData = new FormData(form);
      const apiUrl = buildApiUrl("/actions/drop/drop_add.php");

      try {
        const response = await fetch(apiUrl, {
          method: "POST",
          body: formData,
        });

        const responseText = await response.text();
        let data;
        try {
          data = JSON.parse(responseText);
        } catch (parseError) {
          throw new Error(`Invalid JSON: ${responseText.substring(0, 200)}`);
        }

        hideLoading();

        if (data.success && data.drop_id) {
          if (typeof hideModal === "function") {
            hideModal("addModal");
          }

          await customSuccess(
            "Pesanan berhasil disimpan!\n\nMembuka halaman cetak..."
          );

          // FIXED: Open in new window instead of redirect
          const printUrl = buildApiUrl(
            `/actions/drop/cetak_struk.php?id=${data.drop_id}`
          );

          try {
            const printWindow = window.open(
              printUrl,
              `CetakStruk_${data.drop_id}`,
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

              // Reload halaman utama setelah delay
              setTimeout(() => {
                window.location.reload();
              }, 1000);
            }
          } catch (error) {
            console.error("❌ Error opening window:", error);
            await customError(`Error membuka halaman cetak: ${error.message}`);
          }
        } else {
          throw new Error(data.message || "Response tidak valid");
        }
      } catch (error) {
        hideLoading();
        await customError(
          `Terjadi kesalahan:\n\n${error.message}`,
          "Gagal Menyimpan"
        );
        saveAndPrintBtn.disabled = false;
        saveAndPrintBtn.innerHTML =
          '<i class="bi bi-printer"></i> Simpan & Cetak';
      }
    });
  }

  // SAVE ONLY
  const saveOnlyBtn = document.getElementById("saveOnlyBtn");
  if (saveOnlyBtn) {
    saveOnlyBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      if (!(await validateForm())) return;

      const form = document.getElementById("addForm");
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";
      showLoading("Menyimpan pesanan...");

      const formData = new FormData(form);
      const apiUrl = buildApiUrl("/actions/drop/drop_add.php");

      try {
        const response = await fetch(apiUrl, {
          method: "POST",
          body: formData,
        });

        const responseText = await response.text();
        let data;
        try {
          data = JSON.parse(responseText);
        } catch (parseError) {
          throw new Error(`Invalid JSON: ${responseText.substring(0, 200)}`);
        }

        hideLoading();

        if (data.success) {
          if (typeof hideModal === "function") {
            hideModal("addModal");
          }

          sessionStorage.setItem("drop_showSuccess", "true");
          sessionStorage.setItem(
            "drop_successMessage",
            "Pesanan berhasil ditambahkan"
          );
          sessionStorage.setItem("drop_orderCode", data.order_code || "");

          setTimeout(() => window.location.reload(), 300);
        } else {
          throw new Error(data.message || "Response tidak valid");
        }
      } catch (error) {
        hideLoading();
        await customError(
          `Terjadi kesalahan:\n\n${error.message}`,
          "Gagal Menyimpan"
        );
        saveOnlyBtn.disabled = false;
        saveOnlyBtn.innerHTML = '<i class="bi bi-save"></i> Simpan';
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

    sessionStorage.removeItem("drop_showSuccess");
    sessionStorage.removeItem("drop_successMessage");
    sessionStorage.removeItem("drop_orderCode");
  }

  console.log("✅ Modal event listeners initialized");
  console.log("✅ FIXED: Save & Print now opens in NEW WINDOW");
});
