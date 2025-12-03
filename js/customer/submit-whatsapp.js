document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("wa-form");
  const loading = document.getElementById("loading");

  if (!form) {
    console.error("Form WA tidak ditemukan! Periksa ID 'wa-form'.");
    return;
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    let name = document.getElementById("name").value;
    let email = document.getElementById("email").value;
    let message = document.getElementById("message").value;

    // Nomor WhatsApp Admin (GANTI KE NOMOR ADMIN)
    let adminNumber = "6282143248201";

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

    // Tampilkan loading
    loading.style.display = "block";
    loading.innerHTML = "Loading...";

    setTimeout(() => {
      let openWA = window.open(waUrl, "_blank");

      loading.style.display = "none";

      if (openWA) {
        alert("Pesan berhasil dibuka di WhatsApp Admin!");
      } else {
        alert("Gagal membuka WhatsApp. Periksa pop-up browser.");
      }
    }, 1000);
  });
});
