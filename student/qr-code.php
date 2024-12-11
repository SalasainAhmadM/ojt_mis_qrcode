<?php
session_start();
require '../conn/connection.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');
$database->query("SET time_zone = '+08:00'");
// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Fetch student details from the database
$student_id = $_SESSION['user_id'];

$query = "SELECT * FROM student WHERE student_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        $student = [
            'student_firstname' => 'Unknown',
            'student_middle' => 'U',
            'student_lastname' => 'User',
            'student_email' => 'unknown@wmsu.edu.ph'
        ];
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

// Fetch schedule details using the retrieved company_id
// $schedule_query = "SELECT * FROM schedule WHERE company_id = ? AND date = CURDATE()";
// if (isset($company_id)) {
//     if ($schedule_stmt = $database->prepare($schedule_query)) {
//         $schedule_stmt->bind_param("i", $company_id);
//         $schedule_stmt->execute();
//         $schedule_result = $schedule_stmt->get_result();

//         if ($schedule_result->num_rows > 0) {
//             $schedule = $schedule_result->fetch_assoc();
//             $schedule_id = $schedule['schedule_id'];
//         }
//         $schedule_stmt->close();
//     }
// }

// Fetch schedule details using the retrieved company_id and ensure it's not suspended
$schedule_query = "SELECT * FROM schedule WHERE company_id = ? AND date = CURDATE() AND day_type != 'Suspended'";
if (isset($company_id)) {
    if ($schedule_stmt = $database->prepare($schedule_query)) {
        $schedule_stmt->bind_param("i", $company_id);
        $schedule_stmt->execute();
        $schedule_result = $schedule_stmt->get_result();

        if ($schedule_result->num_rows > 0) {
            $schedule = $schedule_result->fetch_assoc();
            $schedule_id = $schedule['schedule_id'];
        }
        $schedule_stmt->close();
    }
}

// Check if today is a holiday
$holiday_query = "SELECT * FROM holiday WHERE holiday_date = CURDATE()";
$todayIsHoliday = false;
if ($holiday_stmt = $database->prepare($holiday_query)) {
    $holiday_stmt->execute();
    $holiday_result = $holiday_stmt->get_result();
    $todayIsHoliday = $holiday_result->num_rows > 0;
    $holiday_stmt->close();
}

$current_time = date('H:i:s');
// Define the grace period in minutes
$isLate = false;
$gracePeriodMinutes = 15;

if (isset($schedule) && !$todayIsHoliday) { // Check if it's not a holiday and not suspended
    // Convert scheduled time_in and current time into DateTime objects
    $scheduledTimeIn = new DateTime($schedule['time_in']);
    $scheduledTimeOut = new DateTime($schedule['time_out']);
    $currentDateTime = new DateTime($current_time);

    // Add the grace period to the scheduled time_in
    $graceEndTime = clone $scheduledTimeIn;
    $graceEndTime->modify("+{$gracePeriodMinutes} minutes");

    // Check if the current time is after the grace period and before the schedule's time_out
    if ($currentDateTime > $graceEndTime && $currentDateTime < $scheduledTimeOut) {
        // Check if there's no existing 'Late' remark
        $remark_query = "SELECT * FROM attendance_remarks WHERE student_id = ? AND schedule_id = ? AND remark_type = 'Late'";
        if ($remark_stmt = $database->prepare($remark_query)) {
            $remark_stmt->bind_param("ii", $student_id, $schedule_id);
            $remark_stmt->execute();
            $remark_result = $remark_stmt->get_result();

            // Only mark as late if no existing 'Late' remark
            if ($remark_result->num_rows === 0) {
                $isLate = true;
            }

            $remark_stmt->close();
        }
    } else {
        $isLate = false; // Either within the grace period or past the time_out
    }
}



// Function to check if a reason for absence is already submitted
function hasSubmittedReason($database, $student_id, $schedule_id)
{
    $query = "SELECT * FROM attendance_remarks WHERE student_id = ? AND schedule_id = ? AND remark_type = 'Absent'";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("ii", $student_id, $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $hasReason = $result->num_rows > 0;
        $stmt->close();
        return $hasReason;
    }
    return false;
}

