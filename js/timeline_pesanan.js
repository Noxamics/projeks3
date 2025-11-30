// Data deadlinesData sudah di-load dari PHP di file utama

// ✅ BACKUP SEMUA ORDER ITEMS (penting untuk switching filter)
let allOrderItems = [];

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

// ✅ FUNGSI SORT ORDERS YANG DIPERBAIKI
function sortOrders() {
  const sortBy = document.getElementById("sortFilter").value;
  const timelineBody = document.getElementById("orderTimeline");

  // Jika belum ada backup, ambil semua order items dari DOM
  if (allOrderItems.length === 0) {
    allOrderItems = Array.from(document.querySelectorAll(".order-item"));
  }

  // Clone array untuk manipulasi
  const ordersArray = [...allOrderItems];

  // RESET: Set semua ke display block
  ordersArray.forEach((order) => (order.style.display = "block"));

  // Jika filter deadline, filter berdasarkan kriteria
  if (sortBy.startsWith("deadline-")) {
    const filteredOrders = ordersArray.filter((order) => {
      const estDate = order.getAttribute("data-est-date");
      const daysUntil = getDaysUntilDeadline(estDate);

      if (sortBy === "deadline-critical") {
        return daysUntil >= 0 && daysUntil <= 2;
      } else if (sortBy === "deadline-warning") {
        return daysUntil >= 3 && daysUntil <= 5;
      } else if (sortBy === "deadline-safe") {
        return daysUntil >= 6;
      }
      return false;
    });

    // Urutkan berdasarkan deadline terdekat
    filteredOrders.sort((a, b) => {
      const dateA = new Date(a.getAttribute("data-est-date"));
      const dateB = new Date(b.getAttribute("data-est-date"));
      return dateA - dateB;
    });

    // Tampilkan hasil filter
    timelineBody.innerHTML = "";
    filteredOrders.forEach((order) => timelineBody.appendChild(order));

    showDeadlineFilterMessage(sortBy, filteredOrders.length);
  } else {
    // Sort berdasarkan tanggal (newest/oldest)
    ordersArray.sort((a, b) => {
      const dateA = new Date(a.getAttribute("data-est-date"));
      const dateB = new Date(b.getAttribute("data-est-date"));
      return sortBy === "newest" ? dateB - dateA : dateA - dateB;
    });

    timelineBody.innerHTML = "";
    ordersArray.forEach((order) => timelineBody.appendChild(order));
    showSortMessage(sortBy);
  }

  // Re-attach double click handlers (sesuai dengan PHP Anda)
  setupDoubleClickHandlers();
}

// Fungsi untuk menampilkan pesan filter deadline
function showDeadlineFilterMessage(filterType, count) {
  let message = "";
  let emoji = "";

  if (filterType === "deadline-critical") {
    message = `Menampilkan ${count} pesanan dengan deadline KRITIS (≤2 hari)`;
    emoji = "🔴";
  } else if (filterType === "deadline-warning") {
    message = `Menampilkan ${count} pesanan dengan deadline MENDESAK (3-5 hari)`;
    emoji = "🟡";
  } else if (filterType === "deadline-safe") {
    message = `Menampilkan ${count} pesanan dengan deadline AMAN (≥6 hari)`;
    emoji = "🟢";
  }

  const existingMessage = document.querySelector(".filter-message");
  if (existingMessage) existingMessage.remove();

  const messageDiv = document.createElement("div");
  messageDiv.className = "filter-message";
  messageDiv.style.cssText = `
    background: ${
      filterType === "deadline-critical"
        ? "#fee"
        : filterType === "deadline-warning"
        ? "#fffbeb"
        : "#d4edda"
    };
    color: ${
      filterType === "deadline-critical"
        ? "#c00"
        : filterType === "deadline-warning"
        ? "#b45309"
        : "#155724"
    };
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    border: 2px solid ${
      filterType === "deadline-critical"
        ? "#fcc"
        : filterType === "deadline-warning"
        ? "#fde68a"
        : "#c3e6cb"
    };
  `;
  messageDiv.textContent = `${emoji} ${message}`;

  const timelineBody = document.getElementById("orderTimeline");
  timelineBody.insertBefore(messageDiv, timelineBody.firstChild);

  if (count === 0) {
    const noDataDiv = document.createElement("div");
    noDataDiv.className = "no-orders-message";
    noDataDiv.innerHTML = `
      <p>Tidak ada pesanan dengan kriteria deadline ini</p>
      <small>Coba pilih filter deadline lainnya</small>
    `;
    timelineBody.appendChild(noDataDiv);
  }
}

