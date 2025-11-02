// === Toggle Dropdown Pengaturan ===
const settingIcon = document.querySelector(".icon-setting");
const dropdownMenu = document.querySelector(".dropdown-menu");

if (settingIcon) {
  settingIcon.addEventListener("click", (e) => {
    e.stopPropagation();
    dropdownMenu.style.display =
      dropdownMenu.style.display === "block" ? "none" : "block";
  });

  // Klik di luar menutup dropdown
  window.addEventListener("click", (e) => {
    if (!e.target.closest(".setting-dropdown")) {
      dropdownMenu.style.display = "none";
    }
  });
}

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
