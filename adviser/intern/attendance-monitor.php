<?php
session_start();
require '../../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch adviser details
$adviser_id = $_SESSION['user_id'];
$query = "SELECT * FROM adviser WHERE adviser_id = ?";
$stmt = $database->prepare($query);
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();

$adviser = $result->num_rows > 0 ? $result->fetch_assoc() : [
    'adviser_firstname' => 'Unknown',
    'adviser_middle' => 'U',
    'adviser_lastname' => 'User',
    'adviser_email' => 'unknown@wmsu.edu.ph'
];
$stmt->close();

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

// Filter by selected course section
$selected_course_section = isset($_GET['course_section']) ? $_GET['course_section'] : '';
$selected_day = isset($_GET['day']) ? $_GET['day'] : date('Y-m-d'); // Default to today's date

// Fetch attendance records for all students under the adviser's course sections for the selected day
$query = "
    SELECT 
        student.student_id,
        student.student_image,
        CONCAT(student.student_firstname, ' ', student.student_middle, '. ', student.student_lastname) AS full_name,
        course_sections.course_section_name,
        attendance.time_in,
        attendance.time_out,
        attendance.ojt_hours,
        attendance.time_out_reason,
        attendance.reason
    FROM attendance
    JOIN student ON attendance.student_id = student.student_id
    JOIN course_sections ON student.course_section = course_sections.id
    WHERE course_sections.adviser_id = ? AND DATE(attendance.time_in) = ?
";

// Add course section filter if selected
$params = ["is", $adviser_id, $selected_day];
if (!empty($selected_course_section)) {
    $query .= " AND course_sections.id = ?";
    $params[0] .= "i"; // Append type for the additional parameter
    $params[] = $selected_course_section;
}

$query .= " ORDER BY student.student_id";

$stmt = $database->prepare($query);
$stmt->bind_param(...$params);
$stmt->execute();
$result = $stmt->get_result();

$attendance_records = [];
while ($row = $result->fetch_assoc()) {
    $row['time_in'] = $row['time_in'] ? date("g:i a", strtotime($row['time_in'])) : 'N/A';
    $row['time_out'] = $row['time_out'] ? date("g:i a", strtotime($row['time_out'])) : 'N/A';

    // Format OJT hours
    if (isset($row['ojt_hours']) && $row['ojt_hours'] > 0) {
        $hours = floor($row['ojt_hours']);
        $minutes = round(($row['ojt_hours'] - $hours) * 60);
        $row['ojt_hours'] = ($hours ? $hours . " hr" . ($hours > 1 ? "s" : "") : "") .
            ($hours && $minutes ? " " : "") .
            ($minutes ? $minutes . " min" . ($minutes > 1 ? "s" : "") : "");
    } else {
        $row['ojt_hours'] = "N/A";
    }

    $attendance_records[] = $row;
}
$stmt->close();

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
    <title>Adviser - Company Intern Profile</title>
    <link rel="icon" href="../../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- <link rel="stylesheet" href="../css/index.css"> -->
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/mobile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>
<style>
    .whole-box {
        max-height: 600px;
        overflow-y: auto;
    }

    .whole-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .whole-box thead {
        position: sticky;
        top: 0;
        z-index: 1;
    }
