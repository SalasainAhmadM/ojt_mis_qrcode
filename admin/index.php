<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../index.php"); // Redirect to login page if not logged in
  exit();
}

// Fetch admin details from the database
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM admin WHERE admin_id = ?";
if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $admin_id); // Bind parameters
  $stmt->execute(); // Execute the query
  $result = $stmt->get_result(); // Get the result

  if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc(); // Fetch admin details
  } else {
    // Handle case where admin is not found
    $admin = [
      'admin_firstname' => 'Unknown',
      'admin_middle' => 'U',
      'admin_lastname' => 'User',
      'admin_email' => 'unknown@wmsu.edu.ph'
    ];
  }
  $stmt->close(); // Close the statement
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Dashboard</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" type="text/css" href="./css/style.css">
  <link rel="stylesheet" href="./css/index.css">
  <link rel="stylesheet" type="text/css" href="./css/mobile.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

<body>
  <div class="header">
    <i class="fas fa-school"></i>
    <div class="school-name">S.Y. 2024-2025 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #095d40;">|</span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
      <img src="../img/ccs.png">
    </div>
  </div>
  <div class="sidebar close">
    <div class="profile-details">
      <img src="../uploads/admin/<?php echo !empty($admin['admin_image']) ? $admin['admin_image'] : 'user.png'; ?>"
        alt="logout Image" class="logout-img">
      <div style="margin-top: 10px;" class="profile-info">
        <span
          class="profile_name"><?php echo $admin['admin_firstname'] . ' ' . $admin['admin_middle'] . '. ' . $admin['admin_lastname']; ?></span>
        <br />
        <span class="profile_email"><?php echo $admin['admin_email']; ?></span>
      </div>
    </div>
    <hr>
    <ul class="nav-links">
      <li>
        <a href="index.php" class="active">
          <i class="fas fa-th-large"></i>
          <span class="link_name">Dashboard</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="index.php">Dashboard</a></li>
        </ul>
      </li>
      <li>
        <div class="iocn-link">
          <a href="user-manage.php">
            <i class="fa-solid fa-user"></i>
            <span class="link_name">Manage Users</span>
          </a>
          <i class="fas fa-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="user-manage.php">User Management</a></li>
          <li><a href="./users/adviser.php">Adviser Management</a></li>
          <li><a href="./users/company.php">Company Management</a></li>
          <li><a href="./users/student.php">Student Management</a></li>
        </ul>
      </li>
      <li>
        <a href="others.php">
          <i class="fa-solid fa-ellipsis-h"></i>
          <span class="link_name">Others</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="others.php">Others</a></li>
        </ul>
      </li>
      <li>
        <a href="calendar.php">
          <i class="fa-regular fa-calendar-days"></i>
          <span class="link_name">Calendar</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="calendar.php">Calendar</a></li>
        </ul>
      </li>
      <li>
        <a href="setting.php">
          <i class="fas fa-cog"></i>
          <span class="link_name">Manage Profile</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="setting.php">Manage Profile</a></li>
        </ul>
      </li>
      <li>
        <a onclick="openLogoutModal()">
          <div class="logout-details">
            <div class="logout-content"></div>
            <div class="name-">
              <div class="logout_name">
                <i class="fas fa-sign-out-alt left-icon"></i>Logout
              </div>
            </div>
            <i class="fas fa-sign-out-alt right-icon"></i>
          </div>
        </a>
      </li>
    </ul>
  </div>
  <section class="home-section">
    <div class="home-content">
      <i class="fas fa-bars bx-menu"></i>
    </div>

    <div class="rectangles-container">
      <?php
      // Count Students
      $student_count_query = "SELECT COUNT(*) AS student_count FROM student";
      $student_count_result = $database->query($student_count_query);
      $student_count = $student_count_result->fetch_assoc()['student_count'];

      // Count Advisers
      $adviser_count_query = "SELECT COUNT(*) AS adviser_count FROM adviser";
      $adviser_count_result = $database->query($adviser_count_query);
      $adviser_count = $adviser_count_result->fetch_assoc()['adviser_count'];

      // Count Companies
      $company_count_query = "SELECT COUNT(*) AS company_count FROM company";
      $company_count_result = $database->query($company_count_query);
      $company_count = $company_count_result->fetch_assoc()['company_count'];

      // Output the counts
      echo "<div class='rectangle-box1'>
      <div class='box-left'>
        <span class='box-name'>STUDENTS</span><br>
        <span class='box-number'>{$student_count}</span>
      </div>
      <div class='box-right'>
        <i class='fa-solid fa-users'></i>
      </div>
    </div>";

      echo "<div class='rectangle-box2'>
        <div class='box-left'>
          <span class='box-name'>ADVISERS</span><br>
          <span class='box-number'>{$adviser_count}</span>
        </div>
        <div class='box-right'>
          <i class='fa-solid fa-chalkboard-user'></i>
        </div>
      </div>";

      echo "<div class='rectangle-box3'>
        <div class='box-left'>
          <span class='box-name'>COMPANIES</span><br>
          <span class='box-number'>{$company_count}</span>
        </div>
        <div class='box-right'>
           <i class='fa-solid fa-building'></i>
        </div>
      </div>";

      ?>

    </div>
    <div class="rectangles-container">
      <?php
      // Count Sections
      $section_count_query = "SELECT COUNT(*) AS section_count FROM course_sections";
      $section_count_result = $database->query($section_count_query);
      $section_count = $section_count_result->fetch_assoc()['section_count'];

      // Count Departments
      $department_count_query = "SELECT COUNT(*) AS department_count FROM departments";
      $department_count_result = $database->query($department_count_query);
      $department_count = $department_count_result->fetch_assoc()['department_count'];

      // Count Announcements
      $announcement_count_query = "SELECT COUNT(*) AS announcement_count FROM adviser_announcement";
      $announcement_count_result = $database->query($announcement_count_query);
      $announcement_count = $announcement_count_result->fetch_assoc()['announcement_count'];

      // Output the counts
      
      echo " <div class='rectangle-box1'>
        <div class='box-left'>
          <span class='box-name'>DEPARTMENTS</span><br>
          <span class='box-number'>{$department_count}</span>
        </div>
        <div class='box-right'>
          <i class='fa-solid fa-school'></i>
        </div>
      </div>";

      echo "<div class='rectangle-box2'>
      <div class='box-left'>
        <span class='box-name'>SECTIONS</span><br>
        <span class='box-number'>{$section_count}</span>
      </div>
      <div class='box-right'>
        <i class='fa-solid fa-clipboard-list'></i>
      </div>
    </div>";

      echo "<div class='rectangle-box3'>
        <div class='box-left'>
          <span class='box-name'>ANNOUNCEMENTS</span><br>
          <span class='box-number'>{$announcement_count}</span>
        </div>
        <div class='box-right'>
          <i class='fa-solid fa-bullhorn'></i>
        </div>
      </div>";
      ?>

    </div>
  </section>
  <!-- Login Success Modal -->
  <div id="loginSuccessModal" class="modal">
    <div class="modal-content">
      <!-- Lottie Animation -->
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Login Successful!</h2>
      <p>Welcome, <span style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
      <button class="proceed-btn" onclick="closeModal('loginSuccessModal')">Proceed</button>
    </div>
  </div>
  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="modal">
    <div class="modal-content">
      <!-- Lottie Animation -->
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/logout-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2 style="color: #000">Are you sure you want to logout?</h2>
      <div style="display: flex; justify-content: space-around; margin-top: 20px;">
        <button class="confirm-btn" onclick="logout()">Confirm</button>
        <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
      </div>
    </div>
  </div>
  <script>
    // Function to open the modal
    function openModal(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    // Function to close the modal
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = "none";
    }

    // Automatically open the modal when the page loads, if login was successful
    window.onload = function () {
      <?php if (isset($_SESSION['login_success']) && $_SESSION['login_success']): ?>
        openModal('loginSuccessModal');
        <?php unset($_SESSION['login_success']); // Clear the session variable ?>
      <?php endif; ?>
    };
  </script>
  <script src="./js/script.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>