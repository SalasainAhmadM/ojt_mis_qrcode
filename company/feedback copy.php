<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch company details from the database
$company_id = $_SESSION['user_id'];
$query = "SELECT * FROM company WHERE company_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $company_id); // Bind parameters
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    if ($result->num_rows > 0) {
        $company = $result->fetch_assoc(); // Fetch company details
    } else {
        // Handle case where company is not found
        $company = [
            'company_name' => 'Unknown',
            'company_email' => 'unknown@wmsu.edu.ph'
        ];
    }
    $stmt->close(); // Close the statement
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
// Fetch students under the current company
$student_query = "SELECT * FROM student WHERE company = ?";
$students = [];
if ($stmt = $database->prepare($student_query)) {
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $student_result = $stmt->get_result();

    if ($student_result->num_rows > 0) {
        while ($row = $student_result->fetch_assoc()) {
            $students[] = $row; // Add each student to the array
        }
    }
    $stmt->close();
}
// Fetch students and their course section names
$student_query = "SELECT student.*, course_sections.course_section_name, student.generated_qr_code 
                  FROM student 
                  LEFT JOIN course_sections ON student.course_section = course_sections.id 
                  WHERE student.company = ?";
$students = [];
if ($stmt = $database->prepare($student_query)) {
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $student_result = $stmt->get_result();

    if ($student_result->num_rows > 0) {
        while ($row = $student_result->fetch_assoc()) {
            $students[] = $row; // Add each student to the array
        }
    }
    $stmt->close();
}

