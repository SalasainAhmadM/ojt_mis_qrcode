<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch admin details from the database
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM admin WHERE admin_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $admin_id); // Bind parameters
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc(); // Fetch admin details
    } else {
        // Handle case where admin is not found
        $admin = [
            'admin_firstname' => 'Unknown',
            'admin_middle' => 'U',
            'admin_lastname' => 'User',
            'admin_email' => 'unknown@wmsu.edu.ph'
        ];
    }
    $stmt->close(); // Close the statement
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
    <title>Admin - Manage Profile</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./css/style.css">
    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/mobile.css">
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
            <img src="../uploads/admin/<?php echo !empty($admin['admin_image']) ? $admin['admin_image'] : 'user.png'; ?>"
                alt="logout Image" class="logout-img">
            <div style="margin-top: 10px;" class="profile-info">
                <span
                    class="profile_name"><?php echo $admin['admin_firstname'] . ' ' . $admin['admin_middle'] . '. ' . $admin['admin_lastname']; ?></span>
                <br />
                <span class="profile_email"><?php echo $admin['admin_email']; ?></span>
            </div>
        </div>
        <hr>
        <ul class="nav-links">
            <li>
                <a href="index.php">
                    <i class="fas fa-th-large"></i>
                    <span class="link_name">Dashboard</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="index.php">Dashboard</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="user-manage.php">
                        <i class="fa-solid fa-user"></i>
                        <span class="link_name">Manage Users</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="user-manage.php">User Management</a></li>
                    <li><a href="./users/adviser.php">Adviser Management</a></li>
                    <li><a href="./users/company.php">Company Management</a></li>
                    <li><a href="./users/student.php">Student Management</a></li>
                </ul>
            </li>
            <li>
                <a href="others.php">
                    <i class="fa-solid fa-ellipsis-h"></i>
                    <span class="link_name">Others</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="others.php">Others</a></li>
                </ul>
            </li>
            <li>
                <a href="calendar.php">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span class="link_name">Calendar</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="calendar.php">Calendar</a></li>
                </ul>
            </li>
            <li>
                <a href="feedback.php">
                    <i class="fa-solid fa-percent"></i>
                    <span class="link_name">Feedback</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="feedback.php">Feedback Management</a></li>
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
                    <label for="wmsu-id">Admin Full Name</label>
                    <div class="name-inputs">
                        <input class="firstname" type="text" id="admin-firstname" name="admin_firstname"
                            value="<?php echo $admin['admin_firstname']; ?>" required>
                        <input class="middle" type="text" id="admin-middle" name="admin_middle"
                            value="<?php echo $admin['admin_middle']; ?>">
                        <input class="lastname" type="text" id="admin-lastname" name="admin_lastname"
                            value="<?php echo $admin['admin_lastname']; ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <input type="hidden" id="admin-id" name="admin_id" value="<?php echo $admin['admin_id']; ?>">
                    <label for="wmsu-email">Admin Email</label>
                    <input type="text" id="admin-email" name="admin_email" value="<?php echo $admin['admin_email']; ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="admin_password" placeholder="Enter New password">
                </div>
                <div class="form-group">
                    <label for="cpassword">Confirm New Password</label>
                    <input type="password" id="cpassword" name="admin_cpassword" placeholder="Confirm New Password">
                </div>


            </div>
            <style>
                .image-preview {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin-top: 20px;
                    margin-bottom: 20px;
                }

                .image-preview img {
                    width: 200px;
                    height: 200px;
                    border-radius: 50%;
                    object-fit: cover;
                }
            </style>
            <!-- Right Side Form -->
            <div class="form-section">
                <div class="image-preview" id="image-preview">
                    <img id="preview-image"
                        src="../uploads/admin/<?php echo !empty($admin['admin_image']) ? $admin['admin_image'] : 'user.png'; ?>"
                        alt="Preview Image">
                </div>
                <div class="form-group">
                    <label for="admin-image">Admin Image</label>
                    <input type="file" id="admin-image" name="admin_image" accept="image/*">
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
    <!-- Password match modal -->
    <div id="TryAgainModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #8B0000">Password does not Match!</h2>
            <p>Try Again!</p>
            <button class="proceed-btn" onclick="closeModal('TryAgainModal')">Close</button>
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
        document.getElementById('admin-image').addEventListener('change', function (event) {
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
        // Show the modal if the session variable for password error is set
        <?php if (isset($_SESSION['password_error']) && $_SESSION['password_error']): ?>
            window.onload = function () {
                showModal('TryAgainModal'); // Show the error modal
                <?php unset($_SESSION['password_error']); ?> // Unset the session variable after showing the modal
            };
        <?php endif; ?>

    </script>
    <script src="./js/script.js"></script>
    <script src="../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>