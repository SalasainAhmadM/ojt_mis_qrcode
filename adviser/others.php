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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser - Others</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

<body>
    <div class="header">
        <i class="fas fa-school"></i>
        <div class="school-name">S.Y. 2024-2025 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span
                style="color: #095d40;">|</span>
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
                    <li><a href="./company/company-feedback.php">Company List</a></li>
                    <li><a href="./company/company-intern-feedback.php">Intern Feedback</a></li>
                </ul>
            </li>
            <li>
                <a href="attendance.php">
                    <i class="fa-regular fa-clock"></i>
                    <span class="link_name">Attendance</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="attendance.php">Attendance</a></li>
                </ul>
            </li>
            <li>
                <a href="announcement.php">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span class="link_name">Announcement</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="announcemnet.php">Announcement</a></li>
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
            <!-- <li>
                <a href="others.php" class="active">
                    <i class="fa-solid fa-ellipsis-h"></i>
                    <span class="link_name">Others</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="others.php">Others</a></li>
                </ul>
            </li> -->
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
            <i class="fas fa-bars bx-menu"></i>
        </div>

        <form class="form-container-header">
            <div style="padding: 10px;" class="form-section-header">
                <label style="color: #a6a6a6">Departments</label>
            </div>
            <div style="padding: 10px;" class="form-section-header">
                <label style="color: #a6a6a6">Course and Section</label>
            </div>
        </form>
        <div class="form-container-others">
            <!-- Departments -->
            <div class="form-section-others">
                <button class="btn-others" type="button" id="openAddDepartmentModal">Add Department</button>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%;text-align: center">Department Name</th>
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
                                    echo "<td style='width: 30%;'>" . htmlspecialchars($row['department_name']) . "</td>";
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
            <div class="form-section-others">
                <button class="btn-others" type="button" id="openAddCourseModal">Add Course and Section</button>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%;text-align: center">Course and Section Name</th>
                            <th style="text-align: center" class="action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch course sections
                        $query = "SELECT * FROM course_sections ORDER BY course_section_name ASC";
                        if ($stmt = $database->prepare($query)) {
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td style='width: 30%;'>" . htmlspecialchars($row['course_section_name']) . "</td>";
                                    echo '<td class="action">
                                <button class="action-icon edit-btn" data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['course_section_name']) . '" onclick="openEditCourseSectionModal(' . $row['id'] . ', \'' . htmlspecialchars($row['course_section_name']) . '\')">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="action-icon delete-btn" data-id="' . $row['id'] . '" onclick="deleteCourseSection(' . $row['id'] . ')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td style='text-align: center;' colspan='2'>No courses or sections found.</td></tr>";
                            }
                            $stmt->close();
                        }
                        ?>
                    </tbody>
                </table>
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
                <button type="submit" class="modal-btn">Update Course and Section</button>
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
            <?php elseif (isset($_SESSION['department_delete'])): ?>
                showModal('departmentDeleteModal');
                <?php unset($_SESSION['department_delete']); ?>
            <?php elseif (isset($_SESSION['course_section_delete'])): ?>
                showModal('courseSectionDeleteModal');
                <?php unset($_SESSION['course_section_delete']); ?>
            <?php elseif (isset($_SESSION['department_edit_success'])): ?>
                showModal('departmentEditModal');
                <?php unset($_SESSION['department_edit_success']); ?>
            <?php elseif (isset($_SESSION['course_section_edit_success'])): ?>
                showModal('courseSectionEditModal');
                <?php unset($_SESSION['course_section_edit_success']); ?>
            <?php elseif (isset($_SESSION['error_1'])): ?>
                showModal('departmentDuplicateModal');
                <?php unset($_SESSION['error_1']); ?>
            <?php elseif (isset($_SESSION['error_2'])): ?>
                showModal('courseSectionDuplicateModal');
                <?php unset($_SESSION['error_2']); ?>
            <?php elseif (isset($_SESSION['error_try'])): ?>
                showModal('TryAgainModal');
                <?php unset($_SESSION['error_try']); ?>
            <?php endif; ?>
        };
        // Get modals
        var addDepartmentModal = document.getElementById("addDepartmentModal");
        var addCourseModal = document.getElementById("addCourseModal");

        // Get open modal buttons
        var openAddDepartmentModalBtn = document.getElementById("openAddDepartmentModal");
        var openAddCourseModalBtn = document.getElementById("openAddCourseModal");

        // Get close buttons
        var closeAddDepartmentModalBtn = document.getElementById("closeAddDepartmentModal");
        var closeAddCourseModalBtn = document.getElementById("closeAddCourseModal");

        // Open modals
        openAddDepartmentModalBtn.onclick = function () {
            addDepartmentModal.style.display = "block";
        }

        openAddCourseModalBtn.onclick = function () {
            addCourseModal.style.display = "block";
        }

        // Close modals
        closeAddDepartmentModalBtn.onclick = function () {
            addDepartmentModal.style.display = "none";
        }

        closeAddCourseModalBtn.onclick = function () {
            addCourseModal.style.display = "none";
        }

        // Close modal if clicked outside
        window.onclick = function (event) {
            if (event.target == addDepartmentModal) {
                addDepartmentModal.style.display = "none";
            } else if (event.target == addCourseModal) {
                addCourseModal.style.display = "none";
            }
        }
        document.querySelectorAll('.edit-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                if (this.closest('.form-section-others').classList.contains('department-section')) {
                    // Open department edit modal
                    document.getElementById('editDepartmentName').value = name;
                    document.getElementById('editDepartmentId').value = id;
                    showModal('editDepartmentModal');
                } else {
                    // Open course section edit modal
                    document.getElementById('editCourseName').value = name;
                    document.getElementById('editCourseId').value = id;
                    showModal('editCourseModal');
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
        function openEditCourseSectionModal(courseSectionId, courseSectionName) {
            document.getElementById("editCourseSectionId").value = courseSectionId;
            document.getElementById("editCourseSectionName").value = courseSectionName;
            document.getElementById("editCourseSectionModal").style.display = "block";
        }

        // Close Edit Course Section Modal
        document.getElementById("closeEditCourseSectionModal").onclick = function () {
            document.getElementById("editCourseSectionModal").style.display = "none";
        };

        // Close modal if clicked outside
        window.onclick = function (event) {
            if (event.target == document.getElementById("editDepartmentModal")) {
                document.getElementById("editDepartmentModal").style.display = "none";
            } else if (event.target == document.getElementById("editCourseSectionModal")) {
                document.getElementById("editCourseSectionModal").style.display = "none";
            }
        }

        // For deleting a department or course section
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
    </script>
    <script src="./js/scripts.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>