<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch student details from the database
$student_id = $_SESSION['user_id'];
$query = "SELECT * FROM student WHERE student_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $student_id); // Bind parameters
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc(); // Fetch student details
    } else {
        // Handle case where student is not found
        $student = [
            'student_firstname' => 'Unknown',
            'student_middle' => 'U',
            'student_lastname' => 'User',
            'student_email' => 'unknown@wmsu.edu.ph'
        ];
    }
    $stmt->close(); // Close the statement
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
                <div class="left-box-qr">
                    <!-- Intern Time-In Details -->
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

                    <!-- Time In Details -->
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
                </div>

                <!-- Right Box for Scanning QR Code-->
                <div class="right-box-qr">
                    <h2>Scan Your QR Code</h2>
                    <div id="qr-scanner">
                        <!-- Lottie Animation -->
                        <div id="lottie-animation" class="lottie-wrapper">
                            <lottie-player src="../animation/qr-095d40.json" background="transparent" speed="1"
                                class="qr-camera" loop autoplay></lottie-player>
                        </div>

                        <video id="video" autoplay hidden></video>
                        <canvas id="canvas" hidden></canvas>

                        <button id="start-scan" class="start-scan">Start Scan <i
                                class="fa-solid fa-camera"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
    <div id="qrsuccessTimeoutModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/clock-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay></lottie-player>
            </div>
            <h2>Time-out Successful!</h2>
            <p>Name: <span id="timeout-name" style="color: #095d40; font-size: 20px"></span></p>
            <p>Time-out</p>
            <h3 id="timeout-time"></h3>
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

        function processQRCode(qrData) {
            const [companyId, scannedDate] = qrData.split(' - ');
            const today = new Date().toISOString().split('T')[0];

            if (scannedDate !== today) {
                alert('Invalid QR Code! This QR code is not for today.');
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
                            // Show Time-in modal
                            openModal('qrsuccessTimeinModal');
                        } else if (data.event_type === 'Time-out') {
                            // Time-out logic
                            updateInternDetails(data);
                            updateTimeOutDetails(data);
                            document.querySelector("#qrsuccessTimeoutModal span").innerText = data.student_name;
                            document.querySelector("#qrsuccessTimeoutModal h3").innerText = data.time_out;
                            document.querySelector("#ojt-hours").innerText = data.ojt_hours;
                            // Show Time-out modal
                            openModal('qrsuccessTimeoutModal');
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error processing QR code:", error);
                });
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
    </script>



    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>