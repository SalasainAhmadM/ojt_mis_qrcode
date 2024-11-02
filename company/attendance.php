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

// Get the selected day (or default to today)
$selected_day = isset($_GET['day']) ? $_GET['day'] : date('Y-m-d');

// Check if the selected day is a holiday
$holiday_name = '';
$holiday_query = "SELECT holiday_name FROM holiday WHERE holiday_date = ?";
if ($stmt = $database->prepare($holiday_query)) {
    $stmt->bind_param("s", $selected_day);
    $stmt->execute();
    $stmt->bind_result($holiday_name);
    $stmt->fetch();
    $stmt->close();
}

// Check if the selected day is suspended based on schedule
$schedule_day_type = 'Regular';
$schedule_query = "SELECT day_type FROM schedule WHERE company_id = ? AND date = ?";
if ($stmt = $database->prepare($schedule_query)) {
    $stmt->bind_param("is", $company_id, $selected_day);
    $stmt->execute();
    $stmt->bind_result($schedule_day_type);
    $stmt->fetch();
    $stmt->close();
}

// Set display text and color based on holiday or suspension status
$display_text = '';
$display_color = '#095d40'; // Default color

if ($schedule_day_type === 'Suspended') {
    $display_text = "Suspended (" . date('F d, Y', strtotime($selected_day)) . ")";
    $display_color = 'orange';
} elseif ($holiday_name) {
    $display_text = "$holiday_name (" . date('F d, Y', strtotime($selected_day)) . ")";
    $display_color = 'darkred';
} else {
    $display_text = date('F d, Y', strtotime($selected_day));
}

// Handle search query
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : null;

// Query to fetch students and attendance based on search or day
$students_query = "
    SELECT s.student_id, s.student_firstname, s.student_middle, s.student_lastname, 
           s.student_image, a.time_in, a.time_out, a.ojt_hours
    FROM student s
    LEFT JOIN attendance a ON s.student_id = a.student_id 
    WHERE s.company = ? AND DATE(a.time_in) = ?
";

// If a search is provided, add the search condition
if ($search) {
    $students_query .= " AND (s.student_firstname LIKE ? OR s.student_lastname LIKE ?)";
    $query_params = [$company_id, $selected_day, $search, $search];
} else {
    $query_params = [$company_id, $selected_day];
}

$students_query .= " ORDER BY s.student_lastname, a.time_in ASC";

if ($stmt = $database->prepare($students_query)) {
    $stmt->bind_param(str_repeat("s", count($query_params)), ...$query_params);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[$row['student_id']][] = $row;
    }
    $stmt->close();
}

// Function to format hours into "X hrs Y mins"
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
$remarks_query = "
    SELECT student_id, remark_type 
    FROM attendance_remarks 
    WHERE schedule_id = (SELECT schedule_id FROM schedule WHERE company_id = ? AND date = ?)
