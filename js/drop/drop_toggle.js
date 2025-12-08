// File: /js/drop/drop_toggle.js
// Toggle functionality untuk completed items (status_id = 6)
// Version: 2.0 - Bootstrap Icons Only (No Emoji)

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Drop toggle module loaded");

  const toggleCheckbox = document.getElementById("toggleCompleted");

  if (!toggleCheckbox) {
    console.warn("⚠️ Toggle checkbox not found");
    return;
  }

  // ===== BOOTSTRAP ICONS SVG =====
  const ICONS = {
    search:
      '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>',
    inboxEmpty:
      '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-inbox" viewBox="0 0 16 16"><path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374z"/></svg>',
  };

  // ===== MARK COMPLETED ITEMS ON LOAD =====
  function markCompletedItems() {
    // Cari semua dropdown status yang valuenya adalah 6 (Diambil/Selesai)
    const allStatusSelects = document.querySelectorAll(".item-status-select");

    allStatusSelects.forEach((select) => {
      const statusId = select.value;
      const itemRow = select.closest(".order-item-row");

      if (itemRow) {
        if (statusId === "6") {
          itemRow.setAttribute("data-status", "completed");
          itemRow.classList.add("item-completed");

          // Add completed badge if not exists
          if (!itemRow.querySelector(".completed-badge-overlay")) {
            const badge = document.createElement("div");
            badge.className = "completed-badge-overlay";

            itemRow.appendChild(badge);
          }
        } else {
          itemRow.setAttribute("data-status", "active");
          itemRow.classList.remove("item-completed");

          // Remove badge if exists
          const badge = itemRow.querySelector(".completed-badge-overlay");
          if (badge) {
            badge.remove();
          }
        }
      }
    });

    console.log(
      `✅ Marked ${
        document.querySelectorAll('[data-status="completed"]').length
      } completed items`
    );
  }

  // Mark completed items on load
  markCompletedItems();

  // ===== LOAD SAVED STATE FROM LOCALSTORAGE =====
  // Default: HIDE completed items (unchecked)
  const savedState = localStorage.getItem("showCompletedItems");
  if (savedState !== null) {
    toggleCheckbox.checked = savedState === "true";
  } else {
    // Default state: UNCHECKED (hide completed)
    toggleCheckbox.checked = false;
    localStorage.setItem("showCompletedItems", "false");
  }

  // ===== UPDATE DISPLAY FUNCTION =====
  function updateCompletedItemsDisplay() {
    const showCompleted = toggleCheckbox.checked;

    console.log(
      `🔄 Toggling completed items: ${showCompleted ? "SHOW" : "HIDE"}`
    );

    // Save state
    localStorage.setItem("showCompletedItems", showCompleted);

    // Get all completed items (status_id = 6)
    const completedItems = document.querySelectorAll(
      '.order-item-row[data-status="completed"]'
    );

    console.log(`📦 Found ${completedItems.length} completed items`);

    // Toggle visibility untuk completed items
    completedItems.forEach((item) => {
      if (showCompleted) {
        item.classList.remove("hidden");
        console.log(`👁️ Showing item: ${item.dataset.itemId}`);
      } else {
        item.classList.add("hidden");
        console.log(`🙈 Hiding item: ${item.dataset.itemId}`);
      }
    });

    // Check each customer group
    const customerContainers = document.querySelectorAll(
      ".customer-orders-container"
    );
    let hiddenCustomers = 0;

    customerContainers.forEach((container) => {
      const customerId = container.getAttribute("data-customer-id");
      const header = container.previousElementSibling;

      if (!header || !header.classList.contains("customer-group-header")) {
        return;
      }

      // Get all items untuk customer ini
      const allItems = container.querySelectorAll(".order-item-row");
      const visibleItems = Array.from(allItems).filter(
        (item) => !item.classList.contains("hidden")
      );

      console.log(
        `👥 Customer ${customerId}: ${visibleItems.length}/${allItems.length} visible items`
      );

      // Jika tidak ada visible items, hide customer group
      if (visibleItems.length === 0) {
        header.classList.add("hidden");
        container.classList.add("hidden");
        hiddenCustomers++;
      } else {
        header.classList.remove("hidden");
        container.classList.remove("hidden");
      }
    });

    console.log(`📊 Completed items: ${completedItems.length}`);
    console.log(`👥 Hidden customer groups: ${hiddenCustomers}`);

    // Show empty state jika semua customer hidden
    checkEmptyState();
  }

  // ===== CHECK EMPTY STATE =====
  function checkEmptyState() {
    const visibleGroups = document.querySelectorAll(
      ".customer-group-header:not(.hidden)"
    );
    const tableContainer = document.querySelector(".table-container");
    let emptyMessage = document.getElementById("toggleEmptyMessage");

    if (visibleGroups.length === 0) {
      // Create empty message jika belum ada
      if (!emptyMessage) {
        emptyMessage = document.createElement("div");
        emptyMessage.id = "toggleEmptyMessage";
        emptyMessage.className = "no-results-message";
        emptyMessage.innerHTML = `
          <div class="empty-state-icon">${ICONS.inboxEmpty}</div>
          <div class="empty-state-title">Tidak ada pesanan aktif</div>
          <small class="empty-state-subtitle">
            Centang "Tampilkan barang yang sudah diambil" untuk melihat pesanan yang sudah selesai
          </small>
        `;
        tableContainer.appendChild(emptyMessage);

        // Inject empty state styles if not exists
        injectEmptyStateStyles();
      }
      emptyMessage.style.display = "flex";
    } else {
      if (emptyMessage) {
        emptyMessage.style.display = "none";
      }
    }
  }

  // ===== INJECT EMPTY STATE STYLES =====
  function injectEmptyStateStyles() {
    if (document.getElementById("dropToggleEmptyStyles")) {
      return;
    }

    const styles = `
      <style id="dropToggleEmptyStyles">
        .no-results-message {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          padding: 60px 20px;
          text-align: center;
        }

        .empty-state-icon {
          width: 80px;
          height: 80px;
          margin-bottom: 20px;
          color: #cbd5e1;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .empty-state-icon svg {
          width: 64px;
          height: 64px;
        }

        .empty-state-title {
          font-size: 18px;
          font-weight: 600;
          color: #475569;
          margin-bottom: 8px;
        }

        .empty-state-subtitle {
          color: #94a3b8;
          font-size: 14px;
          max-width: 400px;
          line-height: 1.5;
        }
      </style>
    `;

    document.head.insertAdjacentHTML("beforeend", styles);
  }

  // ===== TOGGLE CHECKBOX EVENT =====
  toggleCheckbox.addEventListener("change", function () {
    console.log("🎛️ Toggle changed:", this.checked);
    updateCompletedItemsDisplay();
  });

  // ===== LABEL CLICK EVENT =====
  const toggleLabel = document.querySelector(".toggle-label");
  if (toggleLabel) {
    toggleLabel.addEventListener("click", function () {
      toggleCheckbox.checked = !toggleCheckbox.checked;
      toggleCheckbox.dispatchEvent(new Event("change"));
    });
  }

  // ===== INITIAL DISPLAY =====
  updateCompletedItemsDisplay();

  console.log("✅ Toggle functionality initialized");
  console.log(
    `📌 Initial state: ${
      toggleCheckbox.checked ? "Show completed" : "Hide completed"
    }`
  );

  // ===== OBSERVE STATUS CHANGES =====
  const statusObserver = new MutationObserver(function (mutations) {
    let statusChanged = false;

    mutations.forEach(function (mutation) {
      if (
        mutation.type === "attributes" &&
        mutation.attributeName === "value"
      ) {
        const select = mutation.target;
        if (select.classList.contains("item-status-select")) {
          statusChanged = true;
          console.log("🔄 Status dropdown changed, re-marking items");
        }
      }
    });

    if (statusChanged) {
      // Re-mark items setelah status berubah
      setTimeout(() => {
        markCompletedItems();
        updateCompletedItemsDisplay();
      }, 100);
    }
  });

  // Observe all status selects
  document.querySelectorAll(".item-status-select").forEach((select) => {
    statusObserver.observe(select, {
      attributes: true,
      attributeFilter: ["value"],
    });
  });

  // ===== KEYBOARD SHORTCUT: Ctrl+H untuk toggle =====
  document.addEventListener("keydown", function (e) {
    if (e.ctrlKey && e.key === "h") {
      e.preventDefault();
      toggleCheckbox.checked = !toggleCheckbox.checked;
      toggleCheckbox.dispatchEvent(new Event("change"));
      console.log("⌨️ Keyboard shortcut triggered (Ctrl+H)");
    }
  });

  console.log("⌨️ Keyboard shortcut: Ctrl+H to toggle completed items");
  console.log("🎨 Using Bootstrap Icons (No Emoji) - v2.0");
});
