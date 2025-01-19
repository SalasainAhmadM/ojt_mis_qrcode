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
           address.address_barangay,
           street.name
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

// Fetch all barangays from the address table
$query = "SELECT * FROM address";
$barangays = [];
if ($result = $database->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $barangays[] = $row;
    }
}

// Fetch all barangays from the street table
$query = "SELECT * FROM street";
$streets = [];
if ($result = $database->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $streets[] = $row;
    }
}


// Fetch companies from the database
$company_query = "SELECT company_id, company_name FROM company";
$companies = [];
if ($company_stmt = $database->prepare($company_query)) {
    $company_stmt->execute();
    $company_result = $company_stmt->get_result();
    while ($row = $company_result->fetch_assoc()) {
        $companies[] = $row;
    }
    $company_stmt->close();
}

// Fetch advisers from the database
$adviser_query = "SELECT adviser_id, adviser_firstname, adviser_middle, adviser_lastname FROM adviser";
$advisers = [];
if ($adviser_stmt = $database->prepare($adviser_query)) {
    $adviser_stmt->execute();
    $adviser_result = $adviser_stmt->get_result();
    while ($row = $adviser_result->fetch_assoc()) {
        $advisers[] = $row;
    }
    $adviser_stmt->close();
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

// Query to fetch all course sections
$course_section_query = "SELECT id, course_section_name FROM course_sections";
$course_sections_result = $database->query($course_section_query);
$course_sections = [];
if ($course_sections_result->num_rows > 0) {
    while ($row = $course_sections_result->fetch_assoc()) {
        $course_sections[] = $row;
    }
}
$qr_url = ($student['ojt_type'] === 'Project-Based') ? "qr-code_project_based.php" : "qr-code.php";

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

        .left-box-qr,
        .right-box-qr {
            margin-left: -68px;
        }

        .form-container {
            margin-left: -68px;
            width: 95%;
        }

        .left-box,
        .right-box,
        .intern-company {
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
                        <input class="firstname" type="text" id="student-firstname" name="student_firstname"
                            value="<?php echo $student['student_firstname']; ?>" required>
                        <input class="middle" type="text" id="student-middle" name="student_middle"
                            value="<?php echo $student['student_middle']; ?>">
                        <input class="lastname" type="text" id="student-lastname" name="student_lastname"
                            value="<?php echo $student['student_lastname']; ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <input type="hidden" id="student-id" name="student_id"
                        value="<?php echo $student['student_id']; ?>">
                    <label for="wmsu-email">Wmsu Email</label>
                    <input type="text" id="student-email" name="student_email"
                        value="<?php echo $student['student_email']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="wmsu-id">School ID</label>
                    <input type="text" id="wmsu-id" name="wmsu_id" value="<?php echo $student['wmsu_id']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="text" id="contact" name="contact_number"
                        value="<?php echo $student['contact_number']; ?>" required maxlength="13"
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
                    <label for="course_section">Section</label>
                    <select id="course_section" name="course_section" onchange="fetchAdviser()" required>
                        <option disabled selected>Select Section</option>
                        <?php foreach ($course_sections as $course_section): ?>
                            <option value="<?php echo $course_section['id']; ?>" <?php if ($student['course_section'] == $course_section['id'])
                                   echo 'selected'; ?>>
                                <?php echo $course_section['course_section_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="adviser">Adviser</label>
                    <input type="text" id="adviser" name="adviser"
                        value="<?php echo $student['adviser_firstname'] . ' ' . $student['adviser_middle'] . '. ' . $student['adviser_lastname']; ?>"
                        readonly required>
                    <input type="hidden" id="adviser_id" name="adviser_id" value="<?php echo $student['adviser']; ?>">
                </div>


            </div>


            <div class="form-section">
                <div class="form-group">
                    <label for="batch-year">School Year</label>
                    <select id="batch-year" name="batch_year" required>
                        <option disabled>Select Batch Year</option>
                        <?php
                        $currentYear = date('Y');
                        $startYear = $currentYear - 2;
                        $endYear = $currentYear + 3;

                        for ($year = $startYear; $year <= $endYear; $year++) {
                            $batchYear = $year . '-' . ($year + 1);

                            if ($student['batch_year'] == $batchYear) {
                                echo "<option value=\"$batchYear\" selected>$batchYear</option>";
                                continue;
                            }

                            echo "<option value=\"$batchYear\">$batchYear</option>";
                        }
                        ?>
                    </select>
                </div>


                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department" required>
                        <option disabled>Select Department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['department_id']; ?>" <?php if ($student['department'] == $department['department_id'])
                                   echo 'selected'; ?>>
                                <?php echo $department['department_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="company">OJT Company</label>
                    <input type="text" id="company_name" name="company_name" value="<?php
                    $company_map = array_column($companies, 'company_name', 'company_id');
                    echo isset($company_map[$student['company']]) && !empty($student['company'])
                        ? $company_map[$student['company']]
                        : 'No Company Yet';
                    ?>" readonly>
                    <input type="hidden" id="company_id" name="company" value="<?php echo $student['company']; ?>" />
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <select id="address" name="address" required class="form-control">
                        <option disabled>Select Barangay</option>
                        <?php foreach ($barangays as $barangay): ?>
                            <option value="<?php echo $barangay['address_id']; ?>" <?php echo ($student['student_address'] == $barangay['address_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($barangay['address_barangay']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="street">Street</label>
                    <select id="street" name="street" required class="form-control">
                        <option disabled>Select Street</option><?php foreach ($streets as $street): ?>
                            <option value="<?php echo $street['street_id']; ?>" <?php echo ($student['street'] == $street['street_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($street['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="wmsu-id">OJT Type</label>
                    <input type="text" id="wmsu-id" name="wmsu_id" value="<?php echo $student['ojt_type']; ?>" readonly>
                </div>

            </div>

            <!-- Right Side Form -->
            <div class="form-section">
                <div class="image-preview" id="image-preview">
                    <img style="" id="preview-image"
                        src="../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
                        alt="Preview Image">
                </div>
                <div class="form-group">
                    <!-- <label for="student-image">Student Image</label> -->
                    <input type="file" id="student-image" name="student_image" accept="image/*">
                </div>
                <div class="form-group">
                    <!-- <label for="password">New Password</label> -->
                    <input type="hidden" id="password" name="student_password" placeholder="Enter New password">
                </div>
                <div class="form-group">
                    <!-- <label for="cpassword">Confirm New Password</label> -->
                    <input type="hidden" id="cpassword" name="student_cpassword" placeholder="Confirm New Password">
                </div>

                <button type="submit" class="btn-confirm"><i style="margin-right: 4px;"
                        class="fa-solid fa-circle-check"></i>Confirm</button>
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
        function fetchAdviser() {
            var course_section_id = document.getElementById('course_section').value;

            // AJAX request to fetch the adviser details
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "get_adviser.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.adviser_firstname) {
                        document.getElementById('adviser').value = response.adviser_firstname + ' ' + response.adviser_middle + '. ' + response.adviser_lastname;
                        document.getElementById('adviser_id').value = response.adviser_id;
                    } else {
                        document.getElementById('adviser').value = "No adviser found";
                        document.getElementById('adviser_id').value = "";
                    }
                }
            };
            xhr.send("course_section_id=" + course_section_id);
        }
        document.getElementById('student-image').addEventListener('change', function (event) {
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