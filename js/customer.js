// --- RATING BINTANG INTERAKTIF ---
document.addEventListener("DOMContentLoaded", function () {
  const stars = document.querySelectorAll(".star-rating .star");
  const ratingValue = document.getElementById("rating-value");

  if (!stars.length || !ratingValue) return;

  stars.forEach((star) => {
    // Klik bintang
    star.addEventListener("click", function () {
      const value = this.getAttribute("data-value");
      ratingValue.value = value;

      stars.forEach((s) => {
        if (s.getAttribute("data-value") <= value) {
          s.classList.add("active");
          s.style.color = "#FFD700"; // ⭐ warna kuning
        } else {
          s.classList.remove("active");
          s.style.color = "#ccc"; // default abu
        }
      });
    });

    // Hover efek
    star.addEventListener("mouseover", function () {
      const value = this.getAttribute("data-value");
      stars.forEach((s) => {
        s.style.color =
          s.getAttribute("data-value") <= value ? "#FFD700" : "#ccc";
      });
    });
  });

  // Reset warna ketika mouse keluar
  const container = document.querySelector(".star-rating");
  if (container) {
    container.addEventListener("mouseleave", function () {
      stars.forEach((s) => {
        s.style.color = s.classList.contains("active") ? "#FFD700" : "#ccc";
      });
    });
  }
});
