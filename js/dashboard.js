// Data deadlinesData sudah di-load dari PHP di file utama

// Fungsi untuk menghitung sisa hari
function getDaysUntilDeadline(deadlineDate) {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const deadline = new Date(deadlineDate);

  today.setHours(0, 0, 0, 0);
  deadline.setHours(0, 0, 0, 0);

  const timeDiff = deadline - today;
  const daysUntil = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
  return daysUntil;
}

// Fungsi untuk mendapatkan warna berdasarkan sisa hari
function getDeadlineColorClass(daysUntil) {
  if (daysUntil < 0) return "expired";
  if (daysUntil <= 2) return "red";
  if (daysUntil <= 5) return "yellow";
  return "green";
}

// Fungsi untuk generate deadline list
function generateDeadlineList() {
  const deadlineList = document.getElementById("deadlineList");
  if (!deadlineList) return;

  deadlineList.innerHTML = "";

  const upcomingDeadlines = deadlinesData
    .map((deadline) => ({
      ...deadline,
      daysUntil: getDaysUntilDeadline(deadline.deadline_date),
    }))
    .filter((deadline) => deadline.daysUntil >= 0)
    .sort((a, b) => a.daysUntil - b.daysUntil)
    .slice(0, 10);

  if (upcomingDeadlines.length === 0) {
    deadlineList.innerHTML =
      '<p style="color: #fff; text-align: center; padding: 20px;">Tidak ada deadline mendatang</p>';
    return;
  }

  upcomingDeadlines.forEach((deadline) => {
    const colorClass = getDeadlineColorClass(deadline.daysUntil);
    const formattedDate = new Date(deadline.deadline_date).toLocaleDateString(
      "id-ID",
      {
        day: "2-digit",
        month: "short",
        year: "numeric",
      }
    );

    let daysText = "";
    if (deadline.daysUntil === 0) {
      daysText = "Hari ini!";
    } else if (deadline.daysUntil === 1) {
      daysText = "Besok";
    } else {
      daysText = `${deadline.daysUntil} hari lagi`;
    }

    const deadlineItem = document.createElement("div");
    deadlineItem.className = `deadline-item ${colorClass}`;
    deadlineItem.style.cursor = "pointer";
    deadlineItem.onclick = function () {
      showDeadlineDetail(deadline);
    };
    deadlineItem.innerHTML = `
            <div>
                <h4>${deadline.order_code}</h4>
                <p>${deadline.customer_name} - ${deadline.service_name}</p>
                <small style="color: #666; font-size: 11px; font-weight: 600;">${daysText}</small>
            </div>
            <span style="font-size: 12px; white-space: nowrap;">${formattedDate}</span>
        `;

    deadlineList.appendChild(deadlineItem);
  });
}

// Variable untuk menyimpan bulan dan tahun
let currentDisplayYear = new Date().getFullYear();
let currentDisplayMonth = new Date().getMonth();

