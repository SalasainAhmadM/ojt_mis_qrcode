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
    $stmt->bind_param("i", $adviser_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $adviser = $result->fetch_assoc();
    } else {
        $adviser = [
            'adviser_firstname' => 'Unknown',
            'adviser_middle' => 'U',
            'adviser_lastname' => 'User',
            'adviser_email' => 'unknown@wmsu.edu.ph'
        ];
    }
    $stmt->close();
}

// Capture selected date and initialize variables
$selected_day = isset($_GET['day']) ? $_GET['day'] : null; // Selected date
$student_name = "No Student Selected";
$attendance_records = [];

if (isset($_GET['student_id']) && is_numeric($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);

    // Fetch student details
    $student_query = "SELECT student_firstname, student_middle, student_lastname, company FROM student WHERE student_id = ?";
    if ($student_stmt = $database->prepare($student_query)) {
        $student_stmt->bind_param("i", $student_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        if ($student_result->num_rows > 0) {
            $student = $student_result->fetch_assoc();
            $student_name = $student['student_firstname'] . ' ' . $student['student_middle'] . ' ' . $student['student_lastname'];
        }
        $student_stmt->close();
    }

    // Fetch attendance records with filtering by date if provided
    $attendance_query = "
    SELECT 
        sch.date AS attendance_date,
        CASE
            WHEN ar.remark_type = 'Absent' THEN NULL
            ELSE MIN(a.time_in)
        END AS first_time_in,
        CASE
            WHEN ar.remark_type = 'Absent' THEN NULL
            ELSE MAX(CASE WHEN a.time_out IS NOT NULL THEN a.time_out ELSE NULL END)
        END AS last_time_out,
        CASE
            WHEN ar.remark_type = 'Absent' THEN NULL
            ELSE SUM(a.ojt_hours)
        END AS total_duration,
        sch.day_type,
        ar.remark_type,
        ar.remark,
        ar.status AS remark_status,
        h.holiday_name
    FROM schedule sch
    LEFT JOIN attendance a ON sch.schedule_id = a.schedule_id AND a.student_id = ?
    LEFT JOIN attendance_remarks ar ON sch.schedule_id = ar.schedule_id AND ar.student_id = ?
    LEFT JOIN holiday h ON sch.date = h.holiday_date
    WHERE sch.company_id = (SELECT company FROM student WHERE student_id = ?)
      AND sch.date <= CURDATE() -- Exclude future dates
      " . ($selected_day ? "AND sch.date = ?" : "") . "
    GROUP BY sch.date, sch.day_type, ar.remark_type, ar.remark, ar.status, h.holiday_name
    UNION
    SELECT 
        h.holiday_date AS attendance_date,
        NULL AS first_time_in,
        NULL AS last_time_out,
        NULL AS total_duration,
        'Holiday' AS day_type,
        NULL AS remark_type,
        NULL AS remark,
        NULL AS remark_status,
        h.holiday_name
    FROM holiday h
    WHERE h.holiday_date <= CURDATE() -- Include only past holidays
      " . ($selected_day ? "AND h.holiday_date = ?" : "") . "
    ORDER BY attendance_date DESC;
    ";

    if ($attendance_stmt = $database->prepare($attendance_query)) {
        if ($selected_day) {
            $attendance_stmt->bind_param("iiii", $student_id, $student_id, $student_id, $selected_day);
        } else {
            $attendance_stmt->bind_param("iii", $student_id, $student_id, $student_id);
        }
        $attendance_stmt->execute();
        $attendance_result = $attendance_stmt->get_result();
        while ($row = $attendance_result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
        $attendance_stmt->close();
    }
}

// Fetch students assigned to the logged-in adviser
$students = [];
$query = "SELECT student_id, student_firstname, student_middle, student_lastname FROM student WHERE adviser = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("s", $adviser_id); // Use the adviser's ID to filter
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row; // Add each student to the array
    }
    $stmt->close();
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
                <label style="color: #a6a6a6;">Intern Attendance</label>
            </div>
            <div class="main-box">
                <div style="height: 600px;" class="whole-box">
                    <div class="header-group" style="display: flex; align-items: center;">
                        <!-- <a href="../company.php" class="back-btn">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </a> -->
                        <h2><?php echo htmlspecialchars($student_name, ENT_QUOTES); ?></h2>
                    </div>



                    <div class="filter-group">
                        <!-- Student Dropdown -->
                        <form method="GET" action="attendance-intern.php" id="studentFilterForm">
                            <select name="student_id" id="studentSelect" class="search-bar">
                                <option value="" selected disabled>Select Student</option>
                                <?php
                                $current_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
                                foreach ($students as $student):
                                    $selected = ($current_student_id == $student['student_id']) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo htmlspecialchars($student['student_id'], ENT_QUOTES); ?>"
                                        <?php echo $selected; ?>>
                                        <?php
                                        echo htmlspecialchars(
                                            $student['student_firstname'] . ' ' .
                                            $student['student_middle'] . ' ' .
                                            $student['student_lastname'],
                                            ENT_QUOTES
                                        );
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>


                        <!-- Month Picker -->
                        <form method="GET" action="" class="month-picker-form">
                            <div class="search-bar-container">
                                <input type="month" class="search-bar" id="searchMonth" name="month">
                            </div>
                        </form>
                        <form method="GET" action="" class="date-picker-form">
                            <div class="search-bar-container">
                                <input type="date" class="search-bar" id="searchDate" name="day">
                            </div>
                        </form>
                        <form method="GET" action="attendance-intern.php">
                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                            <button type="submit" class="reset-bar-icon"><i class="fa fa-times-circle"></i></button>
                        </form>


                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th class="date">Date</th>
                                <th class="timein">Time-in</th>
                                <th class="timeout">Time-out</th>
                                <th class="duration">Duration</th>
                                <th class="status">Day Type</th>
                                <th class="remark">Remark</th>
                                <th class="action">Status</th>

                            </tr>
                        </thead>
                        <tbody style="max-height: 400px; overflow-y: auto;">
                            <?php if (!empty($attendance_records)): ?>
                                <?php foreach ($attendance_records as $record): ?>
                                    <tr style="<?php echo $record['remark_type'] === 'Absent' ? 'color: red;' : ''; ?>">
                                        <!-- Date -->
                                        <td class="date">
                                            <?php echo htmlspecialchars($record['attendance_date'], ENT_QUOTES); ?>
                                        </td>

                                        <!-- Time In -->
                                        <td class="timein">
                                            <?php
                                            if ($record['remark_type'] === 'Absent') {
                                                echo '<span style="color: red;">N/A</span>';
                                            } elseif ($record['day_type'] === 'Holiday') {
                                                echo '<span style="color: gray;">N/A</span>';
                                            } elseif ($record['day_type'] === 'Suspended') {
                                                echo '<span style="color: orange;">N/A</span>';
                                            } else {
                                                echo $record['first_time_in']
                                                    ? htmlspecialchars(date("g:i a", strtotime($record['first_time_in'])), ENT_QUOTES)
                                                    : 'N/A';
                                            }
                                            ?>
                                        </td>

                                        <!-- Time Out -->
                                        <td class="timeout">
                                            <?php
                                            if ($record['remark_type'] === 'Absent') {
                                                echo '<span style="color: red;">N/A</span>';
                                            } elseif ($record['day_type'] === 'Holiday') {
                                                echo '<span style="color: gray;">N/A</span>';
                                            } elseif ($record['day_type'] === 'Suspended') {
                                                echo '<span style="color: orange;">N/A</span>';
                                            } else {
                                                echo $record['last_time_out']
                                                    ? htmlspecialchars(date("g:i a", strtotime($record['last_time_out'])), ENT_QUOTES)
                                                    : 'N/A';
                                            }
                                            ?>
                                        </td>

                                        <!-- Duration -->
                                        <td class="duration">
                                            <?php
                                            if ($record['remark_type'] === 'Absent') {
                                                echo '<span style="color: red;">N/A</span>';
                                            } elseif ($record['day_type'] === 'Holiday') {
                                                echo '<span style="color: gray;">N/A</span>';
                                            } elseif ($record['day_type'] === 'Suspended') {
                                                echo '<span style="color: orange;">N/A</span>';
                                            } else {
                                                $total_hours = $record['total_duration'];
                                                $hours = floor($total_hours);
                                                $minutes = round(($total_hours - $hours) * 60);

                                                $duration = '';
                                                if ($hours > 0) {
                                                    $duration .= $hours . 'hr' . ($hours > 1 ? 's' : '');
                                                }
                                                if ($minutes > 0) {
                                                    $duration .= ($hours > 0 ? ' ' : '') . $minutes . 'min' . ($minutes > 1 ? 's' : '');
                                                }
                                                echo htmlspecialchars($duration, ENT_QUOTES);
                                            }
                                            ?>
                                        </td>

                                        <!-- Day Type -->
                                        <td class="status" style="<?php
                                        echo $record['day_type'] === 'Suspended' ? 'color: orange;' :
                                            ($record['day_type'] === 'Holiday' ? 'color: gray;' : ''); ?>">
                                            <?php
                                            if ($record['day_type'] === 'Holiday') {
                                                echo '<i>' . htmlspecialchars($record['holiday_name'], ENT_QUOTES) . '</i>';
                                            } else {
                                                echo htmlspecialchars($record['day_type'], ENT_QUOTES);
                                            }
                                            ?>
                                        </td>

                                        <!-- Remarks -->
                                        <td class="remarks">
                                            <?php
                                            if ($record['remark_type'] === 'Absent') {
                                                echo '<span style="color: red;">Absent</span>';
                                            } elseif ($record['remark_type'] === 'Late') {
                                                echo '<span style="color: yellow;">Late</span><span style="color: black;"> - Present</span>';
                                            } elseif ($record['day_type'] === 'Holiday') {
                                                echo '<span style="color: gray;">N/A</span>';
                                            } elseif ($record['day_type'] === 'Suspended') {
                                                echo '<span style="color: orange;">N/A</span>';
                                            } elseif (!empty($record['first_time_in']) && $record['last_time_out'] !== 'N/A' && ($record['day_type'] === 'Halfday' || $record['day_type'] === 'Regular')) {
                                                echo '<span style="color: #095d40;">Present</span>';
                                            } else {
                                                echo '<span>N/A</span>';
                                            }
                                            ?>
                                        </td>

                                        <!-- Remark Status -->
                                        <td class="action">
                                            <?php
                                            if ($record['remark_type'] === 'Absent') {
                                                echo '<span style="color: red;">' . htmlspecialchars($record['remark_status'], ENT_QUOTES) . '</span>';
                                            } elseif ($record['day_type'] === 'Holiday') {
                                                echo '<span style="color: gray;">N/A</span>';
                                            } elseif ($record['day_type'] === 'Suspended') {
                                                echo '<span style="color: orange;">N/A</span>';
                                            } else {
                                                echo htmlspecialchars($record['remark_status'] ?? 'N/A', ENT_QUOTES);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No attendance records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>


                        <script>
                            document.getElementById("studentSelect").addEventListener("change", function () {
                                const form = document.getElementById("studentFilterForm");
                                form.submit(); // Submit the form to update the student_id in the URL
                            });

                            // Filter by Date
                            document.getElementById("searchDate").addEventListener("change", function () {
                                filterTable("date", this.value);
                            });

                            // Filter by Month
                            document.getElementById("searchMonth").addEventListener("change", function () {
                                filterTable("month", this.value);
                            });

                            function filterTable(type, value) {
                                const tableRows = document.querySelectorAll("table tbody tr");
                                let rowsShown = 0;
                                const noAttendanceMessageId = "no-attendance-message";

                                tableRows.forEach(row => {
                                    const dateCell = row.querySelector("td.date");
                                    const recordDate = dateCell ? dateCell.textContent.trim() : null;

                                    // Determine if the row should be shown
                                    let showRow = false;
                                    if (recordDate) {
                                        const recordDateObj = new Date(recordDate); // Convert record date to Date object

                                        if (type === "date" && recordDate === value) {
                                            showRow = true; // Exact date match
                                        } else if (
                                            type === "month" &&
                                            value === recordDateObj.toISOString().slice(0, 7)
                                        ) {
                                            showRow = true; // Month match (e.g., "2024-12")
                                        }
                                    }

                                    // Show or hide the row
                                    if (showRow) {
                                        row.style.display = "";
                                        rowsShown++;
                                    } else {
                                        row.style.display = "none";
                                    }
                                });

                                // Manage the "No Attendance Found" message
                                let noAttendanceMessage = document.getElementById(noAttendanceMessageId);
                                if (rowsShown === 0) {
                                    if (!noAttendanceMessage) {
                                        noAttendanceMessage = document.createElement("tr");
                                        noAttendanceMessage.id = noAttendanceMessageId;
                                        noAttendanceMessage.innerHTML = `<td colspan="7" style="text-align: center; color: gray;">No Attendance Found</td>`;
                                        document.querySelector("table tbody").appendChild(noAttendanceMessage);
                                    }
                                } else {
                                    if (noAttendanceMessage) {
                                        noAttendanceMessage.remove();
                                    }
                                }
                            }


                        </script>
                    </table>

                    <!-- Pagination Links -->
                    <!-- <?php if ($total_pages > 1): ?> -->
                        <div class="pagination">

                        </div>
                        <!-- <?php endif; ?> -->


                </div>
            </div>

    </section>
    <!-- <?php echo htmlspecialchars($record['time_out_reason'] ?? 'N/A', ENT_QUOTES); ?> -->
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