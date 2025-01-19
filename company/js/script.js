let arrow = document.querySelectorAll(".arrow");
for (var i = 0; i < arrow.length; i++) {
  arrow[i].addEventListener("click", (e) => {
    let arrowParent = e.target.parentElement.parentElement; // selecting main parent of arrow
    arrowParent.classList.toggle("showMenu");
  });
}

let sidebar = document.querySelector(".sidebar");
let sidebarBtn = document.querySelector(".bx-menu");

console.log(sidebarBtn);

// Function to update the Logout text/icon
function updateLogoutText() {
  let logoutText = document.querySelector(".logout_name"); // Select the "Logout" text div
  let leftIcon = document.querySelector(".left-icon"); // Select the left icon inside .logout_name

  if (sidebar.classList.contains("close")) {
    logoutText.style.display = 'none'; // Hide the logout text when sidebar is closed
    leftIcon.style.display = 'block'; // Show the left icon when sidebar is closed
  } else {
    logoutText.style.display = 'block'; // Show the logout text when sidebar is open
    leftIcon.style.display = 'none'; // Hide the left icon when sidebar is open
  }
}

// Function to close the sidebar on web view
function checkWindowSize() {
  if (window.innerWidth >= 992) { // Check for web view (e.g., >= 992px for desktop)
    if (!sidebar.classList.contains("close")) {
      sidebar.classList.add("close"); // Close the sidebar on web view
      updateLogoutText(); // Ensure the logout text is updated
    }
  }
}

sidebarBtn.addEventListener("click", () => {
  sidebar.classList.toggle("close");
  updateLogoutText(); // Call the function when sidebar is toggled
});

// Initial check to ensure the correct state is set when the page loads
updateLogoutText();
checkWindowSize(); // Call this function to ensure the sidebar is closed on page load

// Add an event listener to check window resizing and close the sidebar if necessary
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
function closeModal_intern(modalId) {
  document.getElementById(modalId).style.display = "none";
}

// Function to handle logout
function logout_intern() {
  window.location.href = '../../endpoint/logout.php';
}