// Fungsi untuk calendar
function generateCalendar() {
  const year = currentDisplayYear;
  const month = currentDisplayMonth;

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const daysInMonth = lastDay.getDate();

  const calendarGrid = document.getElementById("calendarGrid");
  if (!calendarGrid) return;

  calendarGrid.innerHTML = "";

  const days = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
  days.forEach((day) => {
    const dayElement = document.createElement("div");
    dayElement.className = "day";
    dayElement.textContent = day;
    calendarGrid.appendChild(dayElement);
  });

  const firstDayOfWeek = firstDay.getDay();
  for (let i = 0; i < firstDayOfWeek; i++) {
    const emptyElement = document.createElement("div");
    emptyElement.className = "date empty";
    calendarGrid.appendChild(emptyElement);
  }

  for (let i = 1; i <= daysInMonth; i++) {
    const dateElement = document.createElement("div");
    dateElement.className = "date";

    const currentDate = new Date(year, month, i);
    const dateString = formatDate(currentDate);

    const deadlineInfo = getDeadlineInfo(dateString);

    if (deadlineInfo) {
      const daysUntilDeadline = deadlineInfo.daysUntil;
      dateElement.classList.add("has-deadline");

      if (daysUntilDeadline <= 2) {
        dateElement.classList.add("deadline-critical");
      } else if (daysUntilDeadline <= 5) {
        dateElement.classList.add("deadline-warning");
      } else if (daysUntilDeadline >= 6) {
        dateElement.classList.add("deadline-safe");
      }

      const tooltipText = deadlineInfo.orders
        .map((o) => `${o.order_code} - ${o.customer_name}`)
        .join("\n");
      dateElement.title = `${tooltipText}\n(${daysUntilDeadline} hari lagi)`;
    }

    const today = new Date();
    if (
      i === today.getDate() &&
      month === today.getMonth() &&
      year === today.getFullYear()
    ) {
      dateElement.classList.add("active");
    }

    dateElement.textContent = i;
    dateElement.setAttribute("data-date", dateString);
    calendarGrid.appendChild(dateElement);
  }
}

// Fungsi format date
function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

// Fungsi get deadline info
function getDeadlineInfo(dateString) {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const deadlineDate = new Date(dateString);

  today.setHours(0, 0, 0, 0);
  deadlineDate.setHours(0, 0, 0, 0);

  const timeDiff = deadlineDate - today;
  const daysUntil = Math.floor(timeDiff / (1000 * 60 * 60 * 24));

  if (daysUntil < 0) {
    return null;
  }

  const deadlinesOnDate = deadlinesData.filter(
    (deadline) => deadline.deadline_date === dateString
  );

  if (deadlinesOnDate.length > 0) {
    return {
      count: deadlinesOnDate.length,
      daysUntil: daysUntil,
      orders: deadlinesOnDate,
    };
  }

  return null;
}

// Fungsi change month
function changeMonth(direction) {
  currentDisplayMonth += direction;

  if (currentDisplayMonth > 11) {
    currentDisplayMonth = 0;
    currentDisplayYear++;
  } else if (currentDisplayMonth < 0) {
    currentDisplayMonth = 11;
    currentDisplayYear--;
  }

  updateCalendarHeader();
  generateCalendar();
  updateDeadlineListForMonth();
}

// Update calendar header
function updateCalendarHeader() {
  const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];
  const headerElement = document.querySelector(".calendar-header h3");
  if (headerElement) {
    headerElement.textContent = `${monthNames[currentDisplayMonth]} ${currentDisplayYear}`;
  }
}

// Update deadline list for month
function updateDeadlineListForMonth() {
  const deadlineList = document.getElementById("deadlineList");
  if (!deadlineList) return;

  deadlineList.innerHTML = "";

  const selectedMonthDeadlines = deadlinesData
    .map((deadline) => ({
      ...deadline,
      daysUntil: getDaysUntilDeadline(deadline.deadline_date),
      deadlineDate: new Date(deadline.deadline_date),
    }))
    .filter((deadline) => {
      const dlMonth = deadline.deadlineDate.getMonth();
      const dlYear = deadline.deadlineDate.getFullYear();
      return (
        dlMonth === currentDisplayMonth &&
        dlYear === currentDisplayYear &&
        deadline.daysUntil >= 0
      );
    })
    .sort((a, b) => a.daysUntil - b.daysUntil)
    .slice(0, 10);

  if (selectedMonthDeadlines.length === 0) {
    deadlineList.innerHTML =
      '<p style="color: #fff; text-align: center; padding: 20px;">Tidak ada deadline di bulan ini</p>';
    return;
  }

  selectedMonthDeadlines.forEach((deadline) => {
    const colorClass = getDeadlineColorClass(deadline.daysUntil);
    const formattedDate = deadline.deadlineDate.toLocaleDateString("id-ID", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });

    let daysText = "";
    if (deadline.daysUntil === 0) {
      daysText = "Hari ini!";
    } else if (deadline.daysUntil === 1) {
      daysText = "Besok";
    } else {
      daysText = `${deadline.daysUntil} hari lagi`;
    }

    const deadlineItem = document.createElement("div");
    deadlineItem.className = `deadline-item ${colorClass}`;
    deadlineItem.style.cursor = "pointer";
    deadlineItem.onclick = function () {
      showDeadlineDetail(deadline);
    };
    deadlineItem.innerHTML = `
            <div>
                <h4>${deadline.order_code}</h4>
                <p>${deadline.customer_name} - ${deadline.service_name}</p>
                <small style="color: #666; font-size: 11px; font-weight: 600;">${daysText}</small>
            </div>
            <span style="font-size: 12px; white-space: nowrap;">${formattedDate}</span>
        `;

    deadlineList.appendChild(deadlineItem);
  });
}

