// File: /js/drop/drop_helpers.js
// Helper functions untuk Drop Management - FIXED v3 FINAL

// ===== FORMAT RUPIAH =====
const formatRupiah = (angka) => {
  if (typeof angka !== "number") return "Rp 0";
  return "Rp " + angka.toLocaleString("id-ID");
};

const parseRupiah = (rupiah) => {
  return parseInt(rupiah.replace(/[^0-9]/g, "") || 0);
};

// ===== MODAL HELPER =====
function showModal(id) {
  const m = document.getElementById(id);
  if (m) {
    m.style.display = "flex";
    m.classList.add("show");
    document.body.style.overflow = "hidden";
    console.log(`✅ Modal ${id} opened`);
  } else {
    console.error(`❌ Modal ${id} tidak ditemukan`);
  }
}

function hideModal(id) {
  const m = document.getElementById(id);
  if (m) {
    m.style.display = "none";
    m.classList.remove("show");
    document.body.style.overflow = "";
    console.log(`✅ Modal ${id} closed`);
  } else {
    console.error(`❌ Modal ${id} tidak ditemukan`);
  }
}

// ===== CLOSE EDIT MODAL =====
function closeItemEditModal() {
  hideModal("itemEditModal");
}

// ===== DATE CONVERSION =====
function convertDateToDbFormat(input_date) {
  if (!input_date || input_date.trim() === "") return null;

  const cleaned = input_date.replace(/\//g, "-");

  // Try DD-MM-YYYY
  const dt_dmy = new Date(cleaned.split("-").reverse().join("-"));
  if (!isNaN(dt_dmy.getTime())) {
    return dt_dmy.toISOString().split("T")[0];
  }

  // Try YYYY-MM-DD
  const dt_ymd = new Date(cleaned);
  if (!isNaN(dt_ymd.getTime())) {
    return dt_ymd.toISOString().split("T")[0];
  }

  return null;
}

// ===== DYNAMIC ESTIMATE FETCH =====
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

    if (!response.ok) throw new Error("Network response was not ok");
    return await response.json();
  } catch (error) {
    console.error("Error fetching dynamic estimate:", error);
    return null;
  }
};

// ===== PAYMENT DATE HANDLING =====
function setPaymentDate(displayId, hiddenId, date) {
  const display = document.getElementById(displayId);
  const hidden = document.getElementById(hiddenId);

  if (display && hidden) {
    display.value = date || "";
    hidden.value = date || "";
    hidden.dataset.originalDate = date || "";
  }
}

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

// ===== RUPIAH INPUT SETUP =====
const setupRupiahInput = (displayInputId, hiddenInputId) => {
  const displayInput = document.getElementById(displayInputId);
  const hiddenInput = document.getElementById(hiddenInputId);

  if (displayInput && hiddenInput) {
    if (hiddenInput.value && parseFloat(hiddenInput.value) > 0) {
      displayInput.value = formatRupiah(parseFloat(hiddenInput.value));
    } else {
      displayInput.value = "";
    }

    displayInput.addEventListener("input", function (e) {
      let value = e.target.value;
      const numericValue = parseRupiah(value);
      hiddenInput.value = numericValue;
      e.target.value = formatRupiah(numericValue);
    });

    displayInput.addEventListener("blur", function (e) {
      e.target.value = formatRupiah(parseRupiah(e.target.value));
    });
  }
};

// ===== SHOW NOTIFICATION =====
function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.textContent = message;

  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === "success" ? "#059669" : "#dc2626"};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = "slideOut 0.3s ease";
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// ===== CALCULATE ORDER SUMMARY =====
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

    // Calculate est date from trans_date + duration
    if (transDate && duration > 0) {
      const date = new Date(transDate);
      date.setDate(date.getDate() + duration);
      const estDate = date.toISOString().split("T")[0];

      if (!maxEstDate || estDate > maxEstDate) {
        maxEstDate = estDate;
      }
    }
  });

  // Update summary fields with null checks
  const totalItemsEl = document.getElementById("totalItems");
  const totalPriceDisplayEl = document.getElementById("totalPriceDisplay");
  const totalPriceEl = document.getElementById("totalPrice");
  const maxEstimateEl = document.getElementById("maxEstimate");
  const maxDurationEl = document.getElementById("maxDuration");
  const finalEstDateEl = document.getElementById("finalEstDate");

  if (totalItemsEl) totalItemsEl.value = items.length + " Item";
  if (totalPriceDisplayEl) totalPriceDisplayEl.value = formatRupiah(totalPrice);
  if (totalPriceEl) totalPriceEl.value = totalPrice;
  if (maxEstimateEl)
    maxEstimateEl.value = maxDuration > 0 ? `${maxDuration} Hari` : "-";
  if (maxDurationEl) maxDurationEl.value = maxDuration;
  if (finalEstDateEl) finalEstDateEl.value = maxEstDate || "-";
}

// ===== UPDATE ITEM SERVICE DATA =====
const updateItemServiceData = async (itemGroup) => {
  const serviceSelect = itemGroup.querySelector(".item-service");
  const priceDisplay = itemGroup.querySelector(".item-price-display");
  const priceInput = itemGroup.querySelector(".item-price");
  const estimateDisplay = itemGroup.querySelector(".item-estimate");
  const durationInput = itemGroup.querySelector(".item-duration");
  const transDateInput = itemGroup.querySelector(".item-trans-date");
  const estDateDisplay = itemGroup.querySelector(".item-est-date-display");

  const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

  if (selectedOption && selectedOption.value) {
    const serviceId = selectedOption.value;
    const transDate = transDateInput.value;

    estimateDisplay.value = "⏳ Menghitung...";
    durationInput.value = "0";
    if (estDateDisplay) estDateDisplay.value = "-";

    const result = await fetchDynamicEstimate(serviceId, transDate, 0);

    if (result && result.success) {
      priceDisplay.value = formatRupiah(result.price_min);
      priceInput.value = result.price_min;
      durationInput.value = result.duration;
      estimateDisplay.value = result.estimate_desc;
      if (estDateDisplay) estDateDisplay.value = result.estimate_date;

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

      if (transDate && duration > 0) {
        const date = new Date(transDate);
        date.setDate(date.getDate() + duration);
        const estDate = date.toISOString().split("T")[0];
        if (estDateDisplay) estDateDisplay.value = estDate;
      } else {
        if (estDateDisplay) estDateDisplay.value = "-";
      }
    }
  } else {
    priceDisplay.value = "Rp 0";
    priceInput.value = "0";
    durationInput.value = "0";
    estimateDisplay.value = "-";
    if (estDateDisplay) estDateDisplay.value = "-";
  }

  calculateOrderSummary();
};

console.log("✅ Drop helpers loaded");

// =====================================================================
// ====== DELETE MODAL HANDLER ========
// =====================================================================

// Global
window.pendingDelete = null;

// Buka modal + simpan data yang akan dihapus
function openDeleteModal(data) {
  pendingDelete = data;
  console.log("🟡 Modal opened, pending:", pendingDelete);
  showModal("confirmDeleteModal");
}

// Tutup modal
function closeDeleteModal() {
  hideModal("confirmDeleteModal");
  pendingDelete = null;
  console.log("🔵 Modal closed & pending cleared");
}

console.log("🟢 Delete modal handler ready");
