<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch student details along with full address and adviser's name
$student_id = $_SESSION['user_id'];
$query = "
    SELECT student.*, 
           adviser.adviser_firstname, 
           adviser.adviser_middle, 
           adviser.adviser_lastname, 
           CONCAT(address.address_barangay, ', ', street.name) AS full_address
    FROM student
    LEFT JOIN adviser ON student.adviser = adviser.adviser_id
    LEFT JOIN address ON student.student_address = address.address_id
    LEFT JOIN street ON student.street = street.street_id
    WHERE student.student_id = ?
";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $student_id); // Bind the student ID
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc(); // Fetch student details including address
    } else {
        // Handle case where student is not found
        $student = [
            'student_firstname' => 'Unknown',
            'student_middle' => 'U',
            'student_lastname' => 'User',
            'student_email' => 'unknown@wmsu.edu.ph',
            'full_address' => 'Unknown, Unknown'
        ];
    }
    $stmt->close(); // Close the statement
}
$qr_url = ($student['ojt_type'] === 'Project-Based') ? "qr-code_project_based.php" : "qr-code.php";

$query = "
    SELECT 
        ar.schedule_id, 
        ar.remark_type, 
        ar.remark, 
        ar.proof_image, 
        ar.status, 
        s.date AS schedule_date, 
        MIN(a.time_in) AS first_time_in, 
        MAX(a.time_out) AS last_time_out,
        COALESCE(SUM(a.ojt_hours), 0) AS total_hours
    FROM attendance_remarks ar
    LEFT JOIN schedule s ON ar.schedule_id = s.schedule_id
    LEFT JOIN attendance a ON ar.schedule_id = a.schedule_id 
        AND ar.student_id = a.student_id 
        AND DATE(a.time_in) = s.date  -- Match attendance by date
    WHERE ar.student_id = ?
    GROUP BY ar.schedule_id, s.date, ar.remark_type, ar.remark, ar.proof_image, ar.status
    ORDER BY s.date ASC
";

if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $student_id); // Bind the student ID
    $stmt->execute();
    $result = $stmt->get_result();

    $attendance_remarks = [];
    while ($row = $result->fetch_assoc()) {
        $attendance_remarks[] = $row; // Store each row in the array
    }

    $stmt->close();
} else {
    die("Error preparing statement: " . $database->error);
}



