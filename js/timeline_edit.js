// File: timeline_edit.js
// Handler untuk edit modal di timeline_pesanan.php

// ========== GLOBAL VARIABLES ==========
let editItemCounter = 0;
let servicesData = [];

// ========== FORMAT RUPIAH ==========
function formatRupiah(angka) {
  if (!angka || angka == 0) return "Rp 0";
  return "Rp " + parseInt(angka).toLocaleString("id-ID");
}

function parseRupiah(rupiah) {
  return parseInt(rupiah.replace(/[^0-9]/g, "")) || 0;
}

// ========== LOAD SERVICES DATA ==========
async function loadServicesData() {
  try {
    const response = await fetch("../api/get_services.php");
    const data = await response.json();
    if (data.success) {
      servicesData = data.services;
    }
  } catch (error) {
    console.error("Error loading services:", error);
  }
}

// ========== OPEN EDIT MODAL ==========
async function openEditModal(orderElement) {
  console.log("Opening edit modal...");

  // Get drop_id
  const drop_id = orderElement.dataset.idDrop;

  if (!drop_id || drop_id === "null" || drop_id === "") {
    showNotification("❌ ID pesanan tidak ditemukan", "error");
    return;
  }

  // Load services data if not loaded
  if (servicesData.length === 0) {
    await loadServicesData();
  }

  // Fetch order details
  try {
    const response = await fetch(
      `../api/get_drop_details.php?drop_id=${drop_id}`
    );
    const data = await response.json();

    if (!data.success) {
      showNotification("❌ " + data.message, "error");
      return;
    }

    // Populate modal with data
    populateEditModal(data.drop);

    // Show modal
    document.getElementById("editModal").style.display = "block";
  } catch (error) {
    console.error("Error:", error);
    showNotification("❌ Gagal memuat data pesanan", "error");
  }
}

// ========== POPULATE EDIT MODAL ==========
function populateEditModal(drop) {
  // Basic info
  document.getElementById("edit_drop_id").value = drop.id_drop;
  document.getElementById("edit_customer_id").value = drop.customer_id;
  document.getElementById("edit_customer_name").value = drop.customer_name;
  document.getElementById("edit_customer_phone").value = drop.customer_phone;

  // Payment info
  document.getElementById("edit_payment_status").value =
    drop.payment_status || "Belum Lunas";
  document.getElementById("edit_payment_method").value =
    drop.payment_method || "Tunai";
  document.getElementById("edit_payment_date_hidden").value =
    drop.payment_date || "";
  document.getElementById("edit_payment_date_display").value =
    drop.payment_date || "";
  document.getElementById("edit_amount_paid").value = drop.amount_paid || "0";
  document.getElementById("edit_amount_paid_display").value = formatRupiah(
    drop.amount_paid || 0
  );

  // Employee
  const employeeSelect = document.getElementById("edit_employee_id");
  if (employeeSelect && drop.employee_id) {
    employeeSelect.value = drop.employee_id;
  }

  // Note
  document.getElementById("edit_note").value = drop.note || "";

  // Clear items container
  const itemsContainer = document.getElementById("editItemsContainer");
  itemsContainer.innerHTML = "";
  editItemCounter = 0;

  // Add items
  if (drop.items && drop.items.length > 0) {
    drop.items.forEach((item, index) => {
      addEditItem(item, index === 0);
    });
  } else {
    // Add one empty item
    addEditItem(null, true);
  }

  // Update summary
  updateEditSummary();

  // Setup payment status handler
  setupPaymentStatusHandler();

  // Setup amount paid formatter
  setupAmountPaidFormatter();
}