</style>

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
            <img src="../../uploads/adviser/<?php echo !empty($adviser['adviser_image']) ? $adviser['adviser_image'] : 'user.png'; ?>"
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
                    <!-- <li><a href="../intern/intern-profile.php">Student Profile</a></li> -->
                    <li><a href="../intern/intern-reports.php">Intern Reports</a></li>
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
                    <!--  <li><a href="company-feedback.php">Company List</a></li> -->
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

            <div class="header-box">
                <label style="color: #a6a6a6;margin-left: 5px;">Attendance</label>
            </div>
            <div class="main-box">
                <div class="whole-box">

                    <h2>Attendance - <span style="color: #095d40;">
                            <?php echo date('F j, Y', strtotime($selected_day)); ?></span></h2>

                    <div class="filter-group">
                        <!-- Course Section Filter Form -->
                        <form method="GET" action="">
                            <input type="hidden" name="day"
                                value="<?php echo htmlspecialchars($selected_day, ENT_QUOTES); ?>">
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

                        <!-- Date Picker Form -->
                        <form method="GET" action="" class="date-picker-form">
                            <div class="search-bar-container">
                                <input type="date" class="search-bar" id="searchDate" name="day"
                                    value="<?php echo htmlspecialchars($selected_day, ENT_QUOTES); ?>"
                                    onchange="this.form.submit()">
                            </div>
                        </form>

                        <!-- Reset Button Form -->
                        <form method="GET" action="attendance-monitor.php">
                            <button type="submit" class="reset-bar-icon">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </form>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th class="image">Profile</th>
                                <th class="name">Intern Name</th>
                                <th class="section">Section</th>
                                <th class="timein">Time-in</th>
                                <th class="timeout">Time-out</th>
                                <th class="duration">Duration</th>
                                <th class="duration">Action</th>
                                <th class="duration">Reason</th>
                            </tr>
                        </thead>
                        <tbody style="max-height: 400px; overflow-y: auto;">
                            <?php if (!empty($attendance_records)): ?>
                                <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td class="image">
                                            <img style="border-radius: 50%;"
                                                src="../../uploads/student/<?php echo !empty($record['student_image']) ? htmlspecialchars($record['student_image'], ENT_QUOTES) : 'user.png'; ?>"
                                                alt="Student Image">
                                        </td>
                                        <td class="name"><?php echo htmlspecialchars($record['full_name']); ?></td>
                                        <td class="section"><?php echo htmlspecialchars($record['course_section_name']); ?></td>
                                        <td class="timein"><?php echo htmlspecialchars($record['time_in']); ?></td>
                                        <td class="timeout"><?php echo htmlspecialchars($record['time_out']); ?></td>
                                        <td class="duration"><?php echo htmlspecialchars($record['ojt_hours']); ?></td>
                                        <td class="duration">
                                            <?php echo htmlspecialchars($record['time_out_reason'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="duration">
                                            <?php echo htmlspecialchars($record['reason'] ?? 'N/A'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No attendance records found for this date</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </section>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/alert-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #000">Are you sure you want to delete?</h2>
            <input type="hidden" id="delete-student-id" value="">
            <div style="display: flex; justify-content: space-around; margin-top: 10px; margin-bottom: 20px">
                <button class="confirm-btn" onclick="confirmDeleteAction()">Confirm</button>
                <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="deleteSuccessModal" class="modal" style="display: none;">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/delete.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Student Deleted Successfully!</h2>
            <p>Student has been deleted successfully by <br> <span
                    style="color: #095d40; font-size: 20px;"><?php echo $_SESSION['full_name']; ?>!</span></p>
            <button class="proceed-btn" onclick="closeDeleteSuccessModal()">Close</button>
        </div>
    </div>
    <script>
        function confirmDelete(studentId) {
            // Set the student ID in the hidden input
            document.getElementById('delete-student-id').value = studentId;
            // Show the delete confirmation modal
            document.getElementById('deleteModal').style.display = 'block';
        }

        function confirmDeleteAction() {
            const studentId = document.getElementById('delete-student-id').value;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "../others/delete_student.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);

                    // Hide the delete modal
                    document.getElementById('deleteModal').style.display = 'none';

                    if (response.status === 'success') {
                        // Show the success modal
                        document.getElementById('deleteSuccessModal').style.display = 'block';
                    } else {
                        alert(response.message);
                    }
                }
            };

            xhr.send("id=" + studentId);
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function closeDeleteSuccessModal() {
            document.getElementById('deleteSuccessModal').style.display = 'none';
            location.reload();
        }
    </script>
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
    <script src="../js/scripts.js"></script>
    <script src="../../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>