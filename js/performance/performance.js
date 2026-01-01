/**
 * PERFORMANCE TRACKING - JAVASCRIPT LOGIC
 * Handles filtering, search, table interactions, and export functionality
 */

// ==========================================
// DOM ELEMENTS
// ==========================================
const searchInput = document.getElementById("searchInput");
const statusFilter = document.getElementById("statusFilter");
const monthFilter = document.getElementById("monthFilter");
const tableRows = document.querySelectorAll(".table-row");

// ==========================================
// EVENT LISTENERS
// ==========================================

// Initialize event listeners when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  initializeFilters();
  initializeTableFeatures();
});

/**
 * Initialize all filter event listeners
 */
function initializeFilters() {
  if (searchInput) {
    searchInput.addEventListener("input", filterTable);
  }

  if (statusFilter) {
    statusFilter.addEventListener("change", filterTable);
  }

  if (monthFilter) {
    monthFilter.addEventListener("change", filterTable);
  }
}

/**
 * Initialize additional table features
 */
function initializeTableFeatures() {
  // Add row click listener for details (optional)
  tableRows.forEach((row) => {
    row.style.cursor = "pointer";
    row.addEventListener("click", function (e) {
      // Avoid triggering on button clicks
      if (e.target.tagName === "BUTTON") return;

      // Optional: Show detail modal or expand row
      console.log("Row clicked:", this.dataset);
    });

    // Add hover effect enhancement
    row.addEventListener("mouseenter", function () {
      this.style.backgroundColor = "#f8f9fa";
    });

    row.addEventListener("mouseleave", function () {
      this.style.backgroundColor = "";
    });
  });
}

// ==========================================
// FILTERING FUNCTIONS
// ==========================================

/**
 * Main filter function - combines search, status, and month filters
 */
function filterTable() {
  const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
  const selectedStatus = statusFilter ? statusFilter.value : "all";
  const selectedMonth = monthFilter ? monthFilter.value : "all";

  let visibleCount = 0;

  tableRows.forEach((row) => {
    const matchesSearch = checkSearchMatch(row, searchTerm);
    const matchesStatus = checkStatusMatch(row, selectedStatus);
    const matchesMonth = checkMonthMatch(row, selectedMonth);

    const shouldShow = matchesSearch && matchesStatus && matchesMonth;

    row.style.display = shouldShow ? "" : "none";

    if (shouldShow) {
      visibleCount++;
    }
  });

  // Update empty state if needed
  updateEmptyState(visibleCount);

  // Update result counter
  updateResultCounter(visibleCount);
}

/**
 * Check if row matches search term
 * @param {HTMLElement} row - Table row element
 * @param {string} searchTerm - Search term to match
 * @returns {boolean}
 */
function checkSearchMatch(row, searchTerm) {
  if (!searchTerm) return true;

  const text = row.textContent.toLowerCase();
  return text.includes(searchTerm);
}

/**
 * Check if row matches status filter
 * @param {HTMLElement} row - Table row element
 * @param {string} selectedStatus - Selected status value
 * @returns {boolean}
 */
function checkStatusMatch(row, selectedStatus) {
  if (selectedStatus === "all") return true;

  const rowStatus = row.dataset.status;
  return rowStatus === selectedStatus;
}

/**
 * Check if row matches month filter
 * @param {HTMLElement} row - Table row element
 * @param {string} selectedMonth - Selected month value
 * @returns {boolean}
 */
function checkMonthMatch(row, selectedMonth) {
  if (selectedMonth === "all") return true;

  const rowMonth = row.dataset.month;
  return rowMonth === selectedMonth;
}

/**
 * Update empty state message if no results found
 * @param {number} visibleCount - Number of visible rows
 */
function updateEmptyState(visibleCount) {
  const tbody = document.getElementById("tableBody");
  if (!tbody) return;

  // Remove existing empty state if present
  const existingEmptyState = tbody.querySelector(".filter-empty-state");
  if (existingEmptyState) {
    existingEmptyState.remove();
  }

  // Show empty state if no results
  if (visibleCount === 0 && tableRows.length > 0) {
    const emptyStateRow = createEmptyStateRow();
    tbody.appendChild(emptyStateRow);
  }
}

/**
 * Create empty state row for filtered results
 * @returns {HTMLElement}
 */
function createEmptyStateRow() {
  const row = document.createElement("tr");
  row.className = "filter-empty-state";

  const cell = document.createElement("td");
  cell.colSpan = 11;
  cell.innerHTML = `
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <h3>Tidak ada hasil ditemukan</h3>
            <p>Coba ubah filter atau kata kunci pencarian</p>
        </div>
    `;

  row.appendChild(cell);
  return row;
}

/**
 * Update result counter display
 * @param {number} count - Number of visible results
 */
