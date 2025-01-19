<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
  header("Location: ../index.php"); // Redirect to login page if not logged in
  exit();
}

// Fetch student, company, and address details from the database
$student_id = $_SESSION['user_id'];
$query = "SELECT student.*, student.ojt_type, company.company_name, company.company_address, company.company_email, company.company_image, company.company_number, 
                 address.address_barangay
                --  street.name
          FROM student 
          LEFT JOIN company ON student.company = company.company_id
          LEFT JOIN address ON company.company_address = address.address_id
          -- LEFT JOIN street ON company.company_street = street.street_id
          WHERE student.student_id = ?";

if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $student_id); // Bind parameters
  $stmt->execute(); // Execute the query
  $result = $stmt->get_result(); // Get the result

  if ($result->num_rows > 0) {
    $student = $result->fetch_assoc(); // Fetch student, company, and address details
  } else {
    // Handle case where student is not found
    $student = [
      'student_firstname' => 'Unknown',
      'student_middle' => 'U',
      'student_lastname' => 'User',
      'student_email' => 'unknown@wmsu.edu.ph',
      'company_name' => 'N/A',
      'company_address' => 'N/A',
      'company_email' => 'N/A',
      'company_image' => 'default.png',
      'company_number' => 'N/A',
      'address_barangay' => 'Unknown Barangay',
      'address_street' => 'Unknown Street',
      'ojt_type' => 'Field-Based'
    ];
  }
  $stmt->close(); // Close the statement
}

// Set the URL for QR Scanner based on ojt_type
$qr_url = ($student['ojt_type'] === 'Project-Based') ? "qr-code_project_based.php" : "qr-code.php";

// Fetch student ID from session
$student_id = $_SESSION['user_id'];

// Check if a specific date is being filtered
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : null;

// Prepare the SQL query based on the filter
$attendance_query = "
SELECT DATE_FORMAT(time_in, '%b %d, %Y') AS attendance_date,
TIME_FORMAT(time_in, '%h:%i %p') AS time_in,
TIME_FORMAT(time_out, '%h:%i %p') AS time_out,
IFNULL(ojt_hours, 0) AS total_hours,
time_out_reason,
reason
FROM attendance
WHERE student_id = ?
";

if ($filter_date) {
  $attendance_query .= " AND DATE(time_in) = ?"; // Add date filter if provided
}

$attendance_query .= " ORDER BY DATE(time_in) DESC, time_in DESC"; // Order by date (latest first) and time-in

$attendance_data = [];
if ($stmt = $database->prepare($attendance_query)) {
  if ($filter_date) {
    $stmt->bind_param("is", $student_id, $filter_date);
  } else {
    $stmt->bind_param("i", $student_id);
  }

  $stmt->execute();
  $attendance_result = $stmt->get_result();

  while ($row = $attendance_result->fetch_assoc()) {
    $attendance_data[] = $row;
  }
  $stmt->close();
}


// Function to convert hours into "X hrs Y mins" format
function formatOjtHours($decimalHours)
{
  $totalMinutes = (int) round($decimalHours * 60); // Convert hours to minutes
  $hours = intdiv($totalMinutes, 60); // Get whole hours
  $minutes = $totalMinutes % 60; // Get remaining minutes

  $formatted = [];
  if ($hours > 0) {
    $formatted[] = $hours . ($hours > 1 ? 'hrs' : 'hr');
  }
  if ($minutes > 0) {
    $formatted[] = $minutes . 'mins';
  }

  return !empty($formatted) ? implode(' ', $formatted) : '0 mins';
}

// Fetch announcements
$announcement_query = "SELECT announcement_name, announcement_date, announcement_description
FROM adviser_announcement
ORDER BY announcement_date DESC";
$announcement_result = $database->query($announcement_query);

$announcements = [];
if ($announcement_result->num_rows > 0) {
  while ($row = $announcement_result->fetch_assoc()) {
    $announcements[] = $row;
  }
}

$today = date('Y-m-d'); // Get the current date
$holiday_message = '';
$memo_link = '';

