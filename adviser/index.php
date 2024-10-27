<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
  header("Location: ../index.php"); // Redirect to login page if not logged in
  exit();
}

// Fetch adviser details from the database
$adviser_id = $_SESSION['user_id'];
$query = "SELECT * FROM adviser WHERE adviser_id = ?";
if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $adviser_id); // Bind parameters
  $stmt->execute(); // Execute the query
  $result = $stmt->get_result(); // Get the result

  if ($result->num_rows > 0) {
    $adviser = $result->fetch_assoc(); // Fetch adviser details
  } else {
    // Handle case where adviser is not found
    $adviser = [
      'adviser_firstname' => 'Unknown',
      'adviser_middle' => 'U',
      'adviser_lastname' => 'User',
      'adviser_email' => 'unknown@wmsu.edu.ph'
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
  <title>Adviser - Home</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="./css/index.css">
  <link rel="stylesheet" href="./css/style.css">
  <link rel="stylesheet" href="./css/mobile.css">
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
      <img
        src="../uploads/adviser/<?php echo !empty($adviser['adviser_image']) ? $adviser['adviser_image'] : 'user.png'; ?>"
        alt="logout Image" class="logout-img">
      <div style="margin-top: 10px;" class="profile-info">
        <span
          class="profile_name"><?php echo $adviser['adviser_firstname'] . ' ' . $adviser['adviser_middle'] . '. ' . $adviser['adviser_lastname']; ?></span>
        <br />
        <span class="profile_email"><?php echo $adviser['adviser_email']; ?></span>
      </div>
    </div>
    <hr>
    <ul class="nav-links">
      <li>
        <a href="index.php" class="active">
          <i class="fa-solid fa-house"></i>
          <span class="link_name">Home</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="index.php">Home</a></li>
        </ul>
      </li>
      <li>
        <div class="iocn-link">
          <a href="interns.php">
            <i class="fa-solid fa-user"></i>
            <span class="link_name">Interns</span>
          </a>
          <i class="fas fa-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="interns.php">Manage Interns</a></li>
          <!-- <li><a href="./intern/intern-profile.php">Student Profile</a></li> -->
          <li><a href="./intern/intern-reports.php">Intern Reports</a></li>
        </ul>
      </li>
      <li>
        <div class="iocn-link">
          <a href="company.php">
            <i class="fa-regular fa-building"></i>
            <span class="link_name">Company</span>
          </a>
          <i class="fas fa-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="company.php">Manage Company</a></li>
          <li><a href="./company/company-intern.php">Company Interns</a></li>
          <li><a href="./company/company-feedback.php">Company List</a></li>
          <li><a href="./company/company-intern-feedback.php">Intern Feedback</a></li>
        </ul>
      </li>
      <li>
        <a href="attendance.php">
          <i class="fa-regular fa-clock"></i>
          <span class="link_name">Attendance</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="attendance.php">Attendance</a></li>
        </ul>
      </li>
      <li>
        <a href="announcement.php">
          <i class="fa-solid fa-bullhorn"></i>
          <span class="link_name">Announcement</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="announcemnet.php">Announcement</a></li>
        </ul>
      </li>
      <li>
        <a href="message.php">
          <i class="fa-regular fa-comments"></i>
          <span class="link_name">Message</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="message.php">Message</a></li>
        </ul>
      </li>
      <!-- <li>
        <a href="others.php">
          <i class="fa-solid fa-ellipsis-h"></i>
          <span class="link_name">Others</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="others.php">Others</a></li>
        </ul>
      </li> -->
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

  <style>
    .rectangles-container {
      display: flex;
      justify-content: space-between;
      height: 15%;
      margin-bottom: 20px;
    }

    .rectangle-box1,
    .rectangle-box2,
    .rectangle-box3 {
      background-color: #fff;
      color: #095d40;
      padding: 10px;
      text-align: center;
      width: 20%;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .rectangle-box1 {
      margin-left: 120px;
    }

    .rectangle-box3 {
      margin-right: 120px;
    }

    .rectangle-box1,
    .rectangle-box2,
    .rectangle-box3 {
      border-left: 3px solid #095d40;
    }

    .box-left {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      padding-left: 10px;
    }

    .box-name {
      font-size: 16px;
      font-weight: bold;
    }

    .box-number {
      font-size: 20px;
      text-align: center;
      font-weight: bold;
      color: #000;
      justify-content: center;
    }

    .box-right i {
      font-size: 32px;
    }

    .box-right {
      font-size: 24px;
      display: flex;
      justify-content: center;
      align-items: center;
      padding-right: 20px;
    }
  </style>

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

      // Count Sections
      $section_count_query = "SELECT COUNT(*) AS section_count FROM course_sections";
      $section_count_result = $database->query($section_count_query);
      $section_count = $section_count_result->fetch_assoc()['section_count'];

      // Count Announcements
      $announcement_count_query = "SELECT COUNT(*) AS announcement_count FROM adviser_announcement";
      $announcement_count_result = $database->query($announcement_count_query);
      $announcement_count = $announcement_count_result->fetch_assoc()['announcement_count'];

      // Output the counts
      echo "<div class='rectangle-box1'>
        <div class='box-left'>
          <span class='box-name'>SECTIONS</span><br>
          <span class='box-number'>{$section_count}</span>
        </div>
        <div class='box-right'>
          <i class='fa-solid fa-clipboard-list'></i>
        </div>
      </div>";

      echo "<div class='rectangle-box2'>
        <div class='box-left'>
          <span class='box-name'>STUDENTS</span><br>
          <span class='box-number'>{$student_count}</span>
        </div>
        <div class='box-right'>
          <i class='fa-solid fa-users'></i>
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
    <div class="content-wrapper">
      <div class="header-box">
        <label style="color: #a6a6a6; margin-left: 10px;">Attendance</label>
      </div>
      <div class="main-box">
        <div class="whole-box">
          <div class="filter-group-home">

            <?php
            $course_section_query = "SELECT course_section_name FROM course_sections";
            $course_section_result = $database->query($course_section_query);
            ?>

            <div class="section-section">
              <span><strong>Section</strong></span><br>
              <select class="dropdown">
                <?php
                if ($course_section_result->num_rows > 0) {
                  while ($row = $course_section_result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($row['course_section_name']) . "'>" . htmlspecialchars($row['course_section_name']) . "</option>";
                  }
                } else {
                  echo "<option value=''>No companies available</option>";
                }
                ?>
              </select>
            </div>

            <?php
            $company_query = "SELECT company_name FROM company";
            $company_result = $database->query($company_query);
            ?>

            <div class="company-section">
              <span><strong>Company</strong></span><br>
              <select class="dropdown">
                <?php
                if ($company_result->num_rows > 0) {
                  while ($row = $company_result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($row['company_name']) . "'>" . htmlspecialchars($row['company_name']) . "</option>";
                  }
                } else {
                  echo "<option value=''>No companies available</option>";
                }
                ?>
              </select>
            </div>
            <div class="date-section">
              <span></span><br>
              <input type="date" class="search-bar" placeholder="Search Date">
            </div>
            <div class="search-section">
              <span><strong>Search</strong></span><br>
              <input type="text" class="search-bar" placeholder="Search...">
            </div>
          </div>

          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Section</th>
                <th>Date</th>
                <th>Time-in</th>
                <th>Time-out</th>
                <th>Time-in</th>
                <th>Time-out</th>
                <th>Total Hours</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>Maloi</td>
                <td>BSIT-4B</td>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>12:00 pm</td>
                <td>1:00 pm</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <!-- Login Success Modal -->
  <div id="loginSuccessModal" class="modal">
    <div class="modal-content">
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
  <script src="./js/scripts.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>