// MODAL FUNCTIONS
function showOrderDetail(element) {
  const orderCode = element.getAttribute("data-order-code");
  const customer = element.getAttribute("data-customer");
  const service = element.getAttribute("data-service");
  const category = element.getAttribute("data-category-full");
  const brand = element.getAttribute("data-brand");
  const estDate = element.getAttribute("data-est-date");

  document.getElementById("modal-order-code").textContent = orderCode || "N/A";
  document.getElementById("modal-customer").textContent = customer || "N/A";
  document.getElementById("modal-category").textContent = category || "N/A";
  document.getElementById("modal-service").textContent = service || "N/A";
  document.getElementById("modal-brand").textContent = brand || "N/A";
  document.getElementById("modal-est-date").textContent = estDate
    ? formatDateIndonesia(estDate)
    : "N/A";

  document.getElementById("orderDetailModal").style.display = "block";
}

function showDeadlineDetail(deadline) {
  document.getElementById("modal-order-code").textContent =
    deadline.order_code || "N/A";
  document.getElementById("modal-customer").textContent =
    deadline.customer_name || "N/A";
  document.getElementById("modal-category").textContent = "N/A";
  document.getElementById("modal-service").textContent =
    deadline.service_name || "N/A";
  document.getElementById("modal-brand").textContent = deadline.brand || "N/A";
  document.getElementById("modal-est-date").textContent = deadline.deadline_date
    ? formatDateIndonesia(deadline.deadline_date)
    : "N/A";

  document.getElementById("orderDetailModal").style.display = "block";
}

function closeOrderDetail() {
  document.getElementById("orderDetailModal").style.display = "none";
}

function formatDateIndonesia(dateString) {
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString("id-ID", {
      day: "2-digit",
      month: "long",
      year: "numeric",
    });
  } catch (e) {
    return dateString;
  }
}

function setupModalHandlers() {
  const modal = document.getElementById("orderDetailModal");
  const closeBtn = document.getElementById("closeModalBtn");

  if (closeBtn) {
    closeBtn.addEventListener("click", closeOrderDetail);
  }

  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeOrderDetail();
      }
    });
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeOrderDetail();
    }
  });
}

function setupOrderClickHandlers() {
  const orderItems = document.querySelectorAll(".order-item");
  orderItems.forEach((item) => {
    item.style.cursor = "pointer";
    item.addEventListener("click", function () {
      showOrderDetail(this);
    });
  });
}

// Search, filter, sort functions (tetap sama seperti sebelumnya)
function searchOrders() {
  const searchTerm = document
    .getElementById("searchOrder")
    .value.toLowerCase()
    .trim();
  if (searchTerm === "") return;

  const orderItems = document.querySelectorAll(".order-item");
  let foundResults = false;
  const activeCategory = document
    .querySelector(".filter-type button.active")
    .textContent.toLowerCase();

  orderItems.forEach((item) => {
    const orderId = item.querySelector(".order-id").textContent.toLowerCase();
    const customerName = item
      .querySelector(".order-details strong")
      .textContent.toLowerCase();
    const serviceDetails = item
      .querySelector(".order-details")
      .textContent.toLowerCase();
    const itemCategory = item.getAttribute("data-category");

    const matchesSearch =
      orderId.includes(searchTerm) ||
      customerName.includes(searchTerm) ||
      serviceDetails.includes(searchTerm);
    const matchesCategory =
      activeCategory === "all" || itemCategory === activeCategory;

    if (matchesSearch && matchesCategory) {
      item.style.display = "block";
      foundResults = true;
    } else {
      item.style.display = "none";
    }
  });

  showSearchMessage(searchTerm, foundResults, activeCategory);
}

