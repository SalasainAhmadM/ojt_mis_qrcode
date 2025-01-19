<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
  header("Location: ../index.php"); // Redirect to login page if not logged in
  exit();
}

// Fetch company details from the database
$company_id = $_SESSION['user_id'];
$query = "SELECT * FROM company WHERE company_id = ?";
if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $company_id); // Bind parameters
  $stmt->execute(); // Execute the query
  $result = $stmt->get_result(); // Get the result

  if ($result->num_rows > 0) {
    $company = $result->fetch_assoc(); // Fetch company details
  } else {
    // Handle case where company is not found
    $company = [
      'company_name' => 'Unknown',
      'company_email' => 'unknown@wmsu.edu.ph'
    ];
  }
  $stmt->close(); // Close the statement
}
$company_id = $_SESSION['user_id'];

// Check if today is a holiday
$today = date('Y-m-d');
$holiday_name = null;
$holiday_query = "SELECT holiday_name FROM holiday WHERE holiday_date = ?";
if ($stmt = $database->prepare($holiday_query)) {
  $stmt->bind_param("s", $today);
  $stmt->execute();
  $stmt->bind_result($holiday_name);
  $stmt->fetch();
  $stmt->close();
}

// Check if today is a suspended day for the company
$is_suspended = false;
$schedule_query = "SELECT day_type FROM schedule WHERE date = ? AND company_id = ?";
if ($stmt = $database->prepare($schedule_query)) {
  $stmt->bind_param("si", $today, $company_id);
  $stmt->execute();
  $stmt->bind_result($day_type);
  if ($stmt->fetch() && $day_type === 'Suspended') {
    $is_suspended = true;
  }
  $stmt->close();
}

// Query to fetch students under the logged-in company
$students_query = "
    SELECT s.student_id, s.student_firstname, s.student_middle, s.student_lastname, 
          a.time_in, a.time_out, a.ojt_hours
    FROM student s
    LEFT JOIN attendance a ON s.student_id = a.student_id 
    WHERE s.company = ?
    ORDER BY s.student_lastname, a.time_in ASC"; // Sort by last name and time-in

if ($stmt = $database->prepare($students_query)) {
  $stmt->bind_param("i", $company_id); // Bind the company ID parameter
  $stmt->execute(); // Execute the query
  $result = $stmt->get_result(); // Get the result set

  $students = [];
  while ($row = $result->fetch_assoc()) {
    $students[$row['student_id']][] = $row; // Group attendance by student ID
  }
  $stmt->close(); // Close the statement
}

