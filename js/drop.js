/* ============================================================
   HELPER FUNCTION: Load Service Info
============================================================ */
function loadServiceInfo(serviceId, isEditMode = false) {
  const prefix = isEditMode ? "edit_" : "";
  const priceDisplayEl = document.getElementById(prefix + "price_display");
  const priceMinEl = document.getElementById(prefix + "price_min");
  const estimateEl = document.getElementById(prefix + "estimate_desc");

  if (!serviceId) {
    if (priceDisplayEl) priceDisplayEl.value = "";
    if (priceMinEl) priceMinEl.value = "";
    if (estimateEl) estimateEl.value = "";
    return;
  }

  console.log(
    `[loadServiceInfo] Loading service ${serviceId} (Edit: ${isEditMode})`
  );

  // Set loading state
  if (priceDisplayEl) priceDisplayEl.value = "Memuat...";

  fetch("get_service_info.php?id_service=" + encodeURIComponent(serviceId))
    .then((response) => {
      console.log(`[loadServiceInfo] Response status: ${response.status}`);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      return response.json();
    })
    .then((data) => {
      console.log("[loadServiceInfo] Data received:", data);

      if (data.error) {
        throw new Error(data.error);
      }

      // Format harga
      const formatRupiah = (n) => {
        const num = parseInt(n) || 0;
        return "Rp" + num.toLocaleString("id-ID");
      };

      // Update fields
      if (priceDisplayEl) priceDisplayEl.value = formatRupiah(data.price_min);
      if (priceMinEl) priceMinEl.value = data.price_min;
      if (estimateEl) estimateEl.value = (data.duration || 0) + " Hari";

      console.log("[loadServiceInfo] Fields updated successfully");

      // Auto-calculate finish date jika edit mode
      if (isEditMode) {
        const tanggalMasukEl = document.getElementById("edit_tanggal_masuk");
        const tanggalSelesaiEl = document.getElementById(
          "edit_tanggal_selesai"
        );

        if (tanggalMasukEl && tanggalMasukEl.value && data.duration) {
          const d = new Date(tanggalMasukEl.value);
          d.setDate(d.getDate() + parseInt(data.duration));

          const yyyy = d.getFullYear();
          const mm = String(d.getMonth() + 1).padStart(2, "0");
          const dd = String(d.getDate()).padStart(2, "0");

          if (tanggalSelesaiEl) {
            tanggalSelesaiEl.value = `${yyyy}-${mm}-${dd}`;
          }
        }
      }
    })
    .catch((error) => {
      console.error("[loadServiceInfo] Error:", error);

      if (priceDisplayEl) {
        priceDisplayEl.value = "Gagal memuat data: " + error.message;
      }

      // Show user-friendly error
      alert(
        "Gagal memuat data layanan: " +
          error.message +
          "\n\nCek console untuk detail."
      );
    });
}

