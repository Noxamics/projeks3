// Switch tabs
function switchTab(tabName) {
  // Hide all tabs
  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.classList.remove("active");
  });

  // Remove active from all buttons
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.classList.remove("active");
  });

  // Show selected tab
  const selectedTab = document.getElementById("tab-" + tabName);
  if (selectedTab) {
    selectedTab.classList.add("active");
  }

  // Activate button
  event.target.classList.add("active");
}

// Update detail modal content
function updateDetailModalContent(employee) {
  console.log("Updating detail modal with:", employee);

  // Header
  document.getElementById("detail_name").textContent = employee.name || "-";
  document.getElementById("detail_code").textContent =
    "Kode: " + (employee.employee_code || "-");
  document.getElementById("detail_phone").textContent =
    "HP: " + (employee.phone || "-");
  document.getElementById("detail_join").textContent =
    "Bergabung: " + (employee.join_date ? formatDate(employee.join_date) : "-");
  document.getElementById("detail_status").textContent = employee.status || "-";

  // Photo
  const photo = document.getElementById("detail_photo");
  if (employee.photo) {
    photo.src = "../uploads/employee/" + employee.photo;
  } else {
    photo.src = "../assets/img/default-avatar.png";
  }

  // Info tab - Basic Info
  document.getElementById("info_id").textContent = employee.id_employee || "-";
  document.getElementById("info_code").textContent =
    employee.employee_code || "-";
  document.getElementById("info_name").textContent = employee.name || "-";
  document.getElementById("info_phone").textContent = employee.phone || "-";
  document.getElementById("info_email").textContent = employee.email || "-";

  // Status & Role
  document.getElementById("info_status").textContent = employee.status || "-";
  document.getElementById("info_join").textContent =
    formatDate(employee.join_date) || "-";
  document.getElementById("info_birth").textContent = employee.birth_date
    ? formatDate(employee.birth_date)
    : "-";
  document.getElementById("info_roles").textContent = employee.roles
    ? employee.roles.replace(/,/g, ", ")
    : "-";

  // Address & Contact
  document.getElementById("info_address").textContent = employee.address || "-";
  document.getElementById("info_emergency").textContent =
    employee.emergency_contact || "-";

  // Financial
  const salary = employee.base_salary || 0;
  document.getElementById("info_salary").textContent = formatCurrency(salary);

  // Performance tab
  document.getElementById("perf_today_shoes").textContent =
    employee.today_shoes || "0";
  document.getElementById("perf_today_score").textContent =
    (employee.today_score || "0") + "%";
}

// Format currency
function formatCurrency(amount) {
  return (
    "Rp " +
    parseFloat(amount).toLocaleString("id-ID", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}

// Format date
function formatDate(dateString) {
  if (!dateString) return "-";
  const date = new Date(dateString);
  return date.toLocaleDateString("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}