// Function to format hours into "X hrs Y mins"
function formatDuration($hours)
{
  // Convert hours to total minutes
  $totalMinutes = round($hours * 60); // Use round to ensure accurate minute representation
  $hrs = floor($totalMinutes / 60);  // Extract hours
  $mins = $totalMinutes % 60;        // Extract remaining minutes

  // Format output
  $formatted = '';
  if ($hrs > 0) {
    $formatted .= $hrs . ' hr' . ($hrs > 1 ? 's' : '') . ' ';
  }
  if ($mins > 0) {
    $formatted .= $mins . ' min' . ($mins > 1 ? 's' : '');
  }

  return trim($formatted) ?: '0 mins';
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
  <title>Company - Dashboard</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/mobile.css">
  <!-- <link rel="stylesheet" href="./css/style.css">
  <link rel="stylesheet" href="./css/mobile.css"> -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <style>

  </style>
</head>

<body>
  <div class="header">
    <i class="fas fa-school"></i>
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
        src="../uploads/company/<?php echo !empty($company['company_image']) ? $company['company_image'] : 'user.png'; ?>"
        alt="Company Image" class="logout-img">
      <div style="margin-top: 10px;" class="profile-info">
        <span class="profile_name"><?php echo $company['company_name']; ?></span>
        <br />
        <span class="profile_email"><?php echo $company['company_email']; ?></span>
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
        <a href="qr-code.php">
          <i class="fa-solid fa-qrcode"></i>
          <span class="link_name">QR Scanner</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="qr-code.php">QR Scanner</a></li>
        </ul>
      </li>
      <li>
        <a href="intern.php">
          <i class="fa-solid fa-user"></i>
          <span class="link_name">Interns</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="intern.php">Interns</a></li>
        </ul>
      </li>
      <!-- <li>
        <div class="iocn-link">
          <a href="intern.php">
            <i class="fa-solid fa-user"></i>
            <span class="link_name">Interns</span>
          </a>
          <i class="fas fa-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="intern.php">Interns</a></li>
          <li><a href="./intern/masterlist.php">Masterlist</a></li>
          <li><a href="./intern/create-qr.php">Create QR</a></li>
          <li><a href="./intern/create-id.php">Create ID</a></li>
        </ul>
      </li> -->
      <li>
        <a href="message.php">
          <i class="fa-regular fa-comments"></i>
          <span class="link_name">Message</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="message.php">Message</a></li>
        </ul>
      </li>
      <li>
        <a href="feedback.php">
          <i class="fa-regular fa-star"></i>
          <span class="link_name">Feedback</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="feedback.php">Feedback</a></li>
        </ul>
      </li>

      <li>
        <div class="iocn-link">
          <a href="attendance.php">
            <i class="fa-regular fa-clock"></i>
            <span class="link_name">Attendance</span>
          </a>
          <i class="fas fa-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="attendance.php">Attendance</a></li>
          <li><a href="./intern/attendance.php">Monitoring</a></li>
        </ul>
      </li>
      <li>
        <a href="calendar.php">
          <i class="fa-regular fa-calendar-days"></i>
          <span class="link_name">Schedule</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="calendar.php">Manage Schedule</a></li>
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
            src="../uploads/company/<?php echo !empty($company['company_image']) ? $company['company_image'] : 'user.png'; ?>"
            alt="Company Logo">
        </div>
        <div class="details">
          <h2><?php echo !empty($company['company_name']) ? $company['company_name'] : 'Company Name' ?></h2>
          <label><?php echo !empty($company['company_address']) ? $company['company_address'] : 'Company Address' ?></label>
          <br>
          <div class="contact-info">
            <span><?php echo !empty($company['company_email']) ? $company['company_email'] : 'Company Email' ?> <span
                class="line"> |</span></span>

            <span><?php echo !empty($company['company_number']) ? $company['company_number'] : 'Company Number' ?></span>
          </div>
        </div>
      </div>
      <div class="main-box">
        <div class="left-box">
          <h2>
            Attendance - <span style="color: 
          <?php
          if ($holiday_name) {
            echo '#8B0000';
          } elseif ($is_suspended) {
            echo '#FFA500';
          } else {
            echo '#095d40';
          }
          ?>">
              <?php
              if ($holiday_name) {
                echo $holiday_name . " (" . date('F d, Y') . ")";
              } elseif ($is_suspended) {
                echo "Suspended (" . date('F d, Y') . ")";
              } else {
                echo date('F d, Y');
              }
              ?> </span>
          </h2>
          <table>
            <thead>
              <tr>
                <th>Intern Name</th>
                <th>Time-in</th>
                <th>Time-out</th>
                <th>Duration</th>
                <th>Status</th>
              </tr>
            </thead>
            <?php
            $today = date('Y-m-d');
            ?>

            <tbody>
              <?php if (!empty($students)): ?>
                <?php foreach ($students as $student_id => $attendances): ?>
                  <?php
                  // Filter attendances for today
                  $today_attendances = array_filter($attendances, function ($attendance) use ($today) {
                    return date('Y-m-d', strtotime($attendance['time_in'])) == $today;
                  });

                  // Initialize variables to store attendance details
                  $first_time_in = null;
                  $latest_time_in_without_out = null;
                  $latest_time_out = null;
                  $total_hours_today = 0;

                  foreach ($today_attendances as $attendance) {
                    if (!$first_time_in || strtotime($attendance['time_in']) < strtotime($first_time_in)) {
                      $first_time_in = $attendance['time_in'];
                    }

                    // Check for the latest "Time-in" without "Time-out"
                    if ($attendance['time_in'] && !$attendance['time_out']) {
                      $latest_time_in_without_out = $attendance['time_in'];
                    }

                    // Find the latest "Time-out" if it exists
                    if ($attendance['time_out'] && (!$latest_time_out || strtotime($attendance['time_out']) > strtotime($latest_time_out))) {
                      $latest_time_out = $attendance['time_out'];
                    }

                    // Accumulate total hours for today
                    $total_hours_today += $attendance['ojt_hours'] ?? 0;
                  }

                  // Determine the Time-out display: if there's a latest Time-in without Time-out, display it as empty in Time-out
                  $displayed_time_out = $latest_time_in_without_out ? '' : ($latest_time_out ? date('h:i A', strtotime($latest_time_out)) : 'N/A');

                  // Determine the status
                  if (!$first_time_in && !$latest_time_out) {
                    // If both Time-in and Time-out are N/A, set status to "No Record Yet"
                    $status = '<span style="color:gray;">No Record Yet</span>';
                  } else {
                    // Otherwise, set status based on the presence of Time-in without Time-out
                    $status = $latest_time_in_without_out ? '<span style="color:green;">Timed-in</span>' : '<span style="color:red;">Timed-out</span>';
                  }
                  ?>

                  <tr>
                    <td>
                      <?php echo $attendances[0]['student_firstname'] . ' ' . $attendances[0]['student_middle'] . ' ' . $attendances[0]['student_lastname']; ?>
                    </td>
                    <td><?php echo $first_time_in ? date('h:i A', strtotime($first_time_in)) : 'N/A'; ?></td>
                    <td><?php echo $displayed_time_out; ?></td>
                    <td><?php echo $total_hours_today > 0 ? formatDuration($total_hours_today) : 'N/A'; ?></td>
                    <td><?php echo $status; ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5">No attendance records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>


          </table>
        </div>

        <?php

        $sql = "SELECT required_hours FROM required_hours ORDER BY required_hours_id DESC LIMIT 1";
        $result = $database->query($sql);
        $target_hours = ($result->num_rows > 0) ? (int) $result->fetch_assoc()['required_hours'] : 300;

        $database->close();
        ?>

        <div class="right-box">
          <h2>Target Hours: <span style="color: #095d40"><strong><?php echo $target_hours; ?> hrs</strong></span></h2>
          <?php if (!empty($students)): ?>
            <?php foreach ($students as $student_id => $attendances): ?>
              <?php
              // Calculate total hours for each student
              $total_hours = array_reduce($attendances, function ($carry, $attendance) {
                return $carry + ($attendance['ojt_hours'] ?? 0);
              }, 0);
              // Calculate progress percentage based on dynamic target hours
              $progress_percentage = min(100, ($total_hours / $target_hours) * 100); // Limit to 100%
              ?>
              <div class="student-progress">
                <div class="label">
                  <span><?php echo $attendances[0]['student_firstname'] . ' ' . $attendances[0]['student_middle'] . ' ' . $attendances[0]['student_lastname']; ?></span>
                  <span><?php echo round($progress_percentage); ?>%</span>
                </div>
                <div class="progress-bar">
                  <div class="progress" style="width: <?php echo $progress_percentage; ?>%;"
                    data-progress="<?php echo $progress_percentage; ?>"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No progress records found.</p>
          <?php endif; ?>
        </div>

      </div>
      <script>
        // Dynamically adjust progress bar color based on percentage
        document.querySelectorAll('.progress').forEach(function (progressBar) {
          const progress = progressBar.getAttribute('data-progress'); // Get the progress percentage
          // Calculate green shade from light (0%) to dark (100%)
          const shade = 0.6 * (progress / 100);
          progressBar.style.backgroundColor = `rgba(9, 93, 64, ${shade + 0.4})`; // Minimum opacity of 0.4
        });
      </script>


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
  <script src="./js/script.js"></script>
  <script src="../js/sy.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>