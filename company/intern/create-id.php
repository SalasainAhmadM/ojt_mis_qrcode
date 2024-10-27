<?php
session_start();
require '../../conn/connection.php';

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
// Fetch students under the current company
$student_query = "SELECT * FROM student WHERE company = ?";
$students = [];
if ($stmt = $database->prepare($student_query)) {
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $student_result = $stmt->get_result();

    if ($student_result->num_rows > 0) {
        while ($row = $student_result->fetch_assoc()) {
            $students[] = $row; // Add each student to the array
        }
    }
    $stmt->close();
}
// Fetch students and their course section names
$student_query = "SELECT student.*, course_sections.course_section_name 
                  FROM student 
                  LEFT JOIN course_sections ON student.course_section = course_sections.id 
                  WHERE student.company = ?";
$students = [];
if ($stmt = $database->prepare($student_query)) {
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $student_result = $stmt->get_result();

    if ($student_result->num_rows > 0) {
        while ($row = $student_result->fetch_assoc()) {
            $students[] = $row; // Add each student to the array
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company - Create ID</title>
    <link rel="icon" href="../../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>
<style>

</style>

<body>
    <div class="header">
        <i class="fas fa-school"></i>
        <div class="school-name">S.Y. 2024-2025 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span
                style="color: #095d40;">|</span>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
            <img src="../../img/ccs.png">
        </div>
    </div>
    <div class="sidebar close">
        <div class="profile-details">
            <img src="../../uploads/company/<?php echo !empty($company['company_image']) ? $company['company_image'] : 'user.png'; ?>"
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
                <a href="../index.php">
                    <i class="fa-solid fa-house"></i>
                    <span class="link_name">Home</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../index.php">Home</a></li>
                </ul>
            </li>
            <li>
                <a href="../qr-code.php">
                    <i class="fa-solid fa-qrcode"></i>
                    <span class="link_name">QR Scanner</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../qr-code.php">QR Scanner</a></li>
                </ul>
            </li>
            <li>
                <div style="background-color: #07432e;" class="iocn-link" class="active">
                    <a href="../intern.php">
                        <i class="fa-solid fa-user"></i>
                        <span class="link_name">Interns</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="../intern.php">Interns</a></li>
                    <li><a href="masterlist.php">Masterlist</a></li>
                    <li><a href="create-qr.php">Create QR</a></li>
                    <li><a href="create-id.php">Create ID</a></li>
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
                <a href="../feedback.php">
                    <i class="fa-regular fa-star"></i>
                    <span class="link_name">Feedback</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../feedback.php">Feedback</a></li>
                </ul>
            </li>

            <li>
                <a href="../attendance.php">
                    <i class="fa-regular fa-clock"></i>
                    <span class="link_name">Attendance</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../attendance.php">Attendance</a></li>
                </ul>
            </li>
            <li>
                <a href="../calendar.php">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span class="link_name">Schedule</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../calendar.php">Manage Schedule</a></li>
                </ul>
            </li>
            <li>
                <a href="../setting.php">
                    <i class="fas fa-cog"></i>
                    <span class="link_name">Manage Profile</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../setting.php">Manage Profile</a></li>
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
        <style>
            .header-box-id {
                width: 100%;
                display: flex;
                justify-content: space-between;
                width: calc(100% - 60px);
                margin-left: 20px;
            }

            .left-header-box {
                width: 70%;
                margin-right: 5px;
                padding: 10px;
                padding-left: 20px;
                border: 1px solid #ccc;
                border-radius: 5px;
                background-color: #fff;
            }

            .right-header-box {
                width: 30%;
                display: flex;
                justify-content: flex-end;
                padding: 10px;
                padding-left: 20px;
                border: 1px solid #ccc;
                border-radius: 5px;
                background-color: #fff;
            }
        </style>
        <div class="content-wrapper">
            <div class="header-box-id">
                <div class="left-header-box">
                    <label style="color: #a6a6a6; margin-left: 5px;">Create ID</label>
                </div>
                <div class="right-header-box">
                    <!-- Add content for the right box here -->
                    <button id="downloadBtn">Download</button>
                </div>
            </div>

            <div class="main-box">

                <div class="left-box">
                    <h2>
                        Interns
                    </h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>ID Number</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="maxlength">
                                        <?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>
                                    </td>
                                    <td class="maxlength"><?php echo $student['wmsu_id']; ?></td>
                                    <td class="maxlength"><?php echo $student['student_email']; ?></td>
                                    <td class="maxlength"><?php echo $student['contact_number']; ?></td>
                                    <td class="action">
                                        <!-- ID Button -->
                                        <button class="action-id edit-btn"
                                            data-qr-code="<?php echo !empty($student['generated_qr_code']) ? $student['generated_qr_code'] : ''; ?>"
                                            data-student-name="<?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>"
                                            data-student-email="<?php echo $student['student_email']; ?>">
                                            <i class="fa-solid fa-id-card"></i>
                                        </button>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">There's no intern yet in this company.</td>
                            </tr>
                        <?php endif; ?>

                    </table>
                </div>
                <div class="right-box">
                    <div class="id-container" id="idCard">
                        <div class="top-left-container">
                            <img src="../../img/wmsu.png" alt="WMSU Logo" class="wmsu-logo">
                            <p class="university-text">Western Mindanao<br />State University</p>
                        </div>

                        <img src="../../uploads/student/bini_colet.png" alt="Student Photo" class="student-photo">

                        <div class="mid-container">
                            <h3>Staku B. Seville</h3>
                            <p>Student Intern</p>
                        </div>
                        <div class="bottom-container">
                            <p>ID Number</p>
                            <p class="value">2023-201745</p>
                            <p>Email</p>
                            <p class="value">sevillejastacey@gmail.com</p>
                            <p>Contact Number</p>
                            <p class="value">+639773046522</p>
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
                <lottie-player src="../../animation/logout-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #000">Are you sure you want to logout?</h2>
            <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                <button class="confirm-btn" onclick="logout_intern()">Confirm</button>
                <button class="cancel-btn" onclick="closeModal_intern('logoutModal')">Cancel</button>
            </div>
        </div>
    </div>
    <script>
        document.getElementById("downloadBtn").addEventListener("click", function () {
            domtoimage.toPng(document.getElementById('idCard'))
                .then(function (dataUrl) {
                    // Create a temporary <a> element to trigger download
                    const link = document.createElement('a');
                    link.href = dataUrl;
                    link.download = 'id_card.png'; // Specify file name
                    link.click(); // Trigger download
                })
                .catch(function (error) {
                    console.error('oops, something went wrong!', error);
                });
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <script src="../js/script.js"></script>
</body>

</html>