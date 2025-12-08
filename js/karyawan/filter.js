// ===========================
// EMPLOYEE FILTER & SEARCH
// File: js/karyawan/filter.js
// ===========================

let currentFilters = {
  search: "",
  status: "all",
  role: "all",
};

/**
 * Search employee by keyword
 */
function searchEmployee() {
  const keyword = document
    .getElementById("searchInput")
    .value.toLowerCase()
    .trim();
  currentFilters.search = keyword;
  applyFilters();
}

/**
 * Status Filter Synchronization
 * Syncs between desktop buttons and mobile dropdown
 * Add this to: js/karyawan/filter.js (or create new file)
 */

// Update filterByStatus function to sync both UI elements
function filterByStatus(status) {
  currentFilters.status = status;

  // Update desktop buttons active state
  document.querySelectorAll(".filter-btn[data-status]").forEach((btn) => {
    btn.classList.remove("active");
    if (btn.getAttribute("data-status") === status) {
      btn.classList.add("active");
    }
  });

  // Update mobile dropdown
  const statusSelect = document.getElementById("statusFilter");
  if (statusSelect) {
    statusSelect.value = status;
  }

  applyFilters();
}

// Add event listener for mobile status dropdown
document.addEventListener("DOMContentLoaded", function () {
  const statusSelect = document.getElementById("statusFilter");
  if (statusSelect) {
    statusSelect.addEventListener("change", function () {
      filterByStatus(this.value);
    });
  }
});

// Make sure the function is accessible globally
window.filterByStatus = filterByStatus;

/**
 * Filter by role
 */
function filterByRole(role) {
  currentFilters.role = role;
  applyFilters();
}

/**
 * Apply all filters
 */
function applyFilters() {
  filterCards();
  filterTable();
  updateResultsCount();
}

/**
 * Filter card view
 */
function filterCards() {
  const cards = document.querySelectorAll(".employee-card");
  let visibleCount = 0;

  cards.forEach((card) => {
    const matchesSearch = matchesSearchCriteria(card);
    const matchesStatus = matchesStatusFilter(card);
    const matchesRole = matchesRoleFilter(card);

    const shouldShow = matchesSearch && matchesStatus && matchesRole;

    card.style.display = shouldShow ? "" : "none";
    if (shouldShow) visibleCount++;
  });

  return visibleCount;
}

/**
 * Filter table view
 */
function filterTable() {
  const rows = document.querySelectorAll(".employee-table tbody tr");
  let visibleCount = 0;

  rows.forEach((row) => {
    const matchesSearch = matchesSearchCriteria(row);
    const matchesStatus = matchesStatusFilter(row);
    const matchesRole = matchesRoleFilter(row);

    const shouldShow = matchesSearch && matchesStatus && matchesRole;

    row.style.display = shouldShow ? "" : "none";
    if (shouldShow) visibleCount++;
  });

  return visibleCount;
}

/**
 * Check if element matches search criteria
 */
function matchesSearchCriteria(element) {
  if (!currentFilters.search) return true;

  const searchableFields = [
    element.dataset.name,
    element.dataset.code,
    element.dataset.phone,
  ];

  // For table rows, also check cell content
  if (element.tagName === "TR") {
    const cells = element.querySelectorAll("td");
    cells.forEach((cell) => {
      searchableFields.push(cell.textContent);
    });
  }

  const searchText = searchableFields.join(" ").toLowerCase();
  return searchText.includes(currentFilters.search);
}

/**
 * Check if element matches status filter
 */
function matchesStatusFilter(element) {
  if (currentFilters.status === "all") return true;
  return element.dataset.status === currentFilters.status;
}

/**
 * Check if element matches role filter
 */
function matchesRoleFilter(element) {
  if (currentFilters.role === "all") return true;

  const roles = (element.dataset.roles || "").toLowerCase();
  return roles.includes(currentFilters.role.toLowerCase());
}

/**
 * Update results count
 */