// ========== ADD EDIT ITEM ==========
function addEditItem(itemData = null, isFirst = false) {
  editItemCounter++;
  const itemId = `edit_item_${editItemCounter}`;

  const itemDiv = document.createElement("div");
  itemDiv.className = "item-card";
  itemDiv.id = itemId;
  itemDiv.style.cssText = `
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        padding: 20px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        margin-bottom: 20px;
        position: relative;
    `;

  // Item header
  const headerDiv = document.createElement("div");
  headerDiv.style.cssText = `
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    `;

  const titleSpan = document.createElement("span");
  titleSpan.style.cssText = `
        font-weight: 700;
        font-size: 16px;
        color: #0369a1;
    `;
  titleSpan.textContent = `📦 Barang #${editItemCounter}`;

  const deleteBtn = document.createElement("button");
  deleteBtn.type = "button";
  deleteBtn.className = "remove-item-btn";
  deleteBtn.innerHTML = "🗑️ Hapus";
  deleteBtn.style.cssText = `
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
    `;
  deleteBtn.onclick = () => removeEditItem(itemId);

  headerDiv.appendChild(titleSpan);
  if (!isFirst) {
    headerDiv.appendChild(deleteBtn);
  }

  // Item form grid
  const formGrid = document.createElement("div");
  formGrid.style.cssText = `
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    `;

  // Trans Date (hidden on first item, visible on others)
  if (!isFirst) {
    const transDateDiv = createFormField(
      "Tanggal Masuk",
      "date",
      `items[${editItemCounter}][trans_date]`,
      itemData?.trans_date || new Date().toISOString().split("T")[0],
      true
    );
    formGrid.appendChild(transDateDiv);
  } else {
    // Hidden input for first item trans_date
    const hiddenTransDate = document.createElement("input");
    hiddenTransDate.type = "hidden";
    hiddenTransDate.name = `items[${editItemCounter}][trans_date]`;
    hiddenTransDate.value =
      itemData?.trans_date || new Date().toISOString().split("T")[0];
    formGrid.appendChild(hiddenTransDate);
  }

  // Brand
  const brandDiv = createFormField(
    "Brand / Merk",
    "text",
    `items[${editItemCounter}][brand]`,
    itemData?.brand || "",
    true,
    "Nike, Adidas, dll"
  );
  formGrid.appendChild(brandDiv);

  // Service
  const serviceDiv = document.createElement("div");
  serviceDiv.innerHTML = `
        <label style="display: block; margin-bottom: 6px; font-weight: 600;">Layanan <span style="color: red;">*</span></label>
        <select name="items[${editItemCounter}][service_id]" 
                class="edit-item-service" 
                data-item-id="${editItemCounter}"
                required 
                style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
            <option value="">-- Pilih Layanan --</option>
        </select>
    `;

  const serviceSelect = serviceDiv.querySelector("select");
  servicesData.forEach((service) => {
    const option = document.createElement("option");
    option.value = service.id_service;
    option.textContent = `${service.category} - ${service.service_name}`;
    option.dataset.priceMin = service.price_min;
    option.dataset.priceMax = service.price_max;
    option.dataset.duration = service.duration;
    if (itemData && itemData.service_id == service.id_service) {
      option.selected = true;
    }
    serviceSelect.appendChild(option);
  });

  serviceSelect.addEventListener("change", function () {
    updateEditItemPrice(editItemCounter);
    updateEditSummary();
  });

  formGrid.appendChild(serviceDiv);

  // Price
  const priceDiv = document.createElement("div");
  priceDiv.innerHTML = `
        <label style="display: block; margin-bottom: 6px; font-weight: 600;">Harga <span style="color: red;">*</span></label>
        <input type="text" 
               class="edit-item-price-display" 
               data-item-id="${editItemCounter}"
               placeholder="Rp 0"
               required
               style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
        <input type="hidden" 
               name="items[${editItemCounter}][price]" 
               class="edit-item-price-hidden"
               data-item-id="${editItemCounter}"
               value="${itemData?.price || 0}">
    `;

  const priceDisplay = priceDiv.querySelector(".edit-item-price-display");
  const priceHidden = priceDiv.querySelector(".edit-item-price-hidden");

  priceDisplay.value = formatRupiah(itemData?.price || 0);

  priceDisplay.addEventListener("input", function (e) {
    let value = e.target.value.replace(/[^0-9]/g, "");
    priceHidden.value = value;
    e.target.value = formatRupiah(value);
    updateEditSummary();
  });

  formGrid.appendChild(priceDiv);

  // Duration (readonly)
  const durationDiv = createFormField(
    "Estimasi",
    "text",
    `items[${editItemCounter}][duration]`,
    itemData?.duration || "0",
    false,
    "",
    true
  );
  durationDiv.querySelector("input").style.background = "#f9f9f9";
  formGrid.appendChild(durationDiv);

  // Status
  const statusDiv = document.createElement("div");
  statusDiv.innerHTML = `
        <label style="display: block; margin-bottom: 6px; font-weight: 600;">Status <span style="color: red;">*</span></label>
        <select name="items[${editItemCounter}][status_id]" 
                class="edit-item-status"
                required 
                style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
            <option value="">-- Pilih Status --</option>
        </select>
    `;

  // Load statuses
  fetch("../api/get_statuses.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        const statusSelect = statusDiv.querySelector("select");
        data.statuses.forEach((status) => {
          const option = document.createElement("option");
          option.value = status.id_status;
          option.textContent = status.status_name;
          if (itemData && itemData.status_id == status.id_status) {
            option.selected = true;
          }
          statusSelect.appendChild(option);
        });
      }
    });

  formGrid.appendChild(statusDiv);

  // Notes (full width)
  const notesDiv = document.createElement("div");
  notesDiv.style.gridColumn = "1 / -1";
  notesDiv.innerHTML = `
        <label style="display: block; margin-bottom: 6px; font-weight: 600;">Catatan Item</label>
        <textarea name="items[${editItemCounter}][notes]"
                  placeholder="Catatan khusus untuk barang ini..."
                  rows="2"
                  style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;">${
                    itemData?.notes || ""
                  }</textarea>
    `;
  formGrid.appendChild(notesDiv);

  itemDiv.appendChild(headerDiv);
  itemDiv.appendChild(formGrid);

  document.getElementById("editItemsContainer").appendChild(itemDiv);

  // If itemData exists, update price display
  if (itemData) {
    updateEditItemPrice(editItemCounter);
  }
}