$absent_query = "
    SELECT s.schedule_id, s.date 
    FROM schedule s
    LEFT JOIN attendance a ON s.schedule_id = a.schedule_id AND a.student_id = ?
    LEFT JOIN holiday h ON s.date = h.holiday_date
    JOIN student st ON st.student_id = ? 
    WHERE s.company_id = ? 
    AND s.date >= DATE(st.date_start) -- Ensure schedules are after student's start date
    AND s.date < CURDATE() 
    AND DAYOFWEEK(s.date) NOT IN (1, 7) 
    AND s.day_type != 'Suspended'
    AND h.holiday_date IS NULL 
    AND a.attendance_id IS NULL";

$absentDates = [];
$isAbsent = false;

if ($absent_stmt = $database->prepare($absent_query)) {
    $absent_stmt->bind_param("iii", $student_id, $student_id, $company_id); // Bind student_id twice: once for attendance and once for date_start
    $absent_stmt->execute();
    $absent_result = $absent_stmt->get_result();

    // Fetch all dates where the student was absent, along with schedule_id
    while ($row = $absent_result->fetch_assoc()) {
        if (!hasSubmittedReason($database, $student_id, $row['schedule_id'])) {
            $absentDates[] = [
                'schedule_id' => $row['schedule_id'],
                'date' => $row['date']
            ];
        }
    }

    $isAbsent = count($absentDates) > 0;
    $absent_stmt->close();
}

// Check if the student has already timed out today
$hasTimedOutToday = false;

if (isset($schedule_id)) { // Ensure there's a valid schedule ID
    $timeout_query = "
        SELECT * 
        FROM attendance 
        WHERE student_id = ? 
        AND schedule_id = ? 
        AND time_out_reason = 'Time-Out'";

    if ($timeout_stmt = $database->prepare($timeout_query)) {
        $timeout_stmt->bind_param("ii", $student_id, $schedule_id);
        $timeout_stmt->execute();
        $timeout_result = $timeout_stmt->get_result();
        $hasTimedOutToday = $timeout_result->num_rows > 0;
        $timeout_stmt->close();
    }
}

