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
// Fetch adviser's announcements from the database
$query = "SELECT * FROM adviser_announcement WHERE adviser_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $adviser_id); // Bind adviser ID parameter
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    $announcements = [];
    if ($result->num_rows > 0) {
        // Fetch all announcements
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
    $stmt->close(); // Close the statement
}
include './others/filter_announcement.php';

$pagination_data = getAdviserAnnouncements($database, $adviser_id, $search_query);
$announcements = $pagination_data['announcements'];
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
    <title>Adviser - Announcement</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <!-- <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/mobile.css"> -->
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
                <div class="iocn-link">
                    <a href="attendance.php">
                        <i class="fa-regular fa-clock"></i>
                        <span class="link_name">Attendance</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="attendance.php">Attendance</a></li>
                    <li><a href="./intern/attendance-intern.php">Intern Attendance</a></li>
                    <li><a href="./intern/attendance-monitor.php">Monitoring</a></li>
                    <li><a href="./intern/intern_hours.php">Intern Total Hours</a></li>
                </ul>
            </li>
            <li>
                <a href="announcement.php" class="active">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span class="link_name">Announcement</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="announcement.php">Announcement</a></li>
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
                <label style="color: #a6a6a6; margin-left: 10px;">Manage Announcement</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <div class="header-group">
                        <h2>Announcement Details</h2>

                        <div class="button-container">
                            <button id="openAddModalBtn" class="add-btn">
                                <i class="fa-solid fa-plus"></i>Add
                            </button>
                        </div>
                    </div>
                    <div class="filter-group">
                        <form method="GET" action="announcement.php">
                            <div class="search-bar-container">
                                <input type="text" class="search-bar" name="search" placeholder="Search"
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="search-bar-icon">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </form>
                        <!-- Reset Button Form -->
                        <form method="GET" action="announcement.php">
                            <button type="submit" class="reset-bar-icon">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </form>

                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th class="title">Title</th>
                                <th class="description">Description</th>
                                <th class="date">Date Submitted</th>
                                <th class="action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($announcements)): ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <tr>
                                        <td class="title">
                                            <?php echo htmlspecialchars($announcement['announcement_name']); ?>
                                        </td>
                                        <td class="description">
                                            <?php echo htmlspecialchars($announcement['announcement_description']); ?>
                                        </td>
                                        <td class="date">
                                            <?php echo date("M d, Y", strtotime($announcement['announcement_date'])); ?>
                                        </td>
                                        <td class="action">
                                            <button class="action-icon edit-btn"
                                                data-id="<?php echo $announcement['announcement_id']; ?>"
                                                data-adviser-id="<?php echo $announcement['adviser_id']; ?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="action-icon delete-btn"
                                                data-id="<?php echo $announcement['announcement_id']; ?>">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No announcements found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php renderPaginationLinks($total_pages, $current_page, $search_query); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Add Modal -->
        <div id="addModal" class="modal">
            <div style="width: 50%;" class="modal-content-big">
                <span class="close" id="closeAddModal">&times;</span>
                <h2>Add Announcement Entry</h2>

                <form action="add_announcement.php" method="POST">

                    <div class="horizontal-group">
                        <div class="input-group">
                            <label for="announcementTitle">Title</label>
                            <input class="title" type="text" id="announcementTitle" name="announcementTitle"
                                placeholder="Input Title" required>
                        </div>

                        <div class="input-group">
                            <label for="announcementDate">Date</label>
                            <input class="date" type="date" id="announcementDate" name="announcementDate" required>
                        </div>
                    </div>

                    <label for="announcementDescription">Description</label>
                    <textarea id="announcementDescription" name="announcementDescription"
                        placeholder="Input Announcement Description" required></textarea>

                    <button type="submit" class="modal-btn">Add Entry</button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content-big">
                <span class="close" id="closeEditModal">&times;</span>
                <h2>Edit Announcement Entry</h2>

                <form action="edit_announcement.php" method="POST">
                    <input type="hidden" id="announcementId" name="announcementId">
                    <!-- Hidden Adviser ID -->
                    <input type="hidden" id="adviserId" name="adviserId">

                    <div class="horizontal-group">
                        <div class="input-group">
                            <label for="editAnnouncementTitle">Title</label>
                            <input class="title" type="text" id="editAnnouncementTitle" name="editAnnouncementTitle"
                                required>
                        </div>
                        <div class="input-group">
                            <label for="editAnnouncementDate">Date</label>
                            <input class="date" type="date" id="editAnnouncementDate" name="editAnnouncementDate"
                                required>
                        </div>
                    </div>

                    <label for="editAnnouncementDescription">Description</label>
                    <textarea id="editAnnouncementDescription" name="editAnnouncementDescription" required></textarea>

                    <button type="submit" class="modal-btn">Save Changes</button>
                </form>
            </div>
        </div>



        <!-- Success Modal for Announcement Submission -->
        <div id="announcementSuccessModal" class="modal">
            <div class="modal-content">
                <!-- Lottie Animation -->
                <div style="display: flex; justify-content: center; align-items: center;">
                    <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                        style="width: 150px; height: 150px;" loop autoplay>
                    </lottie-player>
                </div>
                <h2>Announcement Added Successfully!</h2>
                <p>Thank you for adding an announcement, <span
                        style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
                <button class="proceed-btn" onclick="closeModal('announcementSuccessModal')">Close</button>
            </div>
        </div>

        <!-- Success Modal for Announcement Update -->
        <div id="announcementUpdateSuccessModal" class="modal">
            <div class="modal-content">
                <!-- Lottie Animation -->
                <div style="display: flex; justify-content: center; align-items: center;">
                    <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                        style="width: 150px; height: 150px;" loop autoplay>
                    </lottie-player>
                </div>
                <h2>Announcement Updated Successfully!</h2>
                <p>Your Announcement has been updated successfully, <span
                        style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
                <button class="proceed-btn" onclick="closeModal('announcementUpdateSuccessModal')">Close</button>
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
                <?php if (isset($_SESSION['announcement_success'])): ?>
                    showModal('announcementSuccessModal');
                    <?php unset($_SESSION['announcement_success']); ?>
                <?php elseif (isset($_SESSION['announcement_update_success'])): ?>
                    showModal('announcementUpdateSuccessModal');
                    <?php unset($_SESSION['announcement_update_success']); ?>
                <?php elseif (isset($_SESSION['announcement_delete_success'])): ?>
                    showModal('announcementDeleteSuccessModal');
                    <?php unset($_SESSION['announcement_delete_success']); ?>
                <?php endif; ?>
            };
        </script>



    </section>
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
        // Get the modal elements
        var addModal = document.getElementById("addModal");
        var editModal = document.getElementById("editModal"); // Added missing editModal variable

        // Get the buttons that open the modals
        var addBtn = document.getElementById("openAddModalBtn");

        // Get the <span> elements that close the modals
        var closeAddModal = document.getElementById("closeAddModal");
        var closeEditModal = document.getElementById("closeEditModal"); // Added missing closeEditModal

        // Open the Add modal
        addBtn.onclick = function () {
            addModal.style.display = "block";
        };

        // Close the Add modal when clicking the close button
        closeAddModal.onclick = function () {
            addModal.style.display = "none";
        };

        // Close the Edit modal when clicking the close button
        closeEditModal.onclick = function () {
            editModal.style.display = "none";
        };

        // Close the modals if the user clicks outside of the modal content
        window.onclick = function (event) {
            if (event.target == addModal) {
                addModal.style.display = "none";
            }
            if (event.target == editModal) {
                editModal.style.display = "none";
            }
        };

        document.addEventListener("DOMContentLoaded", function () {
            const editButtons = document.querySelectorAll('.edit-btn');
            const editModal = document.getElementById('editModal');
            const closeEditModal = document.getElementById('closeEditModal');
            const announcementIdInput = document.getElementById('announcementId');
            const adviserIdInput = document.getElementById('adviserId'); // Adviser ID element
            const editAnnouncementTitle = document.getElementById('editAnnouncementTitle');
            const editAnnouncementDate = document.getElementById('editAnnouncementDate');
            const editAnnouncementDescription = document.getElementById('editAnnouncementDescription');

            // Add click event listener to each edit button
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const announcementId = this.getAttribute('data-id');

                    // Fetch the data for the selected announcement using AJAX
                    fetch(`fetch_announcement.php?id=${announcementId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                            } else {
                                // Populate the modal fields with the announcement data
                                announcementIdInput.value = data.announcement_id;
                                adviserIdInput.value = data.adviser_id; // Set adviser_id
                                editAnnouncementTitle.value = data.announcement_name;
                                editAnnouncementDate.value = data.announcement_date;
                                editAnnouncementDescription.value = data.announcement_description;

                                // Show the modal
                                editModal.style.display = "block";
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching the announcement data:', error);
                            alert('Failed to fetch the announcement data. Please try again.');
                        });
                });
            });

            // Close modal when close button is clicked
            closeEditModal.addEventListener('click', function () {
                editModal.style.display = "none";
            });

            // Close modal when clicking outside the modal content
            window.addEventListener('click', function (event) {
                if (event.target == editModal) {
                    editModal.style.display = "none";
                }
            });
        });

        // Delete Announcement Entry Confirmation
        const deleteButtons = document.querySelectorAll('.delete-btn');
        let announcementIdToDelete = null;

        deleteButtons.forEach(button => {
            button.addEventListener('click', () => {
                announcementIdToDelete = button.getAttribute('data-id');
                openDeleteModal(); // Open the custom modal instead of using a default alert
            });
        });

        function openDeleteModal() {
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function confirmDeleteAction() {
            if (announcementIdToDelete) {
                window.location.href = `delete_announcement.php?id=${announcementIdToDelete}`;
            }
        }

    </script>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/alert-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay></lottie-player>
            </div>
            <h2 style="color: #000">Are you sure you want to delete?</h2>
            <div style="display: flex; justify-content: space-around; margin-top: 10px; margin-bottom: 20px">
                <button class="confirm-btn" onclick="confirmDeleteAction()">Confirm</button>
                <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Success Modal for Announcement Deletion -->
    <div id="announcementDeleteSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Announcement Deleted Successfully!</h2>
            <p>Your Announcement has been deleted, <span
                    style="color: #095d40; font-size: 20px"><?php echo $_SESSION['full_name']; ?>!</span></p>
            <button class="proceed-btn" onclick="closeModal('announcementDeleteSuccessModal')">Close</button>
        </div>
    </div>
    <script src="./js/scripts.js"></script>
    <script src="../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>