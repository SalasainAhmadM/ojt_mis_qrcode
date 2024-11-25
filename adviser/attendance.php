<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../index.php");
    exit();
}

// Fetch adviser details
$adviser_id = $_SESSION['user_id'];
$query = "SELECT * FROM adviser WHERE adviser_id = ?";
$stmt = $database->prepare($query);
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();

$adviser = $result->num_rows > 0 ? $result->fetch_assoc() : [
    'adviser_firstname' => 'Unknown',
    'adviser_middle' => 'U',
    'adviser_lastname' => 'User',
    'adviser_email' => 'unknown@wmsu.edu.ph'
];
$stmt->close();

// Fetch all course_sections for the dropdown that are under the specific adviser
$query = "SELECT * FROM course_sections WHERE adviser_id = ?";
$course_sections = [];
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $adviser_id); // Bind the adviser ID parameter
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $course_sections[] = $row;
    }
    $stmt->close();
}
// Capture the selected course_section and search query
$selected_course_section = isset($_GET['course_section']) ? $_GET['course_section'] : '';
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Function to get paginated and searched students
function getStudents($database, $selected_course_section, $search_query, $adviser_id, $limit = 5)
{
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $total_students_query = "SELECT COUNT(*) AS total FROM student WHERE adviser = ?";
    $students_query = "
    SELECT student.*, 
           CONCAT(adviser.adviser_firstname, ' ', adviser.adviser_middle, '. ', adviser.adviser_lastname) AS adviser_fullname,
           CONCAT(address.address_barangay, ', ', address.address_street) AS full_address,
           company.company_name,
           course_sections.course_section_name,
           departments.department_name,
           attendance_earliest.time_in AS first_time_in,
           attendance_latest.time_out AS last_time_out,
           IFNULL(attendance_latest.ojt_hours, 0) AS ojt_hours
    FROM student
    LEFT JOIN adviser ON student.adviser = adviser.adviser_id
    LEFT JOIN address ON student.student_address = address.address_id
    LEFT JOIN company ON student.company = company.company_id
    LEFT JOIN course_sections ON student.course_section = course_sections.id
    LEFT JOIN departments ON student.department = departments.department_id
    -- Earliest time_in of the day
    LEFT JOIN (
        SELECT student_id, MIN(time_in) AS time_in 
        FROM attendance 
        WHERE DATE(time_in) = CURDATE()
        GROUP BY student_id
    ) AS attendance_earliest ON student.student_id = attendance_earliest.student_id
    -- Latest time_out of the day
    LEFT JOIN (
        SELECT student_id, MAX(time_out) AS time_out, 
               (TIMESTAMPDIFF(SECOND, MIN(time_in), MAX(time_out)) / 3600) AS ojt_hours
        FROM attendance 
        WHERE DATE(time_in) = CURDATE()
        GROUP BY student_id
    ) AS attendance_latest ON student.student_id = attendance_latest.student_id
    WHERE student.adviser = ?";

    if (!empty($selected_course_section)) {
        $total_students_query .= " AND course_section = ?";
        $students_query .= " AND student.course_section = ?";
    }

    if (!empty($search_query)) {
        $total_students_query .= " AND (student_firstname LIKE ? OR student_middle LIKE ? OR student_lastname LIKE ?)";
        $students_query .= " AND (student_firstname LIKE ? OR student_middle LIKE ? OR student_lastname LIKE ?)";
    }

    $students_query .= " ORDER BY student.student_id LIMIT ? OFFSET ?";

    // Total students count
    if ($stmt = $database->prepare($total_students_query)) {
        $params = [$adviser_id];
        if (!empty($selected_course_section))
            $params[] = $selected_course_section;
        if (!empty($search_query))
            $params = array_merge($params, [$search_query, $search_query, $search_query]);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);

        $stmt->execute();
        $result = $stmt->get_result();
        $total_students = $result->fetch_assoc()['total'];
        $stmt->close();
    }

    $total_pages = ceil($total_students / $limit);

    $students = [];
    if ($stmt = $database->prepare($students_query)) {
        $params = [$adviser_id];
        if (!empty($selected_course_section))
            $params[] = $selected_course_section;
        if (!empty($search_query))
            $params = array_merge($params, [$search_query, $search_query, $search_query]);
        $params = array_merge($params, [$limit, $offset]);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Format time_in and time_out
            $row['first_time_in'] = $row['first_time_in'] ? date("g:i a", strtotime($row['first_time_in'])) : 'N/A';
            $row['last_time_out'] = $row['last_time_out'] ? date("g:i a", strtotime($row['last_time_out'])) : 'N/A';

            // Format OJT hours
            if ($row['ojt_hours'] > 0) {
                $hours = floor($row['ojt_hours']);
                $minutes = round(($row['ojt_hours'] - $hours) * 60);
                $row['ojt_hours'] = ($hours > 0 ? $hours . 'hr' : '') . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
            } else {
                $row['ojt_hours'] = 'N/A';
            }

            // Determine attendance status
            if (is_null($row['last_time_out'])) {
                $row['status'] = '<span style="color: #095d40;">Timed-in</span>';
            } elseif (!is_null($row['last_time_out']) && $row['ojt_hours'] > 0) {
                $row['status'] = '<span style="color: red;">Timed-out</span>';
            } else {
                $row['status'] = '<span style="color: #8B0000;">Absent</span>';
            }

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
$pagination_data = getStudents($database, $selected_course_section, $search_query, $adviser_id);
$students = $pagination_data['students'];
$total_pages = $pagination_data['total_pages'];
$current_page = $pagination_data['current_page'];



?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser - Attendance</title>
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
                    <!-- <li><a href="./company/company-feedback.php">Company List</a></li> -->
                    <li><a href="./company/company-intern-feedback.php">Intern Feedback</a></li>
                </ul>
            </li>
            <li>
                <a href="attendance.php" class="active">
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
                <a href="others.php">
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
            <i style="z-index: 100;" class="fas fa-bars bx-menu"></i>
        </div>

        <div class="content-wrapper">

            <div class="header-box">
                <label style="color: #a6a6a6; margin-left: 5px;">Attendance</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <h2>Attendance -
                        <span style="color: #095d40"> Dec 12, 2024</span>

                    </h2>


                    <div class="filter-group">

                        <!-- Course Section Filter Form -->
                        <form method="GET" action="">
                            <select class="course-section-dropdown" name="course_section" id="course_section"
                                onchange="this.form.submit()">
                                <option value="">All Sections</option><?php foreach ($course_sections as $section): ?>
                                    <option value="<?php echo htmlspecialchars($section['id'], ENT_QUOTES); ?>" <?php echo $selected_course_section == $section['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($section['course_section_name'], ENT_QUOTES); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                        <!-- Date Picker Form for Date Navigation -->
                        <form method="GET" action="" class="date-picker-form">
                            <div class="search-bar-container">
                                <input type="date" class="search-bar" id="searchDate" name="day"
                                    value="<?php echo htmlspecialchars($selected_day); ?>"
                                    onchange="this.form.submit()">
                            </div>
                        </form>
                        <!-- Reset Button Form -->
                        <form method="GET" action="attendance.php">
                            <button type="submit" class="reset-bar-icon">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </form>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th class="image">Profile</th>
                                <th class="name">Full Name</th>
                                <!-- <th class="company">Company</th> -->
                                <th class="section">Section</th>
                                <th class="timein">Time-in</th>
                                <th class="timeout">Time-out</th>
                                <th class="duration">Duration</th>
                                <th class="status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td class="image">
                                            <img style="border-radius: 50%;"
                                                src="../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
                                                alt="Student Image">
                                        </td>
                                        <td class="name">
                                            <?php echo htmlspecialchars($student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']); ?>
                                        </td>
                                        <td class="section"><?php echo htmlspecialchars($student['course_section_name']); ?>
                                        </td>
                                        <td class="timein"><?php echo htmlspecialchars($student['first_time_in']); ?></td>
                                        <td class="timeout"><?php echo htmlspecialchars($student['last_time_out']); ?></td>
                                        <td class="duration"><?php echo htmlspecialchars($student['ojt_hours']); ?></td>
                                        <td class="status">
                                            <?php echo $student['status']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>



                    </table>

                    <!-- Display pagination links -->
                    <div class="pagination">
                        <?php renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Remark Modal -->
    <div id="remarkModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/notice-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 id="remarkTypeTitle" data-remark-type="">Remark</h2>
            <p id="remarkText">Loading remark...</p>
            <button class="proceed-btn" onclick="closeModal('remarkModal')">Close</button>
        </div>
    </div>


    <script>
        function openRemarkModal(studentId, remarkType) {
            const titleElement = document.getElementById('remarkTypeTitle');
            titleElement.innerText = remarkType;
            titleElement.setAttribute('data-remark-type', remarkType); // Set data attribute for CSS styling

            document.getElementById('remarkText').innerText = 'Loading remark...';

            // AJAX to fetch remark from the server
            fetch(`../company/fetch_remark.php?student_id=${studentId}&remark_type=${remarkType}`)
                .then(response => response.text())
                .then(remark => {
                    document.getElementById('remarkText').innerText = remark || 'No remark available';
                })
                .catch(() => {
                    document.getElementById('remarkText').innerText = 'Error loading remark';
                });

            // Display the modal
            document.getElementById('remarkModal').style.display = 'block';
        }

    </script>
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
    <script src="./js/scripts.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>