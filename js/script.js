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
  // HERO SLIDESHOW
  // ============================
  const bgImages = ["../a/img/BG1.jpg", "../a/img/BG2.jpg", "../a/img/BG3.jpg"];
  let current = 0;

  function changeBackground() {
    if (!hero) return;
    hero.style.backgroundImage = `url('${bgImages[current]}')`;
    dots.forEach((dot) => dot.classList.remove("active"));
    if (dots[current]) dots[current].classList.add("active");
    current = (current + 1) % bgImages.length;
  }

  setInterval(changeBackground, 3000);
});
