<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch admin details from the database
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM admin WHERE admin_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $admin_id); // Bind parameters
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc(); // Fetch admin details
    } else {
        // Handle case where admin is not found
        $admin = [
            'admin_firstname' => 'Unknown',
            'admin_middle' => 'U',
            'admin_lastname' => 'User',
            'admin_email' => 'unknown@wmsu.edu.ph'
        ];
    }
    $stmt->close(); // Close the statement
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
    <title>Admin - Manage Profile</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./css/style.css">
    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/mobile.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>
<!-- Modal for past dates -->
<style>
    #pastDateModal {
        /* display: flex; */
        justify-content: center;
        align-items: center;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content-dateerror {
        background-color: #fff;
        border-radius: 8px;
        padding: 20px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .cancel-btn {
        background-color: #8B0000;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
    }

    .cancel-btn:hover {
        background-color: #a30000;
    }
</style>

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
            <img src="../uploads/admin/<?php echo !empty($admin['admin_image']) ? $admin['admin_image'] : 'user.png'; ?>"
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
                <a href="index.php">
                    <i class="fas fa-th-large"></i>
                    <span class="link_name">Dashboard</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="index.php">Dashboard</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="user-manage.php">
                        <i class="fa-solid fa-user"></i>
                        <span class="link_name">Manage Users</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="user-manage.php">User Management</a></li>
                    <li><a href="./users/adviser.php">Adviser Management</a></li>
                    <li><a href="./users/company.php">Company Management</a></li>
                    <li><a href="./users/student.php">Student Management</a></li>
                </ul>
            </li>
            <li>
                <a href="others.php">
                    <i class="fa-solid fa-ellipsis-h"></i>
                    <span class="link_name">Others</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="others.php">Others</a></li>
                </ul>
            </li>
            <li>
                <a href="calendar.php" class="active">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span class="link_name">Calendar</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="calendar.php">Calendar</a></li>
                </ul>
            </li>
            <li>
                <a href="feedback.php">
                    <i class="fa-solid fa-percent"></i>
                    <span class="link_name">Feedback</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="feedback.php">Feedback Management</a></li>
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
            <i class="fas fa-bars bx-menu"></i>
        </div>
        <div class="content-wrapper">

            <div class="header-box">
                <label style="color: #a6a6a6; margin-left: 5px;">Schedule Management</label>
            </div>
            <div class="main-box">
                <div class="right-box">
                    <?php
                    $companyQuery = "SELECT company_id, company_image, company_name FROM company";
                    if ($companyStmt = $database->prepare($companyQuery)) {
                        $companyStmt->execute();
                        $companyResult = $companyStmt->get_result();

                        while ($company = $companyResult->fetch_assoc()) {
                            $companyImage = !empty($company['company_image']) ? htmlspecialchars($company['company_image']) : 'user.png';
                            $companyName = htmlspecialchars($company['company_name']);
                            $companyId = htmlspecialchars($company['company_id']);

                            echo '
                        <div class="company-item" data-company-id="' . $companyId . '">
                            <img src="../uploads/company/' . $companyImage . '" alt="Company Image" class="company-img">
                            <div class="company-name">' . $companyName . '</div>
                        </div>';
                        }

                        $companyStmt->close();
                    }
                    ?>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </section>

    <!-- Schedule Holiday Modal -->
    <div id="scheduleHolidayModal" class="modal">
        <div class="modal-content-date">
            <h2 style="color: #000" id="selectedDate"></h2>
            <form id="scheduleForm" action="submit_holiday.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="scheduleDate" name="date">
                <div>
                    <label for="holidayName">Holiday Name</label>
                    <input type="text" id="holidayName" name="holidayName" placeholder="Enter Holiday Name" required>
                </div>
                <div>
                    <label for="holidayMemo">Upload Memo</label>
                    <input type="file" id="holidayMemo" name="holidayMemo" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                </div>
                <div class="modal-buttons" style="margin-top: 20px;">
                    <button type="submit" class="confirm-btn">Confirm</button>
                    <button type="button" class="cancel-btn"
                        onclick="closeModal('scheduleHolidayModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Edit Holiday Modal -->
    <div id="editHolidayModal" class="modal">
        <div class="modal-content-date">
            <h2 style="color: #000" id="editSelectedDate"></h2>
            <form id="editForm" action="edit_holiday.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="holidayId" name="holidayId">
                <input type="hidden" id="editDate" name="date">
                <div>
                    <label for="editHolidayName">Holiday Name</label>
                    <input type="text" id="editHolidayName" name="holidayName" placeholder="Enter Holiday Name"
                        required>
                </div>
                <div>
                    <label for="editHolidayMemo">Change Memo</label>
                    <input type="file" id="editHolidayMemo" name="holidayMemo" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                </div>
                <div class="modal-buttons" style="margin-top: 20px;">
                    <button type="button" class="confirm-dlt" onclick="triggerDelete()">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                    <button type="submit" class="confirm-btn">Confirm</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('editHolidayModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>



    <script>
        function triggerDelete() {
            const form = document.getElementById('editForm');
            form.action = 'delete_holiday.php';
            form.submit();
        }

        let selectedDate = null;

        function formatDate(dateStr) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Intl.DateTimeFormat('en-US', options).format(new Date(dateStr));
        }

        function closeModal() {
            document.getElementById('scheduleHolidayModal').style.display = 'none';
        }

        function openPastDateModal() {
            document.getElementById('pastDateModal').style.display = 'flex';
        }

        function closePastDateModal() {
            document.getElementById('pastDateModal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: new Date(),
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                weekends: false,
                navLinks: true,
                editable: false,
                selectable: true,
                dayMaxEvents: true,
                eventSources: [
                    {
                        url: 'fetch_holidays.php',
                        method: 'GET',
                        failure: function () {
                            alert('There was an error while fetching holiday events!');
                        },
                        extraParams: function () {
                            return {};
                        },
                    }
                ],
                dateClick: function (info) {
                    var clickedDate = new Date(info.date);
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);

                    // Check if there's an existing event for the clicked date
                    var existingEvent = calendar.getEvents().find(event => event.startStr === info.dateStr);
                    if (existingEvent) {
                        // Trigger the edit modal with the existing event data
                        openEditModal(existingEvent);
                        return;
                    }

                    // If the date is in the past, show a modal to indicate that
                    if (clickedDate < today) {
                        openPastDateModal();
                    } else {
                        // Ensure it's not a weekend before opening the new holiday modal
                        var day = clickedDate.getDay();
                        if (day !== 0 && day !== 6) {
                            openModal(info.dateStr);
                        }
                    }
                }

            });

            calendar.render();

            const rightBox = document.querySelector('.right-box');
            rightBox.addEventListener('click', function (e) {
                const clickedCompanyItem = e.target.closest('.company-item');

                if (clickedCompanyItem) {
                    document.querySelectorAll('.company-item').forEach(item => item.classList.remove('active'));
                    clickedCompanyItem.classList.add('active');

                    let companyId = clickedCompanyItem.dataset.companyId;

                    calendar.removeAllEvents();
                    fetchCompanySchedule(companyId);
                }
            });

            function fetchCompanySchedule(companyId) {
                fetch(`fetch_company_schedule.php?company_id=${companyId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(event => {
                            calendar.addEvent(event);
                        });
                    })
                    .catch(error => console.error('Error fetching company schedule:', error));
            }
        });

        function openEditModal(holiday) {
            if (!holiday || !holiday.startStr || !holiday.extendedProps || !holiday.extendedProps.holidayId) {
                console.error("Invalid holiday data passed to openEditModal:", holiday);
                return;
            }

            const formattedDate = formatDate(holiday.startStr);
            document.getElementById('editSelectedDate').innerHTML = 'Edit <span style="color: #8B0000;">Holiday</span> for ' + formattedDate;

            document.getElementById('holidayId').value = holiday.extendedProps.holidayId;
            document.getElementById('editHolidayName').value = holiday.title;
            document.getElementById('editDate').value = holiday.startStr;

            document.getElementById('editHolidayModal').style.display = 'flex';
        }

        function openModal(dateStr) {
            selectedDate = dateStr;
            const formattedDate = formatDate(dateStr);
            document.getElementById('selectedDate').innerHTML = 'Set <span style="color: #8B0000;">Holiday</span> for ' + formattedDate;
            document.getElementById('scheduleDate').value = dateStr;
            document.getElementById('scheduleHolidayModal').style.display = 'flex';
        }

        function showModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            }
        }

        function closeModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        <?php if (isset($_SESSION['schedule_success']) && $_SESSION['schedule_success']): ?>
            window.onload = function () {
                showModal('scheduleSuccessModal');
                <?php unset($_SESSION['schedule_success']); ?>
            };
        <?php elseif (isset($_SESSION['edit_success']) && $_SESSION['edit_success']): ?>
            window.onload = function () {
                showModal('editSuccessModal');
                <?php unset($_SESSION['edit_success']); ?>
            };
        <?php elseif (isset($_SESSION['delete_success']) && $_SESSION['delete_success']): ?>
            window.onload = function () {
                showModal('deleteSuccessModal');
                <?php unset($_SESSION['delete_success']); ?>
            };
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            window.onload = function () {
                showModal('scheduleErrorModal');
                <?php unset($_SESSION['error_message']); ?>
            };
        <?php endif; ?>
    </script>

    <div id="pastDateModal" class="modal">
        <div class="modal-content-dateerror">
            <!-- Lottie Animation for Error -->
            <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 20px;">
                <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h3 style="color: #8B0000; margin-bottom: 20px;">Past Dates Can't be Modified</h3>
            <div style="display: flex; justify-content: center;">
                <button class="cancel-btn" onclick="closePastDateModal()">Close</button>
            </div>
        </div>
    </div>
    <!-- Schedule Success Modal -->
    <div id="scheduleSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation for Success -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #000">Holiday successfully scheduled!</h2>
            <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                <button class="confirm-btn" onclick="closeModal('scheduleSuccessModal')">Close</button>
            </div>
        </div>
    </div>
    <!-- Edit Success Modal -->
    <div id="editSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation for Success -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #000">Holiday successfully updated!</h2>
            <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                <button class="confirm-btn" onclick="closeModal('editSuccessModal')">Close</button>
            </div>
        </div>
    </div>
    <!-- Delete Success Modal -->
    <div id="deleteSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation for Success -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #000">Holiday successfully deleted!</h2>
            <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                <button class="confirm-btn" onclick="closeModal('deleteSuccessModal')">Close</button>
            </div>
        </div>
    </div>
    <!-- Schedule Error Modal -->
    <div id="scheduleErrorModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation for Error -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/error-8B0000.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #000">
                <?php echo isset($_SESSION['error_message']) ? $_SESSION['error_message'] : 'An error occurred'; ?>
            </h2>
            <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                <button class="confirm-btn" onclick="closeModal('scheduleErrorModal')">Close</button>
            </div>
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
    <script src="./js/script.js"></script>
    <script src="../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>


<!-- Schedule Modal -->
<!-- <div id="scheduleModal" class="modal">
        <div class="modal-content-date">
            <h2 id="selectedDate">Set Schedule</h2>
            <form id="scheduleForm" action="submit_schedule.php" method="POST">
                <input type="hidden" id="scheduleDate" name="date">
                <div>
                    <label for="timeIn">Time In:</label>
                    <input type="time" id="timeIn" name="time_in" value="08:00" required>
                </div>
                <div>
                    <label for="timeOut">Time Out:</label>
                    <input type="time" id="timeOut" name="time_out" value="16:00" required>
                </div>
                <div>
                    <label for="dayType">Day Type:</label>
                    <select id="dayType" name="day_type" required>
                        <option value="Regular">Regular</option>
                        <option value="Holiday">Holiday</option>
                    </select>
                </div>
                <div class="modal-buttons" style="margin-top: 20px;">
                    <button type="submit" class="confirm-btn">Save Schedule</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('scheduleModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div> -->