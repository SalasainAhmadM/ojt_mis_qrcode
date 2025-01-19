<?php
session_start();
require '../../conn/connection.php';

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

// Query to fetch students and total OJT hours under the logged-in adviser
$students_query = "
    SELECT s.student_id, s.student_firstname, s.student_middle, s.student_lastname, s.student_image,
           SUM(a.ojt_hours) AS total_ojt_hours
    FROM student s
    LEFT JOIN attendance a ON s.student_id = a.student_id 
    WHERE s.adviser = ?
    GROUP BY s.student_id
    ORDER BY s.student_lastname ASC";

if ($stmt = $database->prepare($students_query)) {
  $stmt->bind_param("i", $adviser_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $students = [];
  while ($row = $result->fetch_assoc()) {
    $students[] = $row;
  }
  $stmt->close();
}
// Function to format hours into "X hrs Y mins"
function formatDuration($hours)
{
  // Convert hours to total minutes
  $totalMinutes = round($hours * 60);
  $hrs = floor($totalMinutes / 60);
  $mins = $totalMinutes % 60;

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
  <title>Adviser - Intern Total Hours Monitoring</title>
  <link rel="icon" href="../img/ccs.png" type="image/icon type">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../../css/main.css">
  <link rel="stylesheet" href="../../css/mobile.css">
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
      <img src="../../img/ccs.png">
    </div>
  </div>
  <div class="sidebar close">
    <div class="profile-details">
      <img
        src="../../uploads/adviser/<?php echo !empty($adviser['adviser_image']) ? $adviser['adviser_image'] : 'user.png'; ?>"
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
        <a href="../index.php">
          <i class="fa-solid fa-house"></i>
          <span class="link_name">Home</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="../index.php">Home</a></li>
        </ul>
      </li>
      <li>
        <div class="iocn-link">
          <a href="../interns.php">
            <i class="fa-solid fa-user"></i>
            <span class="link_name">Interns</span>
          </a>
          <i class="fas fa-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="../interns.php">Manage Interns</a></li>
          <!-- <li><a href="intern-profile.php">Student Profile</a></li> -->
          <li><a href="intern-reports.php">Intern Reports</a></li>
        </ul>
      </li>
      <li>
        <div class="iocn-link">
          <a href="../company.php">
            <i class="fa-regular fa-building"></i>
            <span class="link_name">Company</span>
          </a>
          <i class="fas fa-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="../company.php">Manage Company</a></li>
          <li><a href="../company/company-intern.php">Company Interns</a></li>
          <!--<li><a href="../company/company-feedback.php">Company List</a></li> -->
          <li><a href="../company/company-intern-feedback.php">Intern Feedback</a></li>
        </ul>
      </li>
      <li>
        <div style="background-color: #07432e;" class="iocn-link">
          <a href="../attendance.php">
            <i class="fa-regular fa-clock"></i>
            <span class="link_name">Attendance</span>
          </a>
          <i class="fas fa-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="../attendance.php">Attendance</a></li>
          <li><a href="attendance-intern.php">Intern Attendance</a></li>
          <li><a href="attendance-monitor.php">Monitoring</a></li>
          <li><a href="intern_hours.php">Intern Total Hours</a></li>
        </ul>
      </li>
      <li>
        <a href="../announcement.php">
          <i class="fa-solid fa-bullhorn"></i>
          <span class="link_name">Announcement</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="../announcement.php">Announcement</a></li>
        </ul>
      </li>
      <li>
        <a href="../message.php">
          <i class="fa-regular fa-comments"></i>
          <span class="link_name">Message</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="../message.php">Message</a></li>
        </ul>
      </li>
      <li>
        <a href="../setting.php">
          <i class="fas fa-cog"></i>
          <span class="link_name">Settings</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="../setting.php">Settings</a></li>
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

      <div class="main-box">
        <div class="left-box">
          <div class="header-group">
            <h2>Total Hours Monitoring</h2>

            <div class="button-container">
              <a style="text-decoration: none;" href="export_hours.php" class="export-btn">
                <i class="fa-solid fa-file-export"></i> Export
              </a>


            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th class="image">Profile</th>
                <th class="name">Intern Name</th>
                <th class="duration">Current Hours</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $student): ?>
                <tr>
                  <td class="image">
                    <img style="border-radius: 50%;"
                      src="../../uploads/student/<?php echo !empty($student['student_image']) ? htmlspecialchars($student['student_image']) : 'user.png'; ?>"
                      alt="Student Image">
                  </td>
                  <td class="name">
                    <?php echo htmlspecialchars($student['student_firstname'] . ' ' . $student['student_middle'] . ' ' . $student['student_lastname']); ?>
                  </td>
                  <td class="duration"><?php echo formatDuration($student['total_ojt_hours']); ?></td>
                </tr>
              <?php endforeach; ?>
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
            <?php foreach ($students as $student): ?>
              <?php
              // Fetch total OJT hours for the student
              $total_hours = $student['total_ojt_hours'] ?? 0;

              // Calculate progress percentage based on target hours
              $progress_percentage = min(100, ($total_hours / $target_hours) * 100); // Cap at 100%
              ?>
              <div class="student-progress">
                <div class="label">
                  <span><?php echo htmlspecialchars($student['student_firstname'] . ' ' . $student['student_middle'] . ' ' . $student['student_lastname']); ?></span>
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

        <script>
          // Dynamically adjust progress bar color based on percentage
          document.querySelectorAll('.progress').forEach(function (progressBar) {
            const progress = progressBar.getAttribute('data-progress'); // Get the progress percentage
            const shade = 0.6 * (progress / 100); // Calculate green shade from light (0%) to dark (100%)
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
        <lottie-player src="../../animation/logout-095d40.json" background="transparent" speed="1"
          style="width: 150px; height: 150px;" loop autoplay>
        </lottie-player>
      </div>
      <h2 style="color: #000">Are you sure you want to logout?</h2>
      <div style="display: flex; justify-content: space-around; margin-top: 20px;">
        <button class="confirm-btn" onclick="logout2()">Confirm</button>
        <button class="cancel-btn" onclick="closeModal2('logoutModal')">Cancel</button>
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
  <script src="../js/scripts.js"></script>
  <script src="../../js/sy.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>