$showSuccessModal = isset($_SESSION['success']) ? $_SESSION['success'] : false;
if ($showSuccessModal) {
    unset($_SESSION['success']); // Clear the flag
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern - Settings</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <!-- <link rel="stylesheet" href="./css/style.css"> -->
    <!-- <link rel="stylesheet" href="./css/index.css"> -->
    <link rel="stylesheet" href="../css/mobile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>

    </style>
</head>
<style>
    /* For Mobile Screens */
    @media (max-width: 768px) {
        .bx-menu {
            display: block;
            /* Show the hamburger icon in mobile view */
        }

        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            /* Disables horizontal scroll */
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
            width: calc(110% - 60px);
            margin-left: -68px;
        }

        .whole-box {
            padding: 0px;
            padding-left: 10px;
            padding-right: 0px;
            margin-left: -68px;
            width: 120%;
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
        max-height: 600px;
        overflow-y: auto;
        /* border: 1px solid #ddd; */
    }

    .whole-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .whole-box thead {
        position: sticky;
        top: 0;
        /* background: #f9f9f9; */
        z-index: 1;
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
                <a href="<?php echo $qr_url; ?>">
                    <i class="fa-solid fa-qrcode"></i>
                    <span class="link_name">QR Scanner</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="<?php echo $qr_url; ?>">QR Scanner</a></li>
                </ul>
            </li>

            <li>
                <a href="dtr.php" class="active">
                    <i class="fa-solid fa-clipboard-question"></i>
                    <span class="link_name">Remarks</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="dtr.php">Remarks</a></li>
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
                <label style="color: #a6a6a6; margin-left: 10px;">Remarks</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <div class="header-group">
                        <h2>Remarks</h2>
                        <div class="button-container">
                            <button class="export-btn"
                                onclick="window.location.href='export_dtr.php?student_id=<?php echo $student_id; ?>';">
                                <i class="fa-solid fa-file-export"></i> Export DTR
                            </button>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Schedule Date</th>
                                <th>Remark Type</th>
                                <th>First Time-in</th>
                                <th>Last Time-out</th>
                                <th>Total Hours</th>
                                <th class="remark">Remark</th>
                                <th class="action">Proof Image</th>
                                <th>Status</th>
                                <th class="action">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($attendance_remarks)): ?>
                                <?php foreach ($attendance_remarks as $attendance): ?>
                                    <?php
                                    // Format first time-in and last time-out
                                    $first_time_in = isset($attendance['first_time_in']) ? date("g:i A", strtotime($attendance['first_time_in'])) : 'N/A';
                                    $last_time_out = isset($attendance['last_time_out']) ? date("g:i A", strtotime($attendance['last_time_out'])) : 'N/A';

                                    // Check if the remark type is "Absent"
                                    if ($attendance['remark_type'] === 'Absent') {
                                        $total_hours_formatted = "N/A";
                                    } else {
                                        // Calculate total hours in hours and minutes
                                        $total_hours_decimal = $attendance['total_hours'] ?? 0;
                                        $hours = floor($total_hours_decimal);
                                        $minutes = round(($total_hours_decimal - $hours) * 60);

                                        $total_hours_formatted = ($hours > 0 ? "{$hours} hr" . ($hours > 1 ? "s" : "") : "") .
                                            ($minutes > 0 ? " {$minutes} min" . ($minutes > 1 ? "s" : "") : "");
                                    }
                                    ?>
                                    <tr>
                                        <!-- Schedule Date -->
                                        <td><?php echo htmlspecialchars(date("m/d/Y", strtotime($attendance['schedule_date']))); ?>
                                        </td>

                                        <!-- Remark Type with Color Coding -->
                                        <td style="
                    <?php
                    if ($attendance['remark_type'] === 'Absent') {
                        echo 'color: red;';
                    } elseif ($attendance['remark_type'] === 'Late') {
                        echo 'color: yellow;';
                    } elseif ($attendance['remark_type'] === 'Forgot Time-out') {
                        echo 'color: gray;';
                    }
                    ?>
                ">
                                            <?php echo htmlspecialchars($attendance['remark_type']); ?>
                                        </td>

                                        <!-- First Time-In -->
                                        <td><?php echo htmlspecialchars($first_time_in); ?></td>

                                        <!-- Last Time-Out -->
                                        <td><?php echo htmlspecialchars($last_time_out); ?></td>

                                        <!-- Total Hours -->
                                        <td><?php echo htmlspecialchars($total_hours_formatted); ?></td>

                                        <!-- Remark -->
                                        <td class="remark"
                                            title="<?php echo htmlspecialchars($attendance['remark'] ?? 'N/A'); ?>">
                                            <?php echo htmlspecialchars($attendance['remark'] ?? 'N/A'); ?>
                                        </td>

                                        <!-- Proof Image -->
                                        <td class="action">
                                            <button class="action-icon view-btn"
                                                onclick="viewImage('<?php echo htmlspecialchars($attendance['proof_image'] ?? '../img/empty.png'); ?>')">
                                                <i class="fa-solid fa-image"></i>
                                            </button>
                                        </td>

                                        <!-- Status -->
                                        <td><?php echo htmlspecialchars($attendance['status']); ?></td>

                                        <!-- Edit Action -->
                                        <td class="action">
                                            <button class="action-icon edit-btn"
                                                onclick="openEditModal('<?php echo $attendance['schedule_id']; ?>', '<?php echo $attendance['remark_type']; ?>', '<?php echo $attendance['remark']; ?>')">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- No Records Found -->
                                <tr>
                                    <td colspan="9">No attendance remarks found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>


                </div>
            </div>
        </div>
    </section>

    <!-- Edit Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div style=" margin: 5% auto;" class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Attendance Remark</h2>

            <form action="edit_attendance_remark.php" method="POST">
                <input type="hidden" id="editScheduleId" name="schedule_id">
                <input type="hidden" id="editStudentId" name="student_id" value="<?php echo $student_id; ?>">

                <label for="editRemarkType">Remark Type</label>
                <input type="text" id="editRemarkType" name="remark_type" readonly required
                    style="background-color: #f0f0f0; border: 1px solid #ccc; cursor: not-allowed; width: 30%; text-align: center;">

                <label for="editRemark">Remark</label>
                <textarea id="editRemark" name="remark" required></textarea>

                <button type="submit" class="modal-btn">Save Changes</button>
            </form>
        </div>
    </div>


    <script>

        function viewImage(imagePath) {
            const modal = document.createElement('div');
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            modal.style.zIndex = '1000';
            modal.style.overflow = 'auto';

            const img = document.createElement('img');
            img.src = imagePath;
            img.style.maxWidth = '90%';
            img.style.maxHeight = '90%';
            img.style.margin = 'auto';
            modal.appendChild(img);

            // Close the modal on click
            modal.onclick = () => document.body.removeChild(modal);

            document.body.appendChild(modal);
        }

        // Image Modal
        function openImageModal(imageSrc) {
            const modal = document.getElementById("imageModal");
            const modalImage = document.getElementById("modalImage");
            modalImage.src = imageSrc;
            modal.style.display = "block";
        }

        function closeImageModal() {
            const modal = document.getElementById("imageModal");
            modal.style.display = "none";
        }

        // Edit Modal
        function openEditModal(scheduleId, remarkType, remark) {
            const modal = document.getElementById("editModal");
            document.getElementById("editScheduleId").value = scheduleId;
            document.getElementById("editRemarkType").value = remarkType;
            document.getElementById("editRemark").value = remark;
            modal.style.display = "block";
        }

        function closeEditModal() {
            const modal = document.getElementById("editModal");
            modal.style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const imageModal = document.getElementById("imageModal");
            const editModal = document.getElementById("editModal");
            if (event.target === imageModal) {
                closeImageModal();
            } else if (event.target === editModal) {
                closeEditModal();
            }
        }
    </script>
    <?php if ($showSuccessModal): ?>
        <div id="successModal" class="modal" style="display: flex;">
            <div class="modal-content">
                <!-- Lottie Animation -->
                <div style="display: flex; justify-content: center; align-items: center;">
                    <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                        style="width: 150px; height: 150px;" loop autoplay>
                    </lottie-player>
                </div>
                <h2>Attendance Remark Updated Successfully!</h2>
                <p>You successfully updated your remark, <span style="color: #095d40; font-size: 20px">
                        <?php echo $_SESSION['full_name']; ?>!</span></p>
                <button class="proceed-btn" onclick="closeModal('successModal')">Close</button>
            </div>
        </div>
    <?php endif; ?>
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

    </script>

    <script src="./js/script.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>