function showSearchMessage(searchTerm, foundResults, activeCategory) {
  const existingMessage = document.querySelector(".search-message");
  if (existingMessage) existingMessage.remove();

  const messageDiv = document.createElement("div");
  messageDiv.className = "search-message";
  messageDiv.style.cssText = `
        background: ${foundResults ? "#d4edda" : "#f8d7da"};
        color: ${foundResults ? "#155724" : "#721c24"};
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 14px;
        border: 1px solid ${foundResults ? "#c3e6cb" : "#f5c6cb"};
    `;

  if (foundResults) {
    messageDiv.textContent =
      activeCategory === "all"
        ? `Ditemukan hasil untuk: "${searchTerm}"`
        : `Ditemukan hasil untuk: "${searchTerm}" dalam kategori ${activeCategory}`;
  } else {
    messageDiv.textContent =
      activeCategory === "all"
        ? `Tidak ditemukan hasil untuk: "${searchTerm}"`
        : `Tidak ditemukan hasil untuk: "${searchTerm}" dalam kategori ${activeCategory}`;
  }

  const timelineBody = document.getElementById("orderTimeline");
  timelineBody.insertBefore(messageDiv, timelineBody.firstChild);
  setTimeout(() => messageDiv.remove(), 3000);
}

function setupRealTimeSearch() {
  const searchInput = document.getElementById("searchOrder");
  let searchTimeout;

  searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    if (searchTerm === "") {
      const activeCategory = document
        .querySelector(".filter-type button.active")
        .textContent.toLowerCase();
      filterByCategory(activeCategory);
      return;
    }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => searchOrders(), 500);
  });

  searchInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") searchOrders();
  });
}

function sortOrders() {
  const sortBy = document.getElementById("sortFilter").value;
  const orderItems = document.querySelectorAll(".order-item");
  const timelineBody = document.getElementById("orderTimeline");

  const ordersArray = Array.from(orderItems);

  ordersArray.sort((a, b) => {
    const dateA = new Date(
      a
        .querySelector(".order-time")
        .textContent.replace("📅 Estimasi Selesai: ", "")
    );
    const dateB = new Date(
      b
        .querySelector(".order-time")
        .textContent.replace("📅 Estimasi Selesai: ", "")
    );
    return sortBy === "newest" ? dateB - dateA : dateA - dateB;
  });

  timelineBody.innerHTML = "";
  ordersArray.forEach((order) => timelineBody.appendChild(order));
  showSortMessage(sortBy);
  setupOrderClickHandlers(); // Re-attach click handlers
}

function filterByCategory(category) {
  const orderItems = document.querySelectorAll(".order-item");
  const filterButtons = document.querySelectorAll(".filter-type button");
  const searchTerm = document
    .getElementById("searchOrder")
    .value.toLowerCase()
    .trim();

  filterButtons.forEach((button) => {
    button.classList.remove("active");
    if (
      button.textContent.toLowerCase() === category.toLowerCase() ||
      (category === "all" && button.textContent.toLowerCase() === "all")
    ) {
      button.classList.add("active");
    }
  });

  let foundResults = false;
  orderItems.forEach((item) => {
    const orderId = item.querySelector(".order-id").textContent.toLowerCase();
    const customerName = item
      .querySelector(".order-details strong")
      .textContent.toLowerCase();
    const serviceDetails = item
      .querySelector(".order-details")
      .textContent.toLowerCase();
    const itemCategory = item.getAttribute("data-category");

    const matchesCategory =
      category === "all" || itemCategory === category.toLowerCase();
    const matchesSearch =
      searchTerm === "" ||
      orderId.includes(searchTerm) ||
      customerName.includes(searchTerm) ||
      serviceDetails.includes(searchTerm);

    if (matchesCategory && matchesSearch) {
      item.style.display = "block";
      foundResults = true;
    } else {
      item.style.display = "none";
    }
  });

  showFilterMessage(category, searchTerm, foundResults);
}

