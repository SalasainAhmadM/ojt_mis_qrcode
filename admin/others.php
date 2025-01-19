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
    <title>Admin - Others</title>
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
                <a href="others.php" class="active">
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
        <form class="form-container-header">
            <div style="padding: 10px;" class="form-section-header">
                <label style="color: #a6a6a6">Departments</label>
            </div>
            <div style="padding: 10px;" class="form-section-header-address">
                <label style="color: #a6a6a6">Course and Section</label>
            </div>
            <div style="padding: 10px;" class="form-section-header">
                <label style="color: #a6a6a6">Barangay</label>
            </div>
            <div style="padding: 10px;" class="form-section-header">
                <label style="color: #a6a6a6">Street</label>
            </div>
        </form>
        <div class="form-container-others">
            <!-- Departments -->
            <div class="form-section-others">
                <button class="btn-others" type="button" id="openAddDepartmentModal">Add Department</button>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%;text-align: center">Department Name</th>
                            <th style="text-align: center" class="action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch departments
                        $query = "SELECT * FROM departments ORDER BY department_name ASC";
                        if ($stmt = $database->prepare($query)) {
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td style='width: 15%;'>" . htmlspecialchars($row['department_name']) . "</td>";
                                    echo '<td class="action">
                                <button class="action-icon edit-btn" data-id="' . $row['department_id'] . '" data-name="' . htmlspecialchars($row['department_name']) . '" onclick="openEditDepartmentModal(' . $row['department_id'] . ', \'' . htmlspecialchars($row['department_name']) . '\')">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="action-icon delete-btn" data-id="' . $row['department_id'] . '" onclick="deleteDepartment(' . $row['department_id'] . ')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td style='text-align: center;' colspan='2'>No departments found.</td></tr>";
                            }
                            $stmt->close();
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Courses and Sections -->
            <div class="form-section-others-address">
                <button class="btn-others" type="button" id="openAddCourseModal">Add Course and Section</button>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 10%;text-align: center">Course & Section</th>
                            <th style="width: 15%;text-align: center">Adviser</th>
                            <th style="text-align: center" class="action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch course sections along with adviser information
                        $query = "SELECT cs.*, a.adviser_id, a.adviser_firstname, a.adviser_middle, a.adviser_lastname 
              FROM course_sections cs 
              LEFT JOIN adviser a ON cs.adviser_id = a.adviser_id 
              ORDER BY cs.course_section_name ASC";

                        if ($stmt = $database->prepare($query)) {
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td style='width: 10%;'>" . htmlspecialchars($row['course_section_name']) . "</td>";
                                    echo "<td style='width: 15%;'>" . htmlspecialchars($row['adviser_firstname'] . ' ' . $row['adviser_middle'] . '.' . ' ' . $row['adviser_lastname']) . "</td>";
                                    echo '<td class="action">
                    <button class="action-icon edit-btn" 
                        data-id="' . $row['id'] . '" 
                        data-name="' . htmlspecialchars($row['course_section_name']) . '" 
                        data-adviser-id="' . htmlspecialchars($row['adviser_id']) . '" 
                        onclick="openEditCourseSectionModal(' . $row['id'] . ', \'' . htmlspecialchars($row['course_section_name']) . '\', ' . htmlspecialchars($row['adviser_id']) . ')">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="action-icon delete-btn" 
                        data-id="' . $row['id'] . '" 
                        onclick="deleteCourseSection(' . $row['id'] . ')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td style='text-align: center;' colspan='3'>No courses or sections found.</td></tr>";
                            }
                            $stmt->close();
                        }
                        ?>
                    </tbody>


                </table>
            </div>

            <!-- Address -->
            <div class="form-section-others">
                <button class="btn-others" type="button" id="openAddAddressModal">Add Barangay</button>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%; text-align: center;">Barangay Name</th>
                            <!-- <th style="width: 15%; text-align: center;">Street Name</th> -->
                            <th style="text-align: center;" class="action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch address
                        $query = "SELECT * FROM address ORDER BY address_barangay ASC";
                        if ($stmt = $database->prepare($query)) {
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td style='width: 15%; text-align: center;'>" . htmlspecialchars($row['address_barangay']) . "</td>";
                                    // echo "<td style='width: 15%; text-align: center;'>" . htmlspecialchars($row['address_street']) . "</td>";
                                    echo '<td class="action" style="text-align: center;">
                        <button class="action-icon edit-btn" 
                            data-id="' . $row['address_id'] . '" 
                            data-barangay="' . htmlspecialchars($row['address_barangay']) . '" 
                            onclick="openEditAddressModal(' . $row['address_id'] . ', \'' . htmlspecialchars($row['address_barangay']) . '\')">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="action-icon delete-btn" 
                            data-id="' . $row['address_id'] . '" 
                            onclick="deleteAddress(' . $row['address_id'] . ')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td style='text-align: center;' colspan='3'>No address found.</td></tr>";
                            }
                            $stmt->close();
                        }
                        ?>
                    </tbody>
                </table>

            </div>

            <!-- Street -->
            <div class="form-section-others">
                <button class="btn-others" type="button" id="openAddStreetModal">Add Street</button>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%; text-align: center;">Street Name</th>
                            <!-- <th style="width: 15%; text-align: center;">Street Name</th> -->
                            <th style="text-align: center;" class="action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch street
                        $query = "SELECT * FROM street ORDER BY name ASC";
                        if ($stmt = $database->prepare($query)) {
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td style='width: 15%; text-align: center;'>" . htmlspecialchars($row['name']) . "</td>";
                                    echo '<td class="action" style="text-align: center;">
                        <button class="action-icon edit-btn" 
                            data-id="' . $row['street_id'] . '" 
                            data-street="' . htmlspecialchars($row['name']) . '" 
                            onclick="openEditStreetModal(' . $row['street_id'] . ', \'' . htmlspecialchars($row['name']) . '\')">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="action-icon delete-btn" 
                            data-id="' . $row['street_id'] . '" 
                            onclick="deleteStreet(' . $row['street_id'] . ')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td style='text-align: center;' colspan='3'>No street found.</td></tr>";
                            }
                            $stmt->close();
                        }
                        ?>
                    </tbody>
                </table>

            </div>

        </div>

        </div>
    </section>
    <!-- Add Department Modal -->
    <div id="addDepartmentModal" class="modal">
        <div class="modal-content-others">
            <span class="close" id="closeAddDepartmentModal">&times;</span>
            <h2>Add Department</h2>
            <form action="./others/add_department.php" method="POST">
                <div class="input-group">
                    <label for="departmentName">Department Name</label>
                    <input type="text" id="departmentName" name="departmentName" placeholder="Input Department Name"
                        required>
                </div>
                <button type="submit" class="modal-btn">Add Department</button>
            </form>
        </div>
    </div>

    <!-- Add Course and Section Modal -->
    <div id="addCourseModal" class="modal">
        <div class="modal-content-others">
            <span class="close" id="closeAddCourseModal">&times;</span>
            <h2>Add Course and Section</h2>
            <form action="./others/add_course_section.php" method="POST">
                <div class="input-group">
                    <label for="courseName">Course and Section</label>
                    <input type="text" id="courseName" name="courseName" placeholder="Input Course and Section Name"
                        required maxlength="7" oninput="formatCourseSection(this)">
                </div>
                <div class="input-group">
                    <label for="adviser">Select Adviser</label>
                    <select id="adviser" type="text" name="adviser_id" required>
                        <option value="">Select Adviser</option>
                        <?php
                        // Fetch advisers from the database
                        $query = "SELECT adviser_id, adviser_firstname, adviser_middle, adviser_lastname FROM adviser";
                        $result = $database->query($query);
                        while ($adviser = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($adviser['adviser_id']) . "'>" . htmlspecialchars($adviser['adviser_firstname'] . ' ' . $adviser['adviser_middle'] . '. ' . $adviser['adviser_lastname']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="modal-btn">Add Course and Section</button>
            </form>
        </div>
    </div>

    <script>
        function formatCourseSection(input) {
            let formatted = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');

            if (formatted.length > 4) {
                formatted = formatted.slice(0, 4) + '-' + formatted.slice(4);
            }

            if (formatted.length > 7) {
                formatted = formatted.slice(0, 7);
            }

            input.value = formatted;
        }

    </script>
    <!-- Add Address Modal -->
    <div id="addAddressModal" class="modal">
        <div class="modal-content-others">
            <span class="close" id="closeAddAddressModal">&times;</span>
            <h2>Add Address</h2>
            <form action="./others/add_address.php" method="POST">
                <div class="input-group">
                    <label for="barangayName">Barangay Name</label>
                    <input type="text" id="barangayName" name="barangayName" placeholder="Input Barangay Name" required>
                    <!-- <label for="streetName">Street Name</label>
                    <input type="text" id="streetName" name="streetName" placeholder="Input Street Name" required> -->
                </div>
                <button type="submit" class="modal-btn">Add Address</button>
            </form>
        </div>
    </div>

    <!-- Add Street Modal -->
    <div id="addStreetModal" class="modal">
        <div class="modal-content-others">
            <span class="close" id="closeAddStreetModal">&times;</span>
            <h2>Add Street</h2>
            <form action="./others/add_street.php" method="POST">
                <div class="input-group">
                    <label for="barangayName">Street Name</label>
                    <input type="text" id="barangayName" name="streetName" placeholder="Input Street Name" required>
                </div>
                <button type="submit" class="modal-btn">Add Street</button>
            </form>
        </div>
    </div>
    <!-- Success Modal for Adding Department -->
    <div id="departmentSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Department Added Successfully!</h2>
            <p>The department has been added successfully!</p>
            <button class="proceed-btn" onclick="closeModal('departmentSuccessModal')">Close</button>
        </div>
    </div>

    <!-- Success Modal for Adding Course and Section -->
    <div id="courseSectionSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Course and Section Added Successfully!</h2>
            <p>The course and section have been added successfully!</p>
            <button class="proceed-btn" onclick="closeModal('courseSectionSuccessModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Adding Address -->
    <div id="addressSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Address Added Successfully!</h2>
            <p>The address has been added successfully!</p>
            <button class="proceed-btn" onclick="closeModal('addressSuccessModal')">Close</button>
        </div>
    </div>

    <!-- Success Modal for Adding Address -->
    <div id="streetSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Street Added Successfully!</h2>
            <p>The street has been added successfully!</p>
            <button class="proceed-btn" onclick="closeModal('streetSuccessModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Editing Department -->
    <div id="departmentEditModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Department Updated Successfully!</h2>
            <p>The department has been updated successfully!</p>
            <button class="proceed-btn" onclick="closeModal('departmentEditModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Editing Course and Section -->
    <div id="courseSectionEditModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Course and Section Updated Successfully!</h2>
            <p>The course and section have been updated successfully!</p>
            <button class="proceed-btn" onclick="closeModal('courseSectionEditModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Editing Address -->
    <div id="addressEditModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Address Updated Successfully!</h2>
            <p>The address has been updated successfully!</p>
            <button class="proceed-btn" onclick="closeModal('addressEditModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Editing street -->
    <div id="streetEditModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Street Updated Successfully!</h2>
            <p>The street has been updated successfully!</p>
            <button class="proceed-btn" onclick="closeModal('streetEditModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Deleting Department -->
    <div id="departmentDeleteModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Department Deleted Successfully!</h2>
            <p>The department has been deleted successfully!</p>
            <button class="proceed-btn" onclick="closeModal('departmentDeleteModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Deleting Course and Section -->
    <div id="courseSectionDeleteModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Course and Section Deleted Successfully!</h2>
            <p>The course and section have been deleted successfully!</p>
            <button class="proceed-btn" onclick="closeModal('courseSectionDeleteModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Deleting Department -->
    <div id="addressDeleteModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Address Deleted Successfully!</h2>
            <p>The address has been deleted successfully!</p>
            <button class="proceed-btn" onclick="closeModal('addressDeleteModal')">Close</button>
        </div>
    </div> <!-- Success Modal for Deleting Department -->
    <div id="streetDeleteModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Street Deleted Successfully!</h2>
            <p>The street has been deleted successfully!</p>
            <button class="proceed-btn" onclick="closeModal('streetDeleteModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Duplicate Department -->
    <div id="departmentDuplicateModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <p>Department name already exists!</p>
            <button class="proceed-btn" onclick="closeModal('departmentDuplicateModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Duplicate Address -->
    <div id="AddressDuplicateModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <p>Address Barangay already exists!</p>
            <button class="proceed-btn" onclick="closeModal('AddressDuplicateModal')">Close</button>
        </div>
    </div>

    <!-- Success Modal for Duplicate Street -->
    <div id="StreetDuplicateModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <p>Street Street name already exists!</p>
            <button class="proceed-btn" onclick="closeModal('StreetDuplicateModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Duplicate Course and Section -->
    <div id="courseSectionDuplicateModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <p>Course section name already exists!</p>
            <button class="proceed-btn" onclick="closeModal('courseSectionDuplicateModal')">Close</button>
        </div>
    </div>
    <!-- Try Again Modal -->
    <div id="TryAgainModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Invalid Syntax!</h2>
            <p>Try Again!</p>
            <button class="proceed-btn" onclick="closeModal('TryAgainModal')">Close</button>
        </div>
    </div>
    <!-- Edit Department Modal -->
    <div id="editDepartmentModal" class="modal">
        <div class="modal-content-others">
            <span class="close" id="closeEditDepartmentModal">&times;</span>
            <h2>Edit Department</h2>
            <form id="editDepartmentForm" action="./others/edit_department.php" method="POST">
                <input type="hidden" id="editDepartmentId" name="department_id">
                <div class="input-group">
                    <label for="editDepartmentName">Department Name</label>
                    <input type="text" id="editDepartmentName" name="department_name" required>
                </div>
                <button type="submit" class="modal-btn">Update Department</button>
            </form>
        </div>
    </div>

    <!-- Edit Course Section Modal -->
    <div id="editCourseSectionModal" class="modal">
        <div class="modal-content-others">
            <span class="close" id="closeEditCourseSectionModal">&times;</span>
            <h2>Edit Course and Section</h2>
            <form id="editCourseSectionForm" action="./others/edit_course_section.php" method="POST">
                <input type="hidden" id="editCourseSectionId" name="id">
                <div class="input-group">
                    <label for="editCourseSectionName">Course and Section Name</label>
                    <input type="text" id="editCourseSectionName" name="course_section_name" required maxlength="7">
                </div>
                <div class="input-group">
                    <label for="editAdviser">Select Adviser</label>
                    <select id="editAdviser" type="text" name="adviser_id" required>
                        <option value="">Select Adviser</option>
                        <?php
                        // Fetch advisers from the database
                        $query = "SELECT adviser_id, adviser_firstname, adviser_middle, adviser_lastname FROM adviser";
                        $result = $database->query($query);
                        while ($adviser = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($adviser['adviser_id']) . "'>"
                                . htmlspecialchars($adviser['adviser_firstname'] . ' ' . $adviser['adviser_middle'] . '. ' . $adviser['adviser_lastname'])
                                . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="modal-btn">Update Course and Section</button>
            </form>
        </div>
    </div>


    <!-- Edit Address Modal -->
    <div id="editAddressModal" class="modal">
        <div class="modal-content-others">
            <span class="close" id="closeEditAddressModal">&times;</span>
            <h2>Edit Address</h2>
            <form id="editAddressForm" action="./others/edit_address.php" method="POST">
                <input type="hidden" id="editAddressId" name="address_id">
                <div class="input-group">
                    <label for="editBarangayName">Barangay Name</label>
                    <input type="text" id="editBarangayName" name="barangay_name" required>
                    <!-- <label for="editStreetName">Street Name</label>
                    <input type="text" id="editStreetName" name="street_name" required> -->
                </div>
                <button type="submit" class="modal-btn">Update Address</button>
            </form>
        </div>
    </div>

    <!-- Edit street Modal -->
    <div id="editStreetModal" class="modal">
        <div class="modal-content-others">
            <span class="close" id="closeEditStreetModal">&times;</span>
            <h2>Edit Street</h2>
            <form id="editStreetForm" action="./others/edit_street.php" method="POST">
                <input type="hidden" id="editStreetId" name="street_id">
                <div class="input-group">
                    <label for="editStreetName">Street Name</label>
                    <input type="text" id="editStreetName" name="street_name" required>
                </div>
                <button type="submit" class="modal-btn">Update Street</button>
            </form>
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
    <script src="./js/script.js"></script>
    <script>
        function showModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Show the appropriate modal based on session variables
        window.onload = function () {
            <?php if (isset($_SESSION['department_success'])): ?>
                showModal('departmentSuccessModal');
                <?php unset($_SESSION['department_success']); ?>
            <?php elseif (isset($_SESSION['course_section_success'])): ?>
                showModal('courseSectionSuccessModal');
                <?php unset($_SESSION['course_section_success']); ?>
            <?php elseif (isset($_SESSION['address_success'])): ?>
                showModal('addressSuccessModal');
                <?php unset($_SESSION['address_success']); ?>
            <?php elseif (isset($_SESSION['street_success'])): ?>
                showModal('streetSuccessModal');
                <?php unset($_SESSION['street_success']); ?>
            <?php elseif (isset($_SESSION['department_delete'])): ?>
                showModal('departmentDeleteModal');
                <?php unset($_SESSION['department_delete']); ?>
            <?php elseif (isset($_SESSION['course_section_delete'])): ?>
                showModal('courseSectionDeleteModal');
                <?php unset($_SESSION['course_section_delete']); ?>
            <?php elseif (isset($_SESSION['address_delete'])): ?>
                showModal('addressDeleteModal');
                <?php unset($_SESSION['address_delete']); ?>
            <?php elseif (isset($_SESSION['street_delete'])): ?>
                showModal('streetDeleteModal');
                <?php unset($_SESSION['street_delete']); ?>
            <?php elseif (isset($_SESSION['department_edit_success'])): ?>
                showModal('departmentEditModal');
                <?php unset($_SESSION['department_edit_success']); ?>
            <?php elseif (isset($_SESSION['course_section_edit_success'])): ?>
                showModal('courseSectionEditModal');
                <?php unset($_SESSION['course_section_edit_success']); ?>
            <?php elseif (isset($_SESSION['address_edit_success'])): ?>
                showModal('addressEditModal');
                <?php unset($_SESSION['address_edit_success']); ?>
            <?php elseif (isset($_SESSION['street_edit_success'])): ?>
                showModal('streetEditModal');
                <?php unset($_SESSION['street_edit_success']); ?>
            <?php elseif (isset($_SESSION['error_1'])): ?>
                showModal('departmentDuplicateModal');
                <?php unset($_SESSION['error_1']); ?>
            <?php elseif (isset($_SESSION['error_2'])): ?>
                showModal('courseSectionDuplicateModal');
                <?php unset($_SESSION['error_2']); ?>
            <?php elseif (isset($_SESSION['error_3'])): ?>
                showModal('AddressDuplicateModal');
                <?php unset($_SESSION['error_3']); ?>
            <?php elseif (isset($_SESSION['error_4'])): ?>
                showModal('StreetDuplicateModal');
                <?php unset($_SESSION['error_4']); ?>
            <?php elseif (isset($_SESSION['error_try'])): ?>
                showModal('TryAgainModal');
                <?php unset($_SESSION['error_try']); ?>
            <?php endif; ?>
        };

        // Get modals
        var addDepartmentModal = document.getElementById("addDepartmentModal");
        var addCourseModal = document.getElementById("addCourseModal");
        var addAddressModal = document.getElementById("addAddressModal");
        var addStreetModal = document.getElementById("addStreetModal");
        // Get open modal buttons
        var openAddDepartmentModalBtn = document.getElementById("openAddDepartmentModal");
        var openAddCourseModalBtn = document.getElementById("openAddCourseModal");
        var openAddAddressModalBtn = document.getElementById("openAddAddressModal");
        var openAddStreetModalBtn = document.getElementById("openAddStreetModal");

        // Get close buttons
        var closeAddDepartmentModalBtn = document.getElementById("closeAddDepartmentModal");
        var closeAddCourseModalBtn = document.getElementById("closeAddCourseModal");
        var closeAddAddressModalBtn = document.getElementById("closeAddAddressModal");
        var closeAddStreetModalBtn = document.getElementById("closeAddStreetModal");

        // Open modals
        openAddDepartmentModalBtn.onclick = function () {
            addDepartmentModal.style.display = "block";
        }

        openAddCourseModalBtn.onclick = function () {
            addCourseModal.style.display = "block";
        }

        openAddAddressModalBtn.onclick = function () {
            addAddressModal.style.display = "block";
        }
        openAddStreetModalBtn.onclick = function () {
            addStreetModal.style.display = "block";
        }
        // Close modals
        closeAddDepartmentModalBtn.onclick = function () {
            addDepartmentModal.style.display = "none";
        }

        closeAddCourseModalBtn.onclick = function () {
            addCourseModal.style.display = "none";
        }

        closeAddAddressModalBtn.onclick = function () {
            addAddressModal.style.display = "none";
        }
        closeAddStreetModalBtn.onclick = function () {
            addStreetModal.style.display = "none";
        }
        // Close modal if clicked outside
        window.onclick = function (event) {
            if (event.target == addDepartmentModal) {
                addDepartmentModal.style.display = "none";
            } else if (event.target == addCourseModal) {
                addCourseModal.style.display = "none";
            } else if (event.target == addAddressModal) {
                addAddressModal.style.display = "none";
            } else if (event.target == addStreetModal) {
                addStreetModal.style.display = "none";
            } else if (event.target == document.getElementById("editDepartmentModal")) {
                document.getElementById("editDepartmentModal").style.display = "none";
            } else if (event.target == document.getElementById("editCourseSectionModal")) {
                document.getElementById("editCourseSectionModal").style.display = "none";
            } else if (event.target == document.getElementById("editAddressModal")) {
                document.getElementById("editAddressModal").style.display = "none";
            } else if (event.target == document.getElementById("editStreetModal")) {
                document.getElementById("editStreetModal").style.display = "none";
            }
        };

        // Edit buttons functionality
        document.querySelectorAll('.edit-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const adviser_id = this.getAttribute('data-adviser-id');
                const barangay_name = this.getAttribute('data-barangay');
                const street_name = this.getAttribute('data-street');
                // const street_name = this.getAttribute('data-street');

                if (this.closest('.form-section-others').classList.contains('department-section')) {
                    document.getElementById('editDepartmentName').value = name;
                    document.getElementById('editDepartmentId').value = id;
                    showModal('editDepartmentModal');
                } else if (this.closest('.form-section-others').classList.contains('course-section')) {
                    document.getElementById('editCourseSectionName').value = name;
                    document.getElementById('editCourseSectionId').value = id;
                    document.getElementById('editAdviser').value = adviser_id;
                    showModal('editCourseSectionModal');
                } else if (this.closest('.form-section-others').classList.contains('address-section')) {
                    document.getElementById('editBarangayName').value = barangay_name;
                    document.getElementById('editAddressId').value = id;
                    showModal('editAddressModal');
                } else if (this.closest('.form-section-others').classList.contains('street-section')) {
                    document.getElementById('editStreetName').value = street_name;
                    document.getElementById('editStreetId').value = id;
                    showModal('editStreetModal');
                }
            });
        });

        // Open Edit Department Modal
        function openEditDepartmentModal(departmentId, departmentName) {
            document.getElementById("editDepartmentId").value = departmentId;
            document.getElementById("editDepartmentName").value = departmentName;
            document.getElementById("editDepartmentModal").style.display = "block";
        }

        // Close Edit Department Modal
        document.getElementById("closeEditDepartmentModal").onclick = function () {
            document.getElementById("editDepartmentModal").style.display = "none";
        };

        // Open Edit Course Section Modal
        function openEditCourseSectionModal(courseSectionId, courseSectionName, adviser_id) {
            document.getElementById("editCourseSectionId").value = courseSectionId;
            document.getElementById("editCourseSectionName").value = courseSectionName;
            document.getElementById('editAdviser').value = adviser_id;
            document.getElementById("editCourseSectionModal").style.display = "block";
        }

        // Close Edit Course Section Modal
        document.getElementById("closeEditCourseSectionModal").onclick = function () {
            document.getElementById("editCourseSectionModal").style.display = "none";
        };

        // Open Edit Address Modal
        function openEditAddressModal(addressId, barangayName) {
            document.getElementById("editAddressId").value = addressId;
            document.getElementById("editBarangayName").value = barangayName;
            document.getElementById("editAddressModal").style.display = "block";
        }

        // Close Edit Address Modal
        document.getElementById("closeEditAddressModal").onclick = function () {
            document.getElementById("editAddressModal").style.display = "none";
        };

        // Open Edit street Modal
        function openEditStreetModal(streetId, streetName) {
            document.getElementById("editStreetId").value = streetId;
            document.getElementById("editStreetName").value = streetName;
            document.getElementById("editStreetModal").style.display = "block";
        }

        // Close Edit Street Modal
        document.getElementById("closeEditStreetModal").onclick = function () {
            document.getElementById("editStreetModal").style.display = "none";
        };
        // For deleting a department, course section, or address
        function deleteDepartment(id) {
            if (confirm('Are you sure you want to delete this department?')) {
                window.location.href = './others/delete_department.php?id=' + id;
            }
        }

        function deleteCourseSection(id) {
            if (confirm('Are you sure you want to delete this course and section?')) {
                window.location.href = './others/delete_course_section.php?id=' + id;
            }
        }

        function deleteAddress(id) {
            if (confirm('Are you sure you want to delete this address?')) {
                window.location.href = './others/delete_address.php?id=' + id;
            }
        }

        function deleteStreet(id) {
            if (confirm('Are you sure you want to delete this street?')) {
                window.location.href = './others/delete_street.php?id=' + id;
            }
        }
    </script>
    <script src="../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>