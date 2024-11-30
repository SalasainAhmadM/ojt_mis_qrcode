let arrow = document.querySelectorAll(".arrow");
for (var i = 0; i < arrow.length; i++) {
  arrow[i].addEventListener("click", (e) => {
    let arrowParent = e.target.parentElement.parentElement;
    arrowParent.classList.toggle("showMenu");
  });
}

let sidebar = document.querySelector(".sidebar");
let sidebarBtn = document.querySelector(".bx-menu");

// Function to update the bx-menu's margin-left dynamically
function updateMenuButtonMargin() {
  if (sidebar.classList.contains("close")) {
    sidebarBtn.style.marginLeft = "-68px"; // Adjust as needed for closed sidebar
  } else {
    sidebarBtn.style.marginLeft = "20px"; // Adjust as needed for open sidebar
  }
}

// Toggle sidebar and update bx-menu styles
sidebarBtn.addEventListener("click", () => {
  sidebar.classList.toggle("close");
  updateMenuButtonMargin();
  updateLogoutText();
});

// Initial updates on page load
updateMenuButtonMargin();
updateLogoutText();

// Adjust sidebar state and styles for larger screens
function checkWindowSize() {
  if (window.innerWidth >= 992) {
    if (!sidebar.classList.contains("close")) {
      sidebar.classList.add("close");
      updateMenuButtonMargin();
      updateLogoutText();
    }
  }
}

checkWindowSize();
window.addEventListener("resize", checkWindowSize);

// Function to update logout text visibility based on sidebar state
function updateLogoutText() {
  let logoutText = document.querySelector(".logout_name");
  let leftIcon = document.querySelector(".left-icon");

  if (sidebar.classList.contains("close")) {
    logoutText.style.display = 'none';
    leftIcon.style.display = 'block';
  } else {
    logoutText.style.display = 'block';
    leftIcon.style.display = 'none';
  }
}

// Modal handling functions
function openLogoutModal() {
  document.getElementById("logoutModal").style.display = "block";
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
}

// Handle logout
function logout() {
  window.location.href = '../endpoint/logout.php';
}