/* ============================================================
   DOUBLE CLICK ROW TO EDIT (IMPROVED)
============================================================ */
document.querySelectorAll(".data-row").forEach((row) => {
  row.addEventListener("dblclick", () => {
    console.log("[Edit Modal] Opening edit modal...");

    // Ambil semua dataset dari baris
    const data = {
      id_drop: row.dataset.id_drop || "",
      customer_name: row.dataset.customer_name || "",
      phone_number: row.dataset.phone_number || "",
      brand: row.dataset.brand || "",
      service_id: row.dataset.service_id || "",
      tanggal_masuk: row.dataset.tanggal_masuk || "",
      tanggal_selesai: row.dataset.tanggal_selesai || "",
      status_id: row.dataset.status_id || "",
      payment_status: row.dataset.payment_status || "",
      payment_method: row.dataset.payment_method || "",
      payment_date: row.dataset.payment_date || "",
      amount_paid: row.dataset.amount_paid || "",
    };

    console.log("[Edit Modal] Data:", data);

    // Isi form dengan data
    const fields = {
      edit_id_drop: data.id_drop,
      edit_customer_name: data.customer_name,
      edit_customer_phone: data.phone_number,
      edit_brand: data.brand,
      edit_service_id: data.service_id,
      edit_tanggal_masuk: data.tanggal_masuk,
      edit_tanggal_selesai: data.tanggal_selesai,
      edit_statusSelect: data.status_id,
      edit_payment_status: data.payment_status || "Belum Lunas",
      edit_payment_method: data.payment_method || "",
      edit_payment_date: data.payment_date
        ? data.payment_date.split(" ")[0]
        : "",
      edit_amount_paid: data.amount_paid || "",
    };

    // Isi semua field
    for (const [id, value] of Object.entries(fields)) {
      const el = document.getElementById(id);
      if (el) {
        el.value = value;
      } else {
        console.warn(`[Edit Modal] Element #${id} not found`);
      }
    }

    // Load service info
    if (data.service_id) {
      loadServiceInfo(data.service_id, true);
    } else {
      console.warn("[Edit Modal] No service_id found");
    }

    // Tampilkan modal
    const modal = document.getElementById("editModal");
    if (modal) {
      modal.style.display = "block";
    } else {
      console.error("[Edit Modal] Modal element not found");
    }
  });
});

/* ============================================================
   SELECT ALL + SHIFT-CLICK + DELETE (SATUAN / MULTI)
============================================================ */
document.addEventListener("DOMContentLoaded", () => {
  const selectAll = document.getElementById("selectAll");
  const checkboxes = Array.from(document.querySelectorAll(".row-checkbox"));
  const deleteButtons = document.querySelectorAll(".delete-btn");

  // Elemen modal konfirmasi custom
  const confirmModal = document.getElementById("confirmDeleteModal");
  const btnOk = document.getElementById("confirmOk");
  const btnCancel = document.getElementById("confirmCancel");

  let idsToDelete = []; // bisa untuk satuan / multi
  let lastChecked = null;

  /* ====== PILIH SEMUA ====== */
  if (selectAll) {
    selectAll.addEventListener("change", () => {
      checkboxes.forEach((cb) => (cb.checked = selectAll.checked));
    });
  }

  /* ====== SHIFT + CLICK UNTUK PILIH BEBERAPA ====== */
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("click", (e) => {
      if (!lastChecked) {
        lastChecked = checkbox;
        return;
      }

      if (e.shiftKey) {
        const start = checkboxes.indexOf(lastChecked);
        const end = checkboxes.indexOf(checkbox);
        const [min, max] = [Math.min(start, end), Math.max(start, end)];
        const shouldCheck = checkbox.checked;

        for (let i = min; i <= max; i++) {
          checkboxes[i].checked = shouldCheck;
        }
      }

      lastChecked = checkbox;
      selectAll.checked = checkboxes.every((cb) => cb.checked);
    });
  });

  /* ====== FUNGSI TAMPILKAN MODAL KONFIRMASI ====== */
  function showConfirmModal(callback) {
    confirmModal.style.display = "flex"; // tampilkan modal
    const handleOk = () => {
      confirmModal.style.display = "none";
      btnOk.removeEventListener("click", handleOk);
      btnCancel.removeEventListener("click", handleCancel);
      callback(true);
    };
    const handleCancel = () => {
      confirmModal.style.display = "none";
      btnOk.removeEventListener("click", handleOk);
      btnCancel.removeEventListener("click", handleCancel);
      callback(false);
    };
    btnOk.addEventListener("click", handleOk);
    btnCancel.addEventListener("click", handleCancel);
  }

  /* ====== DELETE (SATUAN / MULTI) ====== */
  deleteButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Kumpulkan semua checkbox yang dicentang
      const selectedIds = checkboxes
        .filter((cb) => cb.checked)
        .map((cb) => cb.value);

      // Jika ada yang terpilih → hapus banyak
      // Jika tidak, hapus ID dari tombol itu sendiri
      idsToDelete = selectedIds.length > 0 ? selectedIds : [btn.dataset.id];

      if (!idsToDelete || idsToDelete.length === 0) {
        alert("Tidak ada data yang dipilih!");
        return;
      }

      // Gunakan modal custom (bukan confirm bawaan browser)
      showConfirmModal((confirmed) => {
        if (!confirmed) return;

        // Lanjut hapus via fetch
        fetch("drop_delete_multi.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ids: idsToDelete }),
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.success) {
              // Hapus baris tanpa reload
              checkboxes.forEach((cb) => {
                if (idsToDelete.includes(cb.value)) {
                  const row = cb.closest("tr");
                  if (row) {
                    row.style.transition = "opacity 0.3s";
                    row.style.opacity = 0;
                    setTimeout(() => row.remove(), 300);
                  }
                }
              });

              // Opsional: tampilkan modal sukses
              if (typeof showSuccessModal === "function") {
                showSuccessModal(data.message || "Data berhasil dihapus!");
              }
            } else {
              alert(
                "Gagal menghapus data: " + (data.message || "Unknown error")
              );
            }
          })
          .catch((error) => {
            console.error("Delete error:", error);
            alert("Kesalahan koneksi!");
          });
      });
    });
  });

  /* ====== Klik di luar modal menutup ====== */
  window.addEventListener("click", function (e) {
    if (e.target === confirmModal) {
      confirmModal.style.display = "none";
    }
  });
});