";
$remarks = [];
if ($stmt = $database->prepare($remarks_query)) {
    $stmt->bind_param("is", $company_id, $selected_day);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $remarks[$row['student_id']] = $row['remark_type'];
    }
    $stmt->close();
}
// Calculate previous and next day for pagination
$previous_day = date('Y-m-d', strtotime($selected_day . ' -1 day'));
$next_day = date('Y-m-d', strtotime($selected_day . ' +1 day'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company - Attendance</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>
        .whole-box tbody {
            display: block;
            max-height: 350px;
            overflow-y: scroll;
            width: calc(100% + 17px);
            margin-right: -17px;
        }

        @media (max-width: 768px) {
            .whole-box tbody {
                width: calc(100%);
                margin-right: 0px;
            }
        }

        .modal-content {
            max-height: 80%;
            overflow-y: auto;
        }
    </style>
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
            <img src="../uploads/company/<?php echo !empty($company['company_image']) ? $company['company_image'] : 'user.png'; ?>"
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
                <a href="index.php">
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
                <div class="iocn-link" class="active">
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
                <a href="attendance.php" class="active">
                    <i class="fa-regular fa-clock"></i>
                    <span class="link_name">Attendance</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="attendance.php">Attendance</a></li>
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

            <div class="header-box">
                <label style="color: #a6a6a6; margin-left: 5px;">Attendance</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <h2>Attendance -
                        <span style="color: <?php echo $display_color; ?>">
                            <?php echo $display_text; ?>
                        </span>
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
                            <?php if (!empty($students) || !empty($remarks)): ?>
                                <?php
                                // Loop through all students who attended
                                foreach ($students as $student_id => $attendances):
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

                                    // Determine if the student is marked as Absent or Late
                                    $remark_type = isset($remarks[$student_id]) ? $remarks[$student_id] : null;
                                    ?>
                                    <tr>
                                        <td class="image" style="position:relative;">
                                            <img style="border-radius: 50%;"
                                                src="../uploads/student/<?php echo !empty($attendance['student_image']) ? $attendance['student_image'] : 'user.png'; ?>"
                                                alt="Student Image">

                                            <!-- Absent Icon -->
                                            <?php if ($remark_type === 'Absent'): ?>
                                                <span class="tooltip-icon absent" title="Absent"
                                                    onclick="openRemarkModal(<?php echo $student_id; ?>, 'Absent')">X</span>
                                            <?php elseif ($remark_type === 'Late'): ?>
                                                <span class="tooltip-icon late" title="Late"
                                                    onclick="openRemarkModal(<?php echo $student_id; ?>, 'Late')">?</span>
                                            <?php endif; ?>


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

                                <!-- Handle Absent students who have no attendance records -->
                                <?php foreach ($remarks as $student_id => $remark_type): ?>
                                    <?php if ($remark_type === 'Absent' && !isset($students[$student_id])): ?>
                                        <tr>
                                            <td class="image" style="position:relative;">
                                                <img style="border-radius: 50%;" src="../uploads/student/user.png"
                                                    alt="Student Image">
                                                <span class="tooltip-icon absent" title="Absent"
                                                    onclick="openRemarkModal(<?php echo $student_id; ?>, 'Absent')">X</span>
                                            </td>
                                            <td class="name">
                                                <?php
                                                // Fetch the student's name from the database for absent students
                                                $student_query = "SELECT student_firstname, student_middle, student_lastname FROM student WHERE student_id = ?";
                                                $stmt = $database->prepare($student_query);
                                                $stmt->bind_param("i", $student_id);
                                                $stmt->execute();
                                                $student_result = $stmt->get_result();
                                                if ($student_row = $student_result->fetch_assoc()) {
                                                    echo $student_row['student_firstname'] . ' ' . $student_row['student_middle'] . '.' . ' ' . $student_row['student_lastname'];
                                                }
                                                $stmt->close();
                                                ?>
                                            </td>
                                            <td class="timein">N/A</td>
                                            <td class="timeout">N/A</td>
                                            <td class="duration">N/A</td>
                                            <td class="status"><span style="color:red;">Absent</span></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No attendance for this day.</td>
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

    <!-- Remark Modal -->
    <div id="remarkModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/notice-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 id="remarkTypeTitle" data-remark-type="">Remark</h2>
            <p id="remarkText">Loading remark...</p>
            <button class="proceed-btn" onclick="closeModal('remarkModal')">Close</button>
        </div>
    </div>


    <script>
        function openRemarkModal(studentId, remarkType) {
            const titleElement = document.getElementById('remarkTypeTitle');
            titleElement.innerText = remarkType;
            titleElement.setAttribute('data-remark-type', remarkType); // Set data attribute for CSS styling

            document.getElementById('remarkText').innerText = 'Loading remark...';

            // AJAX to fetch remark from the server
            fetch(`fetch_remark.php?student_id=${studentId}&remark_type=${remarkType}`)
                .then(response => response.text())
                .then(remark => {
                    document.getElementById('remarkText').innerText = remark || 'No remark available';
                })
                .catch(() => {
                    document.getElementById('remarkText').innerText = 'Error loading remark';
                });

            // Display the modal
            document.getElementById('remarkModal').style.display = 'block';
        }

    </script>
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
    <script src="./js/script.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>