// ========== CREATE FORM FIELD ==========
function createFormField(
  label,
  type,
  name,
  value,
  required,
  placeholder = "",
  readonly = false
) {
  const div = document.createElement("div");
  div.innerHTML = `
        <label style="display: block; margin-bottom: 6px; font-weight: 600;">
            ${label} ${required ? '<span style="color: red;">*</span>' : ""}
        </label>
        <input type="${type}" 
               name="${name}" 
               value="${value}"
               ${required ? "required" : ""}
               ${readonly ? "readonly" : ""}
               placeholder="${placeholder}"
               style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
    `;
  return div;
}

// ========== UPDATE EDIT ITEM PRICE ==========
function updateEditItemPrice(itemId) {
  const serviceSelect = document.querySelector(
    `.edit-item-service[data-item-id="${itemId}"]`
  );
  const priceDisplay = document.querySelector(
    `.edit-item-price-display[data-item-id="${itemId}"]`
  );
  const priceHidden = document.querySelector(
    `.edit-item-price-hidden[data-item-id="${itemId}"]`
  );
  const durationInput = document.querySelector(
    `input[name="items[${itemId}][duration]"]`
  );

  if (serviceSelect && priceDisplay && priceHidden && durationInput) {
    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

    if (selectedOption && selectedOption.value) {
      const priceMin = selectedOption.dataset.priceMin || 0;
      const duration = selectedOption.dataset.duration || 0;

      priceHidden.value = priceMin;
      priceDisplay.value = formatRupiah(priceMin);
      durationInput.value = duration;
    }
  }
}

// ========== REMOVE EDIT ITEM ==========
function removeEditItem(itemId) {
  const itemDiv = document.getElementById(itemId);
  if (itemDiv) {
    itemDiv.remove();
    updateEditSummary();
  }
}