?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern - QR Scanner</title>
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

        .lottie-wrapper {
            margin-left: -20px;
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
            width: calc(110% - 78px);
            margin-left: -60px;
        }

        .content-wrapper {
            margin-top: 0;
        }

        .left-box-qr,
        .right-box-qr {
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

        video {
            margin-left: 25px;
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

    .whole-box {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 20px;
    }

    .centered-header {
        margin-bottom: 20px;
        font-size: 1.5rem;
        color: #333;
    }

    .qr-scanner {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .lottie-wrapper {
        width: 100%;
        max-width: 300px;
    }

    .qr-camera {
        width: 100%;
        height: auto;
    }

    .start-scan-container {
        margin-top: 20px;
    }

    .start-scan {
        padding: 10px 20px;
        font-size: 1rem;
        color: #fff;
        background-color: #095d40;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
</style>

<body>
    <div class="header">
        <i class=""></i>
        <div class="school-name">S.Y. 2024-2025 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span
                style="color: #095d40;">|</span>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
            <img src="../img/ccs.png">
        </div>
    </div>
    <div class="sidebar close">
        <div class="profile-details">
            <img src="../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
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
                <a href="index.php">
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
                <a href="qr-code.php" class="active">
                    <i class="fa-solid fa-qrcode"></i>
                    <span class="link_name">QR Scanner</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="qr-code.php">QR Scanner</a></li>
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
            <div class="header-box">
                <label style="color: #a6a6a6; margin-left: 10px;">QR Scanner</label>
            </div>
            <div class="main-box">
                <!-- <div class="left-box-qr">
                   
                    <div class="intern-timein-details">
                        <div class="intern-image">
                            <img src="../uploads/student/user.png" alt="Intern Image" id="intern-image">
                        </div>
                        <div class="intern-details">
                            <h3><strong id="intern-name">Intern Name</strong></h3>
                            <p>WMSU ID: <strong id="intern-wmsu-id"></strong></p>
                            <p>Email: <strong id="intern-email"></strong></p>
                            <p>Total OJT Hours: <span class="total-ojt-hrs"><strong id="total-ojt-hrs"></strong></span>
                            </p>
                        </div>
                    </div>

                    <div class="time-in-details">
                        <div class="time-in-info">
                            <h3>Time In</h3>
                            <p>Time: <strong id="time-in-time"></strong></p>
                            <p>Date: <strong id="time-in-date"></strong></p>
                        </div>
                        <div class="clock-image">
                            <img src="../img/clock.png" alt="Clock Image" style="">
                        </div>
                    </div>
                </div> -->

                <!-- Right Box for Scanning QR Code-->
                <div class="whole-box">
                    <h2 style="text-align: center;">Scan Your QR Code</h2>
                    <div id="qr-scanner">
                        <!-- Lottie Animation -->
                        <div id="lottie-animation" class="lottie-wrapper">
                            <lottie-player src="../animation/qr-095d40.json" background="transparent" speed="1"
                                class="qr-camera" loop autoplay></lottie-player>
                        </div>

                        <video id="video" autoplay hidden></video>
                        <canvas id="canvas" hidden></canvas>

                        <div id="start-scan-container" class="start-scan-container">
                            <?php if ($hasTimedOutToday): ?>
                                <button id="stop-scan" class="start-scan timed-out">Timed-Out <i
                                        class="fa-solid fa-ban"></i></button>
                            <?php else: ?>
                                <button id="start-scan" class="start-scan">Start Scan <i
                                        class="fa-solid fa-camera"></i></button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- Absent Response Failure Modal -->
    <div id="absentResponseFailureModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay></lottie-player>
            </div>
            <h2 style="color: #8B0000">Start Scan Failed</h2>
            <p>You cannot scan as you have already timed out for today.</p>
            <button class="cancel-btn" onclick="closeModal('absentResponseFailureModal')">Okay</button>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const stopScanButton = document.getElementById("stop-scan");
            const startScanButton = document.getElementById("start-scan");

            if (stopScanButton) {
                // If the "stop-scan" button exists (Timed-Out case)
                stopScanButton.addEventListener("click", function () {
                    showModal("absentResponseFailureModal");
                });
            }

            if (startScanButton) {
                // If the "start-scan" button exists (Start Scan case)
                startScanButton.addEventListener("click", function () {
                    console.log("Starting QR scan...");
                    // Add QR scanning functionality here
                });
            }
        });

        // Function to show the modal
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = "block";
            }
        }

        // Function to close the modal
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = "none";
            }
        }


    </script>
    <!-- Absent Notification Modal -->
    <?php if ($isAbsent): ?>
        <div id="absentNotificationModal" class="modal" style="display: block;">
        <?php else: ?>
            <div id="absentNotificationModal" class="modal" style="display: none;">
            <?php endif; ?>
            <div style=" margin: 5% auto;" class="modal-content-absent">
                <div style="display: flex; justify-content: center; align-items: center;">
                    <lottie-player src="../animation/alert-8B0000.json" background="transparent" speed="1"
                        style="width: 150px; height: 150px;" loop autoplay></lottie-player>
                </div>
                <h2 style="color: #8B0000">You Have Been Marked Absent</h2>
                <p>Name: <span style="color: #095d40; font-size: 20px">
                        <?= htmlspecialchars($student['student_firstname']) ?>
                    </span></p>

                <p>The following dates were missed:</p>
                <ul>
                    <?php foreach ($absentDates as $absent): ?>
                        <li>
                            <?= "Date: " . htmlspecialchars(date("F j, Y", strtotime($absent['date']))) ?>
                            <input type="hidden" name="schedule_ids[]"
                                value="<?= htmlspecialchars($absent['schedule_id']) ?>">
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div id="absent-reason">
                    <p>Reason for absence:</p>
                    <textarea style="height: 100px" id="absent-reason-text" placeholder="Explain your reason here..."
                        required></textarea>
                    <p>Upload Proof Image:</p>
                    <input type="file" id="proof-image" accept="image/*" required>
                </div>
                <button class="proceed-btn"
                    onclick="submitAbsentReason(<?= htmlspecialchars($student['student_id']) ?>)">Submit</button>
            </div>
        </div>

        <script>
            function submitAbsentReason(studentId) {
                const reason = document.getElementById("absent-reason-text").value.trim();
                const proofImageInput = document.getElementById("proof-image");
                const proofImage = proofImageInput.files[0];

                if (reason === "") {
                    alert("Please provide a reason for your absence.");
                    return;
                }

                // Get all schedule IDs from hidden inputs
                const scheduleIds = Array.from(document.querySelectorAll("input[name='schedule_ids[]']")).map(input => input.value);

                const formData = new FormData();
                formData.append("student_id", studentId);
                formData.append("reason", reason);
                if (proofImage) {
                    formData.append("proof_image", proofImage);
                }
                scheduleIds.forEach(scheduleId => formData.append("schedule_ids[]", scheduleId));

                fetch('submit_absent_reason.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            closeModal('absentNotificationModal');
                            openModal('absentResponseSuccessModal');
                        } else {
                            openModal('absentResponseFailureModal');
                        }
                    })
                    .catch(error => {
                        console.error("Error submitting absent reason:", error);
                        openModal('absentResponseFailureModal');
                    });
            }

        </script>
        <!-- Absent Response Success Modal -->
        <div id="absentResponseSuccessModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div style="display: flex; justify-content: center; align-items: center;">
                    <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                        style="width: 150px; height: 150px;" loop autoplay></lottie-player>
                </div>
                <h2>Absent Reason Submitted Successfully!</h2>
                <p>Your reason for being absent has been recorded successfully.</p>
                <button class="proceed-btn" onclick="closeModal('absentResponseSuccessModal')">Proceed</button>
            </div>
        </div>

        <!--  -->
        <!-- Time-Out Error Modal -->
        <div id="timeoutErrorModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div style="display: flex; justify-content: center; align-items: center;">
                    <lottie-player src="../animation/error-095d40.json" background="transparent" speed="1"
                        style="width: 150px; height: 150px;" loop autoplay></lottie-player>
                </div>
                <h2>Time-In Not Allowed</h2>
                <p>The schedule's time-out has already passed.</p>
                <button class="proceed-btn" onclick="closeModal('timeoutErrorModal')">Close</button>
            </div>
        </div>


        <!-- Absent Response Failure Modal -->
        <div id="absentResponseFailureModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div style="display: flex; justify-content: center; align-items: center;">
                    <lottie-player src="../animation/error-095d40.json" background="transparent" speed="1"
                        style="width: 150px; height: 150px;" loop autoplay></lottie-player>
                </div>
                <h2>Submission Failed</h2>
                <p>There was an error submitting your absent reason. Please try again.</p>
                <button class="proceed-btn" onclick="closeModal('absentResponseFailureModal')">Retry</button>
            </div>
        </div>


        <!-- Late Modal -->
        <?php if ($isLate): ?>
            <div id="qrsuccesslateTimeinModal" class="modal" style="display: block;">
            <?php else: ?>
                <div id="qrsuccesslateTimeinModal" class="modal" style="display: none;">
                <?php endif; ?>

                <div style=" margin: 5% auto;" class="modal-content-late">
                    <div style="display: flex; justify-content: center; align-items: center;">
                        <lottie-player src="../animation/clock-095d40.json" background="transparent" speed="1"
                            style="width: 150px; height: 150px;" loop autoplay></lottie-player>
                    </div>
                    <h2>You Are Late!</h2>
                    <p>Name: <span
                            style="color: #095d40; font-size: 20px"><?= htmlspecialchars($student['student_firstname']) ?></span>
                    </p>

                    <?php
                    // Convert scheduled time_in to 12-hour format with lowercase am/pm if schedule exists
                    if ($schedule) {
                        $scheduledTime = new DateTime($schedule['time_in']);
                        $scheduledTimeFormatted = $scheduledTime->format('g:ia');

                        // Calculate the late duration in hours and minutes
                        $currentDateTime = new DateTime($current_time);
                        $interval = $scheduledTime->diff($currentDateTime);

                        if ($interval->h > 0) {
                            $lateDuration = $interval->h . "hr" . ($interval->h > 1 ? "s" : "") . ($interval->i > 0 ? " " . $interval->i . "min" . ($interval->i > 1 ? "s" : "") : "");
                        } else {
                            $lateDuration = $interval->i . "min" . ($interval->i > 1 ? "s" : "");
                        }
                    } else {
                        $scheduledTimeFormatted = "N/A";
                        $lateDuration = "N/A";
                    }
                    ?>
                    <p>Time-in required: <strong><?= $scheduledTimeFormatted ?></strong></p>
                    <p>You are late by <strong><?= $lateDuration ?>.</strong></p>

                    <div id="late-reason">
                        <p>Reason for being late:</p>
                        <textarea style="height: 100px" id="late-reason-text" placeholder="Explain your reason here..."
                            required></textarea>
                    </div>
                    <button class="proceed-btn"
                        onclick="submitReason(<?= htmlspecialchars($student['student_id']) ?>, <?= htmlspecialchars($schedule['schedule_id']) ?>)">Submit</button>
                </div>
            </div>

            <script>
                function submitReason(studentId, scheduleId) {
                    const reason = document.getElementById("late-reason-text").value.trim();
                    if (reason === "") {
                        alert("Please provide a reason for being late.");
                        return;
                    }

                    // Send late reason to the server via POST
                    fetch('submit_late_reason.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ student_id: studentId, schedule_id: scheduleId, reason: reason })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                closeModal('qrsuccesslateTimeinModal');
                                openModal('lateResponseSuccessModal');
                            } else {
                                alert("Failed to submit late reason.");
                            }
                        })
                        .catch(error => {
                            console.error("Error submitting reason:", error);
                        });
                }


            </script>
            <!-- Late Response Success Modal -->
            <div id="lateResponseSuccessModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div style="display: flex; justify-content: center; align-items: center;">
                        <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                            style="width: 150px; height: 150px;" loop autoplay></lottie-player>
                    </div>
                    <h2>Late Response Submitted Successfully!</h2>
                    <p>Your reason for being late has been recorded successfully.</p>
                    <button class="proceed-btn" onclick="closeModal('lateResponseSuccessModal')">Proceed</button>
                </div>
            </div>

            <script>
                document.getElementById("start-scan").addEventListener("click", function () {
                    document.getElementById("lottie-animation").style.display = 'none';
                    const video = document.getElementById("video");
                    video.hidden = false;
                    video.style.display = 'block';

                    startQRCodeScanner();
                });

                function startQRCodeScanner() {
                    const video = document.getElementById("video");
                    const canvas = document.getElementById("canvas");

                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({
                            video: { facingMode: 'environment' }
                        }).then(function (stream) {
                            video.srcObject = stream;
                            video.play();

                            const context = canvas.getContext('2d');
                            video.addEventListener('play', () => {
                                const scanInterval = setInterval(() => {
                                    canvas.width = video.videoWidth;
                                    canvas.height = video.videoHeight;
                                    context.drawImage(video, 0, 0, canvas.width, canvas.height);

                                    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                                    const qrCode = jsQR(imageData.data, canvas.width, canvas.height);

                                    if (qrCode) {
                                        stopScanner(video, scanInterval);
                                        processQRCode(qrCode.data);
                                    }
                                }, 500);
                            });
                        }).catch(function (err) {
                            console.error("Error accessing camera: ", err);
                            alert("Could not access camera. Please ensure you have allowed camera access in your browser settings.");
                        });
                    } else {
                        alert("Camera not supported in this browser.");
                    }
                }

                function stopScanner(video, scanInterval) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                    clearInterval(scanInterval);
                    video.style.display = 'none';
                }

                function updateInternDetails(data) {
                    document.querySelector(".intern-image img").src = data.student_image ?
                        "../uploads/student/" + data.student_image :
                        "../uploads/student/user.png";
                    document.querySelector(".intern-details h3 strong").innerText = data.student_name;
                    document.querySelector(".intern-details p:nth-of-type(1) strong").innerText = data.wmsu_id;
                    document.querySelector(".intern-details p:nth-of-type(2) strong").innerText = data.email;
                    document.querySelector(".intern-details p:nth-of-type(3) strong").innerText = data.total_ojt_hours;
                }

                function updateTimeInDetails(data) {
                    document.querySelector(".time-in-details p:nth-of-type(1) strong").innerText = data.time_in;
                    document.querySelector(".time-in-details p:nth-of-type(2) strong").innerText = data.date_in;
                    document.querySelector(".time-in-details h3").innerText = data.event_type;
                }

                function updateTimeOutDetails(data) {
                    document.querySelector(".time-in-details p:nth-of-type(1) strong").innerText = data.time_out;
                    document.querySelector(".time-in-details p:nth-of-type(2) strong").innerText = data.date_in;
                    document.querySelector(".time-in-details h3").innerText = data.event_type;
                }

                function openModal(modalId) {
                    document.getElementById(modalId).style.display = 'block';
                }

                function closeModal(modalId) {
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.style.display = 'none';
                    }
                }

                function processQRCode(qrData) {
                    const [companyId, scannedDate] = qrData.split(' - ');
                    // Convert current date to Asia/Manila time
                    const now = new Date();
                    const offsetInHours = 8; // UTC+8 for Manila
                    const manilaDate = new Date(now.getTime() + offsetInHours * 60 * 60 * 1000);
                    const today = manilaDate.toISOString().split('T')[0];

                    if (scannedDate !== today) {
                        displayErrorModal('Invalid QR Code! This QR code is not for today.');
                        return;
                    }

                    fetch('scan_attendance.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ company_id: companyId })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.event_type === 'Time-in') {
                                    // Time-in logic
                                    updateInternDetails(data);
                                    updateTimeInDetails(data);
                                    document.querySelector("#qrsuccessTimeinModal span").innerText = data.student_name;
                                    document.querySelector("#qrsuccessTimeinModal h3").innerText = data.time_in;
                                    openModal('qrsuccessTimeinModal'); // Show Time-in modal
                                } else if (data.event_type === 'Time-out') {
                                    // Time-out logic
                                    updateInternDetails(data);
                                    updateTimeOutDetails(data);
                                    document.querySelector("#qrsuccessTimeoutModal span").innerText = data.student_name;
                                    document.querySelector("#qrsuccessTimeoutModal h3").innerText = data.time_out;
                                    document.querySelector("#ojt-hours").innerText = data.ojt_hours;
                                    document.querySelector("#attendance-id").value = data.attendance_id;
                                    openModal('qrsuccessTimeoutModal'); // Show Time-out modal
                                }
                            } else {
                                // Display error for invalid QR codes
                                displayErrorModal(data.message);
                            }
                        })
                        .catch(error => {
                            console.error("Error processing QR code:", error);
                            displayErrorModal('An error occurred while processing the QR code. Please try again.');
                        });
                }

                function displayErrorModal(message) {
                    document.querySelector("#qrErrorModal p").innerText = message;
                    openModal("qrErrorModal");
                }
                function submitTimeout() {
                    const attendanceId = document.querySelector("#attendance-id").value;
                    const timeoutReason = document.querySelector("#timeout-reason").value;

                    fetch('update_attendance.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ attendance_id: attendanceId, reason: timeoutReason })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Dynamic modal title and animation based on the reason
                                let modalTitle, animationSrc;
                                switch (timeoutReason) {
                                    case 'Time-Out':
                                        closeModal('qrsuccessTimeinModal');
                                        closeModal('qrsuccessTimeoutModal');
                                        modalTitle = "You have successfully timed out!";
                                        animationSrc = "../animation/success-095d40.json";
                                        break;
                                    case 'Company Errand':
                                        closeModal('qrsuccessTimeoutModal');
                                        modalTitle = "Company errand time-out recorded successfully!";
                                        animationSrc = "../animation/success-095d40.json";
                                        break;
                                    case 'Lunch Break':
                                        closeModal('qrsuccessTimeoutModal');
                                        modalTitle = "Lunch break time-out recorded successfully!";
                                        animationSrc = "../animation/success-095d40.json";
                                        break;
                                    default:
                                        modalTitle = "Time-out reason updated successfully!";
                                        animationSrc = "../animation/success-095d40.json";
                                }

                                // Update the modal content dynamically
                                document.querySelector("#timeoutModal h2").textContent = modalTitle;
                                document.querySelector("#timeoutModal lottie-player").setAttribute("src", animationSrc);

                                // Show the modal
                                document.querySelector("#timeoutModal").style.display = "block";
                            } else {
                                alert("Failed to update time-out reason.");
                            }
                        })
                        .catch(error => {
                            console.error("Error updating time-out reason:", error);
                        });
                }

            </script>

            <div id="timeoutModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div style="display: flex; justify-content: center; align-items: center;">
                        <lottie-player src="../animation/success-095d40.json" background=" transparent" speed="1"
                            style="width: 150px; height: 150px;" loop autoplay></lottie-player>
                    </div>
                    <h2></h2>
                    <button class="proceed-btn" onclick="closeModal('timeoutModal')">Close</button>
                </div>
            </div>

            <!-- QR Scan Time-in Modal -->
            <div id="qrsuccessTimeinModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div style="display: flex; justify-content: center; align-items: center;">
                        <lottie-player src="../animation/clock-095d40.json" background="transparent" speed="1"
                            style="width: 150px; height: 150px;" loop autoplay></lottie-player>
                    </div>
                    <h2>Time-in Successful!</h2>
                    <p>Name: <span id="timein-name" style="color: #095d40; font-size: 20px"></span></p>
                    <p>Time-in</p>
                    <h3 id="timein-time"></h3>
                    <button class="proceed-btn" onclick="closeModal('qrsuccessTimeinModal')">Close</button>
                </div>
            </div>

            <!-- QR Scan Time-out Modal -->
            <div id="qrsuccessTimeoutModal" class="modal" style="display: none; background-color: rgba(0, 0, 0, 0.5);">
                <div class="modal-content" style="
        color: #095d40; 
        border-radius: 10px; 
        width: 400px; 
        margin: 5% auto; 
        padding: 20px; 
    ">
                    <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 15px;">
                        <lottie-player src="../animation/clock-095d40.json" background="transparent" speed="1"
                            style="width: 150px; height: 150px;" loop autoplay>
                        </lottie-player>
                    </div>
                    <h2 style="text-align: center; font-family: Arial, sans-serif;">Time-out Successful!</h2>
                    <p style="text-align: center; font-size: 18px; margin-top: 10px; margin-bottom: 5px;">
                        <span>Name:</span> <span id="timeout-name" style="font-weight: bold; font-size: 20px;"></span>
                    </p>
                    <p style="text-align: center; font-size: 18px; margin: 5px 0;">Time-out</p>
                    <h3 id="timeout-time"
                        style="text-align: center; font-size: 24px; margin: 5px 0; font-weight: bold;"></h3>
                    <p style="text-align: center; font-size: 18px; margin: 10px 0;">
                        OJT Hours: <strong id="ojt-hours" style="font-size: 20px;"></strong>
                    </p>

                    <input type="hidden" id="attendance-id">

                    <div style="margin: 20px 0;">
                        <label for="timeout-reason" style="display: block; font-weight: bold; margin-bottom: 10px;">
                            Select Reason:
                        </label>
                        <select id="timeout-reason" style="
                width: 100%; 
                padding: 10px; 
                font-size: 16px; 
                border: 1px solid #b3d7c2; 
                border-radius: 5px; 
                background: #f9fff9; 
                color: #095d40;">

                            <option value="Lunch Break">Lunch Break</option>
                            <option value="Company Errand">Company Errand</option>
                            <option value="Time-Out">Time-Out</option>
                        </select>
                    </div>

                    <button class="proceed-btn" onclick="submitTimeout()" style="
            display: block; 
            width: 100%; 
            padding: 12px; 
            font-size: 18px; 
            font-weight: bold; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            margin-top: 20px;">
                        Submit
                    </button>
                </div>
            </div>

            <!-- QR Scan Error Modal -->
            <div id="qrErrorModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h2>QR Scan Error</h2>
                    <p style="color: red; font-size: 16px;"></p>
                    <button class="proceed-btn" onclick="closeModal('qrErrorModal')">Close</button>
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

            <script src="./js/script.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
            <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>