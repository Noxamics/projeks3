// --- RATING BINTANG INTERAKTIF ---
document.addEventListener("DOMContentLoaded", function () {
  const stars = document.querySelectorAll(".star-rating .star");
  const ratingValue = document.getElementById("rating-value");

  if (!stars.length || !ratingValue) return;

  // Set default style untuk semua bintang
  stars.forEach((star) => {
    star.style.cursor = "pointer";
    star.style.fontSize = "2rem";
    star.style.transition = "color 0.2s ease";
    star.style.color = "#ccc";
  });

  stars.forEach((star) => {
    // Klik bintang - set rating
    star.addEventListener("click", function () {
      const value = this.getAttribute("data-value");
      ratingValue.value = value;

      // Update tampilan bintang
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

    // Hover efek - preview rating
    star.addEventListener("mouseover", function () {
      const value = this.getAttribute("data-value");
      stars.forEach((s) => {
        if (s.getAttribute("data-value") <= value) {
          s.style.color = "#FFD700";
        } else {
          s.style.color = "#ccc";
        }
      });
    });
  });

  // Reset warna ketika mouse keluar dari container
  const container = document.querySelector(".star-rating");
  if (container) {
    container.addEventListener("mouseleave", function () {
      stars.forEach((s) => {
        // Kembalikan ke state active atau default
        if (s.classList.contains("active")) {
          s.style.color = "#FFD700";
        } else {
          s.style.color = "#ccc";
        }
      });
    });
  }
});

// --- VALIDASI FORM TESTIMONI ---
document.addEventListener("DOMContentLoaded", function () {
  const testimonialForm = document.querySelector(".testimonial-form");

  if (testimonialForm) {
    testimonialForm.addEventListener("submit", function (e) {
      const ratingValue = document.getElementById("rating-value").value;
      const testimonialText = document
        .getElementById("testimonial")
        .value.trim();

      if (!ratingValue) {
        e.preventDefault();
        alert("Silakan pilih rating bintang terlebih dahulu!");
        return false;
      }

      if (testimonialText.length < 10) {
        e.preventDefault();
        alert("Testimoni minimal 10 karakter!");
        return false;
      }
    });
  }
});

// --- SMOOTH SCROLL untuk navigasi ---
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  });
});

// --- AUTO HIDE SUCCESS MESSAGE ---
if (window.location.search.includes("testimonial_success=1")) {
  setTimeout(() => {
    const url = new URL(window.location);
    url.searchParams.delete("testimonial_success");
    window.history.replaceState({}, document.title, url);
  }, 3000);
}