// ========== UPDATE EDIT SUMMARY ==========
function updateEditSummary() {
  const items = document.querySelectorAll(".item-card");
  let totalItems = items.length;
  let totalPrice = 0;
  let maxDuration = 0;

  items.forEach((item) => {
    const priceHidden = item.querySelector(".edit-item-price-hidden");
    const durationInput = item.querySelector('input[name*="[duration]"]');

    if (priceHidden) {
      totalPrice += parseFloat(priceHidden.value || 0);
    }

    if (durationInput) {
      const duration = parseInt(durationInput.value || 0);
      if (duration > maxDuration) {
        maxDuration = duration;
      }
    }
  });

  // Update displays
  document.getElementById("editTotalItems").value = totalItems;
  document.getElementById("editTotalPrice").value = totalPrice;
  document.getElementById("editTotalPriceDisplay").value =
    formatRupiah(totalPrice);
  document.getElementById("editMaxDuration").value = maxDuration;
  document.getElementById("editMaxEstimate").value =
    maxDuration > 0 ? `${maxDuration} hari` : "-";

  // Calculate est_finish_date
  const firstItem = items[0];
  if (firstItem && maxDuration > 0) {
    const transDateInput = firstItem.querySelector(
      'input[name*="[trans_date]"]'
    );
    if (transDateInput) {
      const transDate = new Date(transDateInput.value);
      transDate.setDate(transDate.getDate() + maxDuration);
      document.getElementById("editFinalEstDate").value = transDate
        .toISOString()
        .split("T")[0];
    }
  }
}

// ========== SETUP PAYMENT STATUS HANDLER ==========
function setupPaymentStatusHandler() {
  const paymentStatus = document.getElementById("edit_payment_status");
  const paymentDateHidden = document.getElementById("edit_payment_date_hidden");
  const paymentDateDisplay = document.getElementById(
    "edit_payment_date_display"
  );

  let originalStatus = paymentStatus.value;
  let originalDate = paymentDateHidden.value;

  paymentStatus.addEventListener("change", function () {
    if (
      this.value === "Lunas" &&
      originalStatus === "Belum Lunas" &&
      !originalDate
    ) {
      const today = new Date().toISOString().split("T")[0];
      paymentDateHidden.value = today;
      paymentDateDisplay.value = today;
      showNotification("📅 Tanggal pembayaran diisi otomatis", "success");
    } else if (
      this.value === "Belum Lunas" &&
      paymentDateHidden.value !== originalDate
    ) {
      paymentDateHidden.value = "";
      paymentDateDisplay.value = "";
      showNotification("🗑️ Tanggal pembayaran dikosongkan", "info");
    }
  });
}

// ========== SETUP AMOUNT PAID FORMATTER ==========
function setupAmountPaidFormatter() {
  const amountDisplay = document.getElementById("edit_amount_paid_display");
  const amountHidden = document.getElementById("edit_amount_paid");

  amountDisplay.addEventListener("input", function (e) {
    let value = e.target.value.replace(/[^0-9]/g, "");
    amountHidden.value = value;
    e.target.value = formatRupiah(value);
  });
}

// ========== CLOSE EDIT MODAL ==========
function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
  document.getElementById("editItemsContainer").innerHTML = "";
  editItemCounter = 0;
}

// ========== ADD ITEM BUTTON HANDLER ==========
document.addEventListener("DOMContentLoaded", function () {
  const addItemBtn = document.getElementById("editAddItemBtn");
  if (addItemBtn) {
    addItemBtn.addEventListener("click", function () {
      addEditItem(null, false);
      updateEditSummary();
    });
  }

  // Form submit handler
  const editForm = document.getElementById("editForm");
  if (editForm) {
    editForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);

      fetch("timeline_edit.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            showNotification("✅ " + data.message, "success");
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          } else {
            showNotification("❌ " + data.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showNotification("❌ Gagal menyimpan perubahan", "error");
        });
    });
  }

  // Load services data on page load
  loadServicesData();
});

// ========== NOTIFICATION HELPER ==========
function showNotification(message, type = "info") {
  const colors = {
    success: "#4CAF50",
    error: "#f44336",
    info: "#2196F3",
  };

  const notification = document.createElement("div");
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10001;
        font-size: 14px;
        font-weight: 500;
        animation: slideInRight 0.3s ease-out;
    `;
  notification.textContent = message;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = "slideOutRight 0.3s ease-in";
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Export functions for global access
window.openEditModal = openEditModal;
window.closeEditModal = closeEditModal;