function updateResultCounter(count) {
  let counter = document.getElementById("resultCounter");

  // Create counter if doesn't exist
  if (!counter) {
    counter = document.createElement("div");
    counter.id = "resultCounter";
    counter.style.cssText = `
      font-size: 14px;
      color: #666;
      margin-bottom: 10px;
      padding: 8px 12px;
      background: #f8f9fa;
      border-radius: 6px;
      display: inline-block;
    `;

    const tableWrapper = document.querySelector(".table-wrapper");
    if (tableWrapper) {
      tableWrapper.insertBefore(counter, tableWrapper.firstChild);
    }
  }

  const total = tableRows.length;
  counter.textContent = `Menampilkan ${count} dari ${total} data`;
  counter.style.display = count !== total ? "inline-block" : "none";
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

/**
 * Reset all filters to default state
 */
function resetFilters() {
  if (searchInput) {
    searchInput.value = "";
  }

  if (statusFilter) {
    statusFilter.value = "all";
  }

  if (monthFilter) {
    monthFilter.value = "all";
  }

  filterTable();
}

/**
 * Get current filter state
 * @returns {Object} Current filter values
 */
function getFilterState() {
  return {
    search: searchInput ? searchInput.value : "",
    status: statusFilter ? statusFilter.value : "all",
    month: monthFilter ? monthFilter.value : "all",
  };
}

/**
 * Get count of visible rows
 * @returns {number}
 */
function getVisibleRowCount() {
  let count = 0;
  tableRows.forEach((row) => {
    if (row.style.display !== "none") {
      count++;
    }
  });
  return count;
}

// ==========================================
// EXPORT FUNCTIONALITY
// ==========================================

/**
 * Export visible table data to CSV
 */
function exportToCSV() {
  const visibleRows = Array.from(tableRows).filter(
    (row) => row.style.display !== "none"
  );

  if (visibleRows.length === 0) {
    alert("Tidak ada data untuk diekspor");
    return;
  }

  // CSV Headers
  const headers = [
    "Kode Order",
    "Tanggal Masuk",
    "Customer",
    "Telepon",
    "Kasir",
    "Kode Kasir",
    "Tanggal Dikerjakan",
    "Karyawan Proses",
    "Kode Karyawan",
    "Tanggal Siap",
    "Karyawan Packing",
    "Kode Packing",
    "Tanggal Diambil",
    "Karyawan Release",
    "Kode Release",
    "Status",
  ];

  // Build CSV content
  let csvContent = headers.join(",") + "\n";

  visibleRows.forEach((row) => {
    const cells = row.querySelectorAll("td");
    const rowData = [];

    cells.forEach((cell) => {
      // Clean text and handle commas
      let text = cell.textContent.trim().replace(/\n/g, " ").replace(/,/g, ";");
      rowData.push(`"${text}"`);
    });

    csvContent += rowData.join(",") + "\n";
  });

  // Download CSV
  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);

  link.setAttribute("href", url);
  link.setAttribute(
    "download",
    `performance_tracking_${new Date().toISOString().split("T")[0]}.csv`
  );
  link.style.visibility = "hidden";

  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

/**
 * Print visible table data
 */
function printTable() {
  const visibleRows = Array.from(tableRows).filter(
    (row) => row.style.display !== "none"
  );

  if (visibleRows.length === 0) {
    alert("Tidak ada data untuk dicetak");
    return;
  }

  // Create print window
  const printWindow = window.open("", "_blank");

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Performance Tracking - Sengkuclean</title>
      <style>
        body { 
          font-family: Arial, sans-serif; 
          padding: 20px;
          font-size: 12px;
        }
        h1 { 
          text-align: center; 
          color: #333;
          margin-bottom: 20px;
        }
        table { 
          width: 100%; 
          border-collapse: collapse; 
          margin-top: 20px;
        }
        th, td { 
          border: 1px solid #ddd; 
          padding: 8px; 
          text-align: left;
        }
        th { 
          background-color: #f8f9fa; 
          font-weight: bold;
        }
        tr:nth-child(even) { 
          background-color: #f9f9f9; 
        }
        .header-info {
          margin-bottom: 20px;
          text-align: center;
          color: #666;
        }
        @media print {
          body { margin: 0; }
          button { display: none; }
        }
      </style>
    </head>
    <body>
      <h1>Performance Tracking Report</h1>
      <div class="header-info">
        Tanggal Cetak: ${new Date().toLocaleDateString("id-ID")}
        <br>
        Total Data: ${visibleRows.length}
      </div>
      <table>
        <thead>
          <tr>
            <th>Kode Order</th>
            <th>Tgl Masuk</th>
            <th>Customer</th>
            <th>Kasir</th>
            <th>Karyawan Proses</th>
            <th>Karyawan Packing</th>
            <th>Karyawan Release</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
  `);

  visibleRows.forEach((row) => {
    const cells = row.querySelectorAll("td");
    printWindow.document.write("<tr>");

    // Extract key columns (adjust indices as needed)
    [0, 1, 2, 3, 5, 7, 9, 10].forEach((index) => {
      if (cells[index]) {
        const text = cells[index].textContent.trim().replace(/\n/g, " ");
        printWindow.document.write(`<td>${text}</td>`);
      }
    });

    printWindow.document.write("</tr>");
  });

  printWindow.document.write(`
        </tbody>
      </table>
      <script>
        window.onload = function() {
          window.print();
          window.onafterprint = function() {
            window.close();
          };
        };
      </script>
    </body>
    </html>
  `);

  printWindow.document.close();
}

// ==========================================
// STATISTICS & ANALYTICS
// ==========================================

/**
 * Calculate performance statistics from visible data
 * @returns {Object} Statistics object
 */
function calculateStatistics() {
  const visibleRows = Array.from(tableRows).filter(
    (row) => row.style.display !== "none"
  );

  const stats = {
    total: visibleRows.length,
    byStatus: {},
    byEmployee: {},
    avgProcessingTime: 0,
  };

  visibleRows.forEach((row) => {
    // Count by status
    const status = row.dataset.status || "Unknown";
    stats.byStatus[status] = (stats.byStatus[status] || 0) + 1;

    // Count by employee (processing)
    const employeeCell = row.querySelectorAll("td")[5];
    if (employeeCell) {
      const employeeName = employeeCell.querySelector(".employee-name");
      if (employeeName) {
        const name = employeeName.textContent.trim();
        stats.byEmployee[name] = (stats.byEmployee[name] || 0) + 1;
      }
    }
  });

  return stats;
}

/**
 * Display statistics in console (for debugging)
 */
function showStatistics() {
  const stats = calculateStatistics();
  console.log("=== Performance Statistics ===");
  console.log("Total Orders:", stats.total);
  console.log("\nBy Status:");
  Object.entries(stats.byStatus).forEach(([status, count]) => {
    console.log(`  ${status}: ${count}`);
  });
  console.log("\nBy Employee:");
  Object.entries(stats.byEmployee).forEach(([name, count]) => {
    console.log(`  ${name}: ${count} orders`);
  });
}

// ==========================================
// STATUS UPDATE WITH EMPLOYEE TRACKING
// ==========================================

/**
 * Update status with employee tracking
 * @param {number} dropId - Drop ID
 * @param {number} itemId - Item ID
 * @param {number} statusId - Status ID
 * @param {number} employeeId - Employee ID
 */
function updateStatusWithTracking(dropId, itemId, statusId, employeeId) {
  const formData = new URLSearchParams({
    drop_id: dropId,
    item_id: itemId,
    status_id: statusId,
    employee_id: employeeId,
  });

  fetch("../actions/drop/update_status_order.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: formData.toString(),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        console.log("Status & tracking updated:", data);

        // Show success message
        showNotification("Status berhasil diupdate!", "success");

        // Reload after short delay
        setTimeout(() => {
          location.reload();
        }, 1000);
      } else {
        showNotification("Error: " + data.message, "error");
      }
    })
    .catch((error) => {
      console.error("Fetch error:", error);
      showNotification("Terjadi kesalahan saat mengupdate status", "error");
    });
}

/**
 * Show notification message
 * @param {string} message - Notification message
 * @param {string} type - Notification type (success, error, info)
 */
function showNotification(message, type = "info") {
  // Remove existing notification
  const existing = document.querySelector(".notification-toast");
  if (existing) {
    existing.remove();
  }

  // Create notification
  const notification = document.createElement("div");
  notification.className = `notification-toast notification-${type}`;
  notification.textContent = message;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    background: ${
      type === "success" ? "#10b981" : type === "error" ? "#ef4444" : "#3b82f6"
    };
    color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000;
    animation: slideIn 0.3s ease;
    font-size: 14px;
    font-weight: 500;
  `;

  document.body.appendChild(notification);

  // Auto remove after 3 seconds
  setTimeout(() => {
    notification.style.animation = "slideOut 0.3s ease";
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Add CSS animations
const style = document.createElement("style");
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);

// ==========================================
// EXPORT FOR EXTERNAL USE
// ==========================================

// Make functions available globally if needed
window.PerformanceTracker = {
  filterTable,
  resetFilters,
  getFilterState,
  getVisibleRowCount,
  updateStatusWithTracking,
  exportToCSV,
  printTable,
  calculateStatistics,
  showStatistics,
  showNotification,
};

// Add keyboard shortcuts
document.addEventListener("keydown", (e) => {
  // Ctrl/Cmd + K: Focus search
  if ((e.ctrlKey || e.metaKey) && e.key === "k") {
    e.preventDefault();
    searchInput?.focus();
  }

  // Ctrl/Cmd + P: Print
  if ((e.ctrlKey || e.metaKey) && e.key === "p") {
    e.preventDefault();
    printTable();
  }

  // Escape: Clear search
  if (e.key === "Escape" && document.activeElement === searchInput) {
    resetFilters();
  }
});

console.log("✅ Performance Tracker initialized");
console.log("📊 Use PerformanceTracker.showStatistics() to see stats");
console.log("⌨️  Shortcuts: Ctrl+K (search), Ctrl+P (print), Esc (reset)");
