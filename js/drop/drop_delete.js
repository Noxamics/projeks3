// =====================================================================
// File: /js/drop/drop_delete.js
// Drop Item & Customer Delete Handler
// Version: 4.0 - Using Universal Modal System
// Database: mifmyho2_sengkuclean
// =====================================================================

document.addEventListener("DOMContentLoaded", function () {
  console.log("🔰 Drop Delete Module Initialized (v4.0 - Universal Modal)");

  // ==================== DELETE CUSTOMER (ALL ORDERS) ====================

  /**
   * Event listener untuk tombol delete semua pesanan customer
   */
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".delete-customer-btn");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const customerId = btn.dataset.customerId;

    // Tampilkan konfirmasi dengan modal system
    const confirmed = await customConfirm(
      "Anda akan menghapus <strong>SEMUA pesanan</strong> dari customer ini.<br><br>" +
        "<span style='color: #dc2626; font-weight: 600;'>Semua data pesanan akan dihapus dan tidak dapat dikembalikan!</span>",
      "Hapus Semua Pesanan?"
    );

    if (confirmed) {
      deleteAllCustomerOrders(customerId);
    }
  });

  // ==================== DELETE SINGLE ITEM ====================

  /**
   * Event listener untuk tombol delete single item
   */
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".delete-item-btn");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const itemId = btn.dataset.itemId;
    const dropId = btn.dataset.dropId;
    const customerId = btn.dataset.customerId;

    // Tampilkan konfirmasi dengan modal system
    const confirmed = await customConfirm(
      "Anda akan menghapus pesanan yang dipilih.<br><br>" +
        "<span style='color: #dc2626; font-weight: 600;'>Data yang dihapus tidak dapat dikembalikan!</span>",
      "Hapus Item?"
    );

    if (confirmed) {
      deleteSingleItem(itemId, dropId, customerId);
    }
  });

  // ==================== DELETE OPERATIONS ====================

  /**
   * Delete semua pesanan customer
   * @param {number} customerId - ID customer
   */
  function deleteAllCustomerOrders(customerId) {
    console.log("Deleting ALL orders for customer:", customerId);
    showLoading("Menghapus semua pesanan...");

    const formData = new FormData();
    formData.append("action", "delete_all_customer");
    formData.append("customer_id", customerId);

    fetch(window.location.href, {
      method: "POST",
      body: formData,
    })
      .then(validateJSON)
      .then(async (response) => {
        hideLoading();

        if (response.success) {
          // Tampilkan success modal
          await customSuccess(
            response.message || "Semua pesanan berhasil dihapus!",
            "Berhasil Dihapus!"
          );

          // Reload page
          location.reload();
        } else {
          // Tampilkan error modal
          await customError(
            response.message || "Gagal menghapus pesanan",
            "Gagal Menghapus"
          );
        }
      })
      .catch(async (error) => {
        hideLoading();
        console.error("❌ Delete error:", error);
        await customError(
          "Terjadi kesalahan saat menghapus data: " + error.message,
          "Error Sistem"
        );
      });
  }

  /**
   * Delete single item
   * @param {number} itemId - ID item
   * @param {number} dropId - ID drop
   * @param {number} customerId - ID customer
   */
  function deleteSingleItem(itemId, dropId, customerId) {
    console.log(
      `Deleting item ${itemId} in drop ${dropId} (customer ${customerId})`
    );
    showLoading("Menghapus item...");

    const formData = new FormData();
    formData.append("action", "delete_single_item");
    formData.append("item_id", itemId);
    formData.append("drop_id", dropId);

    fetch(window.location.href, {
      method: "POST",
      body: formData,
    })
      .then(validateJSON)
      .then(async (response) => {
        hideLoading();

        if (response.success) {
          // Jika item terakhir → tampilkan success dan reload
          if (response.last_item) {
            await customSuccess(
              response.message ||
                "Item terakhir dihapus — pesanan juga dihapus.",
              "Berhasil Dihapus!"
            );
            location.reload();
            return;
          }

          // Tampilkan success notification
          await customSuccess(
            response.message || "Item berhasil dihapus!",
            "Berhasil!"
          );

          // Hapus row dari DOM tanpa reload
          const row = document.querySelector(
            `[data-item-id="${itemId}"][data-drop-id="${dropId}"]`
          );

          if (row) {
            // Fade out animation
            row.style.opacity = "0";
            row.style.transition = "opacity 0.3s ease";

            setTimeout(() => {
              // Get price sebelum remove
              const priceVal = parseFloat(row.dataset.price) || 0;
              row.remove();

              // Update customer totals
              updateCustomerTotals(customerId, dropId, -priceVal);

              // Update total dari server response
              if (typeof response.new_total !== "undefined") {
                updateTotalDisplay(customerId, response.new_total);
              }
            }, 350);
          } else {
            // Fallback: reload jika row tidak ditemukan
            location.reload();
          }
        } else {
          // Tampilkan error modal
          await customError(
            response.message || "Gagal menghapus item",
            "Gagal Menghapus"
          );
        }
      })
      .catch(async (error) => {
        hideLoading();
        console.error("❌ Delete error:", error);
        await customError(
          "Terjadi kesalahan saat menghapus: " + error.message,
          "Error Sistem"
        );
      });
  }

  // ==================== HELPER FUNCTIONS ====================

  /**
   * Validasi JSON response
   * @param {Response} response - Fetch response
   * @returns {Promise<Object>} - Parsed JSON
   */
  function validateJSON(response) {
    const contentType = response.headers.get("content-type") || "";

    if (!contentType.includes("application/json")) {
      return response.text().then((text) => {
        console.error("❌ Non-JSON response:", text.substring(0, 500));
        throw new Error("Response bukan JSON. Cek server error log.");
      });
    }

    return response.json();
  }

  /**
   * Update customer totals setelah delete
   * @param {number} customerId - ID customer
   * @param {number} dropId - ID drop
   * @param {number} deltaPrice - Perubahan harga (negatif untuk delete)
   */
  function updateCustomerTotals(customerId, dropId, deltaPrice = 0) {
    console.log("Updating customer totals:", customerId);

    const header = document.querySelector(
      `.customer-group-header input[data-customer-id="${customerId}"]`
    );

    if (!header) {
      console.warn("⚠️ Customer header tidak ditemukan untuk ID:", customerId);
      return;
    }

    const container = header.closest(
      ".customer-group-header"
    ).nextElementSibling;
    const allItems = container.querySelectorAll(".order-item-row");

    // Update badge jumlah pesanan
    const badge = header
      .closest(".customer-group-header")
      .querySelector(".order-count-badge");
    if (badge) {
      badge.textContent = `${allItems.length} Pesanan`;
    }

    // Hitung ulang total harga
    let total = 0;
    allItems.forEach((item) => {
      const price = parseFloat(item.dataset.price) || 0;
      total += price;
    });

    if (deltaPrice !== 0) {
      total += deltaPrice;
    }

    // Update display total
    const totalEl = container.querySelector(".total-amount");
    if (totalEl) {
      totalEl.textContent = "Rp " + total.toLocaleString("id-ID");
    }

    // Update nomor urut item
    allItems.forEach((item, index) => {
      const badgeNo = item.querySelector(".item-number-badge");
      if (badgeNo) {
        badgeNo.textContent = `#${index + 1}`;
      }
    });
  }

  /**
   * Update total display dari server
   * @param {number} customerId - ID customer
   * @param {number} newTotal - Total baru
   */
  function updateTotalDisplay(customerId, newTotal) {
    try {
      const header = document.querySelector(
        `.customer-group-header input[data-customer-id="${customerId}"]`
      );

      if (header) {
        const container = header.closest(
          ".customer-group-header"
        ).nextElementSibling;
        const totalEl = container.querySelector(".total-amount");

        if (totalEl) {
          totalEl.textContent =
            "Rp " + Number(newTotal).toLocaleString("id-ID");
        }
      }
    } catch (error) {
      console.warn("⚠️ Could not update total display:", error);
    }
  }

  // ==================== INITIALIZATION COMPLETE ====================
  console.log("✅ Drop Delete Module Ready (Using Universal Modal System)");
  console.log("📦 Database: mifmyho2_sengkuclean");
});
