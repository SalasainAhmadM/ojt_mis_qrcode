<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
  header("Location: ../index.php"); // Redirect to login page if not logged in
  exit();
}
date_default_timezone_set('Asia/Manila');
$current_day = date('l');
$show_journal_check = ($current_day === 'Friday' || $current_day === 'Saturday');

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
// Fetch all course_sections for the dropdown that are under the specific adviser
$query = "SELECT * FROM course_sections WHERE adviser_id = ?";
$course_sections = [];
if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $adviser_id); // Bind the adviser ID parameter
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $course_sections[] = $row;
  }
  $stmt->close();
}
$query = "SELECT DISTINCT company_name FROM company WHERE company_id IN 
            (SELECT DISTINCT company FROM student WHERE adviser = ?)";
$companies = [];
if ($stmt = $database->prepare($query)) {
  $stmt->bind_param("i", $adviser_id); // Bind the adviser ID
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $companies[] = $row['company_name'];
  }
  $stmt->close();
}

// Capture the selected course_section and search query
$selected_company = isset($_GET['company']) ? $_GET['company'] : '';

$selected_course_section = isset($_GET['course_section']) ? $_GET['course_section'] : '';
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Function to get paginated and searched students
function getStudents($database, $selected_course_section, $search_query, $adviser_id, $limit = 5, $selected_day, $selected_company)
{
  $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
  $offset = ($page - 1) * $limit;

  $total_students_query = "SELECT COUNT(*) AS total FROM student WHERE adviser = ?";
  $students_query = "
    SELECT student.*, 
           CONCAT(adviser.adviser_firstname, ' ', adviser.adviser_middle, '. ', adviser.adviser_lastname) AS adviser_fullname,
           CONCAT(address.address_barangay, ', ', street.name) AS full_address,
           company.company_name,
           course_sections.course_section_name,
           departments.department_name,
           attendance_earliest.time_in AS first_time_in,
           attendance_latest.time_out AS last_time_out,
           IFNULL(SUM(attendance.ojt_hours), 0) AS total_ojt_hours,
           attendance_remarks.remark_type AS attendance_remark,
           attendance_remarks.remark AS attendance_remark_description,
           attendance_remarks.status AS attendance_remark_status
    FROM student
    LEFT JOIN adviser ON student.adviser = adviser.adviser_id
    LEFT JOIN address ON student.student_address = address.address_id
    LEFT JOIN street ON student.street = street.street_id
    LEFT JOIN company ON student.company = company.company_id
    LEFT JOIN course_sections ON student.course_section = course_sections.id
    LEFT JOIN departments ON student.department = departments.department_id
    -- Earliest time_in of the selected day
    LEFT JOIN (
        SELECT student_id, MIN(time_in) AS time_in 
        FROM attendance 
        WHERE DATE(time_in) = ?
        GROUP BY student_id
    ) AS attendance_earliest ON student.student_id = attendance_earliest.student_id
    -- Latest time_out of the selected day
    LEFT JOIN (
        SELECT student_id, MAX(time_out) AS time_out 
        FROM attendance 
        WHERE DATE(time_in) = ?
        GROUP BY student_id
    ) AS attendance_latest ON student.student_id = attendance_latest.student_id
    -- Attendance records for the selected day
    LEFT JOIN attendance ON student.student_id = attendance.student_id 
                          AND DATE(attendance.time_in) = ?
    -- Join with attendance_remarks to fetch remarks
    LEFT JOIN attendance_remarks ON student.student_id = attendance_remarks.student_id 
                                  AND attendance_remarks.schedule_id = ?
    WHERE student.adviser = ?";

  if (!empty($selected_course_section)) {
    $total_students_query .= " AND course_section = ?";
    $students_query .= " AND student.course_section = ?";
  }

  if (!empty($selected_company)) {
    $total_students_query .= " AND student.company = ?";
    $students_query .= " AND student.company = ?";
  }

  if (!empty($search_query)) {
    $total_students_query .= " AND (student_firstname LIKE ? OR student_middle LIKE ? OR student_lastname LIKE ?)";
    $students_query .= " AND (student_firstname LIKE ? OR student_middle LIKE ? OR student_lastname LIKE ?)";
  }

  $students_query .= " GROUP BY student.student_id ORDER BY student.student_id LIMIT ? OFFSET ?";

  // Parameters for queries
  $params = [$adviser_id];
  if (!empty($selected_course_section))
    $params[] = $selected_course_section;
  if (!empty($selected_company))
    $params[] = $selected_company;
  if (!empty($search_query))
    $params = array_merge($params, [$search_query, $search_query, $search_query]);

  $stmt = $database->prepare($total_students_query);
  $stmt->bind_param(str_repeat('s', count($params)), ...$params);
  $stmt->execute();
  $result = $stmt->get_result();
  $total_students = $result->fetch_assoc()['total'];
  $stmt->close();

  $total_pages = ceil($total_students / $limit);

  $students = [];
  if ($stmt = $database->prepare($students_query)) {
    $params = [$selected_day, $selected_day, $selected_day, $selected_day, $adviser_id];
    if (!empty($selected_course_section))
      $params[] = $selected_course_section;
    if (!empty($selected_company))
      $params[] = $selected_company;
    if (!empty($search_query))
      $params = array_merge($params, [$search_query, $search_query, $search_query]);
    $params = array_merge($params, [$limit, $offset]);

    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $row['first_time_in'] = $row['first_time_in'] ? date("g:i a", strtotime($row['first_time_in'])) : 'N/A';
      $row['last_time_out'] = $row['last_time_out'] ? date("g:i a", strtotime($row['last_time_out'])) : 'N/A';

      // Convert total_ojt_hours to "1hr 1min" format
      if ($row['total_ojt_hours'] > 0) {
        $hours = floor($row['total_ojt_hours']);
        $minutes = round(($row['total_ojt_hours'] - $hours) * 60);
        $row['total_ojt_hours'] = ($hours > 0 ? $hours . ' hr' . ($hours > 1 ? 's' : '') : '') .
          ($hours > 0 && $minutes > 0 ? ' ' : '') .
          ($minutes > 0 ? $minutes . ' min' . ($minutes > 1 ? 's' : '') : '');
      } else {
        $row['total_ojt_hours'] = 'N/A';
      }

      // Determine attendance status
      if ($row['attendance_remark'] === 'Late') {
        $row['status'] = '<span style="color: yellow;">Late Timed-in</span>';
      } elseif ($row['attendance_remark'] === 'Absent') {
        $row['status'] = '<span style="color: #8B0000;">Absent</span>';
      } else {
        $row['status'] = is_null($row['last_time_out']) ? '<span style="color: #095d40;">Timed-in</span>' :
          (!is_null($row['last_time_out']) && $row['total_ojt_hours'] !== 'N/A' ? '<span style="color: red;">Timed-out</span>' :
            '<span style="color: #8B0000;">Absent</span>');
      }

      $students[] = $row;
    }
    $stmt->close();
  }

  return [
    'students' => $students,
    'total_pages' => $total_pages,
    'current_page' => $page,
  ];
}




function renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query, $selected_day, $selected_company)
{
  // Remove unnecessary '%' from search_query before encoding
  $clean_search_query = trim($search_query, '%');
  $search_query_encoded = htmlspecialchars($clean_search_query, ENT_QUOTES);
  $course_section_query_encoded = htmlspecialchars($selected_course_section ?? '', ENT_QUOTES);
  $day_encoded = htmlspecialchars($selected_day ?? '', ENT_QUOTES);

  // Display Previous button
  if ($current_page > 1) {
    echo '<a href="?page=' . ($current_page - 1) . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '&day=' . $day_encoded . '" class="prev">Previous</a>';
  }

  // Display page numbers (only show 5 page links)
  for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
    $active = $i == $current_page ? 'active' : '';
    echo '<a href="?page=' . $i . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '&day=' . $day_encoded . '" class="' . $active . '">' . $i . '</a>';
  }

  // Display Next button
  if ($current_page < $total_pages) {
    echo '<a href="?page=' . ($current_page + 1) . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '&day=' . $day_encoded . '" class="next">Next</a>';
  }
}


// Set the default selected day to today if not specified
$selected_day = isset($_GET['day']) ? $_GET['day'] : date('Y-m-d');

// Format the selected date for display in the header
$display_date = date('F d, Y', strtotime($selected_day));
// Fetch students with pagination, course_section, and search functionality
$pagination_data = getStudents($database, $selected_course_section, $search_query, $adviser_id, 5, $selected_day, $selected_company);
$students = $pagination_data['students'];
$total_pages = $pagination_data['total_pages'];
$current_page = $pagination_data['current_page'];

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
          <!-- <li><a href="./company/company-feedback.php">Company List</a></li> -->
          <li><a href="./company/company-intern-feedback.php">Intern Feedback</a></li>
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
          <li><a href="./intern/attendance-intern.php">Intern Attendance</a></li>
          <li><a href="./intern/attendance-monitor.php">Monitoring</a></li>
          <li><a href="./intern/intern_hours.php">Intern Total Hours</a></li>
        </ul>
      </li>
      <li>
        <a href="announcement.php">
          <i class="fa-solid fa-bullhorn"></i>
          <span class="link_name">Announcement</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="announcement.php">Announcement</a></li>
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
      // Count Students under the specific adviser
      $student_count_query = "SELECT COUNT(*) AS student_count FROM student WHERE adviser = ?";
      if ($stmt = $database->prepare($student_count_query)) {
        $stmt->bind_param("i", $adviser_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student_count = $result->fetch_assoc()['student_count'];
        $stmt->close();
      }

      // Count Sections under the specific adviser
      $section_count_query = "SELECT COUNT(*) AS section_count FROM course_sections WHERE adviser_id = ?";
      if ($stmt = $database->prepare($section_count_query)) {
        $stmt->bind_param("i", $adviser_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $section_count = $result->fetch_assoc()['section_count'];
        $stmt->close();
      }

      // Count Announcements
      $announcement_count_query = "SELECT COUNT(*) AS announcement_count FROM adviser_announcement WHERE adviser_id = ?";
      if ($stmt = $database->prepare($announcement_count_query)) {
        $stmt->bind_param("i", $adviser_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $announcement_count = $result->fetch_assoc()['announcement_count'];
        $stmt->close();
      }

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
                <form method="GET" action="">
                  <input type="hidden" name="day" value="<?php echo htmlspecialchars($selected_day, ENT_QUOTES); ?>">
                  <select class="course-section-dropdown" name="course_section" id="course_section"
                    onchange="this.form.submit()">
                    <option value="">All Sections</option>
                    <?php foreach ($course_sections as $section): ?>
                      <option value="<?php echo htmlspecialchars($section['id'], ENT_QUOTES); ?>" <?php echo $selected_course_section == $section['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($section['course_section_name'], ENT_QUOTES); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </div>

              <?php
              $section_stmt->close();
            }
            ?>

            <?php
            // $company_query = "SELECT company_name, company_id FROM company";
            // $company_result = $database->query($company_query);
            ?>

            <div class="company-section">
              <span><strong>Company</strong></span><br>
              <form method="GET" action="">
                <input type="hidden" name="day" value="<?php echo htmlspecialchars($selected_day, ENT_QUOTES); ?>">
                <input type="hidden" name="course_section"
                  value="<?php echo htmlspecialchars($selected_course_section, ENT_QUOTES); ?>">
                <select class="course-section-dropdown" name="company" onchange="this.form.submit()">
                  <option value="">All Companies</option>
                  <?php
                  $company_query = "SELECT company_id, company_name FROM company";
                  $company_result = $database->query($company_query);
                  while ($row = $company_result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($row['company_id'], ENT_QUOTES) . "'" .
                      ($selected_company == $row['company_id'] ? ' selected' : '') . ">" .
                      htmlspecialchars($row['company_name'], ENT_QUOTES) . "</option>";
                  }
                  ?>
                </select>
              </form>
            </div>


            <div class="date-section">
              <span></span><br>
              <!-- Date Picker Form for Date Navigation -->
              <form method="GET" action="" class="date-picker-form">
                <div class="search-bar-container">
                  <input type="date" class="search-bar" id="searchDate" name="day"
                    value="<?php echo htmlspecialchars($selected_day, ENT_QUOTES); ?>" onchange="this.form.submit()">
                </div>
              </form>
            </div>

            <div class="date-section">
              <span></span><br>
              <!-- Date Picker Form for Date Navigation -->
              <!-- Reset Button Form -->
              <form method="GET" action="index.php">
                <button type="submit" class="reset-bar-icon">
                  <i class="fa fa-times-circle"></i>
                </button>
              </form>
            </div>

            <div class="search-section">
              <span><strong>Search</strong></span><br>
              <!-- Search Bar Form -->
              <form method="GET" action="">
                <input type="hidden" name="course_section"
                  value="<?php echo htmlspecialchars($selected_course_section, ENT_QUOTES); ?>">
                <input type="hidden" name="day" value="<?php echo htmlspecialchars($selected_day, ENT_QUOTES); ?>">
                <div class="search-bar-container">
                  <input type="text" class="search-bar" name="search" placeholder="Search Student"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                  <button type="submit" class="search-bar-icon">
                    <i class="fa fa-search"></i>
                  </button>
                </div>
              </form>
            </div>
          </div>


          <table>
            <thead>
              <tr>
                <th class="image">Profile</th>
                <th class="name">Full Name</th>
                <!-- <th class="company">Company</th> -->
                <th class="section">Section</th>
                <th class="timein">Time-in</th>
                <th class="timeout">Time-out</th>
                <th class="duration">Duration</th>
                <th class="status">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($students)): ?>
                <?php foreach ($students as $student): ?>
                  <tr>
                    <td class="image">
                      <img style="border-radius: 50%;"
                        src="../uploads/student/<?php echo !empty($student['student_image']) ? htmlspecialchars($student['student_image']) : 'user.png'; ?>"
                        alt="Student Image">
                    </td>
                    <td class="name">
                      <?php echo htmlspecialchars($student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']); ?>
                    </td>
                    <td class="section"><?php echo htmlspecialchars($student['course_section_name']); ?>
                    </td>
                    <td class="timein"><?php echo htmlspecialchars($student['first_time_in']); ?></td>
                    <td class="timeout"><?php echo htmlspecialchars($student['last_time_out']); ?></td>
                    <td class="duration"><?php echo htmlspecialchars($student['total_ojt_hours']); ?></td>
                    <td class="status">
                      <?php
                      if (!empty($student['attendance_remark'])) {
                        if ($student['attendance_remark'] === 'Late') {
                          echo '<span style="color: yellow;">Late Timed-in</span>';
                        } elseif ($student['attendance_remark'] === 'Absent') {
                          echo '<span style="color: #8B0000;">Absent</span>';
                        }
                      } elseif ($student['first_time_in'] === 'N/A' && $student['last_time_out'] === 'N/A') {
                        echo '<span style="color:gray;">No Record Yet</span>';
                      } elseif ($student['last_time_out'] === 'N/A') {
                        echo '<span style="color: #095d40;">Timed-in</span>';
                      } else {
                        echo $student['status'];
                      }
                      ?>

                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7">No students found</td>
                </tr>
              <?php endif; ?>
            </tbody>



          </table>

          <?php if ($total_pages > 1): ?>
            <div class="pagination">
              <?php
              renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query, $selected_day, $selected_company);
              ?>
            </div>
          <?php endif; ?>

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
      <?php if ($show_journal_check): ?>
        <p style="color: #d9534f; font-size: 16px; font-weight: bold;">Weekly Journal Check</p>
        <button class="proceed-btn" style="margin-right: 10px;"
          onclick="location.href='./intern/intern-reports.php'">Check Journals</button>
      <?php endif; ?>
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
  <script src="../js/sy.js"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>