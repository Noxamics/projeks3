// =====================================================================
// File: /js/drop/drop_status_change.js
// Item Status Change Handler with Employee Selection BEFORE Dropdown Opens
// Version: 5.0 - Employee Selection BEFORE Dropdown Opens
// Database: mifmyho2_sengkuclean
// =====================================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("🔄 Status Change Handler - Employee Selection BEFORE Dropdown");

  // ==================== PAGE DETECTION ====================
  const currentPath = window.location.pathname;
  const isTimelinePage = currentPath.includes("timeline_pesanan.php");
  const isDropPage = currentPath.includes("drop.php");

  console.log(
    `🔍 Current page: ${
      isDropPage ? "Drop" : isTimelinePage ? "Timeline" : "Other"
    }`
  );

  // ==================== GLOBAL STATE ====================
  let selectedEmployee = null;
  let employeesCache = null;

  // ==================== MODAL CREATION ====================

  /**
   * Create employee selection modal
   */
  function createEmployeeModal() {
    if (document.getElementById("employeeSelectionModal")) {
      return;
    }

    const modalHTML = `
      <div id="employeeSelectionModal" class="employee-modal-overlay" style="display: none;">
        <div class="employee-modal">
          <div class="employee-modal-header">
            <span class="employee-modal-icon">👤</span>
            <h3 class="employee-modal-title">Pilih Karyawan</h3>
            <button class="employee-modal-close" id="employeeModalClose">×</button>
          </div>
          <div class="employee-modal-body">
            <p class="employee-modal-text">Pilih karyawan yang sudah checkin hari ini untuk memproses perubahan status:</p>
            <div id="employeeListContainer" class="employee-list-container">
              <!-- Employee list will be inserted here -->
            </div>
          </div>
          <div class="employee-modal-footer">
            <button class="employee-btn employee-btn-cancel" id="employeeCancelBtn">
              ✕ Batal
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
    injectEmployeeModalStyles();
  }

  /**
   * Create status confirmation modal
   */
  function createConfirmModal() {
    if (document.getElementById("statusConfirmModal")) {
      return;
    }

    const modalHTML = `
      <div id="statusConfirmModal" class="status-confirm-overlay" style="display: none;">
        <div class="status-confirm-modal">
          <div class="status-confirm-header">
            <span class="status-confirm-icon">⚠️</span>
            <h3 class="status-confirm-title">Konfirmasi Perubahan Status</h3>
          </div>
          <div class="status-confirm-body">
            <p id="statusConfirmMessage">Ubah status item ini?</p>
          </div>
          <div class="status-confirm-footer">
            <button class="status-btn status-btn-cancel" id="statusConfirmCancel">
              ✕ Batal
            </button>
            <button class="status-btn status-btn-confirm" id="statusConfirmOK">
              ✓ Ubah Status
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
    injectModalStyles();
  }

  /**
   * Inject employee modal styles
   */
  function injectEmployeeModalStyles() {
    if (document.getElementById("employeeModalStyles")) {
      return;
    }

    const styles = `
      <style id="employeeModalStyles">
        .employee-modal-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.65);
          backdrop-filter: blur(4px);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 10000;
          animation: fadeIn 0.2s ease;
        }

        .employee-modal {
          background: white;
          border-radius: 16px;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
          max-width: 500px;
          width: 90%;
          max-height: 80vh;
          display: flex;
          flex-direction: column;
          animation: slideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .employee-modal-header {
          background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
          color: white;
          padding: 24px;
          display: flex;
          align-items: center;
          gap: 12px;
          border-radius: 16px 16px 0 0;
          position: relative;
        }

        .employee-modal-icon {
          font-size: 32px;
          line-height: 1;
        }

        .employee-modal-title {
          margin: 0;
          font-size: 20px;
          font-weight: 700;
          letter-spacing: -0.02em;
          flex: 1;
        }

        .employee-modal-close {
          background: rgba(255, 255, 255, 0.2);
          border: none;
          color: white;
          font-size: 28px;
          width: 36px;
          height: 36px;
          border-radius: 8px;
          cursor: pointer;
          transition: all 0.2s;
          display: flex;
          align-items: center;
          justify-content: center;
          line-height: 1;
        }

        .employee-modal-close:hover {
          background: rgba(255, 255, 255, 0.3);
          transform: rotate(90deg);
        }

        .employee-modal-body {
          padding: 24px;
          overflow-y: auto;
          flex: 1;
        }

        .employee-modal-text {
          margin: 0 0 16px 0;
          font-size: 14px;
          color: #475569;
          font-weight: 500;
        }

        .employee-list-container {
          display: flex;
          flex-direction: column;
          gap: 10px;
        }

        .employee-card {
          background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
          border: 2px solid #e2e8f0;
          border-radius: 12px;
          padding: 16px;
          cursor: pointer;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .employee-card:hover {
          background: linear-gradient(135deg, #e6f2ff 0%, #d9e9ff 100%);
          border-color: #0066cc;
          transform: translateX(4px);
          box-shadow: 0 4px 12px rgba(0, 102, 204, 0.15);
        }

        .employee-card:active {
          transform: translateX(2px);
        }

        .employee-avatar {
          width: 48px;
          height: 48px;
          border-radius: 50%;
          background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
          color: white;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 20px;
          font-weight: 700;
          flex-shrink: 0;
        }

        .employee-info {
          flex: 1;
        }

        .employee-name {
          font-size: 15px;
          font-weight: 700;
          color: #1e293b;
          margin: 0 0 4px 0;
        }

        .employee-code {
          font-size: 12px;
          color: #64748b;
          font-weight: 600;
        }

        .employee-modal-footer {
          padding: 16px 24px;
          background: #f8fafc;
          border-radius: 0 0 16px 16px;
          display: flex;
          justify-content: flex-end;
        }

        .employee-btn {
          padding: 10px 24px;
          border: none;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
          display: inline-flex;
          align-items: center;
          gap: 8px;
        }

        .employee-btn-cancel {
          background: #e2e8f0;
          color: #475569;
        }

        .employee-btn-cancel:hover {
          background: #cbd5e1;
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(71, 85, 105, 0.2);
        }

        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }

        @keyframes slideIn {
          from {
            transform: scale(0.9) translateY(-20px);
            opacity: 0;
          }
          to {
            transform: scale(1) translateY(0);
            opacity: 1;
          }
        }
      </style>
    `;

    document.head.insertAdjacentHTML("beforeend", styles);
  }

  /**
   * Inject status confirmation modal styles
   */
  function injectModalStyles() {
    if (document.getElementById("statusConfirmStyles")) {
      return;
    }

    const styles = `
      <style id="statusConfirmStyles">
        .status-confirm-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.65);
          backdrop-filter: blur(4px);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 10001;
          animation: fadeIn 0.2s ease;
        }

        .status-confirm-modal {
          background: white;
          border-radius: 16px;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
          max-width: 500px;
          width: 90%;
          animation: slideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
          overflow: hidden;
        }

        .status-confirm-header {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          padding: 24px;
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .status-confirm-icon {
          font-size: 32px;
          line-height: 1;
          animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.1); }
        }

        .status-confirm-title {
          margin: 0;
          font-size: 20px;
          font-weight: 700;
          letter-spacing: -0.02em;
        }

        .status-confirm-body {
          padding: 28px;
        }

        .status-confirm-body p {
          margin: 0;
          font-size: 16px;
          line-height: 1.7;
          color: #334155;
        }

        .status-confirm-body strong {
          color: #0369a1;
          font-weight: 700;
        }

        .status-confirm-footer {
          padding: 16px 24px 24px;
          display: flex;
          gap: 12px;
          justify-content: flex-end;
          background: #f8fafc;
        }

        .status-btn {
          padding: 12px 28px;
          border: none;
          border-radius: 10px;
          font-size: 15px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
          display: inline-flex;
          align-items: center;
          gap: 8px;
          letter-spacing: -0.01em;
        }

        .status-btn-cancel {
          background: #e2e8f0;
          color: #475569;
        }

        .status-btn-cancel:hover {
          background: #cbd5e1;
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(71, 85, 105, 0.2);
        }

        .status-btn-confirm {
          background: linear-gradient(135deg, #10b981 0%, #059669 100%);
          color: white;
          box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
        }

        .status-btn-confirm:hover {
          background: linear-gradient(135deg, #059669 0%, #047857 100%);
          transform: translateY(-2px);
          box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .status-btn:active {
          transform: scale(0.96) !important;
        }
      </style>
    `;

    document.head.insertAdjacentHTML("beforeend", styles);
  }

  // ==================== EMPLOYEE SELECTION ====================

  /**
   * Fetch active employees from server
   */
  async function fetchActiveEmployees() {
    // Return from cache if available
    if (employeesCache) {
      console.log("📦 Using cached employees data");
      return employeesCache;
    }

    try {
      // Detect correct path based on current location
      const currentPath = window.location.pathname;
      let apiPath = "";

      if (currentPath.includes("/admin/")) {
        // We're in /admin/ folder, so actions is one level up
        apiPath = "../actions/drop/get_active_employees.php";
      } else {
        // We're likely in root, adjust accordingly
        apiPath = "./actions/drop/get_active_employees.php";
      }

      console.log(`📡 Fetching employees from: ${apiPath}`);

      const response = await fetch(apiPath);

      // Check if response is OK
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        const text = await response.text();
        console.error("❌ Non-JSON response:", text.substring(0, 200));
        throw new Error("Server tidak mengembalikan JSON. Periksa path file.");
      }

      const data = await response.json();

      if (data.success) {
        employeesCache = data.employees;
        return data.employees;
      } else {
        throw new Error(data.message || "Gagal mengambil data karyawan");
      }
    } catch (error) {
      console.error("❌ Error fetching employees:", error);
      throw error;
    }
  }

  /**
   * Show employee selection modal
   */
  function showEmployeeModal(employees) {
    return new Promise((resolve, reject) => {
      createEmployeeModal();

      const modal = document.getElementById("employeeSelectionModal");
      const listContainer = document.getElementById("employeeListContainer");
      const closeBtn = document.getElementById("employeeModalClose");
      const cancelBtn = document.getElementById("employeeCancelBtn");

      if (!modal || !listContainer) {
        reject(new Error("Modal elements not found"));
        return;
      }

      // Clear previous content
      listContainer.innerHTML = "";

      // Create employee cards
      employees.forEach((emp) => {
        const card = document.createElement("div");
        card.className = "employee-card";
        card.dataset.employeeId = emp.id_employee;

        const initial = emp.name.charAt(0).toUpperCase();

        // Format check-in time if available
        let checkinInfo = "";
        if (emp.check_in_time) {
          const time = new Date(emp.check_in_time);
          const timeStr = time.toLocaleTimeString("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
          });
          checkinInfo = `<div style="font-size: 11px; color: #10b981; font-weight: 600; margin-top: 2px;">✓ Checkin: ${timeStr}</div>`;
        }

        card.innerHTML = `
          <div class="employee-avatar">${initial}</div>
          <div class="employee-info">
            <div class="employee-name">${emp.name}</div>
            <div class="employee-code">Kode: ${emp.employee_code}</div>
            ${checkinInfo}
          </div>
        `;

        card.addEventListener("click", () => {
          closeModal();
          resolve({
            id: emp.id_employee,
            name: emp.name,
            code: emp.employee_code,
          });
        });

        listContainer.appendChild(card);
      });

      // Show modal
      modal.style.display = "flex";

      const closeModal = () => {
        modal.style.display = "none";
        cleanup();
      };

      const handleCancel = () => {
        closeModal();
        reject(new Error("Employee selection cancelled"));
      };

      const handleOverlayClick = (e) => {
        if (e.target === modal) {
          handleCancel();
        }
      };

      const handleEscape = (e) => {
        if (e.key === "Escape") {
          handleCancel();
        }
      };

      const cleanup = () => {
        closeBtn.removeEventListener("click", handleCancel);
        cancelBtn.removeEventListener("click", handleCancel);
        modal.removeEventListener("click", handleOverlayClick);
        document.removeEventListener("keydown", handleEscape);
      };

      // Attach event listeners
      closeBtn.addEventListener("click", handleCancel);
      cancelBtn.addEventListener("click", handleCancel);
      modal.addEventListener("click", handleOverlayClick);
      document.addEventListener("keydown", handleEscape);
    });
  }

  /**
   * Handle employee selection process
   */
  async function selectEmployee() {
    try {
      console.log("👤 Fetching employees who checked in today...");
      const employees = await fetchActiveEmployees();

      console.log(`✅ Found ${employees.length} employee(s) who checked in`);

      if (employees.length === 0) {
        // No employees checked in today
        if (typeof showNotification === "function") {
          showNotification(
            "⚠️ Tidak ada karyawan yang absen hari ini. Silakan checkin terlebih dahulu.",
            "warning"
          );
        } else {
          alert(
            "Tidak ada karyawan yang absen hari ini.\n\nSilakan checkin terlebih dahulu."
          );
        }
        throw new Error("Tidak ada karyawan yang checkin");
      }

      // If only one employee, auto-select
      if (employees.length === 1) {
        console.log(
          "ℹ️ Auto-selecting single checked-in employee:",
          employees[0].name
        );
        return {
          id: employees[0].id_employee,
          name: employees[0].name,
          code: employees[0].employee_code,
        };
      }

      // Multiple employees, show selection modal
      console.log("👥 Multiple employees checked in, showing selection modal");
      return await showEmployeeModal(employees);
    } catch (error) {
      console.error("❌ Error in employee selection:", error);
      throw error;
    }
  }

  // ==================== STATUS CONFIRMATION ====================

  /**
   * Show status change confirmation
   */
  function customConfirm(message) {
    return new Promise((resolve) => {
      createConfirmModal();

      const modal = document.getElementById("statusConfirmModal");
      const messageEl = document.getElementById("statusConfirmMessage");
      const btnOK = document.getElementById("statusConfirmOK");
      const btnCancel = document.getElementById("statusConfirmCancel");

      if (!modal || !messageEl || !btnOK || !btnCancel) {
        console.error("❌ Modal elements not found");
        resolve(false);
        return;
      }

      messageEl.innerHTML = message;
      modal.style.display = "flex";

      const handleOK = () => {
        closeModal();
        resolve(true);
      };

      const handleCancel = () => {
        closeModal();
        resolve(false);
      };

      const handleOverlayClick = (e) => {
        if (e.target === modal) {
          handleCancel();
        }
      };

      const handleEscape = (e) => {
        if (e.key === "Escape") {
          handleCancel();
        }
      };

      const closeModal = () => {
        modal.style.display = "none";
        cleanup();
      };

      const cleanup = () => {
        btnOK.removeEventListener("click", handleOK);
        btnCancel.removeEventListener("click", handleCancel);
        modal.removeEventListener("click", handleOverlayClick);
        document.removeEventListener("keydown", handleEscape);
      };

      btnOK.addEventListener("click", handleOK);
      btnCancel.addEventListener("click", handleCancel);
      modal.addEventListener("click", handleOverlayClick);
      document.addEventListener("keydown", handleEscape);

      setTimeout(() => btnOK.focus(), 100);
    });
  }

  // ==================== STATUS UPDATE ====================

  /**
   * Update item status via API
   */
  async function updateItemStatus(
    selectElement,
    itemId,
    newStatusId,
    oldStatusId,
    itemBrand,
    employeeId
  ) {
    const originalStyles = {
      bg: selectElement.style.backgroundColor,
      color: selectElement.style.color,
      opacity: selectElement.style.opacity,
      cursor: selectElement.style.cursor,
    };

    setLoadingState(selectElement, true);

    const formData = new FormData();
    formData.append("item_id", itemId);
    formData.append("status_id", newStatusId);
    formData.append("employee_id", employeeId);

    console.log("📤 Sending status update request with employee:", employeeId);

    try {
      const response = await fetch(
        "../actions/drop/drop_update_item_status.php",
        {
          method: "POST",
          body: formData,
        }
      );

      const text = await response.text();
      console.log("📥 Response received:", text.substring(0, 200));

      const data = parseJsonResponse(text);

      if (data.success) {
        console.log("✅ Status updated successfully");

        selectElement.dataset.oldStatus = newStatusId;
        selectElement.dataset.currentStatus = newStatusId;

        showSuccessFeedback(selectElement);
        saveNotificationToSession(data.message);

        setTimeout(() => {
          console.log("🔄 Reloading page to refresh data...");
          window.location.reload();
        }, 700);
      } else {
        throw new Error(data.message || "Gagal update status");
      }
    } catch (error) {
      console.error("❌ Error updating status:", error);
      handleUpdateError(error, selectElement, oldStatusId, originalStyles);
    }
  }

  function setLoadingState(element, isLoading) {
    element.disabled = isLoading;
    element.style.opacity = isLoading ? "0.6" : "1";
    element.style.cursor = isLoading ? "wait" : "";
  }

  function parseJsonResponse(text) {
    try {
      return JSON.parse(text);
    } catch (error) {
      console.error("❌ Invalid JSON response:", text);
      throw new Error("Server mengembalikan response tidak valid");
    }
  }

  function showSuccessFeedback(element) {
    element.style.backgroundColor = "#10b981";
    element.style.color = "white";
    element.style.transition = "all 0.3s ease";

    setTimeout(() => {
      element.style.backgroundColor = "";
      element.style.color = "";
      element.style.opacity = "1";
      element.style.cursor = "";
      element.disabled = false;
    }, 300);
  }

  function handleUpdateError(error, element, oldStatusId, originalStyles) {
    const errorMessage = `❌ ${error.message}`;

    if (typeof showNotification === "function") {
      showNotification(errorMessage, "error");
    } else {
      alert(errorMessage);
    }

    element.value = oldStatusId;
    element.style.backgroundColor = originalStyles.bg;
    element.style.color = originalStyles.color;
    element.style.opacity = originalStyles.opacity;
    element.style.cursor = originalStyles.cursor;
    element.disabled = false;
  }

  function saveNotificationToSession(message) {
    const notificationData = [
      {
        condition: isDropPage,
        key: "dropNotification",
        typeKey: "dropNotificationType",
      },
      {
        condition: isTimelinePage,
        key: "timelineNotification",
        typeKey: "timelineNotificationType",
      },
    ];

    const activeNotification = notificationData.find((n) => n.condition);

    if (activeNotification) {
      sessionStorage.setItem(activeNotification.key, `✅ ${message}`);
      sessionStorage.setItem(activeNotification.typeKey, "success");
      console.log(
        `💾 Saved notification for ${isDropPage ? "Drop" : "Timeline"} page`
      );
    }
  }

  // ==================== DROPDOWN INTERCEPTOR ====================

  /**
   * Attach click interceptor to dropdown
   * This prevents dropdown from opening until employee is selected
   */
  function attachDropdownInterceptor(select) {
    if (select.dataset.interceptorAttached === "true") {
      return;
    }

    // Intercept mousedown and click events
    const handleInterceptClick = async (e) => {
      // Check if employee already selected for this interaction
      if (select.dataset.employeeSelected === "true") {
        // Allow dropdown to open normally
        console.log("✅ Employee already selected, allowing dropdown to open");
        return;
      }

      // Prevent dropdown from opening
      e.preventDefault();
      e.stopPropagation();

      console.log("🚫 Intercepting dropdown click - selecting employee first");

      try {
        // Select employee first
        const employee = await selectEmployee();

        // Check if no employees available (null return)
        if (employee === null) {
          console.log("⚠️ No employees checked in - blocking dropdown");

          // Show single notification
          if (typeof showNotification === "function") {
            showNotification(
              "⚠️ Tidak ada karyawan yang checkin hari ini. Silakan checkin terlebih dahulu.",
              "warning"
            );
          } else {
            alert(
              "Tidak ada karyawan yang checkin hari ini.\n\nSilakan checkin terlebih dahulu."
            );
          }

          return; // Exit silently without error
        }

        console.log("✅ Employee selected:", employee);

        // Store selected employee for this dropdown
        selectedEmployee = employee;
        select.dataset.employeeId = employee.id;
        select.dataset.employeeName = employee.name;
        select.dataset.employeeSelected = "true";

        // Check employee count
        const employees = await fetchActiveEmployees();

        if (employees.length === 1) {
          // Single employee: Show notification and allow selection
          console.log(
            "ℹ️ Single employee mode - allowing dropdown to open immediately"
          );

          // Programmatically open the dropdown
          select.focus();

          // For browsers that support showPicker
          if (select.showPicker) {
            try {
              select.showPicker();
            } catch (err) {
              // Fallback: trigger click event
              select.click();
            }
          } else {
            // Fallback for browsers without showPicker
            const clickEvent = new MouseEvent("mousedown", {
              bubbles: true,
              cancelable: true,
              view: window,
            });
            select.dispatchEvent(clickEvent);
          }
        } else {
          // Multiple employees: Show success message and open dropdown
          console.log(
            "👥 Multiple employees - employee selected, opening dropdown"
          );

          if (typeof showNotification === "function") {
            showNotification(`Karyawan terpilih: ${employee.name}`, "success");
          }

          // Open dropdown after short delay
          setTimeout(() => {
            select.focus();

            if (select.showPicker) {
              try {
                select.showPicker();
              } catch (err) {
                select.click();
              }
            } else {
              const clickEvent = new MouseEvent("mousedown", {
                bubbles: true,
                cancelable: true,
                view: window,
              });
              select.dispatchEvent(clickEvent);
            }
          }, 100);
        }
      } catch (error) {
        // User cancelled selection - this is normal, don't show error
        if (error.message === "Employee selection cancelled") {
          console.log("ℹ️ User cancelled employee selection");
          return; // Exit silently
        }

        // Real error - show notification
        console.error("❌ Error in dropdown interceptor:", error);

        if (typeof showNotification === "function") {
          showNotification(`❌ ${error.message}`, "error");
        } else {
          alert(`Error: ${error.message}`);
        }
      }
    };

    // Attach interceptor
    select.addEventListener("mousedown", handleInterceptClick, true);
    select.addEventListener("click", handleInterceptClick, true);

    select.dataset.interceptorAttached = "true";
    console.log("🔒 Dropdown interceptor attached");
  }

  /**
   * Attach status change handler (after dropdown is opened)
   */
  function attachStatusChangeListener(select) {
    if (select.dataset.changeListenerAttached === "true") {
      return;
    }

    select.addEventListener("change", async function (e) {
      e.stopPropagation();

      const itemId = this.dataset.itemId;
      const dropId = this.dataset.dropId;
      const newStatusId = this.value;
      const oldStatusId = this.dataset.currentStatus || this.dataset.oldStatus;
      const itemBrand = this.dataset.itemBrand || "Item";
      const newStatusText =
        this.options[this.selectedIndex]?.text || "Status Baru";

      console.log(`📊 Status change request:`, {
        itemId,
        itemBrand,
        oldStatus: oldStatusId,
        newStatus: newStatusId,
        newStatusText,
      });

      // Get employee (should already be selected)
      const employeeId = selectedEmployee?.id || this.dataset.employeeId;
      const employeeName = selectedEmployee?.name || this.dataset.employeeName;

      if (!employeeId) {
        console.error("❌ No employee selected!");
        this.value = oldStatusId;
        if (typeof showNotification === "function") {
          showNotification("❌ Karyawan tidak terpilih!", "error");
        }
        return;
      }

      console.log("👤 Using employee:", { id: employeeId, name: employeeName });

      // Show confirmation
      const confirmed = await customConfirm(
        `Ubah status <strong>"${itemBrand}"</strong> menjadi <strong>"${newStatusText}"</strong>?<br><br>Karyawan: <strong>${employeeName}</strong>`
      );

      if (!confirmed) {
        console.log("❌ Status change cancelled by user");
        this.value = oldStatusId;

        // Reset employee selection for next time
        this.dataset.employeeSelected = "false";
        selectedEmployee = null;
        return;
      }

      // Update status
      await updateItemStatus(
        this,
        itemId,
        newStatusId,
        oldStatusId,
        itemBrand,
        employeeId
      );

      // Reset for next interaction
      this.dataset.employeeSelected = "false";
      selectedEmployee = null;
    });

    select.dataset.changeListenerAttached = "true";
  }

  // ==================== INITIALIZATION ====================

  function initializeStatusSelects() {
    const existingSelects = document.querySelectorAll(".item-status-select");
    console.log(`🔍 Found ${existingSelects.length} status select element(s)`);

    existingSelects.forEach((select, index) => {
      console.log(`  ✓ Initializing select #${index + 1}`, {
        itemId: select.dataset.itemId,
        currentStatus: select.dataset.currentStatus,
      });

      // Attach both interceptor and change listener
      attachDropdownInterceptor(select);
      attachStatusChangeListener(select);
    });
  }

  initializeStatusSelects();

  // ==================== MUTATION OBSERVER ====================

  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      mutation.addedNodes.forEach(function (node) {
        if (node.nodeType !== Node.ELEMENT_NODE) return;

        if (node.classList?.contains("item-status-select")) {
          console.log("🆕 New status select detected");
          attachDropdownInterceptor(node);
          attachStatusChangeListener(node);
        }

        const selects = node.querySelectorAll?.(".item-status-select");
        if (selects?.length > 0) {
          console.log(`🆕 ${selects.length} new status select(s) detected`);
          selects.forEach((select) => {
            attachDropdownInterceptor(select);
            attachStatusChangeListener(select);
          });
        }
      });
    });
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });

  console.log(
    "✅ Status Change Handler Ready - Employee Selection BEFORE Dropdown"
  );
  console.log("📦 Database: mifmyho2_sengkuclean");
  console.log(
    "🎯 Flow: Click Dropdown → Select Employee → Dropdown Opens → Change Status"
  );
});
