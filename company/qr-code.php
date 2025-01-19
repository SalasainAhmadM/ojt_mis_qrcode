<?php
session_start();
require '../conn/connection.php';
date_default_timezone_set('Asia/Manila');
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
$current_date = date('Y-m-d');

// Fetch the holiday for the current date
$query = "SELECT * FROM holiday WHERE holiday_date = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("s", $current_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $holiday = $result->fetch_assoc();
    } else {
        $holiday = null;
    }
    $stmt->close();
}

// Fetch the schedule for the current date if there is no holiday
if (!$holiday) {
    $query = "SELECT * FROM schedule WHERE company_id = ? AND date = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("is", $company_id, $current_date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $schedule = $result->fetch_assoc();
        } else {
            $schedule = [
                'generated_qr_code' => '../img/qr-code-error.png',
                'time_in' => '',
                'time_out' => '',
                'day_type' => ''
            ];
        }
        $stmt->close();
    }
}
$isSuspended = isset($schedule['day_type']) && $schedule['day_type'] === 'Suspended';

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
    <title>Company - QR Code</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <!-- <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/mobile.css"> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

<body>
    <div class="header">
        <i class=""></i>
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
                <a href="qr-code.php" class="active">
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
                <div class="iocn-link">
                    <a href="attendance.php">
                        <i class="fa-regular fa-clock"></i>
                        <span class="link_name">Attendance</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="attendance.php">Attendance</a></li>
                    <li><a href="./intern/attendance.php">Monitoring</a></li>
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
    <style>
        #qr-code-img {
            width: 360px;
            height: 360px;
            margin-bottom: 0px;
        }

        .qr-time {
            color: #095d40;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 0;
        }

        .time-in-label,
        .time-out-label {
            font-size: 20px;
            color: #333;
            font-weight: bold;
        }
    </style>
    <section class="home-section">
        <div class="home-content">
            <i style="z-index: 100;" class="fas fa-bars bx-menu"></i>
        </div>
        </div>

        <div class="content-wrapper">
            <div class="header-box">
                <label style="color: #a6a6a6; margin-left: 10px;">QR Scanner</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <!-- Intern Time-In Details 
                    <div class="intern-timein-details">
                        <div class="intern-image">
                            <img src="../uploads/student/user.png" alt="Intern Image">
                        </div>
                        <div class="intern-details">
                            <h3><strong>Intern Name</strong></h3>
                            <p>WMSU ID: <strong></strong></p>
                            <p>Email: <strong></strong></p>
                            <p>Total OJT Hours: <span class="total-ojt-hrs"><strong></strong></span></p>
                        </div>
                    </div>-->

                    <!-- Time In Details 
                    <div class="time-in-details">
                        <div class="time-in-info">
                            <h3>Time In</h3>
                            <p>Time: <strong></strong></p>
                            <p>Date: <strong></strong></p>
                        </div>
                        <div class="clock-image">
                            <img src="../img/clock.png" alt="Clock Image" style="">
                        </div>
                    </div>-->
                    <!-- </div> -->
                    <!-- Right Box for Scanning QR Code -->
                    <!-- <div class="right-box-qr"> -->
                    <h2 style="text-align: center; margin-bottom: 0; color: #095d40">Good Day Interns!</h2>
                    <h3 style="text-align: center; margin-bottom: 0">Today's QR Code</h3>
                    <div class="qr-container">
                        <img src="<?php echo !empty($schedule['generated_qr_code']) ? $schedule['generated_qr_code'] : '../img/qr-code-error.png'; ?>"
                            alt="QR Code" id="qr-code-img" style="">
                        <p class="qr-time"></p>

                        <script>
                            function updateTime() {
                                const options = {
                                    timeZone: 'Asia/Manila',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    second: '2-digit',
                                    hour12: true
                                };

                                const now = new Date();
                                const formattedTime = now.toLocaleTimeString('en-US', options);

                                document.querySelector('.qr-time').textContent = formattedTime;
                            }

                            setInterval(updateTime, 1000);

                            updateTime();
                        </script>

                        <p class="qr-date"><?php echo date('F j, Y'); ?></p>

                        <div style="flex-direction: column; align-items: center;" class="time-container">
                            <?php
                            $isWeekend = date('N') >= 6; // 6 = Saturday, 7 = Sunday
                            if ($holiday): ?>
                                <p style="text-align: center;" class="holiday-label">
                                    <strong><?php echo $holiday['holiday_name']; ?></strong>
                                </p>
                            <?php elseif ($isWeekend): ?>
                                <p style="text-align: center; color: red" class="weekend-label">
                                    <strong><?php echo date('l'); ?>: No Duty!</strong>
                                </p>
                            <?php elseif ($isSuspended): ?>
                                <p style="text-align: center; color: orange" class="suspended-label">
                                    <strong>No Duty</strong>
                                </p>
                            <?php else:
                                $timeIn = date('g:ia', strtotime($schedule['time_in']));
                                $timeOut = date('g:ia', strtotime($schedule['time_out']));
                                ?>
                                <div class="time-label">
                                    <p class="time-in-label"><?php echo "Time-in: $timeIn"; ?></p>
                                    <p class="time-out-label"><?php echo "Time-out: $timeOut"; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>


                        <p class="day-type">
                            <strong id="day-type-text">
                                <?php
                                if ($holiday) {
                                    echo 'Holiday';
                                } elseif ($isWeekend) {
                                    echo 'Weekend';
                                } else {
                                    echo $schedule['day_type'];
                                }
                                ?>
                            </strong>
                        </p>
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
        window.addEventListener('DOMContentLoaded', () => {
            const today = new Date();

            // Format the date to Asia/Manila timezone
            const formatter = new Intl.DateTimeFormat('en-PH', {
                timeZone: 'Asia/Manila',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long',
            });

            const day = today.getUTCDay(); // Get day in UTC (0 = Sunday, 6 = Saturday)
            const isWeekend = day === 0 || day === 6;

            const formattedDate = formatter.format(today); // Convert to Manila timezone
            const qrDate = document.querySelector('.qr-date');
            const dayType = "<?php echo $holiday ? 'Holiday' : $schedule['day_type']; ?>";
            const dayTypeText = document.getElementById('day-type-text');
            const timePeriod = document.getElementById('time-period');
            const holidayModal = document.getElementById('holidayModal');
            const weekendModal = document.getElementById('weekendModal');
            const suspendedModal = document.getElementById('suspendedModal');

            // Set the formatted date to the QR date element
            if (qrDate) {
                qrDate.textContent = formattedDate;
            }

            if (isWeekend) {
                if (weekendModal) {
                    weekendModal.style.display = 'block';
                }
            } else if (dayType.trim() === 'Holiday') {
                qrDate.classList.add('holiday');
                dayTypeText.classList.add('holiday');
                if (timePeriod) timePeriod.style.display = 'none';

                if (holidayModal) {
                    holidayModal.style.display = 'block';
                }
            } else if (dayType.trim() === 'Suspended') {
                // Apply suspended styles
                qrDate.classList.add('suspended');
                dayTypeText.classList.add('suspended');

                if (suspendedModal) {
                    suspendedModal.style.display = 'block';
                }
            } else if (dayType.trim() === 'Regular') {
                if (timePeriod) timePeriod.style.display = '';
                dayTypeText.parentElement.style.display = 'none';
            } else if (dayType.trim() === '') {
                if (timePeriod) timePeriod.style.display = 'none';
                dayTypeText.parentElement.style.display = 'none';
            }
        });

        // Close modal function
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <!-- <script>
        window.addEventListener('DOMContentLoaded', () => {
            const today = new Date();
            const day = today.getDay(); // 0 = Sunday, 6 = Saturday
            const isWeekend = day === 0 || day === 6;

            const dayType = "<?php echo $holiday ? 'Holiday' : $schedule['day_type']; ?>";
            const qrDate = document.querySelector('.qr-date');
            const dayTypeText = document.getElementById('day-type-text');
            const timePeriod = document.getElementById('time-period');
            const holidayModal = document.getElementById('holidayModal');
            const weekendModal = document.getElementById('weekendModal');
            const suspendedModal = document.getElementById('suspendedModal');

            if (isWeekend) {
                if (weekendModal) {
                    weekendModal.style.display = 'block';
                }
            } else if (dayType.trim() === 'Holiday') {
                qrDate.classList.add('holiday');
                dayTypeText.classList.add('holiday');
                if (timePeriod) timePeriod.style.display = 'none';

                if (holidayModal) {
                    holidayModal.style.display = 'block';
                }
            } else if (dayType.trim() === 'Suspended') {
                // Apply suspended styles
                qrDate.classList.add('suspended');
                dayTypeText.classList.add('suspended');

                if (suspendedModal) {
                    suspendedModal.style.display = 'block';
                }
            } else if (dayType.trim() === 'Regular') {
                if (timePeriod) timePeriod.style.display = '';
                dayTypeText.parentElement.style.display = 'none';
            } else if (dayType.trim() === '') {
                if (timePeriod) timePeriod.style.display = 'none';
                dayTypeText.parentElement.style.display = 'none';
            }
        });

        // Close modal function
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }

    </script> -->
    <!-- Holiday Modal -->
    <div id="holidayModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/alert-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay></lottie-player>
            </div>
            <h2 style="color: #8B0000">It's a Holiday!</h2>
            <p><strong><?php echo date('F j, Y'); ?></strong></p>
            <?php if ($holiday): ?>
                <p style="color: #8B0000"><strong><?php echo htmlspecialchars($holiday['holiday_name']); ?></strong></p>
                <?php if (!empty($holiday['memo'])): ?>
                    <?php
                    $memo = $holiday['memo'];
                    $file_extension = strtolower(pathinfo($memo, PATHINFO_EXTENSION));
                    $memo_url = "../uploads/admin/memos/" . $memo;

                    // Determine the icon and action based on file type
                    if (in_array($file_extension, ['pdf'])): ?>
                        <p>
                            <i class="fa-solid fa-file-pdf" style="color: #8B0000; font-size: 24px;"></i>
                            <a style="text-decoration: none;" href="<?php echo $memo_url; ?>" download>Memorandum</a>
                        </p>
                    <?php elseif (in_array($file_extension, ['doc', 'docx'])): ?>
                        <p>
                            <i class="fa-solid fa-file-word" style="color: #0072C6; font-size: 24px;"></i>
                            <a style="text-decoration: none;" href="<?php echo $memo_url; ?>" download>Memorandum</a>
                        </p>
                    <?php elseif (in_array($file_extension, ['jpg', 'jpeg', 'png'])): ?>
                        <p>
                            <i class="fa-solid fa-file-image" style="color: #4CAF50; font-size: 24px;"></i>
                            <a style="text-decoration: none;" href="<?php echo $memo_url; ?>" target="_blank">Memorandum</a>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- <p>No memo available for this holiday.</p> -->
                <?php endif; ?>
            <?php else: ?>
                <p>No holiday scheduled for today.</p>
            <?php endif; ?>
            <button class="proceed-btn" onclick="closeModal('holidayModal')">Close</button>
        </div>
    </div>

    <!-- Suspended Modal -->
    <div id="suspendedModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/alert-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay></lottie-player>
            </div>
            <h2 style="color: #8B0000">Schedule Suspended!</h2>
            <p><strong><?php echo date('F j, Y'); ?></strong></p>
            <button class="proceed-btn" onclick="closeModal('suspendedModal')">Close</button>
        </div>
    </div>

    <!-- Weekend Modal -->
    <div id="weekendModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/alert-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay></lottie-player>
            </div>
            <h2 style="color: #8B0000">It's a Weekend!</h2>
            <p><strong><?php echo date('F j, Y'); ?></strong></p>
            <button class="proceed-btn" onclick="closeModal('weekendModal')">Close</button>
        </div>
    </div>

    <script src="./js/script.js"></script>
    <script src="../js/sy.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>