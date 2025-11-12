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

  if (priceDisplayEl) priceDisplayEl.value = "Memuat...";

  fetch("get_service_info.php?id_service=" + encodeURIComponent(serviceId))
    .then((response) => {
      console.log(`[loadServiceInfo] Response status: ${response.status}`);
      if (!response.ok)
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      return response.json();
    })
    .then((data) => {
      console.log("[loadServiceInfo] Data received:", data);
      if (data.error) throw new Error(data.error);

      const formatRupiah = (n) =>
        "Rp" + (parseInt(n) || 0).toLocaleString("id-ID");

      if (priceDisplayEl) priceDisplayEl.value = formatRupiah(data.price_min);
      if (priceMinEl) priceMinEl.value = data.price_min;
      if (estimateEl) estimateEl.value = (data.duration || 0) + " Hari";

      console.log("[loadServiceInfo] Fields updated successfully");

      // Auto-calculate finish date (edit mode)
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
          if (tanggalSelesaiEl) tanggalSelesaiEl.value = `${yyyy}-${mm}-${dd}`;
        }
      }
    })
    .catch((error) => {
      console.error("[loadServiceInfo] Error:", error);
      if (priceDisplayEl)
        priceDisplayEl.value = "Gagal memuat data: " + error.message;
      alert(
        "Gagal memuat data layanan: " +
          error.message +
          "\n\nCek console untuk detail."
      );
    });
}

/* ============================================================
   MODAL HANDLING
============================================================ */
const addModal = document.getElementById("addModal");
const editModal = document.getElementById("editModal");
const addBtn = document.getElementById("openAddModal");
const closeAdd = document.querySelector(".close");
const closeEdit = editModal?.querySelector(".close");

const dropChoiceModal = document.getElementById("dropChoiceModal");
const btnDropBaru = document.getElementById("btnDropBaru");
const btnDropLama = document.getElementById("btnDropLama");
const searchCustomerModal = document.getElementById("searchCustomerModal");
const closeSearch = document.querySelector(".close-search");

let selectedCustomer = null;

// === Open add choice modal ===
if (addBtn && dropChoiceModal) {
  addBtn.onclick = () => (dropChoiceModal.style.display = "block");
}

// === Customer Baru ===
if (btnDropBaru) {
  btnDropBaru.onclick = () => {
    dropChoiceModal.style.display = "none";
    addModal.style.display = "block";
  };
}

// === Customer Lama ===
if (btnDropLama) {
  btnDropLama.onclick = () => {
    dropChoiceModal.style.display = "none";
    searchCustomerModal.style.display = "block";
  };
}

// === Close buttons ===
if (closeAdd) closeAdd.onclick = () => (addModal.style.display = "none");
if (closeEdit) closeEdit.onclick = () => (editModal.style.display = "none");
if (closeSearch)
  closeSearch.onclick = () => (searchCustomerModal.style.display = "none");

// === Klik luar untuk menutup modal ===
window.addEventListener("click", (e) => {
  const modals = [addModal, editModal, dropChoiceModal, searchCustomerModal];
  modals.forEach((m) => {
    if (e.target === m) m.style.display = "none";
  });
});

/* ============================================================
   SERVICE INFO (Tambah / Edit)
============================================================ */
const addServiceSelect = document.getElementById("service_id");
const editServiceSelect = document.getElementById("edit_service_id");

if (addServiceSelect) {
  addServiceSelect.addEventListener("change", function () {
    console.log("[Add Service] Service changed to:", this.value);
    loadServiceInfo(this.value, false);
  });
}

if (editServiceSelect) {
  editServiceSelect.addEventListener("change", function () {
    console.log("[Edit Service] Service changed to:", this.value);
    loadServiceInfo(this.value, true);
  });
}

/* ============================================================
   KALKULASI OTOMATIS TANGGAL SELESAI
============================================================ */
const tanggalMasuk = document.getElementById("tanggal_masuk");
const tanggalSelesai = document.getElementById("tanggal_selesai");
const estimateInput = document.getElementById("estimate_desc");

function getDurasiHari() {
  const val = estimateInput?.value?.trim() || "";
  return parseInt(val.replace(/[^\d]/g, "")) || 0;
}

if (tanggalMasuk && tanggalSelesai && estimateInput) {
  tanggalMasuk.addEventListener("change", () => {
    const durasi = getDurasiHari();
    if (!tanggalMasuk.value || !durasi) return (tanggalSelesai.value = "");
    const d = new Date(tanggalMasuk.value);
    d.setDate(d.getDate() + durasi);
    tanggalSelesai.value = d.toISOString().split("T")[0];
  });

  estimateInput.addEventListener("input", () => {
    if (!tanggalMasuk.value) return;
    const durasi = getDurasiHari();
    if (!durasi) return (tanggalSelesai.value = "");
    const d = new Date(tanggalMasuk.value);
    d.setDate(d.getDate() + durasi);
    tanggalSelesai.value = d.toISOString().split("T")[0];
  });
}

