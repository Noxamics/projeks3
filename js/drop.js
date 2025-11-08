/* ============================================================
   ELEMENT SELEKTOR UTAMA
============================================================ */

const addModal = document.getElementById("addModal");
const editModal = document.getElementById("editModal");
const addBtn = document.getElementById("openAddModal");
const closeAdd = document.querySelector(".close");

/* Modal pilihan customer baru / lama */
const dropChoiceModal = document.getElementById("dropChoiceModal");
const btnDropBaru = document.getElementById("btnDropBaru");
const btnDropLama = document.getElementById("btnDropLama");

/* Modal search customer */
const searchCustomerModal = document.getElementById("searchCustomerModal");
const closeSearch = document.querySelector(".close-search");
const searchInput = document.getElementById("searchCustomerInput");
const resultsDiv = document.getElementById("customerResults");
const saveCustomerBtn = document.getElementById("saveCustomer");

/* Form tambah barang */
const serviceSelect = document.getElementById("service_id");
const priceDisplay = document.getElementById("price_display");
const priceMinInput = document.getElementById("price_min");
const priceMaxInput = document.getElementById("price_max");
const estimateInput = document.getElementById("estimate_desc");

/* Untuk delete single dan multi */
let selectedCustomer = null;
let deleteId = null;

/* ============================================================
   MODAL HANDLING
============================================================ */

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
/* ============================================================
   DROPDOWN AKSI (⋮)
============================================================ */

document.addEventListener("click", (e) => {
  const isBtn = e.target.matches(".dropdown-toggle");
  const dropdowns = document.querySelectorAll(".dropdown");

  dropdowns.forEach((dropdown) => {
    const menu = dropdown.querySelector(".dropdown-menu");

    if (isBtn && dropdown.contains(e.target)) {
      dropdown.classList.toggle("active");
    } else {
      dropdown.classList.remove("active");
    }
  });
});

/* ============================================================
   SEARCH CUSTOMER (Drop Lama)
============================================================ */

if (searchInput) {
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
}