function updateResultsCount() {
  const cardCount = document.querySelectorAll(
    '.employee-card[style=""]'
  ).length;
  const tableCount = document.querySelectorAll(
    '.employee-table tbody tr[style=""]'
  ).length;

  // Update count display if exists
  const countElement = document.getElementById("results-count");
  if (countElement) {
    const total = document.querySelectorAll(".employee-card").length;
    const visible = cardCount || tableCount;
    countElement.textContent = `Menampilkan ${visible} dari ${total} karyawan`;
  }
}

/**
 * Clear all filters
 */
function clearFilters() {
  // Reset filter values
  currentFilters = {
    search: "",
    status: "all",
    role: "all",
  };

  // Reset UI
  document.getElementById("searchInput").value = "";
  document.getElementById("roleFilter").value = "all";

  // Reset status buttons
  document.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.classList.remove("active");
    if (btn.dataset.status === "all") {
      btn.classList.add("active");
    }
  });

  // Apply filters
  applyFilters();

  showToast("Filter direset", "info");
}

/**
 * Sort employees
 */
function sortEmployees(criteria, order = "asc") {
  const cardContainer = document.querySelector(".cards-grid");
  const tableBody = document.querySelector(".employee-table tbody");

  if (!cardContainer && !tableBody) return;

  // Sort cards
  if (cardContainer) {
    const cards = Array.from(document.querySelectorAll(".employee-card"));
    const sorted = cards.sort((a, b) => {
      return compareByCriteria(a, b, criteria, order);
    });

    sorted.forEach((card) => cardContainer.appendChild(card));
  }

  // Sort table rows
  if (tableBody) {
    const rows = Array.from(
      document.querySelectorAll(".employee-table tbody tr")
    );
    const sorted = rows.sort((a, b) => {
      return compareByCriteria(a, b, criteria, order);
    });

    sorted.forEach((row) => tableBody.appendChild(row));
  }

  showToast(`Diurutkan berdasarkan ${criteria}`, "info");
}

/**
 * Compare elements by criteria
 */
function compareByCriteria(a, b, criteria, order) {
  let valueA, valueB;

  switch (criteria) {
    case "name":
      valueA = a.dataset.name || "";
      valueB = b.dataset.name || "";
      break;
    case "code":
      valueA = a.dataset.code || "";
      valueB = b.dataset.code || "";
      break;
    case "status":
      valueA = a.dataset.status || "";
      valueB = b.dataset.status || "";
      break;
    default:
      return 0;
  }

  const comparison = valueA.localeCompare(valueB);
  return order === "asc" ? comparison : -comparison;
}

/**
 * Advanced search with multiple criteria
 */
function advancedSearch(criteria) {
  const results = [];
  const elements = [
    ...document.querySelectorAll(".employee-card"),
    ...document.querySelectorAll(".employee-table tbody tr"),
  ];

  elements.forEach((element) => {
    let matches = true;

    // Check each criterion
    for (const [key, value] of Object.entries(criteria)) {
      if (!value) continue;

      const elementValue = (element.dataset[key] || "").toLowerCase();
      const searchValue = value.toLowerCase();

      if (!elementValue.includes(searchValue)) {
        matches = false;
        break;
      }
    }

    if (matches) {
      results.push(element);
    }
  });

  return results;
}

// Initialize filters on page load
document.addEventListener("DOMContentLoaded", function () {
  // Setup search input
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    // Real-time search
    searchInput.addEventListener("input", searchEmployee);

    // Search on Enter
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        searchEmployee();
      }
    });
  }

  // Setup role filter
  const roleFilter = document.getElementById("roleFilter");
  if (roleFilter) {
    roleFilter.addEventListener("change", function () {
      filterByRole(this.value);
    });
  }

  // Setup status filters
  document.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      filterByStatus(this.dataset.status);
    });
  });

  // Initial filter application
  applyFilters();
});

// Expose functions to global scope
window.searchEmployee = searchEmployee;
window.filterByStatus = filterByStatus;
window.filterByRole = filterByRole;
window.clearFilters = clearFilters;
window.sortEmployees = sortEmployees;
window.advancedSearch = advancedSearch;