/* ============================================================
   EDIT MODAL - SERVICE CHANGE HANDLER
============================================================ */
const editServiceSelect = document.getElementById("edit_service_id");

if (editServiceSelect) {
  editServiceSelect.addEventListener("change", function () {
    console.log("[Edit Service] Service changed to:", this.value);
    loadServiceInfo(this.value, true);
  });
}

/* ============================================================
   ADD MODAL - SERVICE CHANGE HANDLER
============================================================ */
const addServiceSelect = document.getElementById("service_id");

if (addServiceSelect) {
  addServiceSelect.addEventListener("change", function () {
    console.log("[Add Service] Service changed to:", this.value);
    loadServiceInfo(this.value, false);
  });
}

/* ============================================================
   CLOSE MODALS
============================================================ */
document.querySelectorAll(".modal .close").forEach((btn) => {
  btn.addEventListener("click", () => {
    btn.closest(".modal").style.display = "none";
  });
});

window.addEventListener("click", (e) => {
  document.querySelectorAll(".modal").forEach((modal) => {
    if (e.target === modal) modal.style.display = "none";
  });
});

const addModal = document.getElementById("addModal");
const editModal = document.getElementById("editModal");
const addBtn = document.getElementById("openAddModal");
const closeAdd = document.querySelector(".close");

/* Modal pilihan customer baru / lama */
const dropChoiceModal = document.getElementById("dropChoiceModal");
const btnDropBaru = document.getElementById("btnDropBaru");
const btnDropLama = document.getElementById("btnDropLama");

if (addBtn && dropChoiceModal) {
  addBtn.onclick = () => {
    dropChoiceModal.style.display = "block";
  };
}

/* Customer Baru */
if (btnDropBaru) {
  btnDropBaru.onclick = () => {
    dropChoiceModal.style.display = "none";
    addModal.style.display = "block";
  };
}

/* Customer Lama */
if (btnDropLama) {
  btnDropLama.onclick = () => {
    dropChoiceModal.style.display = "none";
    searchCustomerModal.style.display = "block";
  };
}

/* Close */
if (closeAdd) closeAdd.onclick = () => (addModal.style.display = "none");
if (closeSearch)
  closeSearch.onclick = () => (searchCustomerModal.style.display = "none");

/* Klik luar untuk menutup */
window.addEventListener("click", (e) => {
  const modals = [addModal, editModal, dropChoiceModal, searchCustomerModal];

  modals.forEach((m) => {
    if (e.target === m) m.style.display = "none";
  });
});
