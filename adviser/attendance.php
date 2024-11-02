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

function formatDuration($hours)
{
    $totalMinutes = $hours * 60;
    $hrs = floor($totalMinutes / 60);
    $mins = $totalMinutes % 60;

    $formatted = '';
    if ($hrs > 0)
        $formatted .= $hrs . ' hr' . ($hrs > 1 ? 's' : '') . ' ';
    if ($mins > 0)
        $formatted .= $mins . ' min' . ($mins > 1 ? 's' : '');

    return trim($formatted) ?: '0 mins';
}

// Set the selected day (or default to today)
$selected_day = isset($_GET['day']) ? $_GET['day'] : date('Y-m-d');

// Calculate previous and next day for pagination
$previous_day = date('Y-m-d', strtotime($selected_day . ' -1 day'));
$next_day = date('Y-m-d', strtotime($selected_day . ' +1 day'));

// Fetch students and their attendance records for the logged-in adviser
$query = "
    SELECT s.student_id, s.student_image, s.student_firstname, s.student_middle, s.student_lastname,
           a.time_in, a.time_out, a.ojt_hours
    FROM student s
    LEFT JOIN attendance a ON s.student_id = a.student_id AND DATE(a.time_in) = ?
    WHERE s.adviser = ?";
$stmt = $database->prepare($query);
$stmt->bind_param("si", $selected_day, $adviser_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[$row['student_id']][] = $row;
}
$stmt->close();
// $adviser_id = $_SESSION['user_id'];

// Get the selected day (or default to today)

// // Handle search query
// $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : null;

// // Query to fetch students and attendance based on search or day
// $students_query = "
//     SELECT s.student_id, s.student_firstname, s.student_middle, s.student_lastname, 
//            s.student_image, a.time_in, a.time_out, a.ojt_hours
//     FROM student s
//     LEFT JOIN attendance a ON s.student_id = a.student_id 
//     WHERE s.company = ? AND DATE(a.time_in) = ?
// ";

// If a search is provided, add the search condition
// if ($search) {
//     $students_query .= " AND (s.student_firstname LIKE ? OR s.student_lastname LIKE ?)";
//     $query_params = [$adviser_id, $selected_day, $search, $search];
// } else {
//     $query_params = [$adviser_id, $selected_day];
// }

// $students_query .= " ORDER BY s.student_lastname, a.time_in ASC";

// if ($stmt = $database->prepare($students_query)) {
//     $stmt->bind_param(str_repeat("s", count($query_params)), ...$query_params);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     $students = [];
//     while ($row = $result->fetch_assoc()) {
//         $students[$row['student_id']][] = $row;
//     }
//     $stmt->close();
// }

// Function to format hours into "X hrs Y mins"


// Calculate previous and next day for pagination
// $previous_day = date('Y-m-d', strtotime($selected_day . ' -1 day'));
// $next_day = date('Y-m-d', strtotime($selected_day . ' +1 day'));


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser - Attendance</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <!-- <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/mobile.css">
    <link rel="stylesheet" href="./css/style.css"> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

<body>
    <div class="header">
        <i class="fas fa-school"></i>
        <div class="school-name">S.Y. 2024-2025 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span
                style="color: #095d40;">|</span>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
            <img src="../img/ccs.png">
        </div>
    </div>
    <div class="sidebar close">
        <div class="profile-details">
            <img src="../uploads/adviser/<?php echo !empty($adviser['adviser_image']) ? $adviser['adviser_image'] : 'user.png'; ?>"
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
                <a href="index.php">
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
                <a href="attendance.php" class="active">
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

        <div class="content-wrapper">

            <div class="header-box">
                <label style="color: #a6a6a6; margin-left: 5px;">Attendance</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <h2>Attendance - <span
                            style="color: #095d40"><?php echo date('F d, Y', strtotime($selected_day)); ?></span>
                    </h2>

                    <div class="filter-group">
                        <!-- Search Bar Form -->
                        <form method="GET" action="">
                            <input type="hidden" name="day"
                                value="<?php echo htmlspecialchars($selected_day, ENT_QUOTES); ?>">
                            <div class="search-bar-container">
                                <input type="text" class="search-bar" name="search" placeholder="Search Student"
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="search-bar-icon">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Reset Button Form -->
                        <form method="GET" action="">
                            <input type="hidden" name="day"
                                value="<?php echo htmlspecialchars($selected_day, ENT_QUOTES); ?>">
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

                    <div class="paginationDay">
                        <a href="?day=<?php echo $previous_day; ?>" class="prev">Previous Day</a>
                        <a href="?day=<?php echo date('Y-m-d'); ?>"
                            class="<?php echo ($selected_day == date('Y-m-d')) ? 'active' : ''; ?>">Today</a>
                        <?php if ($selected_day != date('Y-m-d')): ?>
                            <a href="?day=<?php echo $next_day; ?>" class="next">Next Day</a>
                        <?php endif; ?>
                    </div>

                    </table>
                </div>
            </div>
        </div>
    </section>
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
    <script src="./js/scripts.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>