// File: /js/drop/drop_modal_edit.js
// FIXED VERSION - Separate session storage for drop and timeline pages

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Drop edit modal module loaded");

  // ===== DETECT CURRENT PAGE =====
  const currentPage = window.location.pathname;
  const isTimelinePage = currentPage.includes("timeline_pesanan.php");
  const isDropPage = currentPage.includes("drop.php");

  // ===== CONFIGURE API PATH BASED ON PAGE =====
  const API_BASE_PATH = isTimelinePage
    ? "../actions/drop/" // Timeline page
    : "../actions/drop/"; // Drop page

  console.log(
    `📍 Current page: ${
      isTimelinePage ? "Timeline" : isDropPage ? "Drop" : "Unknown"
    }`
  );
  console.log(`🔗 API Base Path: ${API_BASE_PATH}`);

  let editItemCounter = 1;

  // ===== GET SERVICES AND STATUSES OPTIONS HTML =====
  function getServicesOptionsHTML() {
    // Try timeline template first
    const timelineTemplate = document.querySelector(
      "#timeline_services_template"
    );
    if (timelineTemplate) {
      console.log("✅ Using timeline services template");
      return timelineTemplate.innerHTML;
    }

    // Fallback to modal add template (for drop.php)
    const firstItem = document.querySelector("#itemsContainer .item-group");
    if (firstItem) {
      const serviceSelect = firstItem.querySelector(".item-service");
      if (serviceSelect) {
        console.log("✅ Using modal add services template");
        return serviceSelect.innerHTML;
      }
    }

    console.error("❌ No services template found!");
    return '<option value="">-- Pilih Layanan --</option>';
  }

  function getStatusesOptionsHTML() {
    // Try timeline template first
    const timelineTemplate = document.querySelector(
      "#timeline_statuses_template"
    );
    if (timelineTemplate) {
      console.log("✅ Using timeline statuses template");
      return timelineTemplate.innerHTML;
    }

    // Fallback to modal add template (for drop.php)
    const firstItem = document.querySelector("#itemsContainer .item-group");
    if (firstItem) {
      const statusSelect = firstItem.querySelector(".item-status");
      if (statusSelect) {
        console.log("✅ Using modal add statuses template");
        return statusSelect.innerHTML;
      }
    }

    console.error("❌ No statuses template found!");
    return '<option value="">Pilih Status</option>';
  }

  // ===== UPDATE REMOVE BUTTONS VISIBILITY (EDIT) =====
  function updateEditRemoveButtons() {
    const items = document.querySelectorAll("#editItemsContainer .item-group");
    items.forEach((item, index) => {
      const removeBtn = item.querySelector(".remove-item-btn");
      if (removeBtn) {
        removeBtn.style.display = items.length > 1 ? "inline-block" : "none";
      }
    });
  }

  // ===== CREATE NEW EDIT ITEM HTML =====
  function createEditItemHTML(itemIndex, itemData = {}) {
    const servicesOptions = getServicesOptionsHTML();
    const statusOptions = getStatusesOptionsHTML();

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
                        <input type="text" class="item-price-display" readonly value="Rp ${
                          itemData.price
                            ? Number(itemData.price).toLocaleString("id-ID")
                            : "0"
                        }"
                            style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                        <input type="hidden" name="items[${itemIndex}][price]" class="item-price" value="${itemData.price || "0"}">
                    </div>

                    <div>
                        <label><i class="bi bi-clock-history"></i> Estimasi Selesai</label>
                        <input type="text" class="item-estimate" readonly value="${
                          itemData.duration ? itemData.duration + " Hari" : "-"
                        }"
                            style="background: #f1f5f9; font-weight: 600;">
                        <input type="hidden" name="items[${itemIndex}][duration]" class="item-duration" value="${itemData.duration || "0"}">
                    </div>

                    <div>
                        <label><i class="bi bi-calendar-event"></i> Tgl. Transaksi</label>
                        <input type="date" name="items[${itemIndex}][trans_date]" class="item-trans-date"
                            value="${
                              itemData.trans_date ||
                              new Date().toISOString().split("T")[0]
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

  // ===== ATTACH EVENT LISTENERS TO EDIT ITEM =====
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
      removeBtn.addEventListener("click", function () {
        const items = document.querySelectorAll(
          "#editItemsContainer .item-group"
        );
        if (items.length > 1) {
          itemElement.remove();
          calculateEditOrderSummary();
          updateEditRemoveButtons();
          reindexEditItems();
          console.log(`🗑️ Edit item removed`);
        } else {
          alert("⚠️ Minimal harus ada 1 item dalam pesanan!");
        }
      });
    }
  }

  // ===== UPDATE ITEM SERVICE DATA (EDIT) =====
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

        if (result.queue_info) {
          console.log(`📋 ${result.queue_info}`);
        }
        if (result.has_overdue) {
          console.warn(
            `⚠️ Ada ${result.overdue_count} pesanan overdue - queue direset`
          );
        }
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

  // ===== CALCULATE EDIT ORDER SUMMARY =====
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

      if (duration > maxDuration) {
        maxDuration = duration;
      }

      if (estDate && (!maxEstDate || estDate > maxEstDate)) {
        maxEstDate = estDate;
      }
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

  // ===== REINDEX EDIT ITEMS =====
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

  // ===== LOAD EDIT MODAL DATA =====
  window.loadEditModal = function (dropId) {
    console.log(`📝 Loading edit modal for drop ID: ${dropId}`);

    const apiUrl = `${API_BASE_PATH}get_order_detail.php?drop_id=${dropId}`;
    console.log(`🔗 Fetching from: ${apiUrl}`);

    fetch(apiUrl)
      .then((response) => {
        console.log(`📡 Response status: ${response.status}`);

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
          throw new Error(
            `Server mengembalikan ${contentType} bukan JSON. Cek path file PHP!`
          );
        }

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
      })
      .then((data) => {
        if (data.success) {
          console.log("✅ Data loaded successfully:", data);

          // Set customer info (read-only)
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
            document.getElementById(
              "edit_payment_date_display"
            ).disabled = false;
            document.getElementById(
              "edit_payment_date_hidden"
            ).dataset.originalDate = data.payment.date;
          } else {
            document.getElementById("edit_payment_date_display").value = "";
            document.getElementById("edit_payment_date_hidden").value = "";
            document.getElementById(
              "edit_payment_date_display"
            ).disabled = true;
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

            // Set selected service
            const serviceSelect = itemElement.querySelector(".item-service");
            if (serviceSelect) {
              serviceSelect.value = item.service_id;
              console.log(
                `✅ Set service ${item.service_id} for item ${idx + 1}`
              );
            }

            // Set selected status
            const statusSelect = itemElement.querySelector(".item-status");
            if (statusSelect) {
              statusSelect.value = item.status_id;
              console.log(
                `✅ Set status ${item.status_id} for item ${idx + 1}`
              );
            }

            attachEditItemListeners(itemElement);
          });

          editItemCounter = data.items.length;

          calculateEditOrderSummary();
          updateEditRemoveButtons();

          showModal("editModal");

          console.log("✅ Edit modal opened successfully");
        } else {
          alert("❌ Error: " + data.message);
        }
      })
      .catch((error) => {
        console.error("❌ Error:", error);
        alert(
          `❌ Gagal memuat data pesanan: ${error.message}\n\nCek console browser untuk detail lebih lanjut.`
        );
      });
  };

  // ===== DOUBLE-CLICK TO EDIT (Only for drop.php page) =====
  if (!isTimelinePage && isDropPage) {
    document.querySelectorAll(".order-item-row").forEach((row) => {
      row.addEventListener("dblclick", function (e) {
        if (e.target.type === "checkbox" || e.target.tagName === "SELECT") {
          return;
        }

        const dropId = this.getAttribute("data-drop-id");
        console.log(
          `✏️ Double-click detected - Opening edit modal for Drop ${dropId}`
        );

        loadEditModal(dropId);
      });
    });
  }

  // ===== CLICK NOTE CELL TO EDIT (Only for drop.php page) =====
  if (!isTimelinePage && isDropPage) {
    document.querySelectorAll(".note-cell").forEach((noteCell) => {
      noteCell.addEventListener("click", function (e) {
        e.stopPropagation();

        const dropId = this.getAttribute("data-drop-id");
        console.log(
          `📝 Note cell clicked - Opening edit modal for Drop ${dropId}`
        );

        loadEditModal(dropId);

        setTimeout(() => {
          const notesField = document.getElementById("edit_note");
          if (notesField) notesField.focus();
        }, 300);
      });
    });
  }

  // ===== ADD ITEM BUTTON (EDIT) =====
  const editAddItemBtn = document.getElementById("editAddItemBtn");
  if (editAddItemBtn) {
    editAddItemBtn.addEventListener("click", function () {
      editItemCounter++;
      console.log(`➕ Adding edit item #${editItemCounter}`);

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

  // ===== RUPIAH INPUT SETUP (EDIT) =====
  setupRupiahInput("edit_amount_paid_display", "edit_amount_paid");

  // ===== PAYMENT STATUS CHANGE (EDIT) =====
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

  // ===== SAVE ONLY (EDIT) =====
  const editSaveOnlyBtn = document.getElementById("editSaveOnlyBtn");
  if (editSaveOnlyBtn) {
    editSaveOnlyBtn.addEventListener("click", function (e) {
      e.preventDefault();

      const form = document.getElementById("editForm");

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";

      const formData = new FormData(form);

      fetch(`${API_BASE_PATH}drop_edit.php`, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          const contentType = response.headers.get("content-type");
          if (!contentType || !contentType.includes("application/json")) {
            return response.text().then((text) => {
              console.error("❌ Server response (bukan JSON):", text);
              throw new Error(
                "Server mengembalikan HTML/Error bukan JSON. Cek PHP error!"
              );
            });
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            hideModal("editModal");

            // 🔧 FIXED: Gunakan key yang berbeda untuk setiap halaman
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
            alert(
              "❌ Gagal menyimpan perubahan: " +
                (data.message || "Unknown error")
            );
            this.disabled = false;
            this.innerHTML = "<i class='bi bi-save'></i> Simpan";
          }
        })
        .catch((error) => {
          alert("❌ Terjadi kesalahan: " + error.message);
          this.disabled = false;
          this.innerHTML = "<i class='bi bi-save'></i> Simpan";
        });
    });
  }

  // ===== SAVE & PRINT (EDIT) =====
  const editSaveAndPrintBtn = document.getElementById("editSaveAndPrintBtn");
  if (editSaveAndPrintBtn) {
    editSaveAndPrintBtn.addEventListener("click", function (e) {
      e.preventDefault();
      console.log("=== EDIT SAVE & PRINT CLICKED ===");

      const form = document.getElementById("editForm");

      if (!form.checkValidity()) {
        console.error("❌ Form validation failed");
        form.reportValidity();
        return;
      }

      console.log("✅ Form valid, submitting...");

      this.disabled = true;
      this.innerHTML = "⏳ Menyimpan...";

      const formData = new FormData(form);
      const dropId = document.getElementById("edit_drop_id").value;

      fetch(`${API_BASE_PATH}drop_edit.php`, {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          const contentType = response.headers.get("content-type");
          if (!contentType || !contentType.includes("application/json")) {
            return response.text().then((text) => {
              console.error("❌ Server response (bukan JSON):", text);
              throw new Error(
                "Server mengembalikan HTML/Error bukan JSON. Cek PHP error!"
              );
            });
          }

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            console.log("✅ Pesanan berhasil diperbarui!");

            hideModal("editModal");

            setTimeout(() => {
              // Get base URL
              const pathArray = window.location.pathname.split("/");
              const projectIndex = pathArray.indexOf("PROJEKS3");
              const basePath =
                projectIndex !== -1
                  ? pathArray.slice(0, projectIndex + 1).join("/")
                  : "/PROJEKS3";
              const baseUrl = window.location.origin + basePath;

              const cetak_url = `${baseUrl}/actions/drop/cetak_struk.php?id=${dropId}`;
              window.open(cetak_url, "CetakStruk", "width=600,height=800");

              // 🔧 FIXED: Gunakan key yang berbeda untuk setiap halaman
              if (isTimelinePage) {
                sessionStorage.setItem("timeline_showSuccess", "true");
                sessionStorage.setItem(
                  "timeline_successMessage",
                  data.message || "✅ Pesanan berhasil diperbarui dan dicetak!"
                );
              } else if (isDropPage) {
                sessionStorage.setItem("drop_showSuccess", "true");
                sessionStorage.setItem(
                  "drop_successMessage",
                  data.message || "✅ Pesanan berhasil diperbarui dan dicetak!"
                );
              }

              setTimeout(() => {
                window.location.reload();
              }, 1000);
            }, 500);
          } else {
            alert(
              "❌ Gagal menyimpan perubahan: " +
                (data.message || "Unknown error")
            );
            this.disabled = false;
            this.innerHTML = "<i class='bi bi-printer'></i> Simpan & Cetak";
          }
        })
        .catch((error) => {
          console.error("❌ Network error:", error);
          alert("❌ Terjadi kesalahan: " + error.message);
          this.disabled = false;
          this.innerHTML = "<i class='bi bi-printer'></i> Simpan & Cetak";
        });
    });
  }

  // ===== CLOSE EDIT MODAL =====
  function closeEditModal() {
    hideModal("editModal");
  }

  window.closeEditModal = closeEditModal;

  console.log("✅ Edit modal event listeners initialized");
  if (!isTimelinePage && isDropPage) {
    console.log("🖱️ Double-click row to edit full order");
    console.log("📝 Click note cell to edit order");
  }
});
