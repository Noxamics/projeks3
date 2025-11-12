// Ambil elemen penting
const modal = document.getElementById("employeeModal");
const closeModal = document.getElementById("closeModal");
const btnAdd = document.getElementById("btnAddEmployee");
const form = document.getElementById("employeeForm");
const modalTitle = document.getElementById("modalTitle");
const passwordInput = document.getElementById("password");
const passwordHelp = document.getElementById("passwordHelp");

// === OPEN MODAL TAMBAH ===
btnAdd.addEventListener("click", () => {
  form.reset();
  document.getElementById("id_employee").value = "";

  modalTitle.textContent = "Tambah Karyawan";
  passwordInput.required = true;
  passwordHelp.textContent = "Wajib diisi untuk membuat akun karyawan baru.";

  modal.style.display = "block";
});

// === OPEN MODAL EDIT ===
document.querySelectorAll(".btn-edit").forEach((btn) => {
  btn.addEventListener("click", async () => {
    const id = btn.getAttribute("data-id");

    const response = await fetch(`../actions/employee_get.php?id=${id}`);
    const data = await response.json();

    if (data.success) {
      modalTitle.textContent = "Edit Karyawan";
      document.getElementById("id_employee").value = data.employee.id_employee;
      document.getElementById("name").value = data.employee.name;
      document.getElementById("phone").value = data.employee.phone;
      document.getElementById("status").value = data.employee.status;
      document.getElementById("join_date").value = data.employee.join_date;

      // password kosong, tidak wajib
      passwordInput.value = "";
      passwordInput.required = false;
      passwordHelp.textContent =
        "Kosongkan jika tidak ingin mengubah password.";

      modal.style.display = "block";
    } else {
      alert("Gagal memuat data karyawan!");
    }
  });
});

// === CLOSE MODAL ===
closeModal.addEventListener("click", () => {
  modal.style.display = "none";
});

window.addEventListener("click", (e) => {
  if (e.target === modal) modal.style.display = "none";
});

// === FITUR ABSEN HARI INI (Check In / Check Out) ===
document.addEventListener("DOMContentLoaded", () => {
  const absenButtons = document.querySelectorAll(".btn-absen");

  absenButtons.forEach((btn) => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.id;
      if (!id) return;

      // 💡 Minta password sebelum absen
      const password = prompt(
        "Masukkan password karyawan untuk konfirmasi absen:"
      );
      if (!password) return alert("Password wajib diisi!");

      btn.disabled = true;
      const originalText = btn.textContent;
      btn.textContent = "Memproses...";

      try {
        const response = await fetch("../actions/employee_absen.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `id_employee=${encodeURIComponent(
            id
          )}&password=${encodeURIComponent(password)}`,
        });

        const result = await response.json();

        if (result.error) {
          alert(result.error);
          btn.textContent = originalText;
          btn.disabled = false;
          return;
        }

        if (result.status === "checkin") {
          btn.textContent = "Check Out";
          btn.disabled = false;
          btn.classList.add("checkin");
        } else if (result.status === "checkout") {
          btn.textContent = `✅ Selesai (${result.hours} jam)`;
          btn.classList.remove("checkin");
          btn.classList.add("done");
          btn.disabled = true;
        } else if (result.status === "done") {
          btn.textContent = "✅ Sudah Absen";
          btn.disabled = true;
          btn.classList.add("done");
        } else {
          btn.textContent = "Terjadi kesalahan";
          btn.disabled = false;
        }
      } catch (err) {
        console.error("Error:", err);
        btn.textContent = originalText;
        btn.disabled = false;
      }
    });
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const dropdowns = document.querySelectorAll(".status-dropdown");

  dropdowns.forEach((select) => {
    select.addEventListener("change", async () => {
      const id = select.dataset.id;
      const newStatus = select.value;

      try {
        const response = await fetch("../actions/update_status_employee.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `id_employee=${id}&status=${encodeURIComponent(newStatus)}`,
        });

        const result = await response.json();

        if (result.success) {
          select.style.backgroundColor = "#d4edda"; // hijau lembut
          setTimeout(() => (select.style.backgroundColor = ""), 800);
        } else {
          alert("Gagal memperbarui status!");
        }
      } catch (error) {
        console.error("Error:", error);
        alert("Terjadi kesalahan koneksi.");
      }
    });
  });
});

document.querySelectorAll(".btn-absen").forEach((btn) => {
  btn.addEventListener("click", async () => {
    const id = btn.dataset.id;
    btn.disabled = true;
    btn.textContent = "Memproses...";

    try {
      const res = await fetch("../actions/employee_absen.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id_employee=${id}`,
      });
      const result = await res.json();

      if (result.error) {
        alert(result.error);
        btn.disabled = false;
        btn.textContent = "Absen Hari Ini";
        return;
      }

      const statusDropdown = document.querySelector(
        `.status-dropdown[data-id="${id}"]`
      );

      if (result.status === "checkin") {
        btn.textContent = "Check Out";
        btn.classList.add("checkin");
        btn.classList.remove("done");
        btn.disabled = false;
        if (statusDropdown) statusDropdown.value = "Aktif";
      } else if (result.status === "checkout") {
        btn.textContent = "✅ Sudah Absen";
        btn.disabled = true;
        btn.classList.add("done");
        btn.classList.remove("checkin");
        if (statusDropdown) statusDropdown.value = "Non-Aktif";
      } else if (result.status === "done") {
        btn.textContent = "✅ Sudah Absen";
        btn.disabled = true;
        if (statusDropdown) statusDropdown.value = "Non-Aktif";
      }
    } catch (err) {
      console.error(err);
      alert("Terjadi kesalahan saat absen.");
      btn.disabled = false;
      btn.textContent = "Absen Hari Ini";
    }
  });
});
