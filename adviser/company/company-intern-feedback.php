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

// Fetch all companies from the database
$companies = [];
$companies_query = "SELECT company_id, company_name FROM company ORDER BY company_name ASC";
if ($stmt = $database->prepare($companies_query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    $stmt->close();
}
$company_name = "Select Company";

if ($company_id > 0) {
    $company_query = "SELECT company_name FROM company WHERE company_id = ?";
    if ($stmt = $database->prepare($company_query)) {
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $company = $result->fetch_assoc();
            $company_name = $company['company_name'];
        }
        $stmt->close();
    }
}

// Function to get feedback for a student
function getStudentFeedback($database, $student_id)
{
    $feedback_query = "SELECT question_1, question_2, question_3, question_4, question_5, total_score 
                       FROM feedback WHERE student_id = ?";
    $feedback = [];

    if ($stmt = $database->prepare($feedback_query)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $feedback = $result->fetch_assoc();
        } else {
            $feedback = null; // No feedback found
        }

        $stmt->close();
    }

    return $feedback;
}

// Helper function to convert feedback value to text
function feedbackText($value)
{
    switch ($value) {
        case 100:
            return 'Strongly Agree';
        case 80:
            return 'Agree';
        case 60:
            return 'Neutral';
        case 40:
            return 'Disagree';
        case 20:
            return 'Strongly Disagree';
        default:
            return 'No Feedback';
    }
}

// Fetch questions from the table
$sql = "SELECT * FROM feedback_questions WHERE id = 1";
$result = $database->query($sql);

