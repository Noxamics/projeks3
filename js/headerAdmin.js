// Toggle Dropdown Setting
document.addEventListener("DOMContentLoaded", function () {
  const settingIcon = document.querySelector(".icon-setting");
  const dropdownMenu = document.querySelector(".dropdown-menu");

  if (settingIcon && dropdownMenu) {
    // Toggle dropdown saat icon di-klik
    settingIcon.addEventListener("click", function (e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle("show");
    });

    // Tutup dropdown saat klik di luar
    document.addEventListener("click", function (e) {
      if (!settingIcon.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.classList.remove("show");
      }
    });

    // Tutup dropdown saat link di-klik
    const dropdownLinks = dropdownMenu.querySelectorAll("a");
    dropdownLinks.forEach((link) => {
      link.addEventListener("click", function () {
        dropdownMenu.classList.remove("show");
      });
    });
  }
});

// === Dark Mode Toggle ===
const darkToggle = document.getElementById("darkModeToggle");
if (darkToggle) {
  // Simpan preferensi ke localStorage
  if (localStorage.getItem("darkMode") === "true") {
    document.body.classList.add("dark");
    darkToggle.checked = true;
  }

  darkToggle.addEventListener("change", () => {
    document.body.classList.toggle("dark");
    localStorage.setItem("darkMode", darkToggle.checked);
  });
}

// Scroll effect untuk header
window.addEventListener("scroll", function () {
  const header = document.querySelector(".admin-header-wrapper");
  if (window.scrollY > 50) {
    header.classList.add("scrolled");
  } else {
    header.classList.remove("scrolled");
  }
});
