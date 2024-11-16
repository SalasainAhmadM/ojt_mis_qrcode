<?php
session_start();
require '../../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../../index.php"); // Redirect to login page if not logged in
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

// include './others/filter_student.php';
// Capture the selected course_section and search query
$selected_course_section = isset($_GET['course_section']) ? $_GET['course_section'] : '';
$search_query = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Function to get paginated and searched students
// Updated function to get students with adviser full name
function getStudents($database, $selected_course_section, $search_query, $adviser_id, $limit = 5)
{
    // Determine current page number for pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Base query for counting total students (for pagination)
    $total_students_query = "SELECT COUNT(*) AS total FROM student WHERE adviser = ?"; // Filter by adviser ID

    // Base query for fetching students with adviser full name
    $students_query = "
    SELECT student.*, 
           CONCAT(adviser.adviser_firstname, ' ', adviser.adviser_middle, '. ', adviser.adviser_lastname) AS adviser_fullname,
           CONCAT(address.address_barangay, ', ', address.address_street) AS full_address,
           company.company_name,
           course_sections.course_section_name,
           departments.department_name
    FROM student 
    LEFT JOIN adviser ON student.adviser = adviser.adviser_id
    LEFT JOIN address ON student.student_address = address.address_id
    LEFT JOIN company ON student.company = company.company_id
    LEFT JOIN course_sections ON student.course_section = course_sections.id
    LEFT JOIN departments ON student.department = departments.department_id
    WHERE student.adviser = ?"; // Filter by adviser ID

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
            $stmt->bind_param("issss", $adviser_id, $selected_course_section, $search_query, $search_query, $search_query);
        } elseif (!empty($selected_course_section)) {
            $stmt->bind_param("is", $adviser_id, $selected_course_section);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("isss", $adviser_id, $search_query, $search_query, $search_query);
        } else {
            $stmt->bind_param("i", $adviser_id);
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
            $stmt->bind_param("issssii", $adviser_id, $selected_course_section, $search_query, $search_query, $search_query, $limit, $offset);
        } elseif (!empty($selected_course_section)) {
            $stmt->bind_param("isii", $adviser_id, $selected_course_section, $limit, $offset);
        } elseif (!empty($search_query)) {
            $stmt->bind_param("isssii", $adviser_id, $search_query, $search_query, $search_query, $limit, $offset);
        } else {
            $stmt->bind_param("iii", $adviser_id, $limit, $offset);
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
$pagination_data = getStudents($database, $selected_course_section, $search_query, $adviser_id);
$students = $pagination_data['students'];
$total_pages = $pagination_data['total_pages'];
$current_page = $pagination_data['current_page'];

// Fetch journals for each student based on the day
function getJournalsForDay($database, $student_id, $day)
{
    $query = "SELECT journal_id, journal_date, journal_name FROM student_journal WHERE student_id = ? AND DAYNAME(journal_date) = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("is", $student_id, $day);
        $stmt->execute();
        $result = $stmt->get_result();
        $journals = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $journals;
    }
    return [];
}
function getJournalsForStudent($database, $student_id, $start_date, $end_date)
{
    $query = "SELECT journal_id, journal_name, journal_date FROM student_journal WHERE student_id = ? AND journal_date BETWEEN ? AND ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("iss", $student_id, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $journals = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $journals;
    }
    return [];
}


date_default_timezone_set('Asia/Manila');

// Get the current date and find the start (Monday) and end (Friday) of the week
$currentDate = new DateTime();
$startOfWeek = clone $currentDate;
$startOfWeek->modify('last Monday');
if ($currentDate->format('N') != 1) {
    $startOfWeek->modify('+0 day'); // Ensure it's always the current week's Monday
}
$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('+4 days');

// Function to check if a given date is a holiday
function isHoliday($database, $date)
{
    $query = "SELECT * FROM holiday WHERE holiday_date = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $holiday = $result->fetch_assoc();
        $stmt->close();
        return $holiday; // Returns the holiday data if found
    }
    return null;
}
$currentDate = new DateTime();
$startOfWeek = clone $currentDate;
$startOfWeek->modify('last Monday');

