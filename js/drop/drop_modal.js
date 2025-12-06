// File: /js/drop/drop_modal.js - FIXED VERSION with correct print path
document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Drop modal module loaded");

  let itemCounter = 1;

  // ===== DATE HELPER FUNCTIONS =====

  /**
   * Format date object to DD/MM/YYYY string
   */
  function formatDateToDDMMYYYY(date) {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, "0");
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
  }

  /**
   * Add days to a date (expects YYYY-MM-DD) and return DD/MM/YYYY
   */
  function addDaysToDate(dateStr, days) {
    const d = new Date(dateStr);
    d.setDate(d.getDate() + days);
    return formatDateToDDMMYYYY(d);
  }

  /**
   * Compare two DD/MM/YYYY dates
   */
  function compareDDMMYYYY(date1, date2) {
    const d1 = date1.split("/");
    const d2 = date2.split("/");
    const dateObj1 = new Date(d1[2], d1[1] - 1, d1[0]);
    const dateObj2 = new Date(d2[2], d2[1] - 1, d2[0]);
    return dateObj1 - dateObj2;
  }

  // ===== VALIDATION =====
  function validateForm() {
    const items = document.querySelectorAll(".item-group");

    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      const itemNum = i + 1;

      const serviceSelect = item.querySelector(".item-service");
      if (!serviceSelect.value || serviceSelect.value === "") {
        alert(`❌ Item #${itemNum}: Pilih layanan terlebih dahulu!`);
        serviceSelect.focus();
        return false;
      }

      const brandInput = item.querySelector(".item-brand");
      if (!brandInput.value || brandInput.value.trim() === "") {
        alert(`❌ Item #${itemNum}: Brand/Merk harus diisi!`);
        brandInput.focus();
        return false;
      }

      const transDate = item.querySelector(".item-trans-date");
      if (!transDate.value || transDate.value === "") {
        alert(`❌ Item #${itemNum}: Tanggal transaksi harus diisi!`);
        transDate.focus();
        return false;
      }

      const statusSelect = item.querySelector(".item-status");
      if (!statusSelect.value || statusSelect.value === "") {
        alert(`❌ Item #${itemNum}: Pilih status!`);
        statusSelect.focus();
        return false;
      }
    }

    return true;
  }

  // ===== UPDATE REMOVE BUTTONS =====
  function updateRemoveButtons() {
    const items = document.querySelectorAll(".item-group");
    items.forEach((item) => {
      const removeBtn = item.querySelector(".remove-item-btn");
      if (removeBtn) {
        removeBtn.style.display = items.length > 1 ? "inline-block" : "none";
      }
    });
  }

  // ===== CREATE NEW ITEM =====
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

  // ===== UPDATE ITEM SERVICE DATA =====
  function updateItemServiceData(itemElement) {
    const serviceSelect = itemElement.querySelector(".item-service");
    const priceDisplay = itemElement.querySelector(".item-price-display");
    const priceHidden = itemElement.querySelector(".item-price");
    const estimateDisplay = itemElement.querySelector(".item-estimate");
    const durationHidden = itemElement.querySelector(".item-duration");
    const transDateInput = itemElement.querySelector(".item-trans-date");
    const estDateDisplay = itemElement.querySelector(".item-est-date-display");
    const estDateHidden = itemElement.querySelector(".item-est-date-hidden");

    if (
      !serviceSelect ||
      !priceDisplay ||
      !priceHidden ||
      !estimateDisplay ||
      !durationHidden
    ) {
      console.error("❌ Missing required elements");
      return;
    }

    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

    if (selectedOption && selectedOption.value) {
      const price = parseFloat(selectedOption.dataset.price || 0);
      const duration = parseInt(selectedOption.dataset.duration || 0);

      // Update price
      priceDisplay.value = `Rp ${price.toLocaleString("id-ID")}`;
      priceHidden.value = price;

      // Update duration
      estimateDisplay.value = `${duration} Hari`;
      durationHidden.value = duration;

      // Calculate estimated finish date
      if (transDateInput && transDateInput.value) {
        const estDateDDMMYYYY = addDaysToDate(transDateInput.value, duration);

        // Update display (DD/MM/YYYY)
        if (estDateDisplay) {
          estDateDisplay.value = estDateDDMMYYYY;
        }

        // Update hidden (DD/MM/YYYY - PHP akan convert)
        if (estDateHidden) {
          estDateHidden.value = estDateDDMMYYYY;
        }

        console.log(
          `📅 Est Date: ${estDateDDMMYYYY} (Trans: ${transDateInput.value} + ${duration} days)`
        );
      }

      calculateOrderSummary();
    } else {
      // Reset
      priceDisplay.value = "Rp 0";
      priceHidden.value = "0";
      estimateDisplay.value = "-";
      durationHidden.value = "0";
      if (estDateDisplay) estDateDisplay.value = "-";
      if (estDateHidden) estDateHidden.value = "";
      calculateOrderSummary();
    }
  }

  // ===== CALCULATE ORDER SUMMARY =====
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

    // Update UI
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

    console.log(
      `💰 Total: Rp ${totalAmount.toLocaleString("id-ID")}, Items: ${
        items.length
      }, Max: ${maxDurationText}, Est: ${latestEstDate}`
    );
  }

  // ===== ADD ITEM BUTTON =====
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
        .addEventListener("click", function () {
          if (document.querySelectorAll(".item-group").length > 1) {
            newItem.remove();
            calculateOrderSummary();
            updateRemoveButtons();
          } else {
            alert("⚠️ Minimal harus ada 1 item!");
          }
        });

      updateRemoveButtons();
      calculateOrderSummary();
    });
  }

  // ===== ATTACH TO FIRST ITEM =====
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
      removeBtn.addEventListener("click", function () {
        if (document.querySelectorAll(".item-group").length > 1) {
          firstItem.remove();
          calculateOrderSummary();
          updateRemoveButtons();
        } else {
          alert("⚠️ Minimal harus ada 1 item!");
        }
      });
    }
  }

  // ===== OPEN ADD MODAL =====
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

  // ===== CLOSE BUTTONS =====
  document.querySelectorAll(".close").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const target = btn.getAttribute("data-target");
      if (target && typeof hideModal === "function") {
        hideModal(target);
      }
    });
  });

  window.addEventListener("click", function (event) {
    if (event.target.classList.contains("modal")) {
      const modalId = event.target.id;
      if (modalId && typeof hideModal === "function") {
        hideModal(modalId);
      } else {
        event.target.style.display = "none";
        event.target.classList.remove("show");
        document.body.style.overflow = "";
      }
    }
  });

  // ===== RUPIAH INPUT =====
  if (typeof setupRupiahInput === "function") {
    setupRupiahInput("amount_paid_display", "amount_paid");
  }

  // ===== PAYMENT STATUS =====
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

  // ===== SAVE & PRINT =====
  const saveAndPrintBtn = document.getElementById("saveAndPrintBtn");
  if (saveAndPrintBtn) {
    saveAndPrintBtn.addEventListener("click", function (e) {
      e.preventDefault();
      console.log("=== SAVE & PRINT CLICKED ===");

      const form = document.getElementById("addForm");

      if (!validateForm()) {
        console.error("❌ Validation failed");
        return;
      }

      if (!form.checkValidity()) {
        console.error("❌ Form validation failed");
        form.reportValidity();
        return;
      }

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";

      const formData = new FormData(form);

      console.log("=== 📤 FORM DATA ===");
      for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
      }

      // FIXED PATH
      fetch("../actions/drop/drop_add.php", {
        method: "POST",
        body: formData,
      })
        .then(async (response) => {
          const responseText = await response.text();
          console.log("📥 Response:", responseText);

          let data;
          try {
            data = JSON.parse(responseText);
          } catch (parseError) {
            console.error("Parse Error:", parseError);
            throw new Error(
              `Invalid JSON response: ${responseText.substring(0, 200)}`
            );
          }

          return data;
        })
        .then((data) => {
          console.log("📊 Parsed Data:", data);

          if (data.success && data.drop_id) {
            console.log("✅ Success! Drop ID:", data.drop_id);

            // Close modal immediately
            if (typeof hideModal === "function") {
              hideModal("addModal");
            } else {
              const modal = document.getElementById("addModal");
              if (modal) {
                modal.style.display = "none";
                modal.classList.remove("show");
              }
            }

            // 🔧 FIXED: Open print window with correct path
            const baseUrl = window.location.origin + "/PROJEKS3";
            window.open(
              `${baseUrl}/actions/drop/cetak_struk.php?id=${data.drop_id}`,
              "CetakStruk",
              "width=800,height=900,scrollbars=yes"
            );

            // Show success and reload
            sessionStorage.setItem("showSuccess", "true");
            sessionStorage.setItem(
              "successMessage",
              `✅ Pesanan ${data.order_code} berhasil ditambahkan!`
            );

            // Reload immediately
            setTimeout(() => {
              window.location.reload();
            }, 300);
          } else {
            throw new Error(data.message || "Response tidak valid");
          }
        })
        .catch((error) => {
          console.error("❌ ERROR:", error);
          alert("❌ Terjadi kesalahan:\n\n" + error.message);
          saveAndPrintBtn.disabled = false;
          saveAndPrintBtn.innerHTML = "🖨️ Simpan & Cetak";
        });
    });
  }

  // ===== SAVE ONLY =====
  const saveOnlyBtn = document.getElementById("saveOnlyBtn");
  if (saveOnlyBtn) {
    saveOnlyBtn.addEventListener("click", function (e) {
      e.preventDefault();

      const form = document.getElementById("addForm");

      if (!validateForm()) return;
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";

      const formData = new FormData(form);

      // FIXED PATH
      fetch("../actions/drop/drop_add.php", {
        method: "POST",
        body: formData,
      })
        .then(async (response) => {
          const responseText = await response.text();
          console.log("📥 Response:", responseText);

          let data;
          try {
            data = JSON.parse(responseText);
          } catch (parseError) {
            console.error("Parse Error:", parseError);
            throw new Error(
              `Invalid JSON response: ${responseText.substring(0, 200)}`
            );
          }

          return data;
        })
        .then((data) => {
          console.log("📊 Parsed Data:", data);

          if (data.success) {
            console.log("✅ Success! Reloading...");

            // Close modal immediately
            if (typeof hideModal === "function") {
              hideModal("addModal");
            } else {
              const modal = document.getElementById("addModal");
              if (modal) {
                modal.style.display = "none";
                modal.classList.remove("show");
              }
            }

            // Show success and reload
            sessionStorage.setItem("showSuccess", "true");
            sessionStorage.setItem(
              "successMessage",
              `✅ Pesanan ${data.order_code || ""} berhasil ditambahkan!`
            );

            // Reload immediately
            setTimeout(() => {
              window.location.reload();
            }, 300);
          } else {
            throw new Error(data.message || "Response tidak valid");
          }
        })
        .catch((error) => {
          console.error("❌ ERROR:", error);
          alert("❌ Terjadi kesalahan:\n\n" + error.message);
          saveOnlyBtn.disabled = false;
          saveOnlyBtn.innerHTML = "💾 Simpan";
        });
    });
  }

  // ===== SUCCESS MODAL =====
  if (sessionStorage.getItem("showSuccess") === "true") {
    const successMessage = sessionStorage.getItem("successMessage");
    const successMessageEl = document.getElementById("successMessage");
    const successModalEl = document.getElementById("successModal");

    if (successMessageEl && successModalEl) {
      successMessageEl.textContent = successMessage;
      if (typeof showModal === "function") {
        showModal("successModal");
      }
    } else {
      alert(successMessage);
    }

    sessionStorage.removeItem("showSuccess");
    sessionStorage.removeItem("successMessage");
  }

  const closeSuccessBtn = document.getElementById("closeSuccess");
  if (closeSuccessBtn && typeof hideModal === "function") {
    closeSuccessBtn.addEventListener("click", function () {
      hideModal("successModal");
    });
  }

  console.log("✅ Modal event listeners initialized");
});
