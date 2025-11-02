// ============================
// TOGGLE PASSWORD VISIBILITY + ICON
// ============================

document.addEventListener("DOMContentLoaded", () => {
  const passwordInput = document.getElementById("password");
  const togglePassword = document.getElementById("toggle-password");
  const eyeIcon = document.getElementById("eye-icon");

  togglePassword.addEventListener("click", () => {
    // Toggle tipe input
    const isPassword = passwordInput.type === "password";
    passwordInput.type = isPassword ? "text" : "password";

    // Ganti ikon sesuai kondisi
    if (isPassword) {
      eyeIcon.src = "../a/svg/eye-on.svg"; // mata terbuka
      eyeIcon.alt = "Hide Password";
    } else {
      eyeIcon.src = "../a/svg/eye-off.svg"; // mata tertutup
      eyeIcon.alt = "Show Password";
    }

    // Animasi kecil (opsional)
    eyeIcon.style.transform = "scale(0.85)";
    setTimeout(() => (eyeIcon.style.transform = "scale(1)"), 150);
  });
});
