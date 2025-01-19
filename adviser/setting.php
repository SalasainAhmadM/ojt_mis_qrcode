<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch adviser details from the database
$adviser_id = $_SESSION['user_id'];
$query = "SELECT * FROM adviser WHERE adviser_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $adviser_id); // Bind parameters
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    if ($result->num_rows > 0) {
        $adviser = $result->fetch_assoc(); // Fetch adviser details
    } else {
        // Handle case where adviser is not found
        $adviser = [
            'adviser_firstname' => 'Unknown',
            'adviser_middle' => 'U',
            'adviser_lastname' => 'User',
            'adviser_email' => 'unknown@wmsu.edu.ph'
        ];
    }
    $stmt->close(); // Close the statement
}
// fetch departments
$department_query = "SELECT department_id, department_name FROM departments";
$departments_result = $database->query($department_query);
$departments = [];
if ($departments_result->num_rows > 0) {
    while ($row = $departments_result->fetch_assoc()) {
        $departments[] = $row;
    }
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
    <title>Adviser - Manage Profile</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <!-- <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/mobile.css">
    <link rel="stylesheet" href="./css/style.css"> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

<body>
    <div class="header">
        <i class="fas fa-school"></i>
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
            <img src="../uploads/adviser/<?php echo !empty($adviser['adviser_image']) ? $adviser['adviser_image'] : 'user.png'; ?>"
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
                <a href="index.php">
                    <i class="fa-solid fa-house"></i>
                    <span class="link_name">Home</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="index.php">Home</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="interns.php">
                        <i class="fa-solid fa-user"></i>
                        <span class="link_name">Interns</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="interns.php">Manage Interns</a></li>
                    <!-- <li><a href="./intern/intern-profile.php">Student Profile</a></li> -->
                    <li><a href="./intern/intern-reports.php">Intern Reports</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="company.php">
                        <i class="fa-regular fa-building"></i>
                        <span class="link_name">Company</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="company.php">Manage Company</a></li>
                    <li><a href="./company/company-intern.php">Company Interns</a></li>
                    <!--   <li><a href="./company/company-feedback.php">Company List</a></li> -->
                    <li><a href="./company/company-intern-feedback.php">Intern Feedback</a></li>
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
                    <li><a href="./intern/attendance-intern.php">Intern Attendance</a></li>
                    <li><a href="./intern/attendance-monitor.php">Monitoring</a></li>
                    <li><a href="./intern/intern_hours.php">Intern Total Hours</a></li>
                </ul>
            </li>
            <li>
                <a href="announcement.php">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span class="link_name">Announcement</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="announcement.php">Announcement</a></li>
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
                <a href="setting.php" class="active">
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

        <form style="" class="form-container">
            <div style="padding: 10px; margin-left: 20px;" class="form-section">
                <label style="color: #a6a6a6">Manage Profile</label>
            </div>
        </form>
        <form class="form-container" action="settings.php" method="POST" enctype="multipart/form-data">
            <!-- Left Side Form -->
            <div class="form-section">
                <div class="form-group-name">
                    <label for="wmsu-id">Full Name</label>
                    <div class="name-inputs">
                        <input class="firstname" type="text" id="adviser-firstname" name="adviser_firstname"
                            value="<?php echo $adviser['adviser_firstname']; ?>" required>
                        <input class="middle" type="text" id="adviser-middle" name="adviser_middle"
                            value="<?php echo $adviser['adviser_middle']; ?>">
                        <input class="lastname" type="text" id="adviser-lastname" name="adviser_lastname"
                            value="<?php echo $adviser['adviser_lastname']; ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <input type="hidden" id="adviser-id" name="adviser_id"
                        value="<?php echo $adviser['adviser_id']; ?>">
                    <label for="wmsu-email">Adviser Email</label>
                    <input type="text" id="adviser-email" name="adviser_email"
                        value="<?php echo $adviser['adviser_email']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="text" id="adviser_contact" name="adviser_number"
                        value="<?php echo $adviser['adviser_number']; ?>" required maxlength="13"
                        oninput="limitInput(this)" required>
                </div>
                <script>
                    function limitInput(input) {
                        if (input.value.length > 13) {
                            input.value = input.value.slice(0, 13);
                        }
                    }
                </script>

                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department">
                        <option disabled>Select Department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['department_name']; ?>" <?php if ($adviser['department'] == $department['department_name'])
                                   echo 'selected'; ?>>
                                <?php echo $department['department_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


            </div>

            <!-- Right Side Form -->
            <div class="form-section">
                <div class="image-preview" id="image-preview">
                    <img id="preview-image"
                        src="../uploads/adviser/<?php echo !empty($adviser['adviser_image']) ? $adviser['adviser_image'] : 'user.png'; ?>"
                        alt="Preview Image">
                </div>
                <div class="form-group">
                    <!-- <label for="adviser-image">Adviser Image</label> -->
                    <input type="file" id="adviser-image" name="adviser_image" accept="image/*">
                </div>
                <button type="submit" class="btn-confirm"><i style="margin-right: 4px;"
                        class="fa-solid fa-circle-check"></i>Confirm</button>
            </div>
            <div style="width: 30%" class="form-section">

            </div>
        </form>

    </section>
    <!-- Success Modal for Manage Profile Update -->
    <div id="settingsSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Profile Updated Successfully!</h2>
            <p>You have updated your profile, <span
                    style="color: #095d40; font-size: 20px;"><?php echo $_SESSION['full_name']; ?>!</span></p>
            <button class="proceed-btn" onclick="closeModal('settingsSuccessModal')">Close</button>
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
        document.getElementById('adviser-image').addEventListener('change', function (event) {
            const file = event.target.files[0]; // Get the selected file
            const previewImage = document.getElementById('preview-image'); // Get the image element

            if (file) {
                const reader = new FileReader(); // Create a FileReader to read the file

                reader.onload = function (e) {
                    previewImage.src = e.target.result; // Set the image source to the file's result
                };

                reader.readAsDataURL(file); // Read the file as a data URL
            } else {
                previewImage.src = '../img/user.png'; // Reset to default if no file selected
            }
        });

        function showModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Show the modal if the session variable is set
        <?php if (isset($_SESSION['settings_success']) && $_SESSION['settings_success']): ?>
            window.onload = function () {
                showModal('settingsSuccessModal');
                <?php unset($_SESSION['settings_success']); ?>
            };
        <?php endif; ?>
    </script>
    <script src="./js/scripts.js"></script>
    <script src="../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>