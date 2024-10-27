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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company - QR Code</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/mobile.css">
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
                <a href="qr-code2.php" class="active">
                    <i class="fa-solid fa-qrcode"></i>
                    <span class="link_name">QR Scanner</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="qr-code2.php">QR Scanner</a></li>
                </ul>
            </li>
            <li>
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
                <a href="attendance.php">
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
            <i class="fas fa-bars bx-menu"></i>
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
                            <img src="../uploads/student/user.png" alt="Intern Image">
                        </div>
                        <div class="intern-details">
                            <h3><strong>Intern Name</strong></h3>
                            <p>WMSU ID: <strong></strong></p>
                            <!-- <p>Course & Section: <strong></strong></p> -->
                            <p>Email: <strong></strong></p>
                            <!-- <p>Contact: <strong></strong></p>
                            <p>Batch Year: <strong></strong></p>
                            <p>Department: <strong></strong></p> -->
                            <!-- <p>Company: <strong></strong></p>
                            <p>Adviser: <strong></strong></p>
                            <p>Barangay: <strong></strong></p>
                            <p>Street: <strong></strong></p> -->
                            <p>Total OJT Hours: <span class="total-ojt-hrs"><strong></strong></span></p>
                        </div>
                    </div>

                    <!-- Time In Details -->
                    <div class="time-in-details">
                        <div class="time-in-info">
                            <h3>Time In</h3>
                            <p>Time: <strong></strong></p>
                            <p>Date: <strong></strong></p>
                        </div>
                        <div class="clock-image">
                            <img src="../img/clock.png" alt="Clock Image" style="width: 350px; height: 350px;">
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
                                style="width: 300px; height: 300px;" loop autoplay>
                            </lottie-player>
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
            <!-- Lottie Animation for success -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/clock-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Time-in Successful!</h2>
            <p>Name: <span style="color: #095d40; font-size: 20px"></span>
            </p>
            <p>Time-in</p>
            <h3></h3>
            <button class="proceed-btn" onclick="closeModal('qrsuccessTimeinModal')">Close</button>
        </div>
    </div>
    <!-- QR Scan Time-out Modal -->
    <div id="qrsuccessTimeoutModal" class="modal" style="display: none;">
        <div class="modal-content">
            <!-- Lottie Animation for time-out success -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/clock-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Time-out Successful!</h2>
            <p>Name: <span style="color: #095d40; font-size: 20px"></span></p>
            <p>Time-out</p>
            <h3></h3>
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
            // Hide the Lottie animation
            document.getElementById("lottie-animation").style.display = 'none';

            // Show the video for QR scanning
            const video = document.getElementById("video");
            video.hidden = false;
            video.style.display = 'block';

            // Start QR code scanner by accessing the camera
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
                                // QR code successfully scanned
                                document.getElementById("video").srcObject.getTracks().forEach(track => track.stop()); // Stop video
                                clearInterval(scanInterval); // Stop scanning

                                // Send QRData to server to process time-in
                                processQRCode(qrCode.data);
                            }
                        }, 500); // Scan every 500ms
                    });
                }).catch(function (err) {
                    console.error("Error accessing camera: ", err);
                    alert("Could not access camera. Please ensure you have allowed camera access in your browser settings.");
                });
            } else {
                alert("Camera not supported in this browser.");
            }
        }

        function processQRCode(qrData) {
            fetch('timein.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ qrData: qrData })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.event_type === 'Time-in') {
                            // Time-in logic
                            document.querySelector(".intern-image img").src = data.student_image ?
                                "../uploads/student/" + data.student_image :
                                "../uploads/student/user.png";
                            document.querySelector(".intern-details h3 strong").innerText = data.student_name;
                            document.querySelector(".intern-details p:nth-of-type(1) strong").innerText = data.wmsu_id;
                            // document.querySelector(".intern-details p:nth-of-type(2) strong").innerText = data.course_section;
                            document.querySelector(".intern-details p:nth-of-type(2) strong").innerText = data.email;
                            // document.querySelector(".intern-details p:nth-of-type(4) strong").innerText = data.contact;
                            // document.querySelector(".intern-details p:nth-of-type(5) strong").innerText = data.batch_year;
                            // document.querySelector(".intern-details p:nth-of-type(6) strong").innerText = data.department;
                            // document.querySelector(".intern-details p:nth-of-type(7) strong").innerText = data.company;
                            // document.querySelector(".intern-details p:nth-of-type(8) strong").innerText = data.adviser;
                            // document.querySelector(".intern-details p:nth-of-type(9) strong").innerText = data.barangay;
                            // document.querySelector(".intern-details p:nth-of-type(10) strong").innerText = data.street;
                            document.querySelector(".intern-details p:nth-of-type(3) strong").innerText = data.total_ojt_hours;

                            // Update time-in details
                            document.querySelector(".time-in-details p:nth-of-type(1) strong").innerText = data.time_in;
                            document.querySelector(".time-in-details p:nth-of-type(2) strong").innerText = data.date_in;
                            document.querySelector(".time-in-details h3").innerText = data.event_type;

                            document.querySelector("#qrsuccessTimeinModal span").innerText = data.student_name;
                            document.querySelector("#qrsuccessTimeinModal h3").innerText = data.time_in;

                            // Show Time-in modal
                            openModal('qrsuccessTimeinModal');
                        } else if (data.event_type === 'Time-out') {
                            // Time-out logic
                            document.querySelector(".intern-image img").src = data.student_image ?
                                "../uploads/student/" + data.student_image :
                                "../uploads/student/user.png";
                            document.querySelector(".intern-details h3 strong").innerText = data.student_name;
                            document.querySelector(".intern-details p:nth-of-type(1) strong").innerText = data.wmsu_id;
                            // document.querySelector(".intern-details p:nth-of-type(2) strong").innerText = data.course_section;
                            document.querySelector(".intern-details p:nth-of-type(2) strong").innerText = data.email;
                            // document.querySelector(".intern-details p:nth-of-type(4) strong").innerText = data.contact;
                            // document.querySelector(".intern-details p:nth-of-type(5) strong").innerText = data.batch_year;
                            // document.querySelector(".intern-details p:nth-of-type(6) strong").innerText = data.department;
                            // document.querySelector(".intern-details p:nth-of-type(7) strong").innerText = data.company;
                            // document.querySelector(".intern-details p:nth-of-type(8) strong").innerText = data.adviser;
                            // document.querySelector(".intern-details p:nth-of-type(9) strong").innerText = data.barangay;
                            // document.querySelector(".intern-details p:nth-of-type(10) strong").innerText = data.street;
                            document.querySelector(".intern-details p:nth-of-type(3) strong").innerText = data.total_ojt_hours;

                            // Update time-out details
                            document.querySelector(".time-in-details p:nth-of-type(1) strong").innerText = data.time_out;
                            document.querySelector(".time-in-details p:nth-of-type(2) strong").innerText = data.date_in;
                            document.querySelector(".time-in-details h3").innerText = data.event_type;

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

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>


    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>