if (saveCustomerBtn) {
  saveCustomerBtn.onclick = () => {
    if (!selectedCustomer) return;

    searchCustomerModal.style.display = "none";
    addModal.style.display = "block";

    document.querySelector('input[name="customer_name"]').value =
      selectedCustomer.nama;
    document.querySelector('input[name="phone_number"]').value =
      selectedCustomer.no_hp;

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
   DELETE SATUAN
============================================================ */

document.addEventListener("click", (e) => {
  if (e.target.classList.contains("delete-btn")) {
    deleteId = e.target.getAttribute("data-id");
    document.getElementById("deleteConfirm").style.display = "block";
  }
});

const cancelDelete = document.getElementById("cancelDelete");
if (cancelDelete) {
  cancelDelete.onclick = () => {
    document.getElementById("deleteConfirm").style.display = "none";
    deleteId = null;
  };
}

const confirmDelete = document.getElementById("confirmDelete");
if (confirmDelete) {
  confirmDelete.onclick = () => {
    if (!deleteId) return;

    fetch("drop_delete.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id_drop=" + encodeURIComponent(deleteId),
    })
      .then((res) => res.text())
      .then((data) => {
        document.getElementById("deleteConfirm").style.display = "none";
        if (data.includes("berhasil")) {
          showSuccessModal("Data berhasil dihapus!");
          setTimeout(() => location.reload(), 1000);
        } else {
          alert("Gagal menghapus data.");
        }
      })
      .catch(() => alert("Kesalahan koneksi!"));
  };
}

/* ============================================================
   AMBIL DATA SERVICE (harga & estimasi)
============================================================ */

if (serviceSelect) {
  serviceSelect.addEventListener("change", () => {
    const id = serviceSelect.value;

    if (!id) {
      priceDisplay.value = "";
      priceMinInput.value = "";
      priceMaxInput.value = "";
      estimateInput.value = "";
      return;
    }

    fetch("get_service_info.php?id_service=" + encodeURIComponent(id))
      .then((res) => res.json())
      .then((data) => {
        if (data.error) {
          priceDisplay.value = "Data tidak ditemukan";
          return;
        }

        const formatRupiah = (n) => "Rp" + parseInt(n).toLocaleString("id-ID");

        priceDisplay.value = formatRupiah(data.price_min);
        priceMinInput.value = data.price_min;

        priceDisplay.disabled = false;
        priceDisplay.removeAttribute("readonly");

        estimateInput.value = `${data.duration} Hari`;
      })
      .catch(() => {
        priceDisplay.value = "Gagal memuat data layanan";
      });
  });

  priceDisplay.addEventListener("input", (e) => {
    let value = e.target.value.replace(/[^\d]/g, "");
    e.target.value = value
      ? "Rp" + parseInt(value).toLocaleString("id-ID")
      : "";
  });
}

/* ============================================================
   KALKULASI TANGGAL SELESAI OTOMATIS
============================================================ */

const tanggalMasuk = document.getElementById("tanggal_masuk");
const tanggalSelesai = document.getElementById("tanggal_selesai");

function getDurasiHari() {
  const val = estimateInput.value.trim();
  return parseInt(val.replace(/[^\d]/g, "")) || 0;
}

if (tanggalMasuk && tanggalSelesai && estimateInput) {
  tanggalMasuk.addEventListener("change", () => {
    const durasi = getDurasiHari();
    if (!tanggalMasuk.value || !durasi) {
      tanggalSelesai.value = "";
      return;
    }

    const d = new Date(tanggalMasuk.value);
    d.setDate(d.getDate() + durasi);

    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");

    tanggalSelesai.value = `${yyyy}-${mm}-${dd}`;
  });

  estimateInput.addEventListener("input", () => {
    if (tanggalMasuk.value) {
      const durasi = getDurasiHari();
      if (!durasi) return (tanggalSelesai.value = "");

      const d = new Date(tanggalMasuk.value);
      d.setDate(d.getDate() + durasi);

      tanggalSelesai.value = `${d.getFullYear()}-${String(
        d.getMonth() + 1
      ).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
    }
  });
}

/* ============================================================
   MULTI DELETE + SELECT ALL
============================================================ */

document.addEventListener("DOMContentLoaded", () => {
  const selectAll = document.getElementById("selectAll");
  const checkboxes = document.querySelectorAll(".row-checkbox");
  const deleteBtn = document.getElementById("deleteSelected");

  /* Pilih semua */
  if (selectAll) {
    selectAll.addEventListener("change", () => {
      checkboxes.forEach((cb) => (cb.checked = selectAll.checked));
    });
  }

  /* Hapus banyak data */
  if (deleteBtn) {
    deleteBtn.addEventListener("click", () => {
      const selected = Array.from(checkboxes)
        .filter((cb) => cb.checked)
        .map((cb) => cb.value);

      if (selected.length === 0) {
        alert("Pilih data yang ingin dihapus!");
        return;
      }

      if (!confirm(`Yakin menghapus ${selected.length} data?`)) return;

      fetch("drop_delete_multi.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ids: selected }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            showSuccessModal("Data berhasil dihapus!");
            setTimeout(() => location.reload(), 1000);
          } else {
            alert("Gagal menghapus data!");
          }
        });
    });
  }
});

/* ============================================================
   NOTIFIKASI BERHASIL
============================================================ */

function showSuccessModal(message) {
  const modal = document.getElementById("successModal");
  const msg = document.getElementById("successMessage");
  const closeBtn = document.getElementById("closeSuccess");

  if (!modal || !msg || !closeBtn) return;

  msg.textContent = message || "Berhasil!";
  modal.style.display = "flex";

  closeBtn.onclick = () => {
    modal.style.display = "none";
  };

  window.onclick = (e) => {
    if (e.target === modal) modal.style.display = "none";
  };
}

/* Untuk halaman yang baru redirect setelah add */
document.addEventListener("DOMContentLoaded", () => {
  if (sessionStorage.getItem("showSuccess") === "true") {
    sessionStorage.removeItem("showSuccess");
    showSuccessModal("Data berhasil disimpan!");
  }
});

// SHIFT + CLICK MULTI SELECT / UNSELECT CHECKBOXES
let lastChecked = null;

document.addEventListener("DOMContentLoaded", () => {
  const checkboxes = document.querySelectorAll(".row-checkbox");

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("click", (e) => {
      // Shift used
      if (lastChecked && e.shiftKey) {
        let inRange = false;
        const shouldCheck = checkbox.checked;
        // true = check all, false = uncheck all

        checkboxes.forEach((box) => {
          if (box === checkbox || box === lastChecked) {
            inRange = !inRange;
            box.checked = shouldCheck;
          }

          if (inRange) {
            box.checked = shouldCheck;
          }
        });
      }

      lastChecked = checkbox;
    });
  });
});
