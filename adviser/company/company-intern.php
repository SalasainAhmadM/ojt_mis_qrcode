<?php
session_start();
require '../../conn/connection.php';

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
// Get the company ID from the URL
// $company_id = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;

// // Fetch students associated with this company ID
// $students = [];
// $query = "SELECT * FROM student WHERE company = ?";
// if ($stmt = $database->prepare($query)) {
//     $stmt->bind_param("i", $company_id);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     while ($row = $result->fetch_assoc()) {
//         $students[] = $row;
//     }
//     $stmt->close();
// }

// Get the company_id from query parameters
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';
$selected_course_section = isset($_GET['course_section']) ? $_GET['course_section'] : '';

// Function to get students based on company_id with pagination
function getStudentsByCompany($database, $company_id, $selected_course_section, $search_query, $limit = 5)
{
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Base query to count total students for pagination
    $total_students_query = "SELECT COUNT(*) AS total FROM student WHERE company = ?";

    // Query to fetch students
    $students_query = "
        SELECT student.*, 
               company.company_name,
               course_sections.course_section_name,
               departments.department_name
        FROM student 
        LEFT JOIN company ON student.company = company.company_id
        LEFT JOIN course_sections ON student.course_section = course_sections.id
        LEFT JOIN departments ON student.department = departments.department_id
        WHERE student.company = ?";

    // Add filters
    if (!empty($selected_course_section)) {
        $total_students_query .= " AND student.course_section = ?";
        $students_query .= " AND student.course_section = ?";
    }

    if (!empty($search_query)) {
        $total_students_query .= " AND (student.student_firstname LIKE ? OR student.student_lastname LIKE ?)";
        $students_query .= " AND (student.student_firstname LIKE ? OR student.student_lastname LIKE ?)";
    }

    // Add pagination to the students query
    $students_query .= " ORDER BY student.student_id LIMIT ? OFFSET ?";

    // Count total students for pagination
    if ($stmt = $database->prepare($total_students_query)) {
        if (!empty($selected_course_section) && !empty($search_query)) {
            $stmt->bind_param("iss", $company_id, $selected_course_section, $search_query);
        } elseif (!empty($selected_course_section)) {
            $stmt->bind_param("is", $company_id, $selected_course_section);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("iss", $company_id, $search_query, $search_query);
        } else {
            $stmt->bind_param("i", $company_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $total_students = $result->fetch_assoc()['total'];
        $stmt->close();
    }

    $total_pages = ceil($total_students / $limit);

    // Fetch students with pagination
    $students = [];
    if ($stmt = $database->prepare($students_query)) {
        if (!empty($selected_course_section) && !empty($search_query)) {
            $stmt->bind_param("issiii", $company_id, $selected_course_section, $search_query, $search_query, $limit, $offset);
        } elseif (!empty($selected_course_section)) {
            $stmt->bind_param("isii", $company_id, $selected_course_section, $limit, $offset);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("issii", $company_id, $search_query, $search_query, $limit, $offset);
        } else {
            $stmt->bind_param("iii", $company_id, $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }

    return [
        'students' => $students,
        'total_pages' => $total_pages,
        'current_page' => $page,
    ];
}

// Function to render pagination links
function renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query, $company_id)
{
    $search_query_encoded = htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES);
    $course_section_query_encoded = htmlspecialchars($_GET['course_section'] ?? '', ENT_QUOTES);

    if ($current_page > 1) {
        echo '<a href="?page=' . ($current_page - 1) . '&company_id=' . $company_id . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '" class="prev">Previous</a>';
    }

    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $active = $i == $current_page ? 'active' : '';
        echo '<a href="?page=' . $i . '&company_id=' . $company_id . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '" class="' . $active . '">' . $i . '</a>';
    }

    if ($current_page < $total_pages) {
        echo '<a href="?page=' . ($current_page + 1) . '&company_id=' . $company_id . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '" class="next">Next</a>';
    }
}

$pagination_data = getStudentsByCompany($database, $company_id, $selected_course_section, $search_query);
$students = $pagination_data['students'];
$total_pages = $pagination_data['total_pages'];
$current_page = $pagination_data['current_page'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser - Company Intern Profile</title>
    <link rel="icon" href="../../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- <link rel="stylesheet" href="../css/index.css"> -->
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/mobile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

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
            <img src="../../uploads/adviser/<?php echo !empty($adviser['adviser_image']) ? $adviser['adviser_image'] : 'user.png'; ?>"
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
                <a href="../index.php">
                    <i class="fa-solid fa-house"></i>
                    <span class="link_name">Home</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../index.php">Home</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="../interns.php">
                        <i class="fa-solid fa-user"></i>
                        <span class="link_name">Interns</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="../interns.php">Manage Interns</a></li>
                    <!-- <li><a href="../intern/intern-profile.php">Student Profile</a></li> -->
                    <li><a href="../intern/intern-reports.php">Intern Reports</a></li>
                </ul>
            </li>
            <li>
                <div style="background-color: #07432e;" class="iocn-link">
                    <a href="../company.php">
                        <i class="fa-regular fa-building"></i>
                        <span class="link_name">Company</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="../company.php">Manage Company</a></li>
                    <li><a href="company-intern.php">Company Interns</a></li>
                    <li><a href="company-feedback.php">Company List</a></li>
                    <li><a href="company-intern-feedback.php">Intern Feedback</a></li>
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
                <a href="../announcement.php">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span class="link_name">Announcement</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../announcemnet.php">Announcement</a></li>
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
                <a href="../setting.php">
                    <i class="fas fa-cog"></i>
                    <span class="link_name">Settings</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../setting.php">Settings</a></li>
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
                <div style="height: 600px;" class="whole-box">
                    <div class="header-group">
                        <h2>Student Details</h2>
                    </div>

                    <div class="filter-group">
                        <form method="GET" action="">
                            <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                            <input type="hidden" name="course_section"
                                value="<?php echo htmlspecialchars($selected_course_section, ENT_QUOTES); ?>">
                            <div class="search-bar-container">
                                <input type="text" class="search-bar" name="search" placeholder="Search Student"
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="search-bar-icon"><i class="fa fa-search"></i></button>
                            </div>
                        </form>

                        <form method="GET" action="company-intern.php">
                            <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                            <button type="submit" class="reset-bar-icon"><i class="fa fa-times-circle"></i></button>
                        </form>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th class="image">Profile</th>
                                <th class="name">Full Name</th>
                                <th class="wmsu_id">WMSU ID</th>
                                <th class="email">Email</th>
                                <th class="section">Section</th>
                                <th class="department">Department</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td class="image"><img style="border-radius: 50%;"
                                                src="../../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
                                                alt="Student Image"></td>
                                        <td class="name">
                                            <?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '. ' . $student['student_lastname']; ?>
                                        </td>
                                        <td class="wmsu_id"><?php echo $student['wmsu_id']; ?></td>
                                        <td class="email"><?php echo $student['student_email']; ?></td>
                                        <td class="section"><?php echo $student['course_section_name']; ?></td>
                                        <td class="department"><?php echo $student['department_name']; ?></td>
                                        <td class="action">
                                            <button class="action-icon edit-btn"
                                                onclick="openEditStudentModal(<?php echo htmlspecialchars(json_encode($student), ENT_QUOTES); ?>)"><i
                                                    class="fa-solid fa-pen-to-square"></i></button>
                                            <button class="action-icon delete-btn"
                                                onclick="confirmDelete(<?php echo $student['student_id']; ?>)"><i
                                                    class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No Interns for this company</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Pagination Links -->
                    <div class="pagination">
                        <?php renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query, $company_id); ?>
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
                <button class="confirm-btn" onclick="logout2()">Confirm</button>
                <button class="cancel-btn" onclick="closeModal2('logoutModal')">Cancel</button>
            </div>
        </div>
    </div>
    <script src="../js/scripts.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>