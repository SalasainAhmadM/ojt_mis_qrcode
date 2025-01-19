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

// Fetch all course_sections for the dropdown
$query = "SELECT * FROM course_sections";
$course_sections = [];
if ($stmt = $database->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $course_sections[] = $row;
    }
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

// Fetch all companys for the dropdown
$query = "SELECT * FROM company ";
$companies = [];
if ($stmt = $database->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    $stmt->close();
}

// Fetch all address for the dropdown
$query = "SELECT * FROM address ";
$addresses = [];
if ($stmt = $database->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    $stmt->close();
}
// Fetch all address for the dropdown
$query = "SELECT * FROM street ";
$streets = [];
if ($stmt = $database->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $streets[] = $row;
    }
    $stmt->close();
}
// include './others/filter_student.php';
// Capture the selected course_section and search query
$selected_course_section = isset($_GET['course_section']) ? $_GET['course_section'] : '';
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Function to get paginated and searched students
// Updated function to get students with adviser full name
function getStudents($database, $selected_course_section, $search_query, $limit = 5)
{
    // Determine current page number for pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Base query for counting total students (for pagination)
    $total_students_query = "SELECT COUNT(*) AS total FROM student WHERE 1";

    // Base query for fetching students with adviser full name
    $students_query = "
    SELECT student.*, 
           CONCAT(adviser.adviser_firstname, ' ', adviser.adviser_middle, '. ', adviser.adviser_lastname) AS adviser_fullname,
           CONCAT(address.address_barangay, ', ', street.name) AS full_address,
           company.company_name,
           course_sections.course_section_name,
           departments.department_name
    FROM student 
    LEFT JOIN adviser ON student.adviser = adviser.adviser_id
    LEFT JOIN address ON student.student_address = address.address_id
    LEFT JOIN street ON student.street = street.street_id
    LEFT JOIN company ON student.company = company.company_id
    LEFT JOIN course_sections ON student.course_section = course_sections.id
    LEFT JOIN departments ON student.department = departments.department_id
    WHERE 1";


    // Add course_section filter if selected
    if (!empty($selected_course_section)) {
        $total_students_query .= " AND course_section = ?";
        $students_query .= " AND student.course_section = ?";
    }

    // Add search filter if applied
    if (!empty($search_query)) {
        $total_students_query .= " AND (student_firstname LIKE ? OR student_middle LIKE ? OR student_lastname LIKE ?)";
        $students_query .= " AND (student_firstname LIKE ? OR student_middle LIKE ? OR student_lastname LIKE ?)";
    }

    // Add pagination to the students query
    $students_query .= " ORDER BY student.student_id LIMIT ? OFFSET ?";

    // Prepare and execute the total students query for pagination
    if ($stmt = $database->prepare($total_students_query)) {
        // Bind parameters based on course_section and search query
        if (!empty($selected_course_section) && !empty($search_query)) {
            $stmt->bind_param("ssss", $selected_course_section, $search_query, $search_query, $search_query);
        } elseif (!empty($selected_course_section)) {
            $stmt->bind_param("s", $selected_course_section);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("sss", $search_query, $search_query, $search_query);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total_students = $result->fetch_assoc()['total'];
        $stmt->close();
    }

    // Calculate total pages
    $total_pages = ceil($total_students / $limit);

    // Prepare and execute the students query with pagination
    $students = [];
    if ($stmt = $database->prepare($students_query)) {
        // Bind parameters based on course_section, search query, and pagination
        if (!empty($selected_course_section) && !empty($search_query)) {
            $stmt->bind_param("ssssii", $selected_course_section, $search_query, $search_query, $search_query, $limit, $offset);
        } elseif (!empty($selected_course_section)) {
            $stmt->bind_param("sii", $selected_course_section, $limit, $offset);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("sssii", $search_query, $search_query, $search_query, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }

    // Return paginated data and pagination information
    return [
        'students' => $students,
        'total_pages' => $total_pages,
        'current_page' => $page,
    ];
}


// Function to render pagination links with course_section and search persistence
function renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query)
{
    $search_query_encoded = htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES);
    $course_section_query_encoded = htmlspecialchars($_GET['course_section'] ?? '', ENT_QUOTES);

    // Display Previous button
    if ($current_page > 1) {
        echo '<a href="?page=' . ($current_page - 1) . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '" class="prev">Previous</a>';
    }

    // Display page numbers (only show 5 page links)
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $active = $i == $current_page ? 'active' : '';
        echo '<a href="?page=' . $i . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '" class="' . $active . '">' . $i . '</a>';
    }

    // Display Next button
    if ($current_page < $total_pages) {
        echo '<a href="?page=' . ($current_page + 1) . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '" class="next">Next</a>';
    }
}
// Fetch students with pagination, course_section, and search functionality
$pagination_data = getStudents($database, $selected_course_section, $search_query);
$students = $pagination_data['students'];
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
    <title>Admin - Student Management</title>
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
            <img style=""
                src="../../uploads/admin/<?php echo !empty($admin['admin_image']) ? $admin['admin_image'] : 'user.png'; ?>"
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
                <label style="color: #a6a6a6;">Student Management</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <div class="header-group">
                        <h2>Student Details</h2>
                        <!-- <div class="button-container">
                            <button id="openAddModalBtn" class="add-btn">
                                <i class="fa-solid fa-plus"></i>Add Student
                            </button>
                        </div> -->
                    </div>

                    <div class="filter-group">
                        <!-- Course_section Filter Form -->
                        <form method="GET" action="">
                            <select class="dropdown" name="course_section" onchange="this.form.submit()">
                                <option value="">Select Section</option>
                                <?php foreach ($course_sections as $course_section): ?>
                                    <option value="<?php echo htmlspecialchars($course_section['id'], ENT_QUOTES); ?>" Use
                                        the course_section ID as the value -->
                                        <?php echo $selected_course_section == $course_section['id'] ? 'selected' : ''; ?>>
                                        <!-- Check selected by ID -->
                                        <?php echo htmlspecialchars($course_section['course_section_name'], ENT_QUOTES); ?>
                                        <!-- Display the course section name -->
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="search"
                                value="<?php echo htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : '', ENT_QUOTES); ?>">
                        </form>


                        <!-- Search Bar Form -->
                        <form method="GET" action="">
                            <input type="hidden" name="course_section"
                                value="<?php echo htmlspecialchars($selected_course_section, ENT_QUOTES); ?>">
                            <div class="search-bar-container">
                                <input type="text" class="search-bar" name="search" placeholder="Search Student"
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="search-bar-icon">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Reset Button Form -->
                        <form method="GET" action="student.php">
                            <button type="submit" class="reset-bar-icon">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </form>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th class="image">Profile</th>
                                <th>Full Name</th>
                                <th>WMSU ID</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th>Adviser</th>
                                <th>Company</th>
                                <th>Section</th>
                                <th>Department</th>
                                <th>School Year</th>
                                <th>Address</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
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
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td class="image">
                                            <img style="border-radius: 50%;"
                                                src="../../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
                                                alt="student Image">
                                        </td>
                                        <td
                                            title="<?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>">
                                            <?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>
                                        </td>
                                        <td title="<?php echo $student['wmsu_id']; ?>">
                                            <?php echo $student['wmsu_id']; ?>
                                        </td>
                                        <td title="<?php echo $student['student_email']; ?>">
                                            <?php echo $student['student_email']; ?>
                                        </td>
                                        <td title="<?php echo $student['contact_number']; ?>">
                                            <?php echo $student['contact_number']; ?>
                                        </td>
                                        <td title="<?php echo $student['adviser_fullname']; ?>">
                                            <?php echo $student['adviser_fullname']; ?>
                                        </td>
                                        <td title="<?php echo $student['company_name']; ?>">
                                            <?php echo $student['company_name']; ?>
                                        </td>
                                        <td title="<?php echo $student['course_section_name']; ?>">
                                            <?php echo $student['course_section_name']; ?>
                                        </td>
                                        <td title="<?php echo $student['department_name']; ?>">
                                            <?php echo $student['department_name']; ?>
                                        </td>
                                        <td title="<?php echo $student['batch_year']; ?>">
                                            <?php echo $student['batch_year']; ?>
                                        </td>
                                        <td title="<?php echo $student['full_address']; ?>">
                                            <?php echo $student['full_address']; ?>
                                        </td>
                                        <td class="action">
                                            <button class="action-icon edit-btn"
                                                onclick="openEditStudentModal(<?php echo htmlspecialchars(json_encode($student), ENT_QUOTES); ?>)">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <button class="action-icon delete-btn"
                                                onclick="openDeleteModal(<?php echo $student['student_id']; ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>

                    <!-- Display pagination links -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query); ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
    </section>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content-bigger">
            <span class="close" id="closeEditStudentModal">&times;</span>
            <h2 class="modal-title">Edit Student</h2>

            <form action="./others/edit_student.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="editStudentId" name="student_id">
                <!-- Profile Image and Preview -->
                <div class="profile-img-container">
                    <label for="editStudentImage">
                        <img id="editImagePreview" src="" alt="Profile Preview" class="profile-preview-img" />
                    </label>
                    <input type="file" id="editStudentImage" name="student_image" accept="image/*"
                        onchange="previewEditImage()" style="display: none;">
                    <p class="profile-img-label">Click to upload image</p>
                </div>

                <!-- Full Name Row -->
                <div class="input-group-row">
                    <div class="input-group-fn">
                        <label for="editStudentFirstname">First Name</label>
                        <input type="text" id="editStudentFirstname" name="student_firstname" required>
                    </div>
                    <div class="input-group-mi">
                        <label id="mi_edit" for="editStudentMiddle">M.I.</label>
                        <input type="text" id="editStudentMiddle" name="student_middle">
                    </div>
                    <div class="input-group-ln">
                        <label for="editStudentLastname">Last Name</label>
                        <input type="text" id="editStudentLastname" name="student_lastname" required>
                    </div>
                </div>

                <!-- Email, Contact, Adviser Row -->
                <div class="input-group-row">
                    <div class="input-group">
                        <label for="editStudentWmsuId">WMSU ID</label>
                        <input type="text" id="editStudentWmsuId" name="wmsu_id" required>
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentEmail">Email</label>
                        <input type="email" id="editStudentEmail" name="student_email" required>
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentContact">Contact Number</label>
                        <input type="text" id="editStudentContact" name="contact_number" required maxlength="13"
                            oninput="limitInput(this)">
                    </div>

                </div>

                <div class="input-group-row">
                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentSection">Section</label>
                        <select type="text" id="editStudentSection" name="course_section_id" class="input-field"
                            onchange="fetchAdviser()" required>
                            <option value="">Select Section</option>
                            <?php foreach ($course_sections as $course_section): ?>
                                <option value="<?php echo htmlspecialchars($course_section['id'], ENT_QUOTES); ?>" <?php if ($course_section['id'] == $student['course_section'])
                                        echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($course_section['course_section_name'], ENT_QUOTES); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentAdviser">Adviser</label>
                        <input type="hidden" id="editAdviserId" name="adviser_id">
                        <input type="text" id="editStudentAdviser" readonly>
                    </div>


                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentCompany">Company</label>
                        <select type="text" id="editStudentCompany" name="company" class="input-field" required>
                            <option value="">Select Company</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo htmlspecialchars($company['company_id'], ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($company['company_name'], ENT_QUOTES); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="editOJTtype">OJT Type</label>

                        <select type="text" id="editOJTtype" name="ojt_type">
                            <option disabled>Select OJT Typer</option>
                            <option value="Field-Based" <?php if ($student['ojt_type'] == 'Field-Based')
                                echo 'selected'; ?>>
                                Field-Based
                            </option>
                            <option value="Project-Based" <?php if ($student['ojt_type'] == 'Project-Based')
                                echo 'selected'; ?>>
                                Project-Based
                            </option>

                        </select>
                    </div>
                </div>

                <!-- Batch Year and Address Row -->
                <div class="input-group-row">

                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentDepartment">Department</label>
                        <select type="text" id="editStudentDepartment" name="student_department" class="input-field"
                            required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department['department_id'], ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($department['department_name'], ENT_QUOTES); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group" style="width: 50%;">
                        <label for="editStudentBatchYear">School Year</label>
                        <select type="text" id="editStudentBatchYear" name="batch_year">
                            <option disabled>Select School Year</option>
                            <option value="2020-2021" <?php if ($student['batch_year'] == '2020-2021')
                                echo 'selected'; ?>>
                                2020-2021
                            </option>
                            <option value="2021-2022" <?php if ($student['batch_year'] == '2021-2022')
                                echo 'selected'; ?>>
                                2021-2022
                            </option>
                            <option value="2022-2023" <?php if ($student['batch_year'] == '2022-2023')
                                echo 'selected'; ?>>
                                2022-2023
                            </option>
                            <option value="2023-2024" <?php if ($student['batch_year'] == '2023-2024')
                                echo 'selected'; ?>>
                                2023-2024
                            </option>
                            <option value="2024-2025" <?php if ($student['batch_year'] == '2024-2025')
                                echo 'selected'; ?>>
                                2024-2025
                            </option>
                            <option value="2025-2026" <?php if ($student['batch_year'] == '2025-2026')
                                echo 'selected'; ?>>
                                2025-2026
                            </option>
                        </select>
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentAddress">Barangay</label>
                        <select type="text" id="editStudentAddress" name="student_address" class="input-field" required>
                            <option value="">Select Barangay</option>
                            <?php foreach ($addresses as $address): ?>
                                <option value="<?php echo htmlspecialchars($address['address_id'], ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($address['address_barangay'], ENT_QUOTES); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentStreet">Street</label>
                        <select type="text" id="editStudentStreet" name="student_street" class="input-field" required>
                            <option value="">Select Street</option>
                            <?php foreach ($streets as $street): ?>
                                <option value="<?php echo htmlspecialchars($street['street_id'], ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($street['name'], ENT_QUOTES); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="modal-btn">Save Changes</button>
            </form>
        </div>
    </div>



    <!-- Success Modal for Editing Student -->
    <div id="editStudentSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Student Updated Successfully!</h2>
            <p>The student details have been updated successfully!</p>
            <button class="proceed-btn" onclick="closeModal('editStudentSuccessModal')">Close</button>
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



    <script>
        function fetchAdviser() {
            const sectionId = document.getElementById('editStudentSection').value;

            // If no section is selected, reset the adviser field
            if (sectionId === '') {
                document.getElementById('editStudentAdviser').value = '';
                document.getElementById('editAdviserId').value = ''; // Reset adviser ID
                return;
            }

            // Send an AJAX request to fetch the adviser for the selected course section
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'get_adviser.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById('editStudentAdviser').value = response.adviser_fullname;
                        document.getElementById('editAdviserId').value = response.adviser_id; // Store adviser ID
                    } else {
                        document.getElementById('editStudentAdviser').value = 'Adviser not found';
                        document.getElementById('editAdviserId').value = ''; // Reset adviser ID if not found
                    }
                }
            };

            xhr.send('course_section_id=' + encodeURIComponent(sectionId)); // Sending section ID to fetch adviser
        }

        var editStudentModal = document.getElementById('editStudentModal');

        // Close modal function
        closeEditStudentModal.onclick = function () {
            editStudentModal.style.display = 'none';
        }

        // Function to open Edit Student modal and populate fields
        function openEditStudentModal(student) {
            document.getElementById('editStudentId').value = student.student_id;
            document.getElementById('editStudentWmsuId').value = student.wmsu_id;
            document.getElementById('editOJTtype').value = student.ojt_type;
            document.getElementById('editImagePreview').src = student.student_image ? `../../uploads/student/${student.student_image}` : '../../img/user.png';
            document.getElementById('editStudentFirstname').value = student.student_firstname;
            document.getElementById('editStudentMiddle').value = student.student_middle;
            document.getElementById('editStudentLastname').value = student.student_lastname;
            document.getElementById('editStudentEmail').value = student.student_email;
            document.getElementById('editStudentContact').value = student.contact_number;
            document.getElementById('editStudentSection').value = student.course_section;
            document.getElementById('editStudentAdviser').value = student.adviser_fullname;
            document.getElementById('editStudentBatchYear').value = student.batch_year;
            document.getElementById('editStudentAddress').value = student.student_address;
            document.getElementById('editStudentStreet').value = student.street;

            // Check if student has an adviser
            if (student.adviser_id) {
                document.getElementById('editAdviserId').value = student.adviser_id;
                document.getElementById('editStudentAdviser').value = student.adviser_fullname;
            } else {
                document.getElementById('editAdviserId').value = ''; // Clear if no adviser
                document.getElementById('editStudentAdviser').value = student.adviser_fullname; // Placeholder if no adviser
            }

            // Set current company in the dropdown
            const companyDropdown = document.getElementById('editStudentCompany');
            const companyOption = Array.from(companyDropdown.options).find(option => option.text === student.company_name);
            companyDropdown.value = companyOption ? companyOption.value : '';

            // Set current department in the dropdown
            const departmentDropdown = document.getElementById('editStudentDepartment');
            const departmentOption = Array.from(departmentDropdown.options).find(option => option.text === student.department_name);
            departmentDropdown.value = departmentOption ? departmentOption.value : '';

            // Set current address in the dropdown
            // const addressDropdown = document.getElementById('editStudentAddress');
            // const addressOption = Array.from(addressDropdown.options).find(option => option.text === student.full_address);
            // addressDropdown.value = addressOption ? addressOption.value : '';

            // Show the edit modal
            editStudentModal.style.display = 'block';
        }



        // Preview function for Add Student Image
        function previewAddImage() {
            const imageInput = document.getElementById('studentImage');
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

        // Preview function for Edit Student Image
        function previewEditImage() {
            const imageInput = document.getElementById('editStudentImage');
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
            <?php if (isset($_SESSION['edit_student_success'])): ?>
                showModal('editStudentSuccessModal');
                <?php unset($_SESSION['edit_student_success']); ?>
            <?php elseif (isset($_SESSION['error'])): ?>
                showModal('TryAgainModal');
                <?php unset($_SESSION['error']); ?>
            <?php elseif (isset($_SESSION['error2'])): ?>
                showModal('TryAgain2Modal');
                <?php unset($_SESSION['error2']); ?>
            <?php endif; ?>

        }

        function openDeleteModal(studentId) {
            document.getElementById("delete-student-id").value = studentId; // Store studentId in hidden field
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
            const studentId = document.getElementById("delete-student-id").value; // Get studentId from hidden input
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "./others/delete_student.php", true);
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

            xhr.send("id=" + studentId);

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
            <input type="hidden" id="delete-student-id" value="">
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
            <h2>Student Delete Successfully!</h2>
            <p>student has been deleted successfully by <br> <span
                    style="color: #095d40; font-size: 20px;"><?php echo $_SESSION['full_name']; ?>!</span></p>
            <button class="proceed-btn" onclick="closeDeleteSuccessModal('deleteSuccessModal')">Close</button>
        </div>
    </div>
    <script src="../js/script.js"></script>
    <script src="../../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>