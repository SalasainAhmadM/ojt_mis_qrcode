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

// Fetch all addresses for the dropdown
$query = "SELECT * FROM address";
$addresses = [];
if ($stmt = $database->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    $stmt->close();
}


include './others/filter_company.php';

// Fetch companys with pagination, department, and search functionality
$pagination_data = getCompanies($database, $search_query);
$companies = $pagination_data['companies'];
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
    <title>Admin - Company Management</title>
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
                <label style="color: #a6a6a6;">Company Management</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <div class="header-group">
                        <h2>Company Details</h2>
                        <div class="button-container">
                            <button id="openAddModalBtn" class="add-btn">
                                <i class="fa-solid fa-plus"></i>Add Company
                            </button>
                        </div>
                    </div>

                    <div class="filter-group">

                        <!-- Search Bar Form -->
                        <form method="GET" action="">
                            <input type="hidden" name="department"
                                value="<?php echo htmlspecialchars($selected_department, ENT_QUOTES); ?>">
                            <div class="search-bar-container">
                                <input type="text" class="search-bar" name="search" placeholder="Search"
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="search-bar-icon">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Reset Button Form -->
                        <form method="GET" action="company.php">
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
                                <th>Company Name</th>
                                <th>Representative</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th>Address</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($companies)): ?>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td class="image">
                                            <img style="border-radius: 50%;"
                                                src="../../uploads/company/<?php echo !empty($company['company_image']) ? $company['company_image'] : 'user.png'; ?>"
                                                alt="Company Image">
                                        </td>
                                        <td title="<?php echo $company['company_name']; ?>">
                                            <?php echo $company['company_name']; ?>
                                        </td>
                                        <td
                                            title="<?php echo $company['company_rep_firstname'] . ' ' . $company['company_rep_middle'] . '.' . ' ' . $company['company_rep_lastname'] . ' - ' . $company['company_rep_position']; ?>">
                                            <?php echo $company['company_rep_firstname'] . ' ' . $company['company_rep_middle'] . '.' . ' ' . $company['company_rep_lastname'] . ' - ' . $company['company_rep_position']; ?>
                                        </td>
                                        <td title="<?php echo $company['company_email']; ?>">
                                            <?php echo $company['company_email']; ?>
                                        </td>
                                        <td title="<?php echo $company['company_number']; ?>">
                                            <?php echo $company['company_number']; ?>
                                        </td>
                                        <td title="<?php echo $company['company_address']; ?>">
                                            <?php echo $company['company_address']; ?>
                                        </td>
                                        <!-- Display the full address -->
                                        <td class="action">
                                            <button class="action-icon edit-btn"
                                                onclick="openEditCompanyModal(<?php echo htmlspecialchars(json_encode($company), ENT_QUOTES); ?>)">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <button class="action-icon delete-btn"
                                                onclick="openDeleteModal(<?php echo $company['company_id']; ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No companies found</td>
                                    <!-- Adjusted column span to match the number of columns -->
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>

                    <!-- Display pagination links -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php renderPaginationLinks($total_pages, $current_page, $search_query); ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
    </section>
    <style>
        .input-group-company {
            display: flex;
            flex-direction: column;
        }

        .input-group-company {
            width: 300px;
        }
    </style>
    <!-- Add Company Modal -->
    <div id="addCompanyModal" class="modal">
        <div class="modal-content-bigger">
            <span class="close" id="closeAddCompanyModal">&times;</span>
            <h2 class="modal-title">Add Company</h2>

            <form action="./others/add_company.php" method="POST" enctype="multipart/form-data">
                <!-- Profile Image and Preview -->
                <div class="profile-img-container">
                    <label for="companyImage">
                        <img id="addImagePreview" src="../../img/user.png" alt="Profile Preview"
                            class="profile-preview-img" />
                    </label>
                    <input type="file" id="companyImage" name="company_image" accept="image/*"
                        onchange="previewAddImage()" style="display: none;">
                    <p class="profile-img-label">Click to upload image</p>
                </div>
                <div class="input-group-row">
                    <div class="input-group-company">
                        <label for="companyName">Company Name</label>
                        <input type="text" id="companyName" name="company_name" placeholder="Company Name" required>
                    </div>
                    <div class="input-group">
                        <label for="companyEmail">Email</label>
                        <input type="email" id="companyEmail" name="company_email" placeholder="Email" required>
                    </div>
                </div>
                <!-- Full Name Row -->
                <label>Representative Full Name</label>
                <div class="input-group-row">
                    <div class="input-group-fn">
                        <input type="text" id="companyFirstname" name="company_rep_firstname" placeholder="First Name"
                            required>
                    </div>
                    <div class="input-group-mi" style="width: 50px;">
                        <input type="text" id="companyMiddlename" name="company_rep_middle" placeholder="M.I." required>
                    </div>
                    <div class="input-group-ln" style="width: 40%;">
                        <input type="text" id="companyLastname" name="company_rep_lastname" placeholder="Last Name"
                            required>
                    </div>

                </div>

                <div class="input-group-row">
                    <div class="input-group">
                        <label for="companyPosition">Position</label>
                        <input type="text" id="companyPosition" name="company_rep_position"
                            placeholder="Representative Position" required>
                    </div>
                    <div class="input-group">
                        <label for="companyContact">Contact Number</label>
                        <input type="text" id="companyContact" value="+63" name="company_number"
                            placeholder="Contact Number" required maxlength="13" oninput="limitInput(this)">
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="addCompanyAddress">Address</label>
                        <select type="text" id="addCompanyAddress" name="company_address" required>
                            <option value="">Select Address</option>
                            <?php foreach ($addresses as $address): ?>
                                <option>
                                    <?php echo htmlspecialchars($address['address_barangay'], ENT_QUOTES); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Password Input -->
                <!-- <div class="input-group-row">
                    <div style="position: relative;" class="input-group">
                        <label for="companyPassword">Password</label>
                        <div class="password-wrapper">
                            <input style="padding-right: 40px; " type="password" id="companyPassword"
                                name="company_password" placeholder="Password" required>
                            <span class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <div style="position: relative;" class="input-group">
                        <label for="companyConfirmPassword">Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="companyConfirmPassword" name="confirm_password"
                                placeholder="Confirm Password" required>
                            <span class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div> 
                </div> -->
                <button type="submit" class="modal-btn">Add Company</button>
            </form>
        </div>
    </div>

    <!-- Edit Company Modal -->
    <div id="editCompanyModal" class="modal">
        <div class="modal-content-bigger">
            <span class="close" id="closeEditCompanyModal">&times;</span>
            <h2 class="modal-title">Edit Company</h2>

            <form action="./others/edit_company.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="editCompanyId" name="company_id">

                <!-- Profile Image and Preview -->
                <div class="profile-img-container">
                    <label for="editCompanyImage">
                        <img id="editImagePreview" src="" alt="Profile Preview" class="profile-preview-img" />
                    </label>
                    <input type="file" id="editCompanyImage" name="company_image" accept="image/*"
                        onchange="previewEditImage()" style="display: none;">
                    <p class="profile-img-label">Click to upload image</p>
                </div>
                <div class="input-group-row">
                    <div class="input-group-company">
                        <label for="editCompanyName">Company Name</label>
                        <input type="text" id="editCompanyName" name="company_name" required>
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="editCompanyEmail">Email</label>
                        <input type="email" id="editCompanyEmail" name="company_email" required>
                    </div>
                </div>

                <!-- Full Name Row -->
                <label>Representative Full Name</label>
                <div class="input-group-row">
                    <div class="input-group-fn">
                        <input type="text" id="editCompanyFirstname" name="company_rep_firstname" required>
                    </div>
                    <div class="input-group-mi">
                        <input type="text" id="editCompanyMiddle" name="company_rep_middle">
                    </div>
                    <div class="input-group-ln">
                        <input type="text" id="editCompanyLastname" name="company_rep_lastname" required>
                    </div>
                </div>

                <!-- Email, Contact, Department Row -->
                <div class="input-group-row">
                    <div class="input-group">
                        <label for="editCompanyPosition">Position</label>
                        <input type="text" id="editCompanyPosition" name="company_rep_position"
                            placeholder="Representative Position" required>
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="editCompanyContact">Contact Number</label>
                        <input type="text" id="editCompanyContact" name="company_number" required maxlength="13"
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
                        <label for="editCompanyAddress">Address</label>
                        <select type="text" id="editCompanyAddress" name="company_address" required>
                            <option value="">Select Address</option>
                            <?php foreach ($addresses as $address): ?>
                                <option value="<?php echo htmlspecialchars($address['address_barangay'], ENT_QUOTES); ?>"
                                    <?php if ($company['company_address'] == $address['address_barangay'] . ', ')
                                        echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($address['address_barangay'], ENT_QUOTES); ?>
                                </option>

                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
                <!-- Password and Confirm Password -->
                <div class="input-group-row">
                    <div style="position: relative;" class="input-group">
                        <label for="editCompanyPassword">Password</label>
                        <input type="password" id="editCompanyPassword" name="company_password" placeholder="Password">
                        <span class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div style="position: relative;" class="input-group">
                        <label for="editCompanyConfirmPassword">Confirm Password</label>
                        <input type="password" id="editCompanyConfirmPassword" name="company_confirm_password"
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

    <!-- Success Modal for Adding Company -->
    <div id="addCompanySuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Company Added Successfully!</h2>
            <p>The company password has been sent via email successfully!</p>
            <button class="proceed-btn" onclick="closeModal('addCompanySuccessModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Editing Company -->
    <div id="editCompanySuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Company Updated Successfully!</h2>
            <p>The company details have been updated successfully!</p>
            <button class="proceed-btn" onclick="closeModal('editCompanySuccessModal')">Close</button>
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
        var addCompanyModal = document.getElementById('addCompanyModal');
        var editCompanyModal = document.getElementById('editCompanyModal');

        // Get buttons
        var openAddModalBtn = document.getElementById('openAddModalBtn');
        var closeAddCompanyModal = document.getElementById('closeAddCompanyModal');
        var closeEditCompanyModal = document.getElementById('closeEditCompanyModal');

        // Open Add Company modal
        openAddModalBtn.onclick = function () {
            addCompanyModal.style.display = 'block';
        }

        // Close modals when close button is clicked
        closeAddCompanyModal.onclick = function () {
            addCompanyModal.style.display = 'none';
        }
        closeEditCompanyModal.onclick = function () {
            editCompanyModal.style.display = 'none';
        }

        // Function to open Edit Company modal and populate fields
        function openEditCompanyModal(company) {
            document.getElementById('editCompanyId').value = company.company_id;
            document.getElementById('editImagePreview').src = company.company_image ? `../../uploads/company/${company.company_image}` : '../../img/user.png';
            document.getElementById('editCompanyName').value = company.company_name;
            document.getElementById('editCompanyFirstname').value = company.company_rep_firstname;
            document.getElementById('editCompanyMiddle').value = company.company_rep_middle;
            document.getElementById('editCompanyLastname').value = company.company_rep_lastname;
            document.getElementById('editCompanyEmail').value = company.company_email;
            document.getElementById('editCompanyPosition').value = company.company_rep_position;
            document.getElementById('editCompanyContact').value = company.company_number;
            document.getElementById('editCompanyAddress').value = company.company_address;

            // Show the edit modal
            editCompanyModal.style.display = 'block';
        }

        // Preview function for Add Company Image
        function previewAddImage() {
            const imageInput = document.getElementById('companyImage');
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

        // Preview function for Edit Company Image
        function previewEditImage() {
            const imageInput = document.getElementById('editCompanyImage');
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
            <?php if (isset($_SESSION['add_company_success'])): ?>
                showModal('addCompanySuccessModal');
                <?php unset($_SESSION['add_company_success']); ?>
            <?php elseif (isset($_SESSION['edit_company_success'])): ?>
                showModal('editCompanySuccessModal');
                <?php unset($_SESSION['edit_company_success']); ?>
            <?php endif; ?>
        }


        function validateAddPassword() {
            var password = document.getElementById("companyPassword").value;
            var confirmPassword = document.getElementById("companyConfirmPassword").value;

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }
            return true;
        }

        function validateEditPassword() {
            var password = document.getElementById("editcompanyPassword").value;
            var confirmPassword = document.getElementById("editcompanyConfirmPassword").value;

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
        function openDeleteModal(companyId) {
            document.getElementById("delete-company-id").value = companyId; // Store companyId in hidden field
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
            const companyId = document.getElementById("delete-company-id").value; // Get companyId from hidden input
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "./others/delete_company.php", true);
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

            xhr.send("id=" + companyId);

            closeDeleteModal();
        }
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
            <input type="hidden" id="delete-company-id" value="">
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
            <h2>Company Delete Successfully!</h2>
            <p>company has been deleted successfully by <span
                    style="color: #095d40; font-size: 20px;"><?php echo $_SESSION['full_name']; ?>!</span></p>
            <button class="proceed-btn" onclick="closeDeleteSuccessModal('deleteSuccessModal')">Close</button>
        </div>
    </div>
    <script src="../js/script.js"></script>
    <script src="../../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>