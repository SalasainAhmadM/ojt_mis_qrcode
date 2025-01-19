<?php
session_start();
require '../../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch admin details
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM admin WHERE admin_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();
}

// Fetch all departments for the dropdown
$query = "SELECT * FROM departments";
$departments = [];
if ($stmt = $database->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    $stmt->close();
}

include './others/filter_adviser.php';

// Fetch advisers with pagination, department, and search functionality
$pagination_data = getAdvisers($database, $selected_department, $search_query);
$advisers = $pagination_data['advisers'];
$total_pages = $pagination_data['total_pages'];
$current_page = $pagination_data['current_page'];

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
    <title>Admin - Adviser Management</title>
    <link rel="icon" href="../../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="../css/style.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400&family=Poppins:wght@600&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="header">
        <i class="fas fa-school"></i>
        <div class="school-name">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $currentSemester; ?> &nbsp;&nbsp;&nbsp;
            <span id="sy-text"></span>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #095d40;">|</span>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;College of Computing Studies
            <img src="../../img/ccs.png">
        </div>
    </div>
    <div class="sidebar close">
        <div class="profile-details">
            <img src="../../uploads/admin/<?php echo !empty($admin['admin_image']) ? $admin['admin_image'] : 'user.png'; ?>"
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
                <a href="../index.php">
                    <i class="fas fa-th-large"></i>
                    <span class="link_name">Dashboard</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../index.php">Dashboard</a></li>
                </ul>
            </li>
            <li>
                <div style="background-color: #07432e;" class="iocn-link">
                    <a href="../user-manage.php">
                        <i class="fa-solid fa-user"></i>
                        <span class="link_name">Manage Users</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="../user-manage.php">User Management</a></li>
                    <li><a href="./adviser.php">Adviser Management</a></li>
                    <li><a href="./company.php">Company Management</a></li>
                    <li><a href="./student.php">Student Management</a></li>
                </ul>
            </li>
            <li>
                <a href="../others.php">
                    <i class="fa-solid fa-ellipsis-h"></i>
                    <span class="link_name">Others</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../others.php">Others</a></li>
                </ul>
            </li>
            <li>
                <a href="../calendar.php">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span class="link_name">Calendar</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../calendar.php">Calendar</a></li>
                </ul>
            </li>
            <li>
                <a href="../feedback.php">
                    <i class="fa-solid fa-percent"></i>
                    <span class="link_name">Feedback</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../feedback.php">Feedback Management</a></li>
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
            <i style="z-index: 100;" class="fas fa-bars bx-menu"></i>
        </div>
        <div class="content-wrapper">

            <div class="header-box">
                <label style="color: #a6a6a6;">Adviser Management</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <div class="header-group">
                        <h2>Adviser Details</h2>
                        <div class="button-container">
                            <button id="openAddModalBtn" class="add-btn">
                                <i class="fa-solid fa-plus"></i>Add Adviser
                            </button>
                        </div>
                    </div>

                    <div class="filter-group">
                        <!-- Department Filter Form -->
                        <form method="GET" action="">
                            <select class="dropdown" name="department" onchange="this.form.submit()">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($department['department_name'], ENT_QUOTES); ?>"
                                        <?php echo $selected_department == $department['department_name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['department_name'], ENT_QUOTES); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="search"
                                value="<?php echo htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : '', ENT_QUOTES); ?>">
                        </form>

                        <!-- Search Bar Form -->
                        <form method="GET" action="">
                            <input type="hidden" name="department"
                                value="<?php echo htmlspecialchars($selected_department, ENT_QUOTES); ?>">
                            <div class="search-bar-container">
                                <input type="text" class="search-bar" name="search" placeholder="Search Adviser"
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="search-bar-icon">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Reset Button Form -->
                        <form method="GET" action="adviser.php">
                            <button type="submit" class="reset-bar-icon">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </form>
                    </div>

                    <style>
                        td:hover {
                            position: relative;
                            cursor: pointer;
                        }

                        td:hover::after {
                            content: attr(title);
                            position: absolute;
                            left: 50%;
                            transform: translateX(-50%);
                            bottom: 100%;
                            font-size: 24px;
                            background: rgba(0, 0, 0, 0.8);
                            color: #fff;
                            padding: 5px 10px;
                            border-radius: 5px;
                            white-space: nowrap;
                            z-index: 10;
                            font-size: 0.9em;
                        }

                        table td {
                            font-family: 'Roboto', sans-serif;
                            /* font-weight: 400; */
                        }
                    </style>
                    <table>
                        <thead>
                            <tr>
                                <th class="image">Profile</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th>Department</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($advisers)): ?>
                                <?php foreach ($advisers as $adviser): ?>
                                    <tr>
                                        <td class="image">
                                            <img style="border-radius: 50%;"
                                                src="../../uploads/adviser/<?php echo !empty($adviser['adviser_image']) ? $adviser['adviser_image'] : 'user.png'; ?>"
                                                alt="Adviser Image">
                                        </td>
                                        <td
                                            title="<?php echo $adviser['adviser_firstname'] . ' ' . $adviser['adviser_middle'] . '.' . ' ' . $adviser['adviser_lastname']; ?>">
                                            <?php echo $adviser['adviser_firstname'] . ' ' . $adviser['adviser_middle'] . '.' . ' ' . $adviser['adviser_lastname']; ?>
                                        </td>
                                        <td title="<?php echo $adviser['adviser_email']; ?>">
                                            <?php echo $adviser['adviser_email']; ?>
                                        </td>
                                        <td title="<?php echo $adviser['adviser_number']; ?>">
                                            <?php echo $adviser['adviser_number']; ?>
                                        </td>
                                        <td title="<?php echo $adviser['department']; ?>"><?php echo $adviser['department']; ?>
                                        </td>
                                        <td class="action">
                                            <button class="action-icon edit-btn"
                                                onclick="openEditAdviserModal(<?php echo htmlspecialchars(json_encode($adviser), ENT_QUOTES); ?>)">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <button class="action-icon delete-btn"
                                                onclick="openDeleteModal(<?php echo $adviser['adviser_id']; ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>


                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No advisers found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Display pagination links -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php renderPaginationLinks($total_pages, $current_page, $selected_department, $search_query); ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
    </section>
    <!-- Add Adviser Modal -->
    <div id="addAdviserModal" class="modal">
        <div class="modal-content-bigger">
            <span class="close" id="closeAddAdviserModal">&times;</span>
            <h2 class="modal-title">Add Adviser</h2>

            <form action="./others/add_adviser.php" method="POST" enctype="multipart/form-data">
                <!-- Profile Image and Preview -->
                <div class="profile-img-container">
                    <label for="adviserImage">
                        <img id="addImagePreview" src="../../img/user.png" alt="Profile Preview"
                            class="profile-preview-img" />
                    </label>
                    <input type="file" id="adviserImage" name="adviser_image" accept="image/*"
                        onchange="previewAddImage()" style="display: none;">
                    <p class="profile-img-label">Click to upload image</p>
                </div>

                <!-- Full Name Row -->
                <div class="input-group-row">
                    <div class="input-group-fn">
                        <label for="adviserFirstname">First Name</label>
                        <input type="text" id="adviserFirstname" name="adviser_firstname" placeholder="First Name"
                            required>
                    </div>
                    <div class="input-group-mi" style="width: 50px;">
                        <label id="mi" for="adviserMiddle">M.I.</label>
                        <input type="text" id="adviserMiddlename" name="adviser_middle" placeholder="M.I.">
                    </div>
                    <div class="input-group-ln" style="width: 40%;">
                        <label for="adviserLastname">Last Name</label>
                        <input type="text" id="adviserLastname" name="adviser_lastname" placeholder="Last Name"
                            required>
                    </div>

                </div>

                <!-- Email, Contact, Department Row -->
                <div class="input-group-row">
                    <div class="input-group">
                        <label for="adviserEmail">Email</label>
                        <input type="email" id="adviserEmail" name="adviser_email" placeholder="Email" required>
                    </div>
                    <div class="input-group">
                        <label for="adviserContact">Contact Number</label>
                        <input type="text" id="adviserContact" value="+63" name="adviser_number"
                            placeholder="Contact Number" required maxlength="13" oninput="limitInput(this)">
                    </div>
                    <div class="input-group">
                        <label for="adviserDepartment">Department</label>
                        <select type="text" id="adviserDepartment" name="adviser_department" class="input-field"
                            required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department['department_name'], ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($department['department_name'], ENT_QUOTES); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>


                <button type="submit" class="modal-btn">Add Adviser</button>
            </form>
        </div>
    </div>


    <!-- Password Input -->
    <!-- <div class="input-group-row">
        <div style="position: relative;" class="input-group">
            <label for="adviserPassword">Password</label>
            <div class="password-wrapper">
                <input style="padding-right: 40px; " type="password" id="adviserPassword" name="adviser_password"
                    placeholder="Password" required>
                <span class="toggle-password">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
        </div>
        <div style="position: relative;" class="input-group">
            <label for="adviserConfirmPassword">Confirm Password</label>
            <div class="password-wrapper">
                <input type="password" id="adviserConfirmPassword" name="confirm_password"
                    placeholder="Confirm Password" required>
                <span class="toggle-password">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
        </div>
    </div> -->
    <!-- Edit Adviser Modal -->
    <div id="editAdviserModal" class="modal">
        <div class="modal-content-bigger">
            <span class="close" id="closeEditAdviserModal">&times;</span>
            <h2 class="modal-title">Edit Adviser</h2>

            <form action="./others/edit_adviser.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="editAdviserId" name="adviser_id">

                <!-- Profile Image and Preview -->
                <div class="profile-img-container">
                    <label for="editAdviserImage">
                        <img id="editImagePreview" src="" alt="Profile Preview" class="profile-preview-img" />
                    </label>
                    <input type="file" id="editAdviserImage" name="adviser_image" accept="image/*"
                        onchange="previewEditImage()" style="display: none;">
                    <p class="profile-img-label">Click to upload image</p>
                </div>


                <!-- Full Name Row -->
                <div class="input-group-row">
                    <div class="input-group-fn">
                        <label for="editAdviserFirstname">First Name</label>
                        <input type="text" id="editAdviserFirstname" name="adviser_firstname" required>
                    </div>
                    <div class="input-group-mi">
                        <label id="mi_edit" for="editAdviserMiddle">M.I.</label>
                        <input type="text" id="editAdviserMiddle" name="adviser_middle" required>
                    </div>
                    <div class="input-group-ln">
                        <label for="editAdviserLastname">Last Name</label>
                        <input type="text" id="editAdviserLastname" name="adviser_lastname" required>
                    </div>
                </div>

                <!-- Email, Contact, Department Row -->
                <div class="input-group-row">
                    <div class="input-group" style="width: 33%;">
                        <label for="editAdviserEmail">Email</label>
                        <input type="email" id="editAdviserEmail" name="adviser_email" required>
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="editAdviserContact">Contact Number</label>
                        <input type="text" id="editAdviserContact" name="adviser_number" required maxlength="13"
                            oninput="limitInput(this)">
                    </div>
                    <script>
                        function limitInput(input) {
                            if (input.value.length > 13) {
                                input.value = input.value.slice(0, 13);
                            }
                        }
                    </script>
                    <div class="input-group" style="width: 33%;">
                        <label for="editAdviserDepartment">Department</label>
                        <select type="text" id="editAdviserDepartment" name="adviser_department" class="input-field"
                            required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department['department_name'], ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($department['department_name'], ENT_QUOTES); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Password and Confirm Password -->
                <div class="input-group-row">
                    <div style="position: relative;" class="input-group">
                        <label for="editAdviserPassword">Password</label>
                        <input type="password" id="editAdviserPassword" name="adviser_password" placeholder="Password">
                        <span class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div style="position: relative;" class="input-group">
                        <label for="editAdviserConfirmPassword">Confirm Password</label>
                        <input type="password" id="editAdviserConfirmPassword" name="adviser_confirm_password"
                            placeholder="Confirm Password">
                        <span class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="modal-btn">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Success Modal for Adding Adviser -->
    <div id="addAdviserSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Adviser Added Successfully!</h2>
            <p>The adviser password has been sent via email successfully!</p>
            <button class="proceed-btn" onclick="closeModal('addAdviserSuccessModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Editing Adviser -->
    <div id="editAdviserSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Adviser Updated Successfully!</h2>
            <p>The adviser details have been updated successfully!</p>
            <button class="proceed-btn" onclick="closeModal('editAdviserSuccessModal')">Close</button>
        </div>
    </div>
    <!-- Password Duplicate Modal -->
    <div id="TryAgainModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #8B0000">Password does not Match!</h2>
            <p>Try Again!</p>
            <button class="proceed-btn" onclick="closeModal('TryAgainModal')">Close</button>
        </div>
    </div>
    <!-- Email Duplicate Modal -->
    <div id="TryAgain2Modal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #8B0000">Email already in use!</h2>
            <p>Try Again!</p>
            <button class="proceed-btn" onclick="closeModal('TryAgain2Modal')">Close</button>
        </div>
    </div>
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
                <button class="confirm-btn" onclick="logout2()">Confirm</button>
                <button class="cancel-btn" onclick="closeModal2('logoutModal')">Cancel</button>
            </div>
        </div>
    </div>
    <script>// Get modal elements
        // Get modal elements
        var addAdviserModal = document.getElementById('addAdviserModal');
        var editAdviserModal = document.getElementById('editAdviserModal');

        // Get buttons
        var openAddModalBtn = document.getElementById('openAddModalBtn');
        var closeAddAdviserModal = document.getElementById('closeAddAdviserModal');
        var closeEditAdviserModal = document.getElementById('closeEditAdviserModal');

        // Open Add Adviser modal
        openAddModalBtn.onclick = function () {
            addAdviserModal.style.display = 'block';
        }

        // Close modals when close button is clicked
        closeAddAdviserModal.onclick = function () {
            addAdviserModal.style.display = 'none';
        }
        closeEditAdviserModal.onclick = function () {
            editAdviserModal.style.display = 'none';
        }

        // Function to open Edit Adviser modal and populate fields
        function openEditAdviserModal(adviser) {
            document.getElementById('editAdviserId').value = adviser.adviser_id;
            document.getElementById('editImagePreview').src = adviser.adviser_image ? `../../uploads/adviser/${adviser.adviser_image}` : '../../img/user.png';
            document.getElementById('editAdviserFirstname').value = adviser.adviser_firstname;
            document.getElementById('editAdviserMiddle').value = adviser.adviser_middle;
            document.getElementById('editAdviserLastname').value = adviser.adviser_lastname;
            document.getElementById('editAdviserEmail').value = adviser.adviser_email;
            document.getElementById('editAdviserContact').value = adviser.adviser_number;
            document.getElementById('editAdviserDepartment').value = adviser.department;
            document.getElementById('editAdviserPassword').value = '';
            // Show the edit modal
            editAdviserModal.style.display = 'block';
        }

        // Preview function for Add Adviser Image
        function previewAddImage() {
            const imageInput = document.getElementById('adviserImage');
            const imagePreview = document.getElementById('addImagePreview');
            const file = imageInput.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '../../img/user.png';
            }
        }

        // Preview function for Edit Adviser Image
        function previewEditImage() {
            const imageInput = document.getElementById('editAdviserImage');
            const imagePreview = document.getElementById('editImagePreview');
            const file = imageInput.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '../../img/user.png';
            }
        }

        // Function to show success modals
        function showModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            }
        }

        // Function to close modals
        function closeModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        window.onload = function () {
            <?php if (isset($_SESSION['add_adviser_success'])): ?>
                showModal('addAdviserSuccessModal');
                <?php unset($_SESSION['add_adviser_success']); ?>
            <?php elseif (isset($_SESSION['edit_adviser_success'])): ?>
                showModal('editAdviserSuccessModal');
                <?php unset($_SESSION['edit_adviser_success']); ?>
            <?php elseif (isset($_SESSION['error'])): ?>
                showModal('TryAgainModal');
                <?php unset($_SESSION['error']); ?>
            <?php elseif (isset($_SESSION['error2'])): ?>
                showModal('TryAgain2Modal');
                <?php unset($_SESSION['error2']); ?>
            <?php endif; ?>

        }

        function openDeleteModal(adviserId) {
            document.getElementById("delete-adviser-id").value = adviserId; // Store adviserId in hidden field
            document.getElementById("deleteModal").style.display = "block"; // Show modal
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").style.display = "none"; // Hide modal
        }
        function closeDeleteSuccessModal() {
            document.getElementById('deleteSuccessModal').style.display = 'none';
            window.location.reload();
        }
        function confirmDelete() {
            const adviserId = document.getElementById("delete-adviser-id").value; // Get adviserId from hidden input
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "./others/delete_adviser.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);

                    if (response.status === 'success') {
                        showModal('deleteSuccessModal');
                    } else {
                        alert(response.message);
                    }
                }
            };

            xhr.send("id=" + adviserId);

            closeDeleteModal();
        }
        function validateAddPassword() {
            var password = document.getElementById("adviserPassword").value;
            var confirmPassword = document.getElementById("adviserConfirmPassword").value;

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }
            return true;
        }

        function validateEditPassword() {
            var password = document.getElementById("editAdviserPassword").value;
            var confirmPassword = document.getElementById("editAdviserConfirmPassword").value;

            if (password !== "" && password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }
            return true;
        }
        // Toggle password visibility
        const togglePasswordButtons = document.querySelectorAll('.toggle-password');

        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function () {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

    </script>

    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/alert-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #000">Are you sure you want to delete?</h2>
            <input type="hidden" id="delete-adviser-id" value="">
            <div style="display: flex; justify-content: space-around; margin-top: 10px; margin-bottom: 20px">
                <button class="confirm-btn" onclick="confirmDelete()">Confirm</button>
                <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div id="deleteSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/delete.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Adviser Delete Successfully!</h2>
            <p>adviser has been deleted successfully by <br> <span
                    style="color: #095d40; font-size: 20px;"><?php echo $_SESSION['full_name']; ?>!</span></p>
            <button class="proceed-btn" onclick="closeDeleteSuccessModal('deleteSuccessModal')">Close</button>
        </div>
    </div>

    <script src="../../js/sy.js"></script>
    <script src="../js/script.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>