function showFilterMessage(category, searchTerm, foundResults) {
  const message =
    category === "all"
      ? searchTerm === ""
        ? "Menampilkan semua pesanan"
        : `Menampilkan semua kategori dengan pencarian: "${searchTerm}"`
      : searchTerm === ""
      ? `Menampilkan kategori: ${category}`
      : `Menampilkan kategori: ${category} dengan pencarian: "${searchTerm}"`;

  const existingMessage = document.querySelector(".filter-message");
  if (existingMessage) existingMessage.remove();

  const messageDiv = document.createElement("div");
  messageDiv.className = "filter-message";
  messageDiv.style.cssText = `
        background: #e3f2fd;
        color: #0d47a1;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 14px;
        border: 1px solid #bbdefb;
    `;
  messageDiv.textContent = message;

  const timelineBody = document.getElementById("orderTimeline");
  timelineBody.insertBefore(messageDiv, timelineBody.firstChild);
  setTimeout(() => messageDiv.remove(), 3000);
}

function showSortMessage(sortType) {
  const message =
    sortType === "newest"
      ? "Pesanan diurutkan dari tanggal terbaru"
      : "Pesanan diurutkan dari tanggal terlama";

  const messageDiv = document.createElement("div");
  messageDiv.style.cssText = `
        background: #d4edda;
        color: #155724;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 14px;
        border: 1px solid #c3e6cb;
    `;
  messageDiv.textContent = message;

  const timelineBody = document.getElementById("orderTimeline");
  timelineBody.insertBefore(messageDiv, timelineBody.firstChild);
  setTimeout(() => messageDiv.remove(), 3000);
}

// Initialize
document.addEventListener("DOMContentLoaded", function () {
  console.log("Dashboard initialized");
  generateCalendar();
  generateDeadlineList();
  setupRealTimeSearch();
  setupOrderClickHandlers();
  setupModalHandlers();
});

/* ============================================================
   DOUBLE CLICK ROW TO EDIT
============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  const orderItems = document.querySelectorAll(".order-item");
  const modal = document.getElementById("editModal");
  const closeModal = modal.querySelector(".close");

  orderItems.forEach((item) => {
    item.addEventListener("dblclick", function () {
      // Ambil data dari atribut data-*
      const orderCode = this.getAttribute("data-order-code");
      const customer = this.getAttribute("data-customer");
      const service = this.getAttribute("data-service");
      const category = this.getAttribute("data-category-full");
      const brand = this.getAttribute("data-brand");
      const estDate = this.getAttribute("data-est-date");

      // === Isi form modal ===
      document.getElementById("edit_id_drop").value = orderCode || "";
      document.getElementById("edit_customer_name").value = customer || "";
      document.getElementById("edit_brand").value = brand || "";
      document.getElementById("edit_tanggal_selesai").value = estDate || "";

      // Pilih layanan berdasarkan teks yang mirip
      const serviceSelect = document.getElementById("edit_service_id");
      for (let option of serviceSelect.options) {
        if (option.text.toLowerCase().includes(service.toLowerCase())) {
          option.selected = true;
          break;
        }
      }

      // Tampilkan modal
      modal.style.display = "flex";
    });
  });

  // Tutup modal saat klik tombol X
  closeModal.addEventListener("click", () => {
    modal.style.display = "none";
  });

  // Tutup modal jika klik di luar area konten modal
  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });
});
