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
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/mobile.css">
  <!-- <link rel="stylesheet" href="./css/index.css">
  <link rel="stylesheet" href="./css/style.css">
  <link rel="stylesheet" href="./css/mobile.css"> -->
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
            // Fetch sections assigned to the adviser
            $course_section_query = "SELECT course_section_name FROM course_sections WHERE adviser_id = ?";
            if ($section_stmt = $database->prepare($course_section_query)) {
              $section_stmt->bind_param("i", $adviser_id);
              $section_stmt->execute();
              $course_section_result = $section_stmt->get_result();
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
                    echo "<option value=''>No sections available</option>";
                  }
                  ?>
                </select>
              </div>

              <?php
              $section_stmt->close();
            }
            ?>

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
                <th class="image">Profile</th>
                <th class="name">Intern Name</th>
                <th class="timein">Time-in</th>
                <th class="timeout">Time-out</th>
                <th class="duration">Duration</th>
                <th class="status">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($students)): ?>
                <?php foreach ($students as $student_id => $attendances): ?>
                  <?php
                  $first_time_in = null;
                  $latest_time_in_without_out = null;
                  $latest_time_out = null;
                  $total_hours_today = 0;

                  foreach ($attendances as $attendance) {
                    if (!$first_time_in || strtotime($attendance['time_in']) < strtotime($first_time_in)) {
                      $first_time_in = $attendance['time_in'];
                    }
                    if ($attendance['time_in'] && !$attendance['time_out']) {
                      $latest_time_in_without_out = $attendance['time_in'];
                    }
                    if ($attendance['time_out'] && (!$latest_time_out || strtotime($attendance['time_out']) > strtotime($latest_time_out))) {
                      $latest_time_out = $attendance['time_out'];
                    }
                    $total_hours_today += $attendance['ojt_hours'] ?? 0;
                  }

                  $displayed_time_out = $latest_time_in_without_out ? '' : ($latest_time_out ? date('h:i A', strtotime($latest_time_out)) : 'N/A');
                  $status = $latest_time_in_without_out ? '<span style="color:green;">Timed-in</span>' : '<span style="color:red;">Timed-out</span>';
                  ?>
                  <tr>
                    <td class="image">
                      <img style="border-radius: 50%;"
                        src="../uploads/student/<?php echo !empty($attendance['student_image']) ? $attendance['student_image'] : 'user.png'; ?>"
                        alt="Student Image">
                    </td>
                    <td class="name">
                      <?php echo $attendances[0]['student_firstname'] . ' ' . $attendances[0]['student_middle'] . '.' . ' ' . $attendances[0]['student_lastname']; ?>
                    </td>
                    <td class="timein">
                      <?php echo $first_time_in ? date('h:i A', strtotime($first_time_in)) : 'N/A'; ?>
                    </td>
                    <td class="timeout"><?php echo $displayed_time_out; ?></td>
                    <td class="duration">
                      <?php echo $total_hours_today > 0 ? formatDuration($total_hours_today) : 'N/A'; ?>
                    </td>
                    <td class="status"><?php echo $status; ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6">No attendance yet for this day.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>


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