if ($result->num_rows > 0) {
    $question = $result->fetch_assoc();
} else {
    die("No questions found in the database.");
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
    <title>Adviser - Company Intern Feedback</title>
    <link rel="icon" href="../../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mobile.css"> -->
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/mobile.css">
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
                    <!-- <li><a href="company-feedback.php">Company List</a></li> -->
                    <li><a href="company-intern-feedback.php">Intern Feedback</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="../attendance.php">
                        <i class="fa-regular fa-clock"></i>
                        <span class="link_name">Attendance</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="../attendance.php">Attendance</a></li>
                    <li><a href="../intern/attendance-intern.php">Intern Attendance</a></li>
                    <li><a href="../intern/attendance-monitor.php">Monitoring</a></li>
                    <li><a href="../intern/intern_hours.php">Intern Total Hours</a></li>

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
                <label style="color: #a6a6a6;">Company Intern Feedbacks</label>
            </div>
            <div class="main-box">
                <div style="height: 600px;" class="whole-box">
                    <div class="header-group" style="display: flex; align-items: center;">
                        <!-- <a href="../company.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a> -->
                        <h2>
                            <?php echo htmlspecialchars($company_name); ?>
                        </h2>
                    </div>



                    <div class="filter-group">
                        <?php
                        $query_params = ['company_id' => $company_id];

                        if (!empty($search_query)) {
                            $query_params['search'] = htmlspecialchars($_GET['search'], ENT_QUOTES);
                        }

                        if (!empty($selected_course_section)) {
                            $query_params['course_section'] = htmlspecialchars($_GET['course_section'], ENT_QUOTES);
                        }

                        $query_string = http_build_query($query_params);
                        ?>
                        <form method="GET" action="company-intern-feedback.php?<?php echo $query_string; ?>">
                            <select name="company_id" class="company-dropdown" onchange="this.form.submit()">
                                <option value="" disabled selected>-- Select Company --</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['company_id']; ?>" <?php echo $company['company_id'] == $company_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($company['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>

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

                        <form method="GET" action="company-intern-feedback.php">
                            <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                            <button type="submit" class="reset-bar-icon"><i class="fa fa-times-circle"></i></button>
                        </form>


                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th class="image">Profile</th>
                                <th class="name">Full Name</th>
                                <th class="feedback">Question 1</th>
                                <th class="feedback">Question 2</th>
                                <th class="feedback">Question 3</th>
                                <th class="feedback">Question 4</th>
                                <th class="feedback">Question 5</th>
                                <th class="feedback">Overall</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <?php $feedback = getStudentFeedback($database, $student['student_id']); ?>
                                    <tr>
                                        <td class="image"><img style="border-radius: 50%;"
                                                src="../../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
                                                alt="Student Image"></td>
                                        <td class="name">
                                            <?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '. ' . $student['student_lastname']; ?>
                                        </td>
                                        <?php if ($feedback): ?>
                                            <td class="feedback"><?php echo feedbackText($feedback['question_1']); ?></td>
                                            <td class="feedback"><?php echo feedbackText($feedback['question_2']); ?></td>
                                            <td class="feedback"><?php echo feedbackText($feedback['question_3']); ?></td>
                                            <td class="feedback"><?php echo feedbackText($feedback['question_4']); ?></td>
                                            <td class="feedback"><?php echo feedbackText($feedback['question_5']); ?></td>
                                            <td class="feedback"><?php echo $feedback['total_score'] . '%'; ?></td>
                                        <?php else: ?>
                                            <td class="feedback">N/A</td>
                                            <td class="feedback">N/A</td>
                                            <td class="feedback">N/A</td>
                                            <td class="feedback">N/A</td>
                                            <td class="feedback">N/A</td>
                                            <td class="feedback">No Feedback Yet</td>
                                        <?php endif; ?>
                                        <td class="action">
                                            <button class="action-rate edit-btn"
                                                data-student-name="<?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>"
                                                data-student-id="<?php echo $student['student_id']; ?>" onclick="openEditFeedbackModal('<?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>',<?php echo $student['student_id']; ?>
                                                )">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9">No Interns for this company</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Pagination Links -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query, $company_id); ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

    </section>

    <style>
        input[type="radio"]:checked:disabled+label {
            color: #095d40;
            /* Change the label color for checked disabled */
            font-weight: bold;
            /* Optional: make it bold for emphasis */
        }

        input[type="radio"]:checked:disabled {
            accent-color: #095d40;
            /* Change the radio button color to green */
        }

        /* General styling for the form */
        .checkbox-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            cursor: not-allowed;
            /* Indicate that the options are disabled */
        }
    </style>
    <div id="editFeedbackModal" class="modal">
        <div class="modal-content-bigger">
            <h2 style="color: #000;">Feedback for <span id="edit_student_name"></span></h2>
            <form id="editFeedbackForm" action="" method="POST">
                <input type="hidden" id="edit_student_id" name="student_id">

                <div class="evaluation-questions">
                    <p>View<strong><span id="edit_student_name"></span></strong>'s performance feedback:</p>

                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <?php if (!empty($question["question$i"])): ?>
                            <label><?php echo $i . ". " . $question["question$i"]; ?></label>
                            <div class="checkbox-group">
                                <label><input type="radio" name="question_<?php echo $i; ?>" value="100"> Strongly Agree</label>
                                <label><input type="radio" name="question_<?php echo $i; ?>" value="80"> Agree</label>
                                <label><input type="radio" name="question_<?php echo $i; ?>" value="60"> Neutral</label>
                                <label><input type="radio" name="question_<?php echo $i; ?>" value="40"> Disagree</label>
                                <label><input type="radio" name="question_<?php echo $i; ?>" value="20"> Strongly
                                    Disagree</label>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <!-- Additional Comments -->
                <div class="additional-comments">
                    <label for="additional_comments">Additional Comments (Optional):</label>
                    <textarea id="edit_comments" name="feedback_comment" rows="4" style="width: 100%;"></textarea>
                </div>
                <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                    <!-- <button type="submit" class="confirm-btn">Update Feedback</button> -->
                    <button type="button" class="confirm-btn" onclick="closeModal('editFeedbackModal')">Close</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditFeedbackModal(studentName, studentId) {
            document.getElementById('edit_student_name').textContent = studentName;
            document.getElementById('edit_student_id').value = studentId;

            fetch(`fetch_feedback.php?student_id=${studentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch feedback.');
                    }
                    return response.json();
                })
                .then(data => {
                    for (let i = 1; i <= 10; i++) {
                        if (data[`question_${i}`] !== undefined) {
                            setRadioButton(`question_${i}`, data[`question_${i}`]);
                        }
                    }
                    if (data.feedback_comment !== undefined) {
                        document.getElementById('edit_comments').value = data.feedback_comment;
                    } else {
                        document.getElementById('edit_comments').value = ''; // Clear if no comment
                    }
                    openModal('editFeedbackModal');
                })
                .catch(error => console.error('Error fetching feedback:', error));
        }


        // Helper function to set radio buttons based on the value
        function setRadioButton(question, value) {
            const radios = document.querySelectorAll(`input[name="${question}"]`);
            let valueFound = false;

            radios.forEach(radio => {
                if (parseInt(radio.value) === value) {
                    radio.checked = true;
                    valueFound = true;
                } else {
                    radio.checked = false; // Ensure other radios are unchecked
                }
            });

            if (!valueFound) {
                console.warn(`No matching radio button found for question: ${question} with value: ${value}`);
            }
        }

        // Helper function for optional radio buttons
        function setOptionalRadioButton(question, value) {
            if (value !== null) {
                setRadioButton(question, value);
            }
        }


        // Function to open the modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        // Function to close the modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

    </script>
    <div id="noFeedbackModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation for Error -->
            <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 20px;">
                <lottie-player src="../../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h3 style="color: #8B0000; margin-bottom: 20px;">No Feedback yet for this Student</h3>
            <div style="display: flex; justify-content: center;">
                <button class="cancel-btn" onclick="closeModal('noFeedbackModal')">Close</button>
            </div>
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
    <script src="../js/scripts.js"></script>
    <script src="../../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>