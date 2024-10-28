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
$query = "SELECT student.*, company.company_name, company.company_address, company.company_email, company.company_image, company.company_number, 
                 address.address_barangay, address.address_street
          FROM student 
          LEFT JOIN company ON student.company = company.company_id
          LEFT JOIN address ON company.company_address = address.address_id
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
      'address_street' => 'Unknown Street'
    ];
  }
  $stmt->close(); // Close the statement
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


<body>
  <div class="header">
    <i class=""></i>
    <div class="school-name">S.Y. 2024-2025 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #095d40;">|</span>
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
        <a href="qr-code.php">
          <i class="fa-solid fa-qrcode"></i>
          <span class="link_name">QR Scanner</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="qr-code.php">QR Scanner</a></li>
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
              <select class="dropdown">
                <option value="AM">AM</option>
                <option value="PM">PM</option>
              </select>
              <input type="date" class="search-bar" placeholder="Search Date">
              <!-- <button class="filter-btn"><i style="margin-right: 3px" class="fa-solid fa-filter"></i>Filter</button> -->
            </div>
          </h2>
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Time-in</th>
                <th>Time-out</th>
                <th>Total Hours</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
              <tr>
                <td>August 20, 2024</td>
                <td>8:00 am</td>
                <td>5:00 pm</td>
                <td>9 hours</td>
              </tr>
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
      <p>Welcome back, <span style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
      <button class="proceed-btn" onclick="closeModallogin('loginSuccessModal')">Proceed</button>
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
  <script src="./js/script.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>