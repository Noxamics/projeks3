// Filter catalog by category with button click
function filterCategory(category, button) {
  const boxes = document.querySelectorAll(".catalog-box");
  const allButtons = document.querySelectorAll(".pill-btn");

  // Remove active class from all buttons
  allButtons.forEach((btn) => btn.classList.remove("active"));

  // Add active class to clicked button
  if (button) {
    button.classList.add("active");
  }

  // Filter boxes
  boxes.forEach((box) => {
    if (category === "all") {
      box.style.display = "flex";
    } else {
      if (box.dataset.category === category) {
        box.style.display = "flex";
      } else {
        box.style.display = "none";
      }
    }
  });
}

// Initialize: show all on page load
document.addEventListener("DOMContentLoaded", function () {
  const allButton = document.querySelector(".pill-btn.active");
  if (allButton) {
    filterCategory("all", allButton);
  }
});