// Update fungsi showSortMessage
function showSortMessage(sortType) {
  const message =
    sortType === "newest"
      ? "📅 Pesanan diurutkan dari tanggal terbaru"
      : "📅 Pesanan diurutkan dari tanggal terlama";

  const existingMessage = document.querySelector(".filter-message");
  if (existingMessage) existingMessage.remove();

  const messageDiv = document.createElement("div");
  messageDiv.className = "filter-message";
  messageDiv.style.cssText = `
    background: #e3f2fd;
    color: #0d47a1;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    border: 2px solid #bbdefb;
  `;
  messageDiv.textContent = message;

  const timelineBody = document.getElementById("orderTimeline");
  timelineBody.insertBefore(messageDiv, timelineBody.firstChild);
  setTimeout(() => messageDiv.remove(), 3000);
}

// Fungsi untuk generate deadline list
function generateDeadlineList() {
  const deadlineList = document.getElementById("deadlineList");
  if (!deadlineList) return;

  deadlineList.innerHTML = "";

  const now = new Date();
  const currentMonth = now.getMonth();
  const currentYear = now.getFullYear();

  const upcomingDeadlines = deadlinesData
    .map((deadline) => {
      const deadlineDate = new Date(deadline.deadline_date);
      return {
        ...deadline,
        daysUntil: getDaysUntilDeadline(deadline.deadline_date),
        deadlineMonth: deadlineDate.getMonth(),
        deadlineYear: deadlineDate.getFullYear(),
      };
    })
    .filter((deadline) => {
      // Filter: deadline harus >= 0 hari DAN harus di bulan sekarang
      return (
        deadline.daysUntil >= 0 &&
        deadline.deadlineMonth === currentMonth &&
        deadline.deadlineYear === currentYear
      );
    })
    .sort((a, b) => a.daysUntil - b.daysUntil)
    .slice(0, 10);

  if (upcomingDeadlines.length === 0) {
    deadlineList.innerHTML =
      '<p style="color: #fff; text-align: center; padding: 20px;">Tidak ada deadline mendatang di bulan ini</p>';
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

// Fungsi untuk calendar - FIXED VERSION
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

  // PENTING: Hitung hari ini sekali saja di awal
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  today.setHours(0, 0, 0, 0);

  for (let i = 1; i <= daysInMonth; i++) {
    const dateElement = document.createElement("div");
    dateElement.className = "date";

    const currentDate = new Date(year, month, i);
    const dateString = formatDate(currentDate);

    const deadlineInfo = getDeadlineInfo(dateString);

    if (deadlineInfo) {
      dateElement.classList.add("has-deadline");

      // PENTING: Hitung sisa hari dari HARI INI ke tanggal di kalender
      const deadlineDate = new Date(dateString);
      deadlineDate.setHours(0, 0, 0, 0);

      const timeDiff = deadlineDate - today;
      const daysUntilDeadline = Math.floor(timeDiff / (1000 * 60 * 60 * 24));

      // Tambahkan warna sesuai sisa hari dari HARI INI
      if (daysUntilDeadline <= 2) {
        dateElement.classList.add("deadline-critical");
      } else if (daysUntilDeadline <= 5) {
        dateElement.classList.add("deadline-warning");
      } else {
        dateElement.classList.add("deadline-safe");
      }

      const tooltipText = deadlineInfo.orders
        .map((o) => `${o.order_code} - ${o.customer_name}`)
        .join("\n");
      dateElement.title = `${tooltipText}\n(${daysUntilDeadline} hari lagi)`;
    }

    // Tandai tanggal hari ini
    if (
      i === now.getDate() &&
      month === now.getMonth() &&
      year === now.getFullYear()
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
  today.setHours(0, 0, 0, 0);

  const deadlineDate = new Date(dateString);
  deadlineDate.setHours(0, 0, 0, 0);

  const timeDiff = deadlineDate - today;
  const daysUntil = Math.floor(timeDiff / (1000 * 60 * 60 * 24));

  // FILTER: Jangan tampilkan deadline yang sudah lewat
  if (daysUntil < 0) {
    return null;
  }

  const deadlinesOnDate = deadlinesData.filter(
    (deadline) => deadline.deadline_date === dateString
  );

  if (deadlinesOnDate.length > 0) {
    return {
      count: deadlinesOnDate.length,
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

// ✅ Setup double click handler (untuk edit modal - sesuai file PHP Anda)
function setupDoubleClickHandlers() {
  const orderItems = document.querySelectorAll(".order-item");
  orderItems.forEach((item) => {
    item.style.cursor = "pointer";

    // Remove old listeners untuk avoid duplicate
    const newItem = item.cloneNode(true);
    item.parentNode.replaceChild(newItem, item);

    // Attach double click untuk edit modal
    newItem.addEventListener("dblclick", function () {
      if (typeof openEditModal === "function") {
        openEditModal(this);
      }
    });
  });
}

// Search functions
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

// Initialize
document.addEventListener("DOMContentLoaded", function () {
  console.log("Dashboard initialized");

  // Backup semua order items saat pertama kali load
  allOrderItems = Array.from(document.querySelectorAll(".order-item"));
  console.log("✅ Total order items loaded:", allOrderItems.length);

  generateCalendar();
  generateDeadlineList();
  setupRealTimeSearch();
  setupDoubleClickHandlers();
  setupModalHandlers();
});
