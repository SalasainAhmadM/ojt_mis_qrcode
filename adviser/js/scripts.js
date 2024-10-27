let arrow = document.querySelectorAll(".arrow");
for (var i = 0; i < arrow.length; i++) {
  arrow[i].addEventListener("click", (e) => {
    let arrowParent = e.target.parentElement.parentElement;
    arrowParent.classList.toggle("showMenu");
  });
}

let sidebar = document.querySelector(".sidebar");
let sidebarBtn = document.querySelector(".bx-menu");

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

function checkWindowSize() {
  if (window.innerWidth >= 992) {
    if (!sidebar.classList.contains("close")) {
      sidebar.classList.add("close");
      updateLogoutText();
    }
  }
}

sidebarBtn.addEventListener("click", () => {
  sidebar.classList.toggle("close");
  updateLogoutText();
});

updateLogoutText();
checkWindowSize();

window.addEventListener("resize", checkWindowSize);


// Function to open the modal
function openLogoutModal() {
  document.getElementById("logoutModal").style.display = "block";
}
// Function to close the modal
function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
}

// Function to handle logout
function logout() {
  window.location.href = '../endpoint/logout.php';
}

// Function to close the modal
function closeModal2(modalId) {
  document.getElementById(modalId).style.display = "none";
}

// Function to handle logout
function logout2() {
  window.location.href = '../../endpoint/logout.php';
}
