<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch company details along with full address from the database
$company_id = $_SESSION['user_id'];
$query = "
    SELECT company.*, CONCAT(address.address_barangay) AS full_address
    FROM company
    LEFT JOIN address ON company.company_address = address.address_id
    WHERE company.company_id = ?
";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $company_id); // Bind parameters
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    if ($result->num_rows > 0) {
        $company = $result->fetch_assoc(); // Fetch company details with address
    } else {
        // Handle case where company is not found
        $company = [
            'company_name' => 'Unknown',
            'company_email' => 'unknown@wmsu.edu.ph',
            'full_address' => 'Unknown, Unknown'
        ];
    }
    $stmt->close(); // Close the statement
}

// Fetch all barangays from the address table
$query = "SELECT * FROM address";
$barangays = [];
if ($result = $database->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $barangays[] = $row;
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
    <title>Company - Manage Company Profile</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <!-- <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/mobile.css"> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>

    </style>
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
                <a href="qr-code.php">
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
                    <label for="wmsu-id">Company Name</label>
                    <input type="text" id="company-name" name="company_name"
                        value="<?php echo $company['company_name']; ?>" required>
                </div>
                <div class="form-group-name">
                    <label for="wmsu-id">Representative Name</label>
                    <div class="name-inputs">
                        <input class="firstname" type="text" id="company-firstname" name="company_rep_firstname"
                            value="<?php echo $company['company_rep_firstname']; ?>" required>
                        <input class="middle" type="text" id="company-middle" name="company_rep_middle"
                            value="<?php echo $company['company_rep_middle']; ?>">
                        <input class="lastname" type="text" id="company-lastname" name="company_rep_lastname"
                            value="<?php echo $company['company_rep_lastname']; ?>" required>
                    </div>
                </div>
                <div class="form-group-name">
                    <label for="wmsu-id">Representative Position</label>
                    <input type="text" id="company-position" name="company_rep_position"
                        value="<?php echo $company['company_rep_position']; ?>" required>
                </div>
                <div class="form-group">
                    <input type="hidden" id="company-id" name="company_id"
                        value="<?php echo $company['company_id']; ?>">
                    <label for="wmsu-email">Company Email</label>
                    <input type="text" id="company-email" name="company_email"
                        value="<?php echo $company['company_email']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="text" id="company_contact" name="company_number"
                        value="<?php echo $company['company_number']; ?>" required maxlength="13"
                        oninput="limitInput(this)" required>
                </div>
                <script>
                    function limitInput(input) {
                        if (input.value.length > 13) {
                            input.value = input.value.slice(0, 13);
                        }
                    }
                </script>
                <!-- <div class="form-group">
                    <label for="address">Address</label>
                    <select id="address" name="address">
                        <option disabled>Select Barangay</option>
                        <?php foreach ($barangays as $barangay): ?>
                            <option
                                value="<?php echo $barangay['address_barangay'] . ', ' . $barangay['address_street']; ?>"
                                <?php if ($company['company_address'] == $barangay['address_barangay'])
                                    echo 'selected'; ?>>
                                <?php echo $barangay['address_barangay'] . ', ' . $barangay['address_street']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div> -->
                <div class="form-group">
                    <label for="address">Address</label>
                    <select id="address" name="address" required class="form-control">
                        <option disabled>Select Barangay</option><?php foreach ($barangays as $barangay): ?>
                            <option value="<?php echo $barangay['address_barangay']; ?>" <?php echo ($company['company_address'] == $barangay['address_barangay']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($barangay['address_barangay']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- Right Side Form -->
            <div class="form-section">


                <div class="image-preview" id="image-preview">
                    <img id="preview-image"
                        src="../uploads/company/<?php echo !empty($company['company_image']) ? $company['company_image'] : 'user.png'; ?>"
                        alt="Preview Image">
                </div>
                <div class="form-group">
                    <label for="company-image">Company Image</label>
                    <input type="file" id="company-image" name="company_image" accept="image/*">
                </div>
                <button type="submit" class="btn-confirm"><i style="margin-right: 4px;"
                        class="fa-solid fa-circle-check"></i>Confirm</button>
            </div>
            <div class="form-section">
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
        document.getElementById('company-image').addEventListener('change', function (event) {
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
    <script src="./js/script.js"></script>
    <script src="../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>