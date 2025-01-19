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
$currentSemester = "1st Sem";
$semesterQuery = "SELECT `type` FROM `semester` WHERE `id` = 1";
if ($result = $database->query($semesterQuery)) {
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentSemester = $row['type'];
  }
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
  <link rel="stylesheet" href="./css/mobile.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

<body>
  <?php
  if (isset($_SESSION['success'])) {
    if ($_SESSION['success'] === true) {
      echo '<script>
      document.addEventListener("DOMContentLoaded", function() {
        openModal("updateSemesterSuccessModal");
      });
    </script>';
    }
    unset($_SESSION['success']);
  }
  ?>

  <div class="header">
    <i class="fas fa-school"></i>
    <div class="school-name">
      <i style="color: #095d40; cursor: pointer;" class="fas fa-edit" onclick="openModal('updateSemesterModal')"></i>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $currentSemester; ?> &nbsp;&nbsp;&nbsp;
      <span id="sy-text"></span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #095d40;">|</span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
      <img src="../img/ccs.png">
    </div>
  </div>

  <!-- Update Semester Modal -->
  <div id="updateSemesterModal" class="modal">
    <div class="modal-content-others">
      <span class="close" onclick="closeModal('updateSemesterModal')">&times;</span>
      <h2>Update Semester</h2>
      <form action="./update_semester.php" method="POST">
        <div class="input-group">
          <label for="semesterType">Select Semester</label>
          <select style="width: 100%;
    padding: 10px;
    margin: 5px 0 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;" id="semesterType" name="semesterType" required>
            <option value="1st Sem" <?php echo ($currentSemester === '1st Sem') ? 'selected' : ''; ?>>1st Sem</option>
            <option value="2nd Sem" <?php echo ($currentSemester === '2nd Sem') ? 'selected' : ''; ?>>2nd Sem</option>
            <option value="Summer" <?php echo ($currentSemester === 'Summer') ? 'selected' : ''; ?>>Summer</option>
          </select>
        </div>
        <button type="submit" class="modal-btn">Update Semester</button>
      </form>
    </div>
  </div>
  <!-- Success Modal for Updating Semester -->
  <div id="updateSemesterSuccessModal" class="modal">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Updated Successfully!</h2>
      <p>The semester has been updated successfully!</p>
      <button class="proceed-btn" onclick="closeModal('updateSemesterSuccessModal')">Close</button>
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
        <a href="feedback.php">
          <i class="fa-solid fa-percent"></i>
          <span class="link_name">Feedback</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="feedback.php">Feedback Management</a></li>
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
      <i style="z-index: 100;" class="fas fa-bars bx-menu"></i>
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

    <?php
    $sql = "SELECT required_hours FROM required_hours ORDER BY required_hours_id DESC LIMIT 1";
    $result = $database->query($sql);
    $currentOjtHours = ($result->num_rows > 0) ? $result->fetch_assoc()['required_hours'] : 0;

    $database->close();
    ?>
    <div class="rectangles-container">
      <div class='rectangle-box1' id="ojtHoursBox" onclick="openModal('updateOjtModal')">
        <div class='box-left'>
          <span class='box-name'>OJT HOURS</span><br>
          <span class='box-number'><?php echo $currentOjtHours; ?> hours</span>
        </div>
        <div class='box-right'>
          <i class="fa-solid fa-business-time"></i>
        </div>
      </div>
    </div>

  </section>

  <!-- Updating OJT Hours Modal -->
  <div id="updateOjtModal" class="modal">
    <div class="modal-content-others">
      <span class="close" onclick="closeModal('updateOjtModal')">&times;</span>
      <h2>Update OJT Hours</h2>
      <form action="./others/update_ojt_hours.php" method="POST">
        <div class="input-group">
          <label for="ojtHours">OJT Hours</label>
          <input type="number" id="ojtHours" name="ojtHours" placeholder="Enter new OJT hours"
            value="<?php echo $currentOjtHours; ?>" required>
        </div>
        <button type="submit" class="modal-btn">Update Hours</button>
      </form>
    </div>
  </div>


  <!-- Success Modal for Updating OJT Hours -->
  <div id="updateOjtSuccessModal" class="modal">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Updated Successfully!</h2>
      <p>The OJT hours updated successfully!</p>
      <button class="proceed-btn" onclick="closeModal('updateOjtSuccessModal')">Close</button>
    </div>
  </div>

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

    // Function to open a modal by its ID
    function showModal(modalId) {
      document.getElementById(modalId).style.display = 'block';
    }

    // Check session variables for success messages and open respective modals
    window.onload = function () {
      <?php if (isset($_SESSION['update_success'])): ?>
        showModal('updateOjtSuccessModal');
        <?php unset($_SESSION['update_success']); // Remove after displaying ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['login_success']) && $_SESSION['login_success']): ?>
        openModal('loginSuccessModal');
        <?php unset($_SESSION['login_success']); // Clear after displaying ?>
      <?php endif; ?>
    };
  </script>

  <script src="../js/sy.js"></script>
  <script src="./js/script.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>