/* ============================================================
   DOUBLE CLICK ROW TO EDIT
============================================================ */
document.querySelectorAll(".data-row").forEach((row) => {
  row.addEventListener("dblclick", () => {
    console.log("[Edit Modal] Opening edit modal...");

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

    for (const [id, value] of Object.entries(fields)) {
      const el = document.getElementById(id);
      if (el) el.value = value;
      else console.warn(`[Edit Modal] Element #${id} not found`);
    }

    // Format tampilan nominal di form edit
    const displayInput = document.getElementById("edit_amount_paid_display");
    const hiddenInput = document.getElementById("edit_amount_paid");

    if (displayInput && hiddenInput) {
      const raw = parseFloat(hiddenInput.value) || 0;
      if (raw > 0) {
        displayInput.value = "Rp " + new Intl.NumberFormat("id-ID").format(raw);
      } else {
        displayInput.value = "";
      }
    }

    if (data.service_id) loadServiceInfo(data.service_id, true);

    if (editModal) editModal.style.display = "block";
    else console.error("[Edit Modal] Modal element not found");
  });
});

/* ============================================================
   FORMAT INPUT NOMINAL PEMBAYARAN (ADD & EDIT)
============================================================ */
document.querySelectorAll('input[id$="_display"]').forEach((displayInput) => {
  const hiddenInputId = displayInput.id.replace("_display", "");
  const hiddenInput = document.getElementById(hiddenInputId);

  if (!hiddenInput) return;

  // Saat mengetik, ubah tampilan & simpan angka mentah ke hidden input
  displayInput.addEventListener("input", function (e) {
    let value = e.target.value.replace(/[^\d]/g, "");
    if (!value) {
      e.target.value = "";
      hiddenInput.value = "";
      return;
    }
    e.target.value = "Rp " + new Intl.NumberFormat("id-ID").format(value);
    hiddenInput.value = value;
  });

  // Saat fokus: hapus prefix "Rp " agar mudah edit
  displayInput.addEventListener("focus", function (e) {
    e.target.value = e.target.value.replace(/^Rp\s?/, "");
  });

  // Saat blur: tampilkan kembali format "Rp x.xxx"
  displayInput.addEventListener("blur", function (e) {
    const val = e.target.value.replace(/[^\d]/g, "");
    if (val)
      e.target.value = "Rp " + new Intl.NumberFormat("id-ID").format(val);
  });
});

