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
        position: relative;
    }

    .notify {
        font-size: 18px;
        position: absolute;
        top: 0;
        left: 0;
        color: #ff9800;
        cursor: pointer;
    }

    /* Journal date style */
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
                <label style="color: #a6a6a6;">Student Management</label>
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
                        <form method="GET" action="interns.php">
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
                            <tr>
                                <td class="image">
                                    <img style="border-radius: 50%;" src="../../uploads/student/user.png"
                                        alt="student Image">
                                </td>
                                <td>Mary Loi Eves Ricalde</td>

                                <!-- Monday -->
                                <td class="date">
                                    <div class="date-cell">
                                        <i class="fa-solid fa-question-circle notify"
                                            onclick="toggleNotify(this, 'Monday')"></i>
                                        <span class="journal-date">Monday</span>
                                    </div>
                                </td>

                                <!-- Tuesday -->
                                <td class="date">
                                    <div class="date-cell">
                                        <i class="fa-solid fa-question-circle notify"
                                            onclick="toggleNotify(this, 'Tuesday')"></i>
                                        <span class="journal-date">Tuesday</span>
                                    </div>
                                </td>

                                <!-- Wednesday -->
                                <td class="date">
                                    <div class="date-cell">
                                        <i class="fa-solid fa-question-circle notify"
                                            onclick="toggleNotify(this, 'Wednesday')"></i>
                                        <span class="journal-date">Wednesday</span>
                                    </div>
                                </td>

                                <!-- Thursday -->
                                <td class="date">
                                    <div class="date-cell">
                                        <i class="fa-solid fa-question-circle notify"
                                            onclick="toggleNotify(this, 'Thursday')"></i>
                                        <span class="journal-date">Thursday</span>
                                    </div>
                                </td>

                                <!-- Friday -->
                                <td class="date">
                                    <div class="date-cell">
                                        <i class="fa-solid fa-question-circle notify"
                                            onclick="toggleNotify(this, 'Friday')"></i>
                                        <span class="journal-date">Friday</span>
                                    </div>
                                </td>

                                <td class="action">
                                    <button class="action-icon print-btn"
                                        onclick="confirmPrint(<?php echo $student['student_id']; ?>)">
                                        <i class="fa-solid fa-file-export"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Display pagination links -->
                    <div class="pagination">
                        <!-- <?php renderPaginationLinks($total_pages, $current_page, $selected_course_section, $search_query); ?> -->
                    </div>


                </div>
            </div>
        </div>
    </section>

    <!-- Edit Modal -->
    <div id="editModal" style="padding-top: 50px;" class="modal">
        <div class="modal-content-big">
            <span class="close" id="closeEditModal">&times;</span>
            <h2>Edit Journal Entry</h2>

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
                            <img id="imageEditPreview1" src="../img/default.png" alt="journal Preview 1"
                                class="journal-preview-img square-img" />
                        </label>
                        <input type="file" id="imageEdit1" name="imageEdit1" accept="image/*"
                            onchange="previewEditImage(1)" style="display: none;">
                        <p class="journal-img-label">Click to upload image 1</p>
                    </div>

                    <div class="journal-img-container">
                        <label for="imageEdit2">
                            <img id="imageEditPreview2" src="../img/default.png" alt="journal Preview 2"
                                class="journal-preview-img square-img" />
                        </label>
                        <input type="file" id="imageEdit2" name="imageEdit2" accept="image/*"
                            onchange="previewEditImage(2)" style="display: none;">
                        <p class="journal-img-label">Click to upload image 2</p>
                    </div>

                    <div class="journal-img-container">
                        <label for="imageEdit3">
                            <img id="imageEditPreview3" src="../img/default.png" alt="journal Preview 3"
                                class="journal-preview-img square-img" />
                        </label>
                        <input type="file" id="imageEdit3" name="imageEdit3" accept="image/*"
                            onchange="previewEditImage(3)" style="display: none;">
                        <p class="journal-img-label">Click to upload image 3</p>
                    </div>
                </div>

                <button type="submit" class="modal-btn">Save Changes</button>
            </form>
        </div>
    </div>

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
    </script>

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