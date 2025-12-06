// drop_delete.js - FINAL FIX (No conflict with drop_main.js)

document.addEventListener("DOMContentLoaded", function () {
  console.log("🔰 Drop Delete Script Loaded");

  // ========================= GLOBAL STATE =========================
  let pendingDelete = null;
  const modalDelete = document.getElementById("confirmDeleteModal");
  const btnDeleteOk = document.getElementById("confirmOk");
  const btnDeleteCancel = document.getElementById("confirmCancel");

  if (!modalDelete || !btnDeleteOk || !btnDeleteCancel) {
    console.warn(
      "Modal delete elements not found - ensure modal_confirm_delete.php is included."
    );
  }

  // ========================= MODAL CONTROL =========================
  function openDeleteModal(data) {
    pendingDelete = data;
    if (modalDelete) {
      // Reset deleteType untuk single/customer delete
      delete modalDelete.dataset.deleteType;
      delete modalDelete.dataset.dropIds;
      delete modalDelete.dataset.itemCount;

      modalDelete.style.display = "flex";
    }
    console.log("🟡 Modal opened, pending:", data);
  }

  function closeDeleteModal() {
    if (modalDelete) modalDelete.style.display = "none";
    pendingDelete = null;
    console.log("🔵 Modal closed");
  }

  // Klik area luar modal → close
  if (modalDelete) {
    modalDelete.addEventListener("click", (e) => {
      if (e.target === modalDelete) closeDeleteModal();
    });
  }

  // ========================= DELETE CUSTOMER =========================
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".delete-customer-btn");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    // Update modal message for customer delete
    const modalMessage = modalDelete.querySelector(".modal-message");
    if (modalMessage) {
      modalMessage.innerHTML = `
        <p style="margin-bottom: 12px;">Anda akan menghapus pesanan yang dipilih.</p>
        <p style="color: #dc2626; font-weight: 600;">⚠️ Data yang dihapus tidak dapat dikembalikan!</p>
      `;
    }

    openDeleteModal({
      action: "delete_all_customer",
      customerId: btn.dataset.customerId,
    });
  });

  // ========================= DELETE SINGLE ITEM =========================
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".delete-item-btn");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    // Update modal message for single item delete
    const modalMessage = modalDelete.querySelector(".modal-message");
    if (modalMessage) {
      modalMessage.innerHTML = `
        <p style="margin-bottom: 12px;">Anda akan menghapus pesanan yang dipilih.</p>
        <p style="color: #dc2626; font-weight: 600;">⚠️ Data yang dihapus tidak dapat dikembalikan!</p>
      `;
    }

    openDeleteModal({
      action: "delete_single_item",
      itemId: btn.dataset.itemId,
      dropId: btn.dataset.dropId,
      customerId: btn.dataset.customerId,
    });
  });

  // ========================= OK BUTTON (SHARED DENGAN drop_main.js) =========================
  if (btnDeleteOk) {
    btnDeleteOk.addEventListener("click", (e) => {
      e.preventDefault();

      // Check if this is bulk delete from drop_main.js
      if (modalDelete.dataset.deleteType === "bulk") {
        console.log("⚠️ Bulk delete - handled by drop_main.js");
        return; // Let drop_main.js handle it
      }

      // Check if pendingDelete exists and has action
      if (!pendingDelete || !pendingDelete.action) {
        console.error("⚠️ No pending delete action");
        closeDeleteModal();
        return;
      }

      console.log("🟥 DELETE CONFIRMED:", pendingDelete);

      // Store data before closing modal
      const deleteData = { ...pendingDelete };
      closeDeleteModal();

      // Execute based on action
      if (deleteData.action === "delete_all_customer") {
        deleteAllCustomerOrders(deleteData.customerId);
      } else if (deleteData.action === "delete_single_item") {
        deleteSingleItem(
          deleteData.itemId,
          deleteData.dropId,
          deleteData.customerId
        );
      }
    });
  }

  if (btnDeleteCancel) {
    btnDeleteCancel.addEventListener("click", (e) => {
      e.preventDefault();
      closeDeleteModal();
    });
  }

  console.log("🟢 Delete modal button events registered");

  // ========================= DELETE ALL CUSTOMER ORDERS =========================
  function deleteAllCustomerOrders(customerId) {
    console.log("🗑 Delete ALL orders for customer:", customerId);
    showLoading("Menghapus semua pesanan...");

    const data = new FormData();
    data.append("action", "delete_all_customer");
    data.append("customer_id", customerId);

    fetch(window.location.href, { method: "POST", body: data })
      .then(checkJSON)
      .then((res) => {
        hideLoading();
        if (res.success) {
          showSuccessMessage(res.message || "Semua pesanan customer terhapus!");
          setTimeout(() => location.reload(), 1000);
        } else {
          showErrorMessage("Gagal menghapus: " + res.message);
        }
      })
      .catch((err) => {
        hideLoading();
        console.error("Delete error:", err);
        showErrorMessage("Error: " + err.message);
      });
  }

  // ========================= DELETE SINGLE ITEM FUNCTION =========================
  function deleteSingleItem(itemId, dropId, customerId) {
    console.log(
      `🗑 Delete item ${itemId} dalam drop ${dropId} (customer ${customerId})`
    );
    showLoading("Menghapus item...");

    const data = new FormData();
    data.append("action", "delete_single_item");
    data.append("item_id", itemId);
    data.append("drop_id", dropId);

    fetch(window.location.href, { method: "POST", body: data })
      .then(checkJSON)
      .then((res) => {
        hideLoading();
        if (res.success) {
          showSuccessMessage(res.message || "Item berhasil dihapus!");

          // Jika item terakhir → reload full
          if (res.last_item) {
            return setTimeout(() => location.reload(), 900);
          }

          // Hapus row di DOM tanpa reload
          let row = document.querySelector(
            `[data-item-id="${itemId}"][data-drop-id="${dropId}"]`
          );

          if (row) {
            row.style.opacity = "0";
            row.style.transition = "opacity 0.3s ease";

            setTimeout(() => {
              // Get price before removing
              const priceVal = parseFloat(row.dataset.price) || 0;
              row.remove();

              // Update customer totals
              updateCustomerTotals(customerId, dropId, -priceVal);

              // Update total from server response
              if (typeof res.new_total !== "undefined") {
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
                        "Rp" + Number(res.new_total).toLocaleString("id-ID");
                    }
                  }
                } catch (e) {
                  console.warn("Could not update total display:", e);
                }
              }
            }, 350);
          } else {
            // Fallback: reload if row not found
            location.reload();
          }
        } else {
          showErrorMessage("Gagal menghapus: " + res.message);
        }
      })
      .catch((err) => {
        hideLoading();
        console.error("Delete error:", err);
        showErrorMessage("Error: " + err.message);
      });
  }

  // ========================= JSON VALIDATION =========================
  function checkJSON(response) {
    const type = response.headers.get("content-type") || "";
    if (!type.includes("application/json")) {
      return response.text().then((text) => {
        console.error("Non-JSON response:", text);
        throw new Error("Response bukan JSON:\n" + text.substring(0, 500));
      });
    }
    return response.json();
  }

  // ========================= UPDATE UI AFTER DELETE =========================
  function updateCustomerTotals(customerId, dropId, deltaPrice = 0) {
    console.log("🔄 Update UI customer total:", customerId);

    const header = document.querySelector(
      `.customer-group-header input[data-customer-id="${customerId}"]`
    );
    if (!header) {
      console.warn("⚠️ Tidak menemukan header customer untuk id:", customerId);
      return;
    }

    const container = header.closest(
      ".customer-group-header"
    ).nextElementSibling;
    const allItems = container.querySelectorAll(".order-item-row");

    // Update jumlah pesanan
    const badge = header
      .closest(".customer-group-header")
      .querySelector(".order-count-badge");
    if (badge) badge.textContent = `${allItems.length} Pesanan`;

    // Hitung ulang total harga
    let total = 0;
    allItems.forEach((i) => {
      const p = parseFloat(i.dataset.price) || 0;
      total += p;
    });

    if (deltaPrice !== 0) {
      total += deltaPrice;
    }

    const totalEl = container.querySelector(".total-amount");
    if (totalEl) totalEl.textContent = "Rp" + total.toLocaleString("id-ID");

    // Update nomor urut
    allItems.forEach((x, i) => {
      const badgeNo = x.querySelector(".item-number-badge");
      if (badgeNo) badgeNo.textContent = `#${i + 1}`;
    });
  }

  // ========================= LOADER + TOAST =========================
  function showLoading(msg = "Memproses...") {
    let loader = document.getElementById("custom-loader");
    if (!loader) {
      document.body.insertAdjacentHTML(
        "beforeend",
        `
        <div id="custom-loader" style="
          position:fixed;top:0;left:0;width:100%;height:100%;
          background:rgba(0,0,0,.55);display:flex;justify-content:center;
          align-items:center;z-index:9999;">
            <div style="background:white;padding:28px 44px;border-radius:12px;">
              <div style="width:36px;height:36px;border:4px solid #ddd;
                  border-top-color:#6366f1;border-radius:50%;
                  margin:auto;margin-bottom:12px;animation:spin 1s linear infinite;">
              </div>
              <b style="display:block;text-align:center;">${msg}</b>
            </div>
        </div>
        <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
      `
      );
      loader = document.getElementById("custom-loader");
    }
    loader.style.display = "flex";
  }

  function hideLoading() {
    const loader = document.getElementById("custom-loader");
    if (loader) loader.style.display = "none";
  }

  function showSuccessMessage(msg) {
    let box = document.getElementById("success-message");
    if (!box) {
      document.body.insertAdjacentHTML(
        "beforeend",
        `
        <div id="success-message" style="
          position:fixed;top:20px;right:20px;padding:15px 22px;border-radius:8px;
          background:#059669;color:white;font-weight:bold;z-index:10000;
          box-shadow:0 4px 18px rgba(0,0,0,0.3);opacity:0;transition:.3s;">
        </div>`
      );
      box = document.getElementById("success-message");
    }
    box.textContent = "✓ " + msg;
    box.style.opacity = "1";
    setTimeout(() => (box.style.opacity = "0"), 1800);
  }

  function showErrorMessage(msg) {
    let box = document.getElementById("error-message");
    if (!box) {
      document.body.insertAdjacentHTML(
        "beforeend",
        `
        <div id="error-message" style="
          position:fixed;top:20px;right:20px;padding:15px 22px;border-radius:8px;
          background:#dc2626;color:white;font-weight:bold;z-index:10000;
          box-shadow:0 4px 18px rgba(0,0,0,0.3);opacity:0;transition:.3s;">
        </div>`
      );
      box = document.getElementById("error-message");
    }
    box.textContent = "✕ " + msg;
    box.style.opacity = "1";
    setTimeout(() => (box.style.opacity = "0"), 3000);
  }
});