$holiday_query = "SELECT holiday_name, memo FROM holiday WHERE holiday_date = ?";
if ($stmt = $database->prepare($holiday_query)) {
  $stmt->bind_param("s", $today);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $holiday = $result->fetch_assoc();
    $holiday_message = "Today is a Holiday: " . $holiday['holiday_name']; // Store holiday message
    $memo_link = $holiday['memo']; // Fetch memo file path
  }
  $stmt->close();
}


// Fetch company_id associated with the student
$query = "
SELECT student.student_id, student.student_firstname, student.student_lastname, company.company_id
FROM student
JOIN company ON student.company = company.company_id
WHERE student.student_id = ?";

if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $student_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $company_id = $data['company_id'];
  }
  $stmt->close();
}
// Check if today's schedule for the student's company is marked as "Suspended"
$suspended_message = '';
$schedule_query = "SELECT day_type FROM schedule WHERE company_id = ? AND date = ?";
if ($stmt = $database->prepare($schedule_query)) {
  $stmt->bind_param("is", $company_id, $today);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $schedule = $result->fetch_assoc();
    if ($schedule['day_type'] === 'Suspended') {
      $suspended_message = "No Duty! Today is Suspended.";
    }
  }
  $stmt->close();
}

// Determine final message (either holiday or suspension notice)
$login_message = $holiday_message ?: $suspended_message;

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
  <title>Intern - Home</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <!-- <link rel="stylesheet" href="./css/style.css"> -->
  <!-- <link rel="stylesheet" href="./css/index.css"> -->
  <link rel="stylesheet" href="../css/mobile.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>
<style>
  /* For Mobile Screens */
  @media (max-width: 768px) {
    .bx-menu {
      display: block;
      /* Show the hamburger icon in mobile view */
    }

    .sidebar.close {
      width: 78px;
      margin-left: -78px;
    }




    .home-section .home-content .bx-menu {
      margin: 0 15px;
      cursor: pointer;
      margin-left: -68px;

    }

    .home-section .home-content .text {
      font-size: 26px;
      font-weight: 600;
      margin-left: -68px;
    }

    .header-box {
      margin-left: 10px;
      width: 110%;
      padding-left: 10px;
      width: calc(110% - 60px);
      margin-left: -68px;
    }

    .left-box-qr,
    .right-box-qr {
      margin-left: -68px;
      width: 120%;
    }

    .left-box,
    .right-box,
    .intern-company {
      margin-left: -68px;
      width: 120%;
    }

    .whole-box {
      padding: 0px;
      padding-left: 10px;
      padding-right: 0px;
      margin-left: -68px;
      width: 120%;
    }

    .qr-camera {
      margin-left: 45px;
    }

    .content-wrapper {
      margin-top: 0;
    }
  }

  @media (max-width: 768px) {
    .modal-content {
      z-index: 1000;
      width: 80%;
      padding: 15px;
    }

    .form-container {
      width: 95%;
      flex-direction: column;
      padding: 10px;
      margin-left: 10px;
      margin-right: 10px;
      font-size: 12px;
    }
  }

  /* For Web/Desktop Screens */
  @media (min-width: 769px) {
    .bx-menu {
      display: none;
      /* Hide the hamburger icon in web/desktop view */
    }
  }

  /* Sidebar */
  @media (max-width: 420px) {
    .sidebar.close .nav-links li .sub-menu {
      display: none;
    }
  }
</style>

