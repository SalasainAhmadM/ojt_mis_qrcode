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
           CONCAT(address.address_barangay, ', ', street.name) AS full_address,
           company.company_name,
           course_sections.course_section_name,
           departments.department_name,
           COALESCE(SUM(attendance.ojt_hours), 0) AS total_ojt_hours
    FROM student 
    LEFT JOIN adviser ON student.adviser = adviser.adviser_id
    LEFT JOIN address ON student.student_address = address.address_id
    LEFT JOIN street ON student.street = street.street_id
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

$company_id = $_SESSION['user_id']; // Retrieve company_id from session
$pagination_data = getStudents($database, $selected_course_section, $search_query, $company_id);
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
    <title>Company - Interns</title>
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
                <a href="intern.php" class="active">
                    <i class="fa-solid fa-user"></i>
                    <span class="link_name">Interns</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="intern.php">Interns</a></li>
                </ul>
            </li>
            <!-- <li>
                <div style="background-color: #07432e;" class="iocn-link" class="active">
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
                <a href="feedback.php">
                    <i class="fa-regular fa-star"></i>
                    <span class="link_name">Feedback</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="feedback.php">Feedback</a></li>
                </ul>
            </li>

            <li>
                <div class="iocn-link">
                    <a href="attendance.php">
                        <i class="fa-regular fa-clock"></i>
                        <span class="link_name">Attendance</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="attendance.php">Attendance</a></li>
                    <li><a href="./intern/attendance.php">Monitoring</a></li>
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
                <label style="color: #a6a6a6; margin-left: 5px;">Masterlist</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <div class="header-group">
                        <h2>Intern Details</h2>
                        <div class="button-container">
                            <button id="openAddStudentModalBtn" class="add-btn">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
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
                                <th class="adviser">Adviser</th>
                                <th class="ojthours">OJT Hours</th>
                                <th class="action">Action</th>
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
                                            <?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>
                                        </td>
                                        <td class="wmsu_id"><?php echo $student['wmsu_id']; ?></td>
                                        <td class="section"><?php echo $student['course_section_name']; ?></td>
                                        <td class="adviser"><?php echo $student['adviser_fullname']; ?></td>
                                        <td class="ojt-hours" data-hours="<?php echo $student['total_ojt_hours']; ?>"></td>

                                        <td class="action">
                                            <?php if (empty($student['date_start'])): ?>
                                                <!-- Show "Date Start" button if date_start is NULL -->
                                                <button class="action-view edit-btn" title="No Start Date Recorded"
                                                    onclick="openDateStartModal(<?php echo $student['student_id']; ?>)">
                                                    <i class="fa-solid fa-calendar"></i>
                                                </button>
                                            <?php else: ?>
                                                <!-- Show view icon if date_start exists -->
                                                <button class="action-view edit-btn"
                                                    data-student-image="<?php echo htmlspecialchars(!empty($student['student_image']) ? $student['student_image'] : 'user.png'); ?>"
                                                    data-student-id="<?php echo $student['wmsu_id']; ?>"
                                                    data-student-name="<?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>"
                                                    data-student-email="<?php echo $student['student_email']; ?>"
                                                    data-contact-number="<?php echo $student['contact_number']; ?>"
                                                    data-section-id="<?php echo $student['course_section_name']; ?>"
                                                    data-student-adviser="<?php echo $student['adviser_fullname']; ?>"
                                                    data-student-ojthours="<?php echo $student['total_ojt_hours']; ?>">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">There's no intern yet in this company.</td>
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
        </div>
    </section>
    <!-- Date Start Modal -->
    <div id="dateStartModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeDateStartModal">&times;</span>
            <h2>Set Start Date for Student</h2>
            <form id="dateStartForm" action="add_date_start.php" method="POST">
                <input type="hidden" name="student_id" id="modalStudentId">
                <label for="date_start">Start Date:</label>
                <input type="date" name="date_start" id="dateStartInput" required>
                <button type="submit" class="assign-btn">Save</button>
            </form>
        </div>
    </div>

    <script>
        // Function to set the default date in Asia/Manila timezone
        function setDefaultDate() {
            const dateInput = document.getElementById('dateStartInput');
            const now = new Date();
            // Convert current time to Asia/Manila timezone
            const manilaTime = now.toLocaleString("en-US", { timeZone: "Asia/Manila" });
            const manilaDate = new Date(manilaTime);

            // Format date as yyyy-MM-dd for the input field
            const year = manilaDate.getFullYear();
            const month = String(manilaDate.getMonth() + 1).padStart(2, '0');
            const day = String(manilaDate.getDate()).padStart(2, '0');

            dateInput.value = `${year}-${month}-${day}`;
        }

        // Call the function when the modal is displayed
        document.addEventListener('DOMContentLoaded', setDefaultDate);
    </script>



    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeAddStudentModal">&times;</span>
            <h2>Assign Student to Company</h2>
            <input type="text" id="studentSearch" placeholder="Search student by name">

            <!-- Styled student list with checkboxes -->
            <ul id="studentList" class="green-palette">
                <!-- Student list items with checkboxes will be dynamically inserted here -->
            </ul>

            <button id="assignStudentsBtn" class="assign-btn">Assign Selected Students</button>
        </div>
    </div>

    <script>
        let students = []; // Store all students fetched
        const checkedStudents = new Set(); // Track checked students

        // Fetch students and populate list
        document.getElementById('openAddStudentModalBtn').addEventListener('click', function () {
            fetch('get_students_no_company.php')
                .then(response => response.json())
                .then(data => {
                    students = data; // Store data for future filtering
                    renderStudentList(students);
                })
                .catch(error => console.error('Error fetching students:', error));

            document.getElementById('addStudentModal').style.display = 'block';
        });

        // Render the student list, prioritizing checked students
        function renderStudentList(data) {
            const studentList = document.getElementById('studentList');
            studentList.innerHTML = ''; // Clear existing list

            if (data.length === 0) {
                // Display a message if no students are found
                const noStudentsMessage = document.createElement('li');
                noStudentsMessage.textContent = "No intern with no company is found!";
                noStudentsMessage.classList.add('no-students-message');
                studentList.appendChild(noStudentsMessage);
                return;
            }

            // Sort to keep checked students at the top
            data.sort((a, b) => checkedStudents.has(b.student_id) - checkedStudents.has(a.student_id));

            data.forEach(student => {
                const li = document.createElement('li');
                li.classList.add('student-item');

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = student.student_id;
                checkbox.classList.add('student-checkbox');
                checkbox.checked = checkedStudents.has(student.student_id);

                // Event listener to manage checked students
                checkbox.addEventListener('change', function () {
                    if (checkbox.checked) {
                        checkedStudents.add(student.student_id);
                    } else {
                        checkedStudents.delete(student.student_id);
                    }
                    renderStudentList(students); // Re-render list
                });

                const label = document.createElement('label');
                label.textContent = `${student.student_firstname} ${student.student_middle} ${student.student_lastname}`;
                label.prepend(checkbox);

                li.appendChild(label);
                studentList.appendChild(li);
            });
        }


        // Filter the student list based on search input
        document.getElementById('studentSearch').addEventListener('input', function () {
            const searchQuery = this.value.toLowerCase();
            const filteredStudents = students.filter(student =>
                `${student.student_firstname} ${student.student_lastname}`.toLowerCase().includes(searchQuery)
            );

            renderStudentList(filteredStudents);
        });

        document.getElementById('closeAddStudentModal').addEventListener('click', function () {
            document.getElementById('addStudentModal').style.display = 'none';
        });

        // Optional: Close modal when clicking outside of it
        window.addEventListener('click', function (event) {
            const modal = document.getElementById('addStudentModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Assign button event listener
        document.getElementById('assignStudentsBtn').addEventListener('click', function () {
            const selectedStudents = Array.from(checkedStudents); // Convert Set to Array
            if (selectedStudents.length > 0) {
                fetch('assign_students.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        company_id: <?php echo json_encode($company_id); ?>, // Pass the company_id from PHP
                        student_ids: selectedStudents
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('addStudentModal').style.display = 'none';
                            document.getElementById('updateSuccessModal').style.display = 'block';
                            checkedStudents.clear(); // Clear selected students
                            renderStudentList(students); // Re-render the list

                        } else {
                            alert('Failed to assign students.');
                        }
                    })
                    .catch(error => console.error('Error assigning students:', error));
            } else {
                alert('Please select at least one student to assign.');
            }
        });

    </script>

    <!-- Update Success Modal -->
    <div id="updateSuccessModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Update Successful!</h2>
            <p>Company assignment has been successfully updated.</p>
            <button class="proceed-btn" onclick="closeModal('updateSuccessModal')">Proceed</button>
        </div>
    </div>

    <script>
        function openDateStartModal(studentId) {
            document.getElementById('modalStudentId').value = studentId; // Pass student ID to the modal
            document.getElementById('dateStartModal').style.display = 'block';
        }

        // Close the modal
        document.getElementById('closeDateStartModal').addEventListener('click', function () {
            document.getElementById('dateStartModal').style.display = 'none';
        });
        // Function to open the modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        // Function to close the modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Automatically open the modal when the page loads, if update was successful
        window.onload = function () {
            <?php if (isset($_SESSION['update_success']) && $_SESSION['update_success']): ?>
                openModal('updateSuccessModal');
                <?php unset($_SESSION['update_success']); // Clear the session variable ?>
            <?php endif; ?>
        };
    </script>


    <!-- View Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content-bigger">
            <span class="close" id="closeEditStudentModal">&times;</span>
            <h2 class="modal-title">View Student Details</h2>

            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="editStudentId" name="student_id">
                <!-- Profile Image and Preview -->
                <div class="profile-img-container">
                    <label for="editStudentImage">
                        <img id="editImagePreview" src="" alt="Profile Preview" class="profile-preview-img" />
                    </label>
                    <!-- <input type="file" id="editStudentImage" name="student_image" accept="image/*"
                        onchange="previewEditImage()" style="display: none;" readonly> -->
                    <!-- <p class="profile-img-label">Click to upload image</p> -->
                </div>

                <!-- Full Name Row -->
                <div class="input-group-row">
                    <div class="input-group-fn">
                        <label for="editStudentFirstname">First Name</label>
                        <input type="text" id="editStudentFirstname" name="student_firstname" readonly>
                    </div>
                    <div class="input-group-mi">
                        <label id="mi_edit" for="editStudentMiddle">M.I.</label>
                        <input type="text" id="editStudentMiddle" name="student_middle" readonly>
                    </div>
                    <div class="input-group-ln">
                        <label for="editStudentLastname">Last Name</label>
                        <input type="text" id="editStudentLastname" name="student_lastname" readonly>
                    </div>
                </div>

                <div class="input-group-row">
                    <div class="input-group">
                        <label for="editStudentWmsuId">WMSU ID</label>
                        <input type="text" id="editStudentWmsuId" name="wmsu_id" readonly>
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentEmail">Email</label>
                        <input type="email" id="editStudentEmail" name="student_email" readonly>
                    </div>
                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentContact">Contact Number</label>
                        <input type="text" id="editStudentContact" name="contact_number" readonly maxlength="13"
                            oninput="limitInput(this)">
                    </div>

                </div>

                <div class="input-group-row">
                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentSection">Section</label>
                        <input type="text" id="editStudentSection" name="course_section_id" readonly>
                    </div>


                    <div class="input-group" style="width: 33%;">
                        <label for="editStudentAdviser">Adviser</label>
                        <input type="hidden" id="editAdviserId" name="adviser_id">
                        <input type="text" id="editStudentAdviser" readonly>
                    </div>

                    <div class="input-group" style="width: 33%;">
                        <label for="editHours">OJT Hours</label>
                        <input type="text" id="editHours" readonly>
                    </div>

                </div>

                <button type="button" id="closeModalButton" class="modal-btn">Close</button>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const editStudentModal = document.getElementById("editStudentModal");
            const closeEditStudentModal = document.getElementById("closeEditStudentModal");
            const closeModalButton = document.getElementById("closeModalButton");

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

            document.querySelectorAll(".action-view").forEach(button => {
                button.addEventListener("click", function () {
                    // Get data attributes from the clicked button
                    const studentImage = this.getAttribute("data-student-image");
                    const studentName = this.getAttribute("data-student-name").split(" ");
                    const studentEmail = this.getAttribute("data-student-email");
                    const sectionName = this.getAttribute("data-section-id");
                    const adviserName = this.getAttribute("data-student-adviser");
                    const ojtHours = parseFloat(this.getAttribute("data-student-ojthours"));

                    const imagePath = studentImage ? `../uploads/student/${studentImage}` : '../img/user.png';
                    document.getElementById("editImagePreview").src = imagePath;

                    document.getElementById("editStudentFirstname").value = studentName[0];
                    document.getElementById("editStudentMiddle").value = studentName[1].charAt(0); // Initial
                    document.getElementById("editStudentLastname").value = studentName[2];
                    document.getElementById("editStudentEmail").value = studentEmail;
                    document.getElementById("editStudentWmsuId").value = this.getAttribute("data-student-id");
                    document.getElementById("editStudentContact").value = this.getAttribute("data-contact-number");
                    document.getElementById("editStudentSection").value = sectionName;
                    document.getElementById("editStudentAdviser").value = adviserName;
                    document.getElementById("editHours").value = formatOjtHours(ojtHours);

                    // Display the modal
                    editStudentModal.style.display = "block";
                });
            });

            closeEditStudentModal.addEventListener("click", function () {
                editStudentModal.style.display = "none";
            });

            closeModalButton.addEventListener("click", function () {
                editStudentModal.style.display = "none";
            });

            window.addEventListener("click", function (event) {
                if (event.target === editStudentModal) {
                    editStudentModal.style.display = "none";
                }
            });

            document.querySelectorAll('.ojt-hours').forEach(cell => {
                const hoursDecimal = parseFloat(cell.getAttribute('data-hours'));
                cell.textContent = formatOjtHours(hoursDecimal);
            });
        });


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
    <script src="../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>