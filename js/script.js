// ============================
// FIXED NAVIGATION SCRIPT
// Replace bagian navigation active state di header-lp.php
// ============================

document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector("header");
  const sections = document.querySelectorAll("section[id]");
  // PENTING: Ambil SEMUA nav links (desktop + mobile)
  const navLinks = document.querySelectorAll(".nav-right a, .nav-mobile a");
  const logo = document.getElementById("logo-img");

  // Mobile Menu Elements
  const mobileMenuToggle = document.getElementById("mobileMenuToggle");
  const navMobile = document.getElementById("navMobile");
  const mobileLinks = document.querySelectorAll(".nav-mobile a");

  // ============================
  // FUNGSI: UPDATE ACTIVE LINK
  // ============================
  function updateActiveLink(targetId) {
    navLinks.forEach((link) => {
      link.classList.remove("active");
      const href = link.getAttribute("href");

      // Check apakah link mengarah ke section yang aktif
      if (href && href.includes(`#${targetId}`)) {
        link.classList.add("active");
      }
    });
  }

  // ============================
  // SET DEFAULT ACTIVE: HOME
  // ============================
  // Pastikan HOME aktif saat pertama kali load
  window.addEventListener("load", () => {
    updateActiveLink("heroSection");
  });

  // ============================
  // MOBILE MENU FUNCTIONS
  // ============================
  function showMobileMenu() {
    navMobile.classList.add("active");
    mobileMenuToggle.classList.add("active");
    document.body.classList.add("menu-open");

    const icon = mobileMenuToggle.querySelector("i");
    if (icon) {
      icon.classList.remove("fa-bars");
      icon.classList.add("fa-times");
    }
  }

  function hideMobileMenu() {
    navMobile.classList.remove("active");
    mobileMenuToggle.classList.remove("active");
    document.body.classList.remove("menu-open");

    const icon = mobileMenuToggle.querySelector("i");
    if (icon) {
      icon.classList.remove("fa-times");
      icon.classList.add("fa-bars");
    }
  }

  // Event listeners for mobile menu toggle
  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener("click", function () {
      if (navMobile.classList.contains("active")) {
        hideMobileMenu();
      } else {
        showMobileMenu();
      }
    });
  }

  // Close menu when clicking navigation links
  mobileLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      if (!this.getAttribute("href").includes("catalog.php")) {
        hideMobileMenu();
      }
    });
  });

  // Close menu on escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      hideMobileMenu();
    }
  });

  // ============================
  // HEADER SCROLL EFFECT + CHANGE LOGO
  // ============================
  window.addEventListener("scroll", () => {
    const isScrolled = window.scrollY > 30;
    header.classList.toggle("scrolled", isScrolled);

    if (logo) {
      if (isScrolled) {
        logo.src = "../a/img/Logo Sengku.png";
      } else {
        logo.src = "../a/img/LGG.png";
      }
    }
  });

  // ============================
  // ACTIVE NAV LINK ON SCROLL (FIXED)
  // ============================
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && entry.target.tagName === "SECTION") {
          const id = entry.target.getAttribute("id");
          // Gunakan fungsi updateActiveLink yang baru
          updateActiveLink(id);
        }
      });
    },
    {
      threshold: 0.3,
      rootMargin: "-80px 0px 0px 0px", // Kompensasi tinggi header
    }
  );

  sections.forEach((section) => observer.observe(section));

  // ============================
  // SMOOTH SCROLL FOR ANCHOR LINKS
  // ============================
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const href = this.getAttribute("href");
      const targetId = href.replace("#", "");
      const target = document.getElementById(targetId);

      if (target) {
        const headerHeight = header.offsetHeight;
        const targetPosition = target.offsetTop - headerHeight;

        window.scrollTo({
          top: targetPosition,
          behavior: "smooth",
        });

        // Update active state immediately saat diklik
        updateActiveLink(targetId);
      }
    });
  });
});