<body>
  <div class="header">
    <i class=""></i>
    <div class="school-name">
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $currentSemester; ?> &nbsp;&nbsp;&nbsp;
      <span id="sy-text"></span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #095d40;">|</span>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
      <img src="../img/ccs.png">
    </div>
  </div>
  <div class="sidebar close">
    <div class="profile-details">
      <img
        src="../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
        alt="logout Image" class="logout-img">
      <div style="margin-top: 10px;" class="profile-info">
        <span
          class="profile_name"><?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '. ' . $student['student_lastname']; ?></span>
        <br />
        <span class="profile_email"><?php echo $student['student_email']; ?></span>
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
        <a href="journal.php">
          <i class="fa-solid fa-pen"></i>
          <span class="link_name">Journal</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="journal.php">Journal</a></li>
        </ul>
      </li>
      <li>
        <a href="<?php echo $qr_url; ?>">
          <i class="fa-solid fa-qrcode"></i>
          <span class="link_name">QR Scanner</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="<?php echo $qr_url; ?>">QR Scanner</a></li>
        </ul>
      </li>

      <?php if ($student['ojt_type'] !== 'Project-Based'): ?>
        <li>
          <a href="dtr.php">
            <i class="fa-solid fa-clipboard-question"></i>
            <span class="link_name">Remarks</span>
          </a>
          <ul class="sub-menu blank">
            <li><a class="link_name" href="dtr.php">Remarks</a></li>
          </ul>
        </li>
      <?php endif; ?>


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
    <div class="content-wrapper">
      <div class="intern-company">
        <div class="company-logo">
          <img
            src="../uploads/company/<?php echo !empty($student['company_image']) ? $student['company_image'] : 'user.png'; ?>"
            alt="Company Logo">
        </div>
        <div class="details">
          <h2><?php echo !empty($student['company_name']) ? $student['company_name'] : 'Company Name' ?></h2>
          <label><?php echo !empty($student['company_address']) ? $student['company_address'] : 'Company Address' ?></label>
          <br>
          <div class="contact-info">
            <span><?php echo !empty($student['company_email']) ? $student['company_email'] : 'Company Email' ?> <span
                class="line"> |</span></span>

            <span><?php echo !empty($student['company_number']) ? $student['company_number'] : 'Company Number' ?></span>
          </div>
        </div>
      </div>
      <div class="main-box">
        <div class="left-box">
          <h2>
            Attendance Details
            <div class="filter-group">
              <form method="GET" action="">
                <input type="date" class="search-bar" name="filter_date"
                  value="<?php echo isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : ''; ?>"
                  onchange="this.form.submit()">
              </form>
            </div>
          </h2>
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Time-in</th>
                <th>Time-out</th>
                <th>Total Hours</th>
                <th>Reason</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($attendance_data)): ?>
                <?php foreach ($attendance_data as $attendance): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($attendance['attendance_date']); ?></td>
                    <td><?php echo htmlspecialchars($attendance['time_in']); ?></td>
                    <td><?php echo htmlspecialchars($attendance['time_out'] ?? 'N/A'); ?></td>
                    <td><?php echo formatOjtHours($attendance['total_hours']); ?></td>
                    <td><?php echo htmlspecialchars($attendance['reason'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($attendance['time_out_reason'] ?? 'N/A'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4">No attendance records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="right-box">
          <h2>Announcements</h2>

          <?php if (!empty($announcements)): ?>
            <?php foreach ($announcements as $announcement): ?>
              <div class="announcement">
                <h3><?php echo htmlspecialchars($announcement['announcement_name']); ?></h3>
                <p><?php echo date('F j, Y', strtotime($announcement['announcement_date'])); ?></p>
                <p><?php echo nl2br(htmlspecialchars($announcement['announcement_description'])); ?></p>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No announcements available.</p>
          <?php endif; ?>
        </div>

      </div>
    </div>

  </section>

  <!-- Profile Update Success Modal -->
  <div id="profileUpdateSuccessModal" class="modal">
    <div class="modal-content">
      <!-- Lottie Animation -->
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2>Profile Updated Successfully!</h2>
      <p>Your profile information has been updated successfully.</p>
      <button class="proceed-btn" onclick="closeModalprofile('profileUpdateSuccessModal')">Proceed</button>
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
      <p>Welcome back, <span
          style="color: #095d40; font-size: 20px;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span></p>

      <!-- Display Holiday or Suspension Message -->
      <?php if (!empty($holiday_message)): ?>
        <p style="color: red; font-size: 18px;"><?php echo htmlspecialchars($holiday_message); ?></p>

        <?php if (!empty($memo_link)): ?>
          <?php
          $file_extension = strtolower(pathinfo($memo_link, PATHINFO_EXTENSION));
          $file_path = "../uploads/admin/memos/" . $memo_link;

          if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
            <div>
              <a style="text-decoration: none;" href="#" onclick="viewImage('<?php echo $file_path; ?>')">
                <i class="fa-solid fa-file-image" style="color: #4CAF50; font-size: 24px; "></i> <span>View Memo</span>
              </a>
            </div>
          <?php elseif ($file_extension === 'pdf'): ?>
            <div>
              <a style="text-decoration: none;" href="<?php echo $file_path; ?>" download>
                <i class="fa-solid fa-file-pdf" style="color: #8B0000; font-size: 24px;"></i> <span>Memorandum</span>
              </a>
            </div>
          <?php elseif (in_array($file_extension, ['doc', 'docx'])): ?>
            <div>
              <a style="text-decoration: none;" href="<?php echo $file_path; ?>" download>
                <i class="fa-solid fa-file-word" style="color: #0072C6; font-size: 24px;"></i> <span>Memorandum</span>
              </a>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>

      <button class="proceed-btn" onclick="closeModallogin('loginSuccessModal')">Proceed</button>
    </div>
  </div>



  <!-- Holiday Modal -->
  <div id="holidayModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/alert-8B0000.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay></lottie-player>
      </div>
      <h2 style="color: #8B0000">It's a Holiday!</h2>
      <p><strong><?php echo date('F j, Y'); ?></strong></p>
      <p style="color: #8B0000"><strong><?php echo $holiday['holiday_name']; ?></strong></p>
      <button class="proceed-btn" onclick="closeModal('holidayModal')">Close</button>
    </div>
  </div>
  <!-- Suspended Modal -->
  <div id="suspendedModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/alert-8B0000.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay></lottie-player>
      </div>
      <h2 style="color: #8B0000">Schedule Suspended!</h2>
      <p><strong><?php echo date('F j, Y'); ?></strong></p>
      <button class="proceed-btn" onclick="closeModal('suspendedModal')">Close</button>
    </div>
  </div>

  <!-- Weekend Modal -->
  <div id="weekendModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div style="display: flex; justify-content: center; align-items: center;">
        <lottie-player src="../animation/alert-8B0000.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay></lottie-player>
      </div>
      <h2 style="color: #8B0000">It's a Weekend!</h2>
      <p><strong><?php echo date('F j, Y'); ?></strong></p>
      <button class="proceed-btn" onclick="closeModal('weekendModal')">Close</button>
    </div>
  </div>
  <script>
    function viewImage(imagePath) {
      const modal = document.createElement('div');
      modal.style.position = 'fixed';
      modal.style.top = '0';
      modal.style.left = '0';
      modal.style.width = '100%';
      modal.style.height = '100%';
      modal.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
      modal.style.display = 'flex';
      modal.style.justifyContent = 'center';
      modal.style.alignItems = 'center';
      modal.style.zIndex = '1000';
      modal.style.overflow = 'auto';

      const img = document.createElement('img');
      img.src = imagePath;
      img.style.maxWidth = '90%';
      img.style.maxHeight = '90%';
      img.style.margin = 'auto';
      modal.appendChild(img);

      // Close the modal on click
      modal.onclick = () => document.body.removeChild(modal);

      document.body.appendChild(modal);
    }


    function showModalprofile(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    function closeModalprofile(modalId) {
      document.getElementById(modalId).style.display = "none";
    }

    function showModallogin(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    function closeModallogin(modalId) {
      document.getElementById(modalId).style.display = "none";
    }
    // Show the modal if the session variable is set
    <?php if (isset($_SESSION['profile_update_success']) && $_SESSION['profile_update_success']): ?>
      window.onload = function () {
        showModalprofile('profileUpdateSuccessModal');
        <?php unset($_SESSION['profile_update_success']); ?>
      };
    <?php elseif (isset($_SESSION['login_success']) && $_SESSION['login_success']): ?>
      window.onload = function () {
        showModallogin('loginSuccessModal');
        <?php unset($_SESSION['login_success']); ?>
      };
    <?php endif; ?>
  </script>

  <script src="../js/sy.js"></script>
  <script src="./js/script.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>