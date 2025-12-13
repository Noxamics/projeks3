// =====================================================================
// File: /js/drop/drop_delete.js
// Drop Item & Customer Delete Handler - IMPROVED VERSION
// Version: 4.1 - Enhanced Error Handling & Code Quality
// Database: mifmyho2_sengkuclean
// =====================================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("🔰 Drop Delete Module Initialized (v4.1 - Enhanced)");

  // ==================== CONSTANTS ====================

  const ANIMATION_DURATION = 350; // ms
  const RELOAD_DELAY = 300; // ms

  const MESSAGES = {
    deleteCustomer: {
      confirm:
        "Anda akan menghapus <strong>SEMUA pesanan</strong> dari customer ini.<br><br>" +
        "<span style='color: #dc2626; font-weight: 600;'>⚠️ Semua data pesanan akan dihapus dan tidak dapat dikembalikan!</span>",
      title: "Hapus Semua Pesanan?",
      icon: "🗑️",
      loading: "Menghapus semua pesanan...",
      success: "Semua pesanan berhasil dihapus!",
      error: "Gagal menghapus pesanan",
    },
    deleteItem: {
      confirm:
        "Anda akan menghapus pesanan yang dipilih.<br><br>" +
        "<span style='color: #dc2626; font-weight: 600;'>⚠️ Data yang dihapus tidak dapat dikembalikan!</span>",
      title: "Hapus Item?",
      icon: "🗑️",
      loading: "Menghapus item...",
      success: "Item berhasil dihapus!",
      successLast: "Item terakhir dihapus — pesanan juga dihapus.",
      error: "Gagal menghapus item",
    },
    errors: {
      nonJson: "Response bukan JSON. Cek server error log.",
      network: "Terjadi kesalahan jaringan",
      unknown: "Terjadi kesalahan yang tidak diketahui",
    },
  };

  // ==================== EVENT DELEGATION ====================

  /**
   * Event listener untuk delete customer (all orders)
   */
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".delete-customer-btn");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const customerId = btn.dataset.customerId;

    if (!customerId) {
      console.error("❌ Customer ID not found in button");
      await customError("ID customer tidak ditemukan", "Error");
      return;
    }

    // Show confirmation
    const confirmed = await customConfirm(
      MESSAGES.deleteCustomer.confirm,
      MESSAGES.deleteCustomer.title,
      MESSAGES.deleteCustomer.icon
    );

    if (confirmed) {
      await deleteAllCustomerOrders(customerId);
    }
  });

  /**
   * Event listener untuk delete single item
   */
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".delete-item-btn");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const itemId = btn.dataset.itemId;
    const dropId = btn.dataset.dropId;
    const customerId = btn.dataset.customerId;

    if (!itemId || !dropId || !customerId) {
      console.error("❌ Missing required IDs in button", {
        itemId,
        dropId,
        customerId,
      });
      await customError("Data tidak lengkap", "Error");
      return;
    }

    // Show confirmation
    const confirmed = await customConfirm(
      MESSAGES.deleteItem.confirm,
      MESSAGES.deleteItem.title,
      MESSAGES.deleteItem.icon
    );

    if (confirmed) {
      await deleteSingleItem(itemId, dropId, customerId);
    }
  });

  // ==================== DELETE OPERATIONS ====================

  /**
   * Delete all orders for a customer
   * @param {string|number} customerId - Customer ID
   * @returns {Promise<void>}
   */
  async function deleteAllCustomerOrders(customerId) {
    console.log("🗑️ Deleting ALL orders for customer:", customerId);
    showLoading(MESSAGES.deleteCustomer.loading);

    const formData = new FormData();
    formData.append("action", "delete_all_customer");
    formData.append("customer_id", customerId);

    try {
      const response = await fetchWithTimeout(window.location.href, {
        method: "POST",
        body: formData,
      });

      const data = await validateJSON(response);
      hideLoading();

      if (data.success) {
        await customSuccess(
          data.message || MESSAGES.deleteCustomer.success,
          "Berhasil Dihapus!"
        );

        // Reload page after delay
        setTimeout(() => {
          location.reload();
        }, RELOAD_DELAY);
      } else {
        throw new Error(data.message || MESSAGES.deleteCustomer.error);
      }
    } catch (error) {
      hideLoading();
      console.error("❌ Delete all customer orders error:", error);
      await customError(
        `Terjadi kesalahan saat menghapus data:\n\n${error.message}`,
        "Error Sistem"
      );
    }
  }

  /**
   * Delete a single item
   * @param {string|number} itemId - Item ID
   * @param {string|number} dropId - Drop ID
   * @param {string|number} customerId - Customer ID
   * @returns {Promise<void>}
   */
  async function deleteSingleItem(itemId, dropId, customerId) {
    console.log(
      `🗑️ Deleting item ${itemId} in drop ${dropId} (customer ${customerId})`
    );
    showLoading(MESSAGES.deleteItem.loading);

    const formData = new FormData();
    formData.append("action", "delete_single_item");
    formData.append("item_id", itemId);
    formData.append("drop_id", dropId);

    try {
      const response = await fetchWithTimeout(window.location.href, {
        method: "POST",
        body: formData,
      });

      const data = await validateJSON(response);
      hideLoading();

      if (data.success) {
        // Handle last item deletion (also deletes order)
        if (data.last_item) {
          await customSuccess(
            data.message || MESSAGES.deleteItem.successLast,
            "Berhasil Dihapus!"
          );

          setTimeout(() => {
            location.reload();
          }, RELOAD_DELAY);
          return;
        }

        // Show success notification
        await customSuccess(
          data.message || MESSAGES.deleteItem.success,
          "Berhasil!"
        );

        // Remove row from DOM with animation
        await removeItemFromDOM(itemId, dropId, customerId, data.new_total);
      } else {
        throw new Error(data.message || MESSAGES.deleteItem.error);
      }
    } catch (error) {
      hideLoading();
      console.error("❌ Delete single item error:", error);
      await customError(
        `Terjadi kesalahan saat menghapus:\n\n${error.message}`,
        "Error Sistem"
      );
    }
  }

  // ==================== DOM MANIPULATION ====================

  /**
   * Remove item from DOM with animation
   * @param {string|number} itemId - Item ID
   * @param {string|number} dropId - Drop ID
   * @param {string|number} customerId - Customer ID
   * @param {number} newTotal - New total amount from server
   * @returns {Promise<void>}
   */
  async function removeItemFromDOM(itemId, dropId, customerId, newTotal) {
    const row = document.querySelector(
      `[data-item-id="${itemId}"][data-drop-id="${dropId}"]`
    );

    if (!row) {
      console.warn("⚠️ Row not found, reloading page");
      setTimeout(() => location.reload(), RELOAD_DELAY);
      return;
    }

    // Get price before removing
    const priceVal = parseFloat(row.dataset.price) || 0;

    // Fade out animation
    row.style.opacity = "0";
    row.style.transition = `opacity ${ANIMATION_DURATION}ms ease`;

    // Wait for animation to complete
    await new Promise((resolve) => setTimeout(resolve, ANIMATION_DURATION));

    // Remove from DOM
    row.remove();

    // Update customer totals
    updateCustomerTotals(customerId, dropId, -priceVal);

    // Update total from server if provided
    if (typeof newTotal !== "undefined") {
      updateTotalDisplay(customerId, newTotal);
    }

    // Check if customer has no more items
    checkAndCleanupEmptyCustomer(customerId);
  }

  /**
   * Check if customer has no more items and cleanup
   * @param {string|number} customerId - Customer ID
   */
  function checkAndCleanupEmptyCustomer(customerId) {
    const header = document.querySelector(
      `.customer-group-header input[data-customer-id="${customerId}"]`
    );

    if (!header) return;

    const container = header.closest(
      ".customer-group-header"
    )?.nextElementSibling;
    if (!container) return;

    const remainingItems = container.querySelectorAll(".order-item-row");

    if (remainingItems.length === 0) {
      console.log("🗑️ No more items for customer, removing section");

      // Remove both header and container
      const headerElement = header.closest(".customer-group-header");
      if (headerElement) headerElement.remove();
      if (container) container.remove();

      // Check if page is empty
      checkIfPageEmpty();
    }
  }

  /**
   * Check if page has no more orders and show message
   */
  function checkIfPageEmpty() {
    const allCustomerHeaders = document.querySelectorAll(
      ".customer-group-header"
    );

    if (allCustomerHeaders.length === 0) {
      console.log("📭 No more orders on page");
      setTimeout(() => location.reload(), RELOAD_DELAY);
    }
  }

  /**
   * Update customer totals after item deletion
   * @param {string|number} customerId - Customer ID
   * @param {string|number} dropId - Drop ID (unused but kept for compatibility)
   * @param {number} deltaPrice - Price change (negative for deletion)
   */
  function updateCustomerTotals(customerId, dropId, deltaPrice = 0) {
    console.log("🔄 Updating customer totals:", customerId);

    const header = document.querySelector(
      `.customer-group-header input[data-customer-id="${customerId}"]`
    );

    if (!header) {
      console.warn("⚠️ Customer header not found for ID:", customerId);
      return;
    }

    const headerElement = header.closest(".customer-group-header");
    const container = headerElement?.nextElementSibling;

    if (!container) {
      console.warn("⚠️ Customer container not found");
      return;
    }

    const allItems = container.querySelectorAll(".order-item-row");

    // Update order count badge
    const badge = headerElement.querySelector(".order-count-badge");
    if (badge) {
      const count = allItems.length;
      badge.textContent = `${count} Pesanan`;

      // Update badge color if needed
      if (count === 0) {
        badge.style.background = "#ef4444";
      } else if (count <= 3) {
        badge.style.background = "#f59e0b";
      }
    }

    // Recalculate total price
    let total = 0;
    allItems.forEach((item) => {
      const price = parseFloat(item.dataset.price) || 0;
      total += price;
    });

    // Apply delta if provided
    if (deltaPrice !== 0) {
      total = Math.max(0, total + deltaPrice);
    }

    // Update total display
    const totalEl = container.querySelector(".total-amount");
    if (totalEl) {
      totalEl.textContent = `Rp ${total.toLocaleString("id-ID")}`;
    }

    // Update item numbering
    allItems.forEach((item, index) => {
      const badgeNo = item.querySelector(".item-number-badge");
      if (badgeNo) {
        badgeNo.textContent = `#${index + 1}`;
      }
    });
  }

  /**
   * Update total display from server response
   * @param {string|number} customerId - Customer ID
   * @param {number} newTotal - New total amount
   */
  function updateTotalDisplay(customerId, newTotal) {
    try {
      const header = document.querySelector(
        `.customer-group-header input[data-customer-id="${customerId}"]`
      );

      if (!header) {
        console.warn("⚠️ Header not found for total update");
        return;
      }

      const container = header.closest(
        ".customer-group-header"
      )?.nextElementSibling;
      if (!container) {
        console.warn("⚠️ Container not found for total update");
        return;
      }

      const totalEl = container.querySelector(".total-amount");
      if (totalEl) {
        const formattedTotal = Number(newTotal).toLocaleString("id-ID");
        totalEl.textContent = `Rp ${formattedTotal}`;

        // Add update animation
        totalEl.style.transition = "color 0.3s ease";
        totalEl.style.color = "#059669";

        setTimeout(() => {
          totalEl.style.color = "";
        }, 1000);
      }
    } catch (error) {
      console.warn("⚠️ Could not update total display:", error);
    }
  }

  // ==================== HELPER FUNCTIONS ====================

  /**
   * Validate JSON response from server
   * @param {Response} response - Fetch response object
   * @returns {Promise<Object>} Parsed JSON data
   * @throws {Error} If response is not JSON
   */
  async function validateJSON(response) {
    const contentType = response.headers.get("content-type") || "";

    if (!contentType.includes("application/json")) {
      const text = await response.text();
      console.error("❌ Non-JSON response:", text.substring(0, 500));
      throw new Error(MESSAGES.errors.nonJson);
    }

    if (!response.ok) {
      const data = await response.json();
      throw new Error(
        data.message || `HTTP ${response.status}: ${response.statusText}`
      );
    }

    return response.json();
  }

  /**
   * Fetch with timeout
   * @param {string} url - URL to fetch
   * @param {Object} options - Fetch options
   * @param {number} timeout - Timeout in milliseconds
   * @returns {Promise<Response>}
   */
  async function fetchWithTimeout(url, options = {}, timeout = 30000) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    try {
      const response = await fetch(url, {
        ...options,
        signal: controller.signal,
      });
      clearTimeout(timeoutId);
      return response;
    } catch (error) {
      clearTimeout(timeoutId);
      if (error.name === "AbortError") {
        throw new Error("Request timeout - silakan coba lagi");
      }
      throw error;
    }
  }

  /**
   * Safe element query selector
   * @param {string} selector - CSS selector
   * @param {HTMLElement} context - Context element (default: document)
   * @returns {HTMLElement|null}
   */
  function safeQuerySelector(selector, context = document) {
    try {
      return context.querySelector(selector);
    } catch (error) {
      console.error("❌ Invalid selector:", selector, error);
      return null;
    }
  }

  /**
   * Safe element query selector all
   * @param {string} selector - CSS selector
   * @param {HTMLElement} context - Context element (default: document)
   * @returns {NodeList}
   */
  function safeQuerySelectorAll(selector, context = document) {
    try {
      return context.querySelectorAll(selector);
    } catch (error) {
      console.error("❌ Invalid selector:", selector, error);
      return [];
    }
  }

  // ==================== DEBUG HELPERS ====================

  /**
   * Log delete operation details (for debugging)
   * @param {string} operation - Operation name
   * @param {Object} data - Operation data
   */
  function logDeleteOperation(operation, data) {
    if (typeof console.groupCollapsed === "function") {
      console.groupCollapsed(`🗑️ Delete: ${operation}`);
      console.table(data);
      console.groupEnd();
    } else {
      console.log(`🗑️ Delete: ${operation}`, data);
    }
  }

  // ==================== INITIALIZATION ====================

  // Add global error handler for unhandled promise rejections
  window.addEventListener("unhandledrejection", (event) => {
    console.error(
      "❌ Unhandled promise rejection in delete module:",
      event.reason
    );
    event.preventDefault();
  });

  console.log("✅ Drop Delete Module Ready");
  console.log("📦 Database: mifmyho2_sengkuclean");
  console.log("🔧 Version: 4.1 - Enhanced Error Handling");
});
