// ============================
// DOM READY
// ============================
document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector("header");
  const sections = document.querySelectorAll("section[id]");
  const navLinks = document.querySelectorAll(".nav-right a");
  const hero = document.querySelector(".hero");
  const dots = document.querySelectorAll(".dot");
  const logo = document.getElementById("logo-img"); // ambil logo

  // ============================
  // HEADER SCROLL EFFECT + CHANGE LOGO
  // ============================
  window.addEventListener("scroll", () => {
    const isScrolled = window.scrollY > 30;
    header.classList.toggle("scrolled", isScrolled);

    // ubah logo sesuai posisi scroll
    if (logo) {
      if (isScrolled) {
        logo.src = "../a/img/Logo Sengku.png"; // logo saat discroll
      } else {
        logo.src = "../a/img/LGG.png"; // logo awal
      }
    }
  });

  // ============================
  // ACTIVE NAV LINK ON SCROLL
  // ============================
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && entry.target.tagName === "SECTION") {
          const id = entry.target.getAttribute("id");
          navLinks.forEach((link) => {
            link.classList.remove("active");
            if (link.getAttribute("href").includes(`#${id}`)) {
              link.classList.add("active");
            }
          });
        }
      });
    },
    { threshold: 0.3 }
  );

  sections.forEach((section) => observer.observe(section));

  // ============================
  // HERO SLIDESHOW - SIMPLE VERSION
  // ============================

  // Pastikan script ini berjalan setelah DOM ready
  window.addEventListener("load", function () {
    const hero = document.getElementById("heroSection");
    const dots = document.querySelectorAll(".slideshow-dots .dot");

    // Cek apakah element ditemukan
    if (!hero) {
      console.error('❌ Element dengan ID "heroSection" tidak ditemukan!');
      return;
    }

    if (dots.length === 0) {
      console.error("❌ Dots tidak ditemukan!");
      return;
    }

    console.log("✅ Hero found:", hero);
    console.log("✅ Dots found:", dots.length);

    // Array gambar background
    const images = ["../a/img/BG1.jpg", "../a/img/BG2.jpg", "../a/img/BG3.jpg"];

    let currentIndex = 0;

    // Fungsi untuk mengganti background
    function changeBackground(index) {
      console.log("🔄 Changing to image index:", index);

      // Set background image
      hero.style.backgroundImage = "url(" + images[index] + ")";

      // Update active dot
      dots.forEach(function (dot, i) {
        if (i === index) {
          dot.classList.add("active");
        } else {
          dot.classList.remove("active");
        }
      });

      console.log("✅ Background changed to:", images[index]);
    }

    // Set background awal
    changeBackground(0);

    // Auto slide setiap 3 detik
    setInterval(function () {
      currentIndex = (currentIndex + 1) % images.length;
      changeBackground(currentIndex);
    }, 3000);

    // Click pada dots
    dots.forEach(function (dot, index) {
      dot.addEventListener("click", function () {
        currentIndex = index;
        changeBackground(currentIndex);
      });
    });

    console.log("🎬 Slideshow started! Will change every 3 seconds");
  });
});
