<?php
session_start();
require '../conn/connection.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

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

// $current_time = date('H:i:s');
// $isLate = false;

// if (isset($schedule) && !$todayIsHoliday) {  // Check if it's not a holiday and not suspended
//     // Check if there's already a 'Late' remark for the student and schedule
//     $remark_query = "SELECT * FROM attendance_remarks WHERE student_id = ? AND schedule_id = ? AND remark_type = 'Late'";
//     if ($remark_stmt = $database->prepare($remark_query)) {
//         $remark_stmt->bind_param("ii", $student_id, $schedule_id);
//         $remark_stmt->execute();
//         $remark_result = $remark_stmt->get_result();

//         // If no 'Late' remark exists, set $isLate based on time comparison
//         if ($remark_result->num_rows === 0 && $current_time > $schedule['time_in']) {
//             $isLate = true;
//         }

//         $remark_stmt->close();
//     }
// }

// // Function to check if a reason for absence is already submitted
// function hasSubmittedReason($database, $student_id, $schedule_id)
// {
//     $query = "SELECT * FROM attendance_remarks WHERE student_id = ? AND schedule_id = ? AND remark_type = 'Absent'";
//     if ($stmt = $database->prepare($query)) {
//         $stmt->bind_param("ii", $student_id, $schedule_id);
//         $stmt->execute();
//         $result = $stmt->get_result();
//         $hasReason = $result->num_rows > 0;
//         $stmt->close();
//         return $hasReason;
//     }
//     return false;
// }

// $absent_query = "
//     SELECT s.schedule_id, s.date 
//     FROM schedule s
//     LEFT JOIN attendance a ON s.schedule_id = a.schedule_id AND a.student_id = ?
//     LEFT JOIN holiday h ON s.date = h.holiday_date
//     WHERE s.company_id = ? 
//     AND s.date < CURDATE() 
//     AND DAYOFWEEK(s.date) NOT IN (1, 7) 
//     AND s.day_type != 'Suspended'
//     AND h.holiday_date IS NULL 
//     AND a.attendance_id IS NULL";

// $absentDates = [];
// $isAbsent = false;

// if ($absent_stmt = $database->prepare($absent_query)) {
//     $absent_stmt->bind_param("ii", $student_id, $company_id);
//     $absent_stmt->execute();
//     $absent_result = $absent_stmt->get_result();

//     // Fetch all dates where the student was absent, along with schedule_id
//     while ($row = $absent_result->fetch_assoc()) {
//         if (!hasSubmittedReason($database, $student_id, $row['schedule_id'])) {
//             $absentDates[] = [
//                 'schedule_id' => $row['schedule_id'],
//                 'date' => $row['date']
//             ];
//         }
//     }

//     $isAbsent = count($absentDates) > 0;
//     $absent_stmt->close();
// }
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
                <a href="qr-code_project_based.php" class="active">
                    <i class="fa-solid fa-qrcode"></i>
                    <span class="link_name">QR Scanner</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="qr-code_project_based.php">QR Scanner</a></li>
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
            const today = new Date().toISOString().split('T')[0];

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
    </script>

    <!-- QR Scan Error Modal -->
    <div id="qrErrorModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>QR Scan Error</h2>
            <p style="color: red; font-size: 16px;"></p>
            <button class="proceed-btn" onclick="closeModal('qrErrorModal')">Close</button>
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
            <h3 style="font-size: 20px" id="timein-time"></h3>
            <button class="proceed-btn" onclick="closeModal('qrsuccessTimeinModal')">Close</button>
        </div>
    </div>

    <!-- QR Scan Time-out Modal -->
    <div id="qrsuccessTimeoutModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/clock-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay></lottie-player>
            </div>
            <h2>Time-out Successful!</h2>
            <p>Name: <span id="timeout-name" style="color: #095d40; font-size: 20px"></span></p>
            <p>Time-out</p>
            <h3 style="font-size: 20px" id="timeout-time"></h3>
            <p>OJT Hours: <strong id="ojt-hours"></strong></p>
            <button class="proceed-btn" onclick="closeModal('qrsuccessTimeoutModal')">Close</button>
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