if ($currentDate->format('N') == 1) {
    // If today is Monday, ensure it starts from today
    $startOfWeek->modify('+0 day');
} else {
    // Otherwise, adjust to the past Monday
    $startOfWeek->modify('+0 day');
}

// Create an array of dates for each day of the week (Mon-Fri)
$daysOfWeek = [];
for ($i = 0; $i < 5; $i++) {
    $daysOfWeek[] = $startOfWeek->format('Y-m-d');
    $startOfWeek->modify('+1 day');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser - Intern Reports</title>
    <link rel="icon" href="../../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/mobile.css">
    <!-- <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mobile.css"> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>
<style>
    .date-cell {
        display: flex;
        align-items: center;
    }

    .notify {
        font-size: 18px;
        color: #ff9800;
        cursor: pointer;
        margin-right: 5px;
        /* Add spacing between the icon and the text */
    }

    .journal-date {
        font-size: 14px;
        color: #333;
        vertical-align: middle;
    }

    /* Green UI for the checked icon */
    .notify.checked {
        color: #4caf50;
        /* Green color */
    }
</style>

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
                <div style="background-color: #07432e;" class="iocn-link">
                    <a href="../interns.php">
                        <i class="fa-solid fa-user"></i>
                        <span class="link_name">Interns</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="../interns.php">Manage Interns</a></li>
                    <!-- <li><a href="intern-profile.php">Student Profile</a></li> -->
                    <li><a href="intern-reports.php">Intern Reports</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="../company.php">
                        <i class="fa-regular fa-building"></i>
                        <span class="link_name">Company</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="../company.php">Manage Company</a></li>
                    <li><a href="../company/company-intern.php">Company Interns</a></li>
                    <li><a href="../company/company-feedback.php">Company List</a></li>
                    <li><a href="../company/company-intern-feedback.php">Intern Feedback</a></li>
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
                <label style="color: #a6a6a6;">Intern Reports</label>
            </div>
            <div class="main-box">
                <div style="height: 600px;" class="whole-box">
                    <div class="header-group">
                        <h2>Intern Reports</h2>
                    </div>

                    <div class="filter-group">
                        <!-- Course_section Filter Form -->



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
                        <form method="GET" action="intern-reports.php">
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
                                <th class="date">Monday</th>
                                <th class="date">Tuesday</th>
                                <th class="date">Wednesday</th>
                                <th class="date">Thursday</th>
                                <th class="date">Friday</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $hasJournalForWeek = false;
                                    $allJournalIds = []; // Array to hold all journal IDs for the student
                            
                                    foreach ($daysOfWeek as $current_date) {
                                        $day = date('l', strtotime($current_date)); // Get day name (e.g., 'Monday')
                                        $journals = getJournalsForDay($database, $student['student_id'], $day);
                                        if (!empty($journals)) {
                                            $hasJournalForWeek = true;
                                            foreach ($journals as $journal) {
                                                $allJournalIds[] = $journal['journal_id']; // Collect journal IDs
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td class="image">
                                            <img style="border-radius: 50%;"
                                                src="../../uploads/student/<?php echo !empty($student['student_image']) ? $student['student_image'] : 'user.png'; ?>"
                                                alt="student Image">
                                        </td>
                                        <td class="name">
                                            <?php echo $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname']; ?>
                                        </td>

                                        <!-- Iterate over each day of the current week -->
                                        <?php foreach ($daysOfWeek as $current_date): ?>
                                            <?php
                                            $day = date('l', strtotime($current_date));
                                            $holiday = isHoliday($database, $current_date);
                                            $journals = getJournalsForDay($database, $student['student_id'], $day);
                                            ?>
                                            <td class="date">
                                                <div class="date-cell">
                                                    <?php if ($holiday): ?>
                                                        <span style="color: #8B0000"><?php echo $holiday['holiday_name']; ?></span>
                                                    <?php elseif (!empty($journals)): ?>
                                                        <i class="fa-solid fa-question-circle notify"
                                                            onclick="toggleNotify(this, '<?php echo $day; ?>', '<?php echo $journals[0]['journal_id']; ?>')"></i>
                                                        <span class="journal-date">
                                                            <?php echo date('M d, Y', strtotime($journals[0]['journal_date'])); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span>No Journal</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        <?php endforeach; ?>

                                        <td class="action">
                                            <?php if ($hasJournalForWeek): ?>
                                                <button class="action-icon print-btn"
                                                    onclick="openJournalModal(<?php echo $student['student_id']; ?>, <?php echo htmlspecialchars(json_encode($allJournalIds), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    <i class="fa-solid fa-file-export"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="action-icon error-btn" onclick="showPastDateModal()">
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                </button>
                                            <?php endif; ?>
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
                    <div class="pagination">
                        <?php renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query); ?>
                    </div>

                </div>
            </div>
        </div>

    </section>

    <!-- Journal Modal -->
    <div id="editModal" style="padding-top: 50px;" class="modal">
        <div class="modal-content-big">
            <span class="close" id="closeEditModal">&times;</span>
            <h2>Journal Entry</h2>

            <form action="edit_journal.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="journalIdDisplay" name="journalIdDisplay" disabled>
                <input type="hidden" id="studentIdDisplay" name="studentIdDisplay" disabled value="">
                <input type="hidden" id="journalId" name="journalId">
                <input type="hidden" id="studentId" name="studentId" value="">

                <div class="horizontal-group">
                    <div class="input-group">
                        <label for="editJournalTitle">Title</label>
                        <input class="title" type="text" id="editJournalTitle" name="editJournalTitle" required>
                    </div>

                    <div class="input-group">
                        <label for="editJournalDate">Date</label>
                        <input class="date" type="date" id="editJournalDate" name="editJournalDate" readonly>
                    </div>
                </div>

                <label for="editJournalDescription">Description</label>
                <textarea id="editJournalDescription" name="editJournalDescription" required></textarea>

                <div class="image-upload-row">
                    <div class="journal-img-container">
                        <label for="imageEdit1">
                            <img id="imageEditPreview1" src="../../img/default.png" alt="journal Preview 1"
                                class="journal-preview-img square-img" />
                        </label>
                        <input type="file" id="imageEdit1" name="imageEdit1" accept="image/*"
                            onchange="previewEditImage(1)" style="display: none;">
                        <p class="journal-img-label">Click to upload image 1</p>
                    </div>

                    <div class="journal-img-container">
                        <label for="imageEdit2">
                            <img id="imageEditPreview2" src="../../img/default.png" alt="journal Preview 2"
                                class="journal-preview-img square-img" />
                        </label>
                        <input type="file" id="imageEdit2" name="imageEdit2" accept="image/*"
                            onchange="previewEditImage(2)" style="display: none;">
                        <p class="journal-img-label">Click to upload image 2</p>
                    </div>

                    <div class="journal-img-container">
                        <label for="imageEdit3">
                            <img id="imageEditPreview3" src="../../img/default.png" alt="journal Preview 3"
                                class="journal-preview-img square-img" />
                        </label>
                        <input type="file" id="imageEdit3" name="imageEdit3" accept="image/*"
                            onchange="previewEditImage(3)" style="display: none;">
                        <p class="journal-img-label">Click to upload image 3</p>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="journalModal" class="modal" style="display:none;">
        <div class="modal-content">
            <input type="hidden" id="studentIdInput" value="">

            <span class="close" id="closeJournalModal">&times;</span>
            <h2>Select Journals to Export</h2>
            <ul id="journalList" class="green-palette">
                <!-- Journals will be dynamically loaded here -->
            </ul>
            <button id="exportSelectedJournalsBtn" class="assign-btn">Export Selected Journals</button>
            <button id="selectAllJournalsBtn" class="assign-btn">Select All</button>
        </div>
    </div>

    <script>
        function openJournalModal(studentId) {
            const modal = document.getElementById('journalModal');
            const journalList = document.getElementById('journalList');
            const selectAllButton = document.getElementById('selectAllJournalsBtn');
            const studentIdInput = document.getElementById('studentIdInput');
            let selectAllState = false;

            // Set the student ID in the hidden input
            studentIdInput.value = studentId;

            // Fetch journals dynamically for the current week
            fetch(`get_journals.php?student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        journalList.innerHTML = ''; // Clear existing list
                        data.journals.forEach(journal => {
                            const listItem = document.createElement('li');
                            listItem.innerHTML = `
                        <label>
                            <input type="checkbox" value="${journal.journal_id}">
                            ${journal.journal_name} - Date: ${journal.journal_date}
                        </label>
                    `;
                            journalList.appendChild(listItem);
                        });

                        selectAllButton.style.display = data.journals.length > 0 ? 'inline-block' : 'none';
                    } else {
                        journalList.innerHTML = '<li>No journals found for this week</li>';
                        selectAllButton.style.display = 'none';
                    }
                })
                .catch(() => {
                    journalList.innerHTML = '<li>Error fetching journals</li>';
                    selectAllButton.style.display = 'none';
                });

            selectAllButton.onclick = function () {
                const checkboxes = journalList.querySelectorAll('input[type="checkbox"]');
                selectAllState = !selectAllState;
                checkboxes.forEach(checkbox => (checkbox.checked = selectAllState));
                selectAllButton.textContent = selectAllState ? 'Deselect All' : 'Select All';
            };

            modal.style.display = 'block'; // Open modal
        }


        // Close modal handler
        document.getElementById('closeJournalModal').onclick = function () {
            document.getElementById('journalModal').style.display = 'none';
        };

        document.getElementById('exportSelectedJournalsBtn').onclick = function () {
            const selectedJournals = Array.from(document.querySelectorAll('#journalList input[type="checkbox"]:checked'))
                .map(input => input.value);

            if (selectedJournals.length === 0) {
                alert('Please select at least one journal to export.');
                return;
            }

            const studentId = document.getElementById('studentIdInput').value; // Ensure student ID is present
            const journalIds = selectedJournals.join(',');

            // Redirect to the export URL
            window.location.href = `export_journal.php?student_id=${studentId}&journal_ids=${journalIds}`;
        };



    </script>




    <script>

        function toggleNotify(icon, day) {
            // Change the icon to a checked icon when clicked and show the modal
            if (icon.classList.contains('fa-question-circle')) {
                icon.classList.remove('fa-question-circle');
                icon.classList.add('fa-regular', 'fa-circle-check');
                icon.classList.add('checked'); // Apply green UI

                showEditModal(day); // Call the function to show the edit modal
            }
        }

        function showEditModal(day) {
            // Open the modal
            const modal = document.getElementById('editModal');
            modal.style.display = "block";

            // Set the journal date and other details for the modal
            document.getElementById('editJournalDate').value = day; // Set the day as the journal date
        }

        document.getElementById('closeEditModal').onclick = function () {
            document.getElementById('editModal').style.display = "none";
        }

        function showPastDateModal() {
            document.getElementById('pastDateModal').style.display = 'block';
        }

        function closePastDateModal() {
            document.getElementById('pastDateModal').style.display = 'none';
        }
    </script>
    <!-- Error modal structure -->
    <div id="pastDateModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation for Error -->
            <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 20px;">
                <lottie-player src="../../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h3 style="color: #8B0000; margin-bottom: 20px;">No Journal Entries for the Week</h3>
            <div style="display: flex; justify-content: center;">
                <button class="cancel-btn" onclick="closePastDateModal()">Close</button>
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
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>