// Capture the selected course_section and search query
$selected_course_section = isset($_GET['course_section']) ? $_GET['course_section'] : '';
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Function to get paginated and searched students
// Updated function to get students with adviser full name
function getStudents($database, $selected_course_section, $search_query, $company_id, $limit = 5)
{
    // Determine the current page for pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Base query to count total students for pagination
    $total_students_query = "
    SELECT COUNT(*) AS total 
    FROM student 
    WHERE company = ?";

    // Base query to fetch students along with related data
    $students_query = "
SELECT student.*, 
       CONCAT(adviser.adviser_firstname, ' ', adviser.adviser_middle, '. ', adviser.adviser_lastname) AS adviser_fullname,
       CONCAT(address.address_barangay, ', ', address.address_street) AS full_address,
       company.company_name,
       course_sections.course_section_name,
       departments.department_name,
       COALESCE(SUM(attendance.ojt_hours), 0) AS total_ojt_hours,
       EXISTS(SELECT 1 FROM feedback WHERE feedback.student_id = student.student_id) AS feedback_exists
FROM student 
LEFT JOIN adviser ON student.adviser = adviser.adviser_id
LEFT JOIN address ON student.student_address = address.address_id
LEFT JOIN company ON student.company = company.company_id
LEFT JOIN course_sections ON student.course_section = course_sections.id
LEFT JOIN departments ON student.department = departments.department_id
LEFT JOIN attendance ON student.student_id = attendance.student_id
WHERE student.company = ?";


    // Add course section filter if selected
    if (!empty($selected_course_section)) {
        $total_students_query .= " AND student.course_section = ?";
        $students_query .= " AND student.course_section = ?";
    }

    // Add search query filter if provided
    if (!empty($search_query)) {
        $total_students_query .= " AND (student_firstname LIKE ? OR student_middle LIKE ? OR student_lastname LIKE ?)";
        $students_query .= " AND (student_firstname LIKE ? OR student_middle LIKE ? OR student_lastname LIKE ?)";
    }

    // Add pagination to the students query
    // $students_query .= " ORDER BY student.student_id LIMIT ? OFFSET ?";
    $students_query .= " GROUP BY student.student_id ORDER BY student.student_id LIMIT ? OFFSET ?";

    // Prepare and execute the total students query
    if ($stmt = $database->prepare($total_students_query)) {
        // Bind parameters dynamically based on filters
        if (!empty($selected_course_section) && !empty($search_query)) {
            $stmt->bind_param("issss", $company_id, $selected_course_section, $search_query, $search_query, $search_query);
        } elseif (!empty($selected_course_section)) {
            $stmt->bind_param("is", $company_id, $selected_course_section);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("isss", $company_id, $search_query, $search_query, $search_query);
        } else {
            $stmt->bind_param("i", $company_id);
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
        // Bind parameters dynamically based on filters and pagination
        if (!empty($selected_course_section) && !empty($search_query)) {
            $stmt->bind_param("issssii", $company_id, $selected_course_section, $search_query, $search_query, $search_query, $limit, $offset);
        } elseif (!empty($selected_course_section)) {
            $stmt->bind_param("isii", $company_id, $selected_course_section, $limit, $offset);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("isssii", $company_id, $search_query, $search_query, $search_query, $limit, $offset);
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

    // Return paginated data and pagination info
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

    // Display page numbers (only show 7 page links)
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $active = $i == $current_page ? 'active' : '';
        echo '<a href="?page=' . $i . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '" class="' . $active . '">' . $i . '</a>';
    }

    // Display Next button
    if ($current_page < $total_pages) {
        echo '<a href="?page=' . ($current_page + 1) . '&course_section=' . $course_section_query_encoded . '&search=' . $search_query_encoded . '" class="next">Next</a>';
    }
}

$company_id = $_SESSION['user_id']; // Retrieve company_id from session
$pagination_data = getStudents($database, $selected_course_section, $search_query, $company_id);
$students = $pagination_data['students'];
$total_pages = $pagination_data['total_pages'];
$current_page = $pagination_data['current_page'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company - Feedback</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>

    </style>
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
                <a href="feedback.php" class="active">
                    <i class="fa-regular fa-star"></i>
                    <span class="link_name">Feedback</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="feedback.php">Feedback</a></li>
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
                <a href="calendar.php">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span class="link_name">Schedule</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="calendar.php">Manage Schedule</a></li>
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

        <div class="content-wrapper">

            <div class="header-box">
                <label style="color: #a6a6a6; margin-left: 5px;">Feedback</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <h2>
                        Interns
                    </h2>
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
                        <form method="GET" action="intern.php">
                            <button type="submit" class="reset-bar-icon">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </form>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th class="image">Profile</th>
                                <th class="name">Name</th>
                                <th class="wmsu_id">Student ID</th>
                                <th class="section">Section</th>
                                <!-- <th>Email</th>
                                <th class="contact">Contact Number</th> -->
                                <th class="adviser">Adviser</th>
                                <th class="ojthours">OJT Hours</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="image">
                                        <img style="border-radius: 50%;"
                                            src="../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
                                            alt="student Image">
                                    </td>
                                    <td class="name">
                                        <?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>
                                    </td>
                                    <td class="wmsu_id"><?php echo $student['wmsu_id']; ?> </td>
                                    <td class="section"><?php echo $student['course_section_name']; ?></td>
                                    <!-- <td class="maxlength"><?php echo $student['student_email']; ?></td>
                                    <td class="contact"><?php echo $student['contact_number']; ?></td> -->
                                    <td class="adviser"><?php echo $student['adviser_fullname']; ?></td>
                                    <td class="ojt-hours" data-hours="<?php echo $student['total_ojt_hours']; ?>"></td>
                                    <td class="action">
                                        <button class="action-rate edit-btn"
                                            data-student-name="<?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>"
                                            data-student-email="<?php echo $student['student_email']; ?>"
                                            data-student-id="<?php echo $student['student_id']; ?>"
                                            data-has-feedback="<?php echo $student['feedback_exists'] ? 'true' : 'false'; ?>">
                                            <i class="fa-regular fa-star"></i>
                                        </button>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">There's no intern yet in this company.</td>
                            </tr>
                        <?php endif; ?>

                    </table>

                    <!-- Display pagination links -->
                    <div class="pagination">
                        <?php renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query); ?>
                    </div>


                </div>
            </div>
        </div>

    </section>
    <!-- Evaluation Modal -->
    <div id="evaluationModal" class="modal">
        <div class="modal-content-bigger">
            <h2 style="color: #000;">Student Performance Evaluation</h2>
            <form id="evaluationForm" action="submit_evaluation.php" method="POST">

                <input type="hidden" id="eval_student_id" name="student_id">

                <div class="evaluation-questions">
                    <p>Evaluate <strong><span id="eval_student_name"></span></strong>'s performance:</p>

                    <!-- Question 1 -->
                    <label>1. Demonstrates initiative in completing tasks.</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="question_1" value="Strongly Agree"> Strongly
                            Agree</label>
                        <label><input type="checkbox" name="question_1" value="Agree"> Agree</label>
                        <label><input type="checkbox" name="question_1" value="Neutral"> Neutral</label>
                        <label><input type="checkbox" name="question_1" value="Disagree"> Disagree</label>
                        <label><input type="checkbox" name="question_1" value="Strongly Disagree"> Strongly
                            Disagree</label>
                    </div>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="question_1" value="Strongly Agree"> Strongly
                            Agree</label>
                        <label><input type="checkbox" name="question_1" value="Agree"> Agree</label>
                        <label><input type="checkbox" name="question_1" value="Neutral"> Neutral</label>
                        <label><input type="checkbox" name="question_1" value="Disagree"> Disagree</label>
                        <label><input type="checkbox" name="question_1" value="Strongly Disagree"> Strongly
                            Disagree</label>
                    </div>
                    <!-- Question 2 -->
                    <label>2. Works well with others in a team environment.</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="question_2" value="Strongly Agree"> Strongly
                            Agree</label>
                        <label><input type="checkbox" name="question_2" value="Agree"> Agree</label>
                        <label><input type="checkbox" name="question_2" value="Neutral"> Neutral</label>
                        <label><input type="checkbox" name="question_2" value="Disagree"> Disagree</label>
                        <label><input type="checkbox" name="question_2" value="Strongly Disagree"> Strongly
                            Disagree</label>
                    </div>

                    <!-- Question 3 -->
                    <label>3. Demonstrates responsibility and accountability.</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="question_3" value="Strongly Agree"> Strongly
                            Agree</label>
                        <label><input type="checkbox" name="question_3" value="Agree"> Agree</label>
                        <label><input type="checkbox" name="question_3" value="Neutral"> Neutral</label>
                        <label><input type="checkbox" name="question_3" value="Disagree"> Disagree</label>
                        <label><input type="checkbox" name="question_3" value="Strongly Disagree"> Strongly
                            Disagree</label>
                    </div>

                    <!-- Question 4 -->
                    <label>4. Effectively manages time to meet deadlines.</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="question_4" value="Strongly Agree"> Strongly
                            Agree</label>
                        <label><input type="checkbox" name="question_4" value="Agree"> Agree</label>
                        <label><input type="checkbox" name="question_4" value="Neutral"> Neutral</label>
                        <label><input type="checkbox" name="question_4" value="Disagree"> Disagree</label>
                        <label><input type="checkbox" name="question_4" value="Strongly Disagree"> Strongly
                            Disagree</label>
                    </div>

                    <!-- Question 5 -->
                    <label>5. Communicates effectively in both written and verbal forms.</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="question_5" value="Strongly Agree"> Strongly
                            Agree</label>
                        <label><input type="checkbox" name="question_5" value="Agree"> Agree</label>
                        <label><input type="checkbox" name="question_5" value="Neutral"> Neutral</label>
                        <label><input type="checkbox" name="question_5" value="Disagree"> Disagree</label>
                        <label><input type="checkbox" name="question_5" value="Strongly Disagree"> Strongly
                            Disagree</label>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                    <button type="submit" class="confirm-btn">Submit Evaluation</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('evaluationModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Feedback Success Modal -->
    <div id="feedbackSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Feedback Submitted Successfully!</h2>
            <p>Thank you for submitting your feedback!</p>
            <button class="proceed-btn" onclick="closeModal('feedbackSuccessModal')">Close</button>
        </div>
    </div>
    <!-- Feedback Edit Success Modal -->
    <div id="editSuccessModal" class="modal" style="display: none;">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay></lottie-player>
            </div>
            <h2>Feedback Updated Successfully!</h2>
            <p>Thank you for updating the feedback!</p>
            <button class="proceed-btn" onclick="closeModal('editSuccessModal')">Close</button>
        </div>
    </div>
    <!-- Edit Feedback Modal -->
    <div id="editFeedbackModal" class="modal">
        <div class="modal-content-bigger">
            <h2 style="color: #000;">Edit Feedback for <span id="edit_student_name"></span></h2>
            <form id="editFeedbackForm" action="update_feedback.php" method="POST">
                <input type="hidden" id="edit_student_id" name="student_id">

                <div class="evaluation-questions">
                    <p>Edit <strong><span id="edit_student_name"></span></strong>'s performance feedback:</p>

                    <!-- Question 1 -->
                    <label>1. Demonstrates initiative in completing tasks.</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="question_1" value="100"> Strongly Agree</label>
                        <label><input type="radio" name="question_1" value="80"> Agree</label>
                        <label><input type="radio" name="question_1" value="60"> Neutral</label>
                        <label><input type="radio" name="question_1" value="40"> Disagree</label>
                        <label><input type="radio" name="question_1" value="20"> Strongly Disagree</label>
                    </div>

                    <!-- Question 2 -->
                    <label>2. Works well with others in a team environment.</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="question_2" value="100"> Strongly Agree</label>
                        <label><input type="radio" name="question_2" value="80"> Agree</label>
                        <label><input type="radio" name="question_2" value="60"> Neutral</label>
                        <label><input type="radio" name="question_2" value="40"> Disagree</label>
                        <label><input type="radio" name="question_2" value="20"> Strongly Disagree</label>
                    </div>

                    <!-- Question 3 -->
                    <label>3. Demonstrates responsibility and accountability.</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="question_3" value="100"> Strongly Agree</label>
                        <label><input type="radio" name="question_3" value="80"> Agree</label>
                        <label><input type="radio" name="question_3" value="60"> Neutral</label>
                        <label><input type="radio" name="question_3" value="40"> Disagree</label>
                        <label><input type="radio" name="question_3" value="20"> Strongly Disagree</label>
                    </div>

                    <!-- Question 4 -->
                    <label>4. Effectively manages time to meet deadlines.</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="question_4" value="100"> Strongly Agree</label>
                        <label><input type="radio" name="question_4" value="80"> Agree</label>
                        <label><input type="radio" name="question_4" value="60"> Neutral</label>
                        <label><input type="radio" name="question_4" value="40"> Disagree</label>
                        <label><input type="radio" name="question_4" value="20"> Strongly Disagree</label>
                    </div>

                    <!-- Question 5 -->
                    <label>5. Communicates effectively in both written and verbal forms.</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="question_5" value="100"> Strongly Agree</label>
                        <label><input type="radio" name="question_5" value="80"> Agree</label>
                        <label><input type="radio" name="question_5" value="60"> Neutral</label>
                        <label><input type="radio" name="question_5" value="40"> Disagree</label>
                        <label><input type="radio" name="question_5" value="20"> Strongly Disagree</label>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                    <button type="submit" class="confirm-btn">Update Feedback</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('editFeedbackModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Feedback Already Submitted Modal -->
    <div id="feedbackExistsModal" class="modal">
        <div class="modal-content">
            <input type="hidden" id="data-student-id" name="student_id">
            <h2 style="color: #000">feedback for <strong><span style="color: #095d40"
                        id="feedback_exists_student_name"></span></strong>
                already
                submitted!</h2>
            <p>Would you like to edit the existing feedback?</p>
            <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                <button type="button" class="update-btn" onclick="editFeedback()">Edit</button>
                <button type="button" class="cancel-btn" onclick="closeModal('feedbackExistsModal')">Close</button>
            </div>
        </div>
    </div>
    <!-- Error Modal -->
    <div id="feedbackErrorModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('feedbackErrorModal')">&times;</span>
            <p>An error occurred while processing the feedback. Please try again.</p>
        </div>
    </div>
    <script>
        // Function to open the Edit Feedback Modal and populate it with data
        function openEditFeedbackModal(studentName, studentId) {
            document.getElementById('edit_student_name').textContent = studentName;
            document.getElementById('edit_student_id').value = studentId;

            // Fetch feedback data from the server
            fetch(`fetch_feedback.php?student_id=${studentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch feedback.');
                    }
                    return response.json();
                })
                .then(data => {
                    if (Object.keys(data).length) {
                        // Set radio buttons based on feedback values
                        setRadioButton('question_1', data.question_1);
                        setRadioButton('question_2', data.question_2);
                        setRadioButton('question_3', data.question_3);
                        setRadioButton('question_4', data.question_4);
                        setRadioButton('question_5', data.question_5);
                    } else {
                        console.warn('No feedback found for this student.');
                    }
                    openModal('editFeedbackModal'); // Open modal only after setting data
                })
                .catch(error => console.error('Error fetching feedback:', error));
        }

        // Helper function to set radio buttons based on the value
        function setRadioButton(question, value) {
            const radios = document.querySelectorAll(`input[name=${question}]`);
            radios.forEach(radio => {
                radio.checked = parseInt(radio.value) === value;
            });
        }

        // Function to open the modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        // Function to close the modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }



        // Handle editing feedback after clicking "Edit" in feedbackExistsModal
        function editFeedback() {
            const studentName = document.getElementById('feedback_exists_student_name').textContent;
            const studentEditId = document.getElementById('data-student-id').value;

            closeModal('feedbackExistsModal');
            openEditFeedbackModal(studentName, studentEditId);
        }

        // Show a modal by ID
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'block';
        }

        // Close a modal by ID
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'none';
        }

        // Handle editing feedback
        function editFeedback() {
            const studentName = document.getElementById('feedback_exists_student_name').textContent;
            const studentEditId = document.getElementById('data-student-id').value;

            closeModal('feedbackExistsModal');
            openEditFeedbackModal(studentName, studentEditId);
        }

        // Attach event listeners to "rate" buttons to trigger the appropriate modal
        document.querySelectorAll('.action-rate').forEach(button => {
            button.addEventListener('click', function () {
                const studentName = this.getAttribute('data-student-name');
                const studentId = this.getAttribute('data-student-id');
                const hasFeedback = this.getAttribute('data-has-feedback'); // Check if feedback exists

                openEvaluationModal(studentName, studentId, hasFeedback);
            });
        });

        // Open evaluation modal or feedback exists modal
        function openEvaluationModal(studentName, studentId, hasFeedback) {
            if (hasFeedback === 'true') {
                document.getElementById('feedback_exists_student_name').textContent = studentName;
                document.getElementById('data-student-id').value = studentId;
                openModal('feedbackExistsModal');
            } else {
                document.getElementById('eval_student_name').textContent = studentName;
                document.getElementById('eval_student_id').value = studentId;
                openModal('evaluationModal');
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function (event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) closeModal(modal.id);
            });
        };

    </script>

    <script>

        function showModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        window.onload = function () {
            <?php if (isset($_SESSION['feedback_success'])): ?>
                showModal('feedbackSuccessModal');
                <?php unset($_SESSION['feedback_success']); ?>
            <?php elseif (isset($_SESSION['feedback_error'])): ?>
                showModal('feedbackErrorModal');
                <?php unset($_SESSION['feedback_error']); ?>
            <?php elseif (isset($_SESSION['edit_success'])): ?>
                showModal('editSuccessModal');
                <?php unset($_SESSION['edit_success']); ?>
            <?php endif; ?>
        };


        function formatOjtHours(hoursDecimal) {
            const hours = Math.floor(hoursDecimal);
            const minutes = Math.round((hoursDecimal - hours) * 60);

            const hoursLabel = hours === 1 ? "hr" : "hrs";
            const minutesLabel = minutes === 1 ? "min" : "mins";

            let formattedTime = "";
            if (hours > 0) {
                formattedTime += `${hours} ${hoursLabel}`;
            }
            if (minutes > 0) {
                if (formattedTime) formattedTime += " ";
                formattedTime += `${minutes} ${minutesLabel}`;
            }

            return formattedTime || "N/A";
        }

        document.querySelectorAll('.ojt-hours').forEach(cell => {
            const hoursDecimal = parseFloat(cell.getAttribute('data-hours'));
            cell.textContent = formatOjtHours(hoursDecimal);
        });

        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('.ojt-hours').forEach(cell => {
                const hoursDecimal = parseFloat(cell.getAttribute('data-hours'));
                cell.textContent = formatOjtHours(hoursDecimal);
            });
        });
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
    <script src="./js/script.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>