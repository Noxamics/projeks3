document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("wa-form");
  const loading = document.getElementById("loading");

  if (!form) {
    console.error("Form WA tidak ditemukan! Periksa ID 'wa-form'.");
    return;
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    let name = document.getElementById("name").value.trim();
    let email = document.getElementById("email").value.trim();
    let message = document.getElementById("message").value.trim();

    // Validasi
    if (!name || !email || !message) {
      alert("Harap isi semua kolom sebelum mengirim.");
      return;
    }

    // Nomor WhatsApp Admin
    let adminNumber = "6287765967190";

    let text =
      "*New Message from Customer*\n\n" +
      "*Name:* " +
      name +
      "\n" +
      "*Email:* " +
      email +
      "\n" +
      "*Message:* " +
      message;

    let waUrl =
      "https://wa.me/" + adminNumber + "?text=" + encodeURIComponent(text);

    loading.style.display = "block";
    loading.innerHTML = "Loading...";

    setTimeout(() => {
      let openWA = window.open(waUrl, "_blank");

      loading.style.display = "none";

      if (openWA) {
        alert("Pesan berhasil dibuka di WhatsApp Admin!");

        // 🔥 Bersihkan semua input setelah sukses
        document.getElementById("name").value = "";
        document.getElementById("email").value = "";
        document.getElementById("message").value = "";
      } else {
        alert("Gagal membuka WhatsApp. Aktifkan pop-up browser.");
      }
    }, 1000);
  });
});