/* ============================================================
   SELECT ALL + SHIFT-CLICK + DELETE
============================================================ */
document.addEventListener("DOMContentLoaded", () => {
  const selectAll = document.getElementById("selectAll");
  const checkboxes = Array.from(document.querySelectorAll(".row-checkbox"));
  const deleteButtons = document.querySelectorAll(".delete-btn");

  const confirmModal = document.getElementById("confirmDeleteModal");
  const btnOk = document.getElementById("confirmOk");
  const btnCancel = document.getElementById("confirmCancel");

  let idsToDelete = [];
  let lastChecked = null;

  // Select all
  if (selectAll) {
    selectAll.addEventListener("change", () => {
      checkboxes.forEach((cb) => (cb.checked = selectAll.checked));
    });
  }

  // Shift + click select range
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("click", (e) => {
      if (!lastChecked) return (lastChecked = checkbox);
      if (e.shiftKey) {
        const start = checkboxes.indexOf(lastChecked);
        const end = checkboxes.indexOf(checkbox);
        const [min, max] = [Math.min(start, end), Math.max(start, end)];
        const shouldCheck = checkbox.checked;
        for (let i = min; i <= max; i++) checkboxes[i].checked = shouldCheck;
      }
      lastChecked = checkbox;
      selectAll.checked = checkboxes.every((cb) => cb.checked);
    });
  });

  // Custom confirm modal
  function showConfirmModal(callback) {
    if (!confirmModal) return callback(false);
    confirmModal.style.display = "flex";
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

  // Delete (single or multi)
  deleteButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const selectedIds = checkboxes
        .filter((cb) => cb.checked)
        .map((cb) => cb.value);
      idsToDelete = selectedIds.length > 0 ? selectedIds : [btn.dataset.id];
      if (!idsToDelete || idsToDelete.length === 0)
        return alert("Tidak ada data yang dipilih!");

      showConfirmModal((confirmed) => {
        if (!confirmed) return;

        fetch("drop_delete_multi.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ids: idsToDelete }),
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.success) {
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

              if (typeof showSuccessModal === "function")
                showSuccessModal(data.message || "Data berhasil dihapus!");
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

  // Klik luar untuk tutup confirm modal
  if (confirmModal) {
    window.addEventListener("click", (e) => {
      if (e.target === confirmModal) confirmModal.style.display = "none";
    });
  }
});

/* ============================================================
   CETAK STRUK (Simpan + Buka Halaman Cetak)
============================================================ */
const saveAndPrintBtn = document.getElementById("saveAndPrintBtn");
if (saveAndPrintBtn) {
  saveAndPrintBtn.addEventListener("click", (e) => {
    e.preventDefault();

    const form = document.querySelector("#addModal form");
    const formData = new FormData(form);

    fetch("drop_add.php?print=1", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.drop_id) {
          window.open("cetak_struk.php?id=" + data.drop_id, "_blank");
          form.reset();
          document.getElementById("addModal").style.display = "none";
          location.reload();
        } else {
          alert("Gagal menyimpan data: " + (data.message || "Unknown error"));
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        alert("Terjadi kesalahan saat menyimpan!");
      });
  });
}

/* ============================================================
   PENCARIAN CUSTOMER
============================================================ */
const searchInput = document.getElementById("searchCustomerInput");
const resultsDiv = document.getElementById("customerResults");
const saveCustomerBtn = document.getElementById("saveCustomer");

if (searchInput && resultsDiv && saveCustomerBtn) {
  searchInput.addEventListener("input", function () {
    const keyword = this.value.trim();
    resultsDiv.innerHTML = "<p style='color:gray'>Mencari...</p>";

    if (keyword.length < 2) {
      resultsDiv.innerHTML = "";
      return;
    }

    fetch("search_customer.php?keyword=" + encodeURIComponent(keyword))
      .then((res) => res.json())
      .then((data) => {
        resultsDiv.innerHTML = "";

        if (data.length === 0) {
          resultsDiv.innerHTML =
            "<p style='color:red'>Customer tidak ditemukan.</p>";
          return;
        }

        data.forEach((cust) => {
          const div = document.createElement("div");
          div.classList.add("customer-item");
          div.textContent = `${cust.nama} - ${cust.no_hp}`;
          div.onclick = () => {
            document
              .querySelectorAll(".customer-item")
              .forEach((el) => el.classList.remove("selected"));
            div.classList.add("selected");
            selectedCustomer = cust;
            saveCustomerBtn.disabled = false;
          };
          resultsDiv.appendChild(div);
        });
      })
      .catch(() => {
        resultsDiv.innerHTML = "<p style='color:red'>Gagal memuat data.</p>";
      });
  });

  saveCustomerBtn.onclick = () => {
    if (!selectedCustomer) return;
    searchCustomerModal.style.display = "none";
    addModal.style.display = "block";

    const nameInput = document.querySelector('input[name="customer_name"]');
    const phoneInput = document.querySelector('input[name="phone_number"]');

    if (nameInput) nameInput.value = selectedCustomer.nama;
    if (phoneInput) phoneInput.value = selectedCustomer.no_hp;

    let hidden = document.querySelector('input[name="customer_id"]');
    if (!hidden) {
      hidden = document.createElement("input");
      hidden.type = "hidden";
      hidden.name = "customer_id";
      document.querySelector("#addModal form").appendChild(hidden);
    }
    hidden.value = selectedCustomer.id_customer;
  };
}

/* ============================================================
   UPDATE STATUS DROPDOWN
============================================================ */
/* ============================================================
   UPDATE STATUS DROPDOWN + SINKRONISASI FORM EDIT
============================================================ */
document.addEventListener("DOMContentLoaded", function () {
  const dropdowns = document.querySelectorAll(".status-dropdown");

  dropdowns.forEach((select) => {
    select.addEventListener("change", function () {
      const id_drop = this.getAttribute("data-id");
      const status_id = this.value;

      if (!id_drop || !status_id) return;

      fetch("../actions/update_status_drop.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body:
          "id_drop=" +
          encodeURIComponent(id_drop) +
          "&status_id=" +
          encodeURIComponent(status_id),
      })
        .then((response) => response.text())
        .then((text) => {
          console.log("Response:", text);
          let data;
          try {
            data = JSON.parse(text);
          } catch (e) {
            throw new Error("Respons bukan JSON valid: " + text);
          }

          if (data.success) {
            // ✅ Tampilkan notifikasi sukses
            alert("✅ " + data.message);

            // 🔁 Sinkronkan form edit jika sedang terbuka
            updateEditForm(id_drop, status_id);
          } else {
            alert("❌ Gagal memperbarui status: " + data.message);
          }
        })
        .catch((error) => {
          alert("⚠️ Terjadi kesalahan koneksi: " + error.message);
        });
    });
  });
});

function updateEditForm(id_drop, status_id) {
  // Ambil elemen form edit
  const form = document.getElementById("editForm");
  if (!form) return;

  // Ambil id_drop yang sedang aktif di modal
  const currentId = form.querySelector("#edit_id_drop").value;

  // Jika form edit sedang menampilkan drop yang sama
  if (String(currentId) === String(id_drop)) {
    const selectStatus = form.querySelector("#edit_statusSelect");
    if (selectStatus) {
      selectStatus.value = status_id;

      // Tambahkan visual feedback
      selectStatus.style.transition = "background 0.3s";
      selectStatus.style.background = "#d4edda"; // hijau lembut
      setTimeout(() => (selectStatus.style.background = ""), 1000);
    }
  }
}
