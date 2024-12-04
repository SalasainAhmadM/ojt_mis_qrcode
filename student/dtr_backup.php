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

                            <button class="export-btn" id="openJournalModalBtn">
                                <i class="fa-solid fa-file-export"></i> Export
                            </button>

                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Schedule ID</th>
                                <th>Remark Type</th>
                                <th>Time-in</th>
                                <th>Time-out</th>
                                <th>Remark</th>
                                <th class="action">Proof Image</th>
                                <th>Status</th>
                                <th class="action">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>202</td>
                                <td>Late</td>
                                <td>8:15 am</td>
                                <td>5:00 pm</td>
                                <td>Traffic Jam</td>
                                <td class="action">
                                    <button class="action-icon view-btn" onclick="openImageModal('../img/empty.png')">
                                        <i class="fa-solid fa-image"></i>
                                    </button>
                                </td>
                                <td>Pending</td>
                                <td class="action">
                                    <button class="action-icon edit-btn"
                                        data-student-id="<?php echo $journal['student_id']; ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                </td>
                            </tr>
                            <tr>
                                <td>203</td>
                                <td>Absent</td>
                                <td>N/A</td>
                                <td>N/A</td>
                                <td>Headache and Accident</td>
                                <td class="action">
                                    <button class="action-icon view-btn" onclick="openImageModal('../img/empty.png')">
                                        <i class="fa-solid fa-image"></i>
                                    </button>
                                </td>
                                <td>Pending</td>
                                <td class="action">
                                    <button class="action-icon edit-btn"
                                        data-student-id="<?php echo $journal['student_id']; ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                </td>
                            </tr>
                            <tr>
                                <td>203</td>
                                <td>Forgot Time-out</td>
                                <td>7:50 am</td>
                                <td>4:00 pm</td>
                                <td>Rush Hour</td>
                                <td class="action">
                                    <button class="action-icon view-btn" onclick="openImageModal('../img/empty.png')">
                                        <i class="fa-solid fa-image"></i>
                                    </button>
                                </td>
                                <td>Pending</td>
                                <td class="action">
                                    <button class="action-icon edit-btn"
                                        data-student-id="<?php echo $journal['student_id']; ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Modal -->
                    <div id="imageModal" class="modal" style="display: none;">
                        <div class="modal-content">
                            <span class="close" onclick="closeImageModal()">&times;</span>
                            <img id="modalImage" src="" alt="Proof Image">
                        </div>
                    </div>



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
    <script>

    </script>

    <script src="./js/script.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>