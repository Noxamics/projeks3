// =====================================================================
// File: /js/drop/drop_modal_edit.js
// Edit Modal Handler - FIXED: Using ICONS from modal_system
// Version: 3.3 - Icons Updated
// =====================================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Drop edit modal module loaded");

  // ==================== PAGE DETECTION ====================

  const currentPage = window.location.pathname;
  const isTimelinePage = currentPage.includes("timeline_pesanan.php");
  const isDropPage = currentPage.includes("drop.php");

  const API_BASE_PATH = "../actions/drop/";

  console.log(
    `📍 Current page: ${
      isTimelinePage ? "Timeline" : isDropPage ? "Drop" : "Unknown"
    }`
  );

  let editItemCounter = 1;

  // ==================== TEMPLATE FUNCTIONS ====================

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

    return `
      <div class="item-group" data-item-index="${itemIndex}">
        <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 10px;">
          <span class="item-badge">Item #${itemIndex}</span>
          <button type="button" class="remove-item-btn" data-item="${itemIndex}">
            ${ICONS.x} Hapus
          </button>
        </div>

        <input type="hidden" name="items[${itemIndex}][item_id]" value="${itemData.item_id || ""}">

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 40px;">
          <div>
            <label>${ICONS.infoCircle} Brand / Merk</label>
            <input type="text" name="items[${itemIndex}][brand]" class="item-brand" required
              placeholder="Contoh: Nike, Adidas" value="${
                itemData.brand || ""
              }">
          </div>
          <div>
            <label>Layanan</label>
            <select name="items[${itemIndex}][service_id]" class="item-service" required>
              ${servicesOptions}
            </select>
          </div>
          <div>
            <label>Harga</label>
            <input type="text" class="item-price-display" readonly value="Rp ${
              itemData.price
                ? Number(itemData.price).toLocaleString("id-ID")
                : "0"
            }" style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
            <input type="hidden" name="items[${itemIndex}][price]" class="item-price" value="${itemData.price || "0"}">
          </div>
          <div>
            <label>Estimasi Selesai</label>
            <input type="text" class="item-estimate" readonly value="${
              itemData.duration ? itemData.duration + " Hari" : "-"
            }" style="background: #f1f5f9; font-weight: 600;">
            <input type="hidden" name="items[${itemIndex}][duration]" class="item-duration" value="${itemData.duration || "0"}">
          </div>
          <div>
            <label>Tgl. Transaksi</label>
            <input type="date" name="items[${itemIndex}][trans_date]" class="item-trans-date"
              value="${
                itemData.trans_date || new Date().toISOString().split("T")[0]
              }">
          </div>
          <div>
            <label>Tanggal Estimasi Selesai</label>
            <input type="date" name="items[${itemIndex}][est_finish_date]" class="item-est-date" readonly
              style="background: #f1f5f9;" value="${
                itemData.est_finish_date || ""
              }">
          </div>
          <div style="grid-column: 1 / -1;">
            <label>Status</label>
            <select name="items[${itemIndex}][status_id]" class="item-status" required>
              ${statusOptions}
            </select>
          </div>
          <div style="grid-column: 1 / -1;">
            <label>Catatan Item</label>
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
        if (serviceSelect && serviceSelect.value)
          updateEditItemServiceData(itemElement);
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
            ICONS.trash
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
            ICONS.warning
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

    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

    if (selectedOption && selectedOption.value) {
      const serviceId = selectedOption.value;
      const transDate = transDateInput.value;
      const dropId = document.getElementById("edit_drop_id").value;

      estimateDisplay.value = "⏳ Menghitung...";
      durationInput.value = "0";
      estDateInput.value = "";

      const result = await fetchDynamicEstimate(serviceId, transDate, dropId);

      if (result && result.success) {
        priceDisplay.value = formatRupiah(result.price_min);
        priceInput.value = result.price_min;
        durationInput.value = result.duration;
        estimateDisplay.value = result.estimate_desc;
        estDateInput.value = result.estimate_date;
      } else {
        const priceMin = parseFloat(
          selectedOption.getAttribute("data-price") || 0
        );
        const duration = parseInt(
          selectedOption.getAttribute("data-duration") || 0
        );
        priceDisplay.value = formatRupiah(priceMin);
        priceInput.value = priceMin;
        durationInput.value = duration;
        estimateDisplay.value = duration > 0 ? `${duration} Hari` : "-";

        const date = new Date(transDate);
        date.setDate(date.getDate() + duration);
        estDateInput.value = date.toISOString().split("T")[0];
      }
    } else {
      priceDisplay.value = "Rp 0";
      priceInput.value = "0";
      durationInput.value = "0";
      estimateDisplay.value = "-";
      estDateInput.value = "";
    }

    calculateEditOrderSummary();
  }

  function calculateEditOrderSummary() {
    const items = document.querySelectorAll("#editItemsContainer .item-group");
    let totalPrice = 0;
    let maxDuration = 0;
    let maxEstDate = "";

    items.forEach((item) => {
      const price = parseFloat(item.querySelector(".item-price").value || 0);
      const duration = parseInt(
        item.querySelector(".item-duration").value || 0
      );
      const estDate = item.querySelector(".item-est-date").value;

      totalPrice += price;
      if (duration > maxDuration) maxDuration = duration;
      if (estDate && (!maxEstDate || estDate > maxEstDate))
        maxEstDate = estDate;
    });

    document.getElementById("editTotalItems").value = items.length + " Item";
    document.getElementById("editTotalPriceDisplay").value =
      formatRupiah(totalPrice);
    document.getElementById("editTotalPrice").value = totalPrice;
    document.getElementById("editMaxEstimate").value =
      maxDuration > 0 ? `${maxDuration} Hari` : "-";
    document.getElementById("editMaxDuration").value = maxDuration;
    document.getElementById("editFinalEstDate").value = maxEstDate;
  }

  function reindexEditItems() {
    const container = document.getElementById("editItemsContainer");
    const items = container.querySelectorAll(".item-group");

    items.forEach((item, idx) => {
      const newIndex = idx + 1;
      item.setAttribute("data-item-index", newIndex);
      item.querySelector(".item-badge").textContent = `Item #${newIndex}`;

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
      if (!contentType || !contentType.includes("application/json")) {
        throw new Error("Server mengembalikan bukan JSON. Cek path file PHP!");
      }

      if (!response.ok)
        throw new Error(`HTTP error! status: ${response.status}`);

      const data = await response.json();

      hideLoading();

      if (data.success) {
        // Set customer info
        document.getElementById("edit_customer_name").value =
          data.customer.name;
        document.getElementById("edit_customer_phone").value =
          data.customer.phone;
        document.getElementById("edit_customer_id").value = data.customer.id;
        document.getElementById("edit_drop_id").value = dropId;

        // Set employee
        const empSelect = document.getElementById("edit_employee_id");
        if (empSelect && empSelect.tagName === "SELECT") {
          empSelect.value = data.employee_id;
        }

        // Set note
        document.getElementById("edit_note").value = data.note || "";

        // Set payment info
        document.getElementById("edit_payment_status").value =
          data.payment.status;
        document.getElementById("edit_payment_method").value =
          data.payment.method;
        document.getElementById("edit_amount_paid").value =
          data.payment.amount_paid;
        document.getElementById("edit_amount_paid_display").value =
          "Rp " + Number(data.payment.amount_paid).toLocaleString("id-ID");

        if (data.payment.date) {
          document.getElementById("edit_payment_date_display").value =
            data.payment.date;
          document.getElementById("edit_payment_date_hidden").value =
            data.payment.date;
          document.getElementById("edit_payment_date_display").disabled = false;
          document.getElementById(
            "edit_payment_date_hidden"
          ).dataset.originalDate = data.payment.date;
        } else {
          document.getElementById("edit_payment_date_display").value = "";
          document.getElementById("edit_payment_date_hidden").value = "";
          document.getElementById("edit_payment_date_display").disabled = true;
          document.getElementById(
            "edit_payment_date_hidden"
          ).dataset.originalDate = "";
        }

        // Load items
        const container = document.getElementById("editItemsContainer");
        container.innerHTML = "";

        data.items.forEach((item, idx) => {
          const itemHTML = createEditItemHTML(idx + 1, item);
          container.insertAdjacentHTML("beforeend", itemHTML);

          const itemElement = container.querySelector(
            `[data-item-index="${idx + 1}"]`
          );

          const serviceSelect = itemElement.querySelector(".item-service");
          if (serviceSelect) serviceSelect.value = item.service_id;

          const statusSelect = itemElement.querySelector(".item-status");
          if (statusSelect) statusSelect.value = item.status_id;

          attachEditItemListeners(itemElement);
        });

        editItemCounter = data.items.length;
        calculateEditOrderSummary();
        updateEditRemoveButtons();
        showModal("editModal");
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

  // ==================== EVENT LISTENERS ====================

  // Double-click to edit (drop.php only)
  if (!isTimelinePage && isDropPage) {
    document.querySelectorAll(".order-item-row").forEach((row) => {
      row.addEventListener("dblclick", function (e) {
        if (e.target.type === "checkbox" || e.target.tagName === "SELECT")
          return;
        const dropId = this.getAttribute("data-drop-id");
        loadEditModal(dropId);
      });
    });
  }

  // Click note cell to edit
  if (!isTimelinePage && isDropPage) {
    document.querySelectorAll(".note-cell").forEach((noteCell) => {
      noteCell.addEventListener("click", function (e) {
        e.stopPropagation();
        const dropId = this.getAttribute("data-drop-id");
        loadEditModal(dropId);
        setTimeout(() => {
          const notesField = document.getElementById("edit_note");
          if (notesField) notesField.focus();
        }, 300);
      });
    });
  }

  // Add item button
  const editAddItemBtn = document.getElementById("editAddItemBtn");
  if (editAddItemBtn) {
    editAddItemBtn.addEventListener("click", function () {
      editItemCounter++;
      const container = document.getElementById("editItemsContainer");
      const itemHTML = createEditItemHTML(editItemCounter);
      container.insertAdjacentHTML("beforeend", itemHTML);

      const newItem = container.querySelector(
        `[data-item-index="${editItemCounter}"]`
      );
      attachEditItemListeners(newItem);

      updateEditRemoveButtons();
      calculateEditOrderSummary();
    });
  }

  // Rupiah input setup
  setupRupiahInput("edit_amount_paid_display", "edit_amount_paid");

  // Payment status change
  const editPayStatus = document.getElementById("edit_payment_status");
  if (editPayStatus) {
    editPayStatus.addEventListener("change", function () {
      handlePaymentStatusChange(
        this.value,
        "edit_payment_date_display",
        "edit_payment_date_hidden",
        document.getElementById("edit_payment_date_hidden").dataset
          .originalDate || null
      );
    });
  }

  // ==================== FORM SUBMISSION ====================

  // Save only
  const editSaveOnlyBtn = document.getElementById("editSaveOnlyBtn");
  if (editSaveOnlyBtn) {
    editSaveOnlyBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      const form = document.getElementById("editForm");
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";
      showLoading("Menyimpan perubahan...");

      const formData = new FormData(form);

      try {
        const response = await fetch(`${API_BASE_PATH}drop_edit.php`, {
          method: "POST",
          body: formData,
        });

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
          const text = await response.text();
          throw new Error("Server mengembalikan HTML/Error bukan JSON.");
        }

        const data = await response.json();

        hideLoading();

        if (data.success) {
          hideModal("editModal");

          if (isTimelinePage) {
            sessionStorage.setItem("timeline_showSuccess", "true");
            sessionStorage.setItem(
              "timeline_successMessage",
              data.message || "✅ Pesanan berhasil diperbarui!"
            );
          } else if (isDropPage) {
            sessionStorage.setItem("drop_showSuccess", "true");
            sessionStorage.setItem(
              "drop_successMessage",
              data.message || "✅ Pesanan berhasil diperbarui!"
            );
          }

          setTimeout(() => window.location.reload(), 500);
        } else {
          await customError(
            data.message || "Gagal menyimpan perubahan",
            "Gagal Menyimpan"
          );
          this.disabled = false;
          this.innerHTML = "Simpan";
        }
      } catch (error) {
        hideLoading();
        await customError(`Terjadi kesalahan:\n\n${error.message}`, "Error");
        this.disabled = false;
        this.innerHTML = "Simpan";
      }
    });
  }

  // Save & print
  const editSaveAndPrintBtn = document.getElementById("editSaveAndPrintBtn");
  if (editSaveAndPrintBtn) {
    editSaveAndPrintBtn.addEventListener("click", async function (e) {
      e.preventDefault();

      const form = document.getElementById("editForm");
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";
      showLoading("Menyimpan perubahan...");

      const formData = new FormData(form);
      const dropId = document.getElementById("edit_drop_id").value;

      try {
        const response = await fetch(`${API_BASE_PATH}drop_edit.php`, {
          method: "POST",
          body: formData,
        });

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
          throw new Error("Server mengembalikan HTML/Error bukan JSON.");
        }

        if (!response.ok)
          throw new Error(`HTTP error! status: ${response.status}`);

        const data = await response.json();

        hideLoading();

        if (data.success) {
          hideModal("editModal");

          await customSuccess(
            "Pesanan berhasil diperbarui!\n\nMembuka halaman cetak..."
          );

          const protocol = window.location.protocol;
          const host = window.location.host;
          const cetak_url = `${protocol}//${host}/actions/drop/cetak_struk.php?id=${dropId}`;

          try {
            const printWindow = window.open(
              cetak_url,
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

              setTimeout(() => {
                window.location.reload();
              }, 1000);
            }
          } catch (error) {
            console.error("❌ Error opening window:", error);
            await customError(`Error membuka halaman cetak: ${error.message}`);
          }
        } else {
          await customError(
            data.message || "Gagal menyimpan perubahan",
            "Gagal Menyimpan"
          );
          this.disabled = false;
          this.innerHTML = `${ICONS.printer} Simpan & Cetak`;
        }
      } catch (error) {
        hideLoading();
        console.error("❌ Error:", error);
        await customError(`Terjadi kesalahan:\n\n${error.message}`, "Error");
        this.disabled = false;
        this.innerHTML = `${ICONS.printer} Simpan & Cetak`;
      }
    });
  }

  // Close modal
  window.closeEditModal = function () {
    hideModal("editModal");
  };

  console.log("✅ Edit modal event listeners initialized");
  console.log("✅ FIXED: All icons using ICONS from modal_system");
  if (!isTimelinePage && isDropPage) {
    console.log("🖱️ Double-click row to edit full order");
    console.log("📝 Click note cell to edit order");
  }
});