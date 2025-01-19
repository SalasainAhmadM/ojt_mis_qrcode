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
    <title>Company - Schedule Management</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./css/style.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <!-- <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/mobile.css"> -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.js"></script>
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
                <a href="calendar.php" class="active">
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
        </div>

        <div class="content-wrapper">

            <div class="header-box">
                <label style="color: #a6a6a6; margin-left: 5px;">Create QR Code</label>
            </div>
            <div class="main-box">

                <div id="calendar"></div>

                <div class="right-box">
                    <h2 style="text-align: center;">Generated QR Code</h2>
                    <div class="qr-container">
                        <img src="../img/qr-code.png" alt="QR Code" id="qr-code-img">
                        <p class="qr-date" id="qr-date">No Schedule Selected</p>

                        <div class="time-container">
                            <div class="time-label" id="time-in">Time-in: --:--</div>
                            <div class="time-label" id="time-out">Time-out: --:--</div>
                        </div>
                        <div class="action-buttons" id="action-buttons" style="display: none;">
                            <button class="edit-button" onclick="openEditModal(scheduleData.schedule_id, 
                                    scheduleData.time_in, 
                                    scheduleData.time_out, 
                                    scheduleData.day_type)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="delete-button" onclick="openDeleteModal('deleteModal')"><i
                                    class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content-date">
            <h2 id="selectedDate">Set Schedule</h2>
            <form id="scheduleForm" action="submit_schedule.php" method="POST">
                <input type="hidden" id="scheduleDate" name="date">
                <input type="hidden" name="company_id" value="<?php echo $_SESSION['user_id']; ?>">

                <div>
                    <label for="timeIn">Time In:</label>
                    <input type="time" id="timeIn" name="time_in" value="08:00">
                </div>
                <div>
                    <label for="timeOut">Time Out:</label>
                    <input type="time" id="timeOut" name="time_out" value="17:00">
                </div>
                <div>
                    <label for="dayType">Day Type:</label>
                    <select id="dayType" name="day_type" required onchange="adjustTimeFields()">
                        <option value="Regular">Regular</option>
                        <option value="Halfday">Halfday</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
                <div class="modal-buttons" style="margin-top: 20px;">
                    <button type="submit" class="confirm-btn">Save Schedule</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('scheduleModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function adjustTimeFields() {
            const dayType = document.getElementById('dayType').value;
            const timeIn = document.getElementById('timeIn');
            const timeOut = document.getElementById('timeOut');

            if (dayType === 'Halfday') {
                timeIn.disabled = false;
                timeOut.disabled = false;
                timeIn.value = "08:00";
                timeOut.min = "09:00";
                timeOut.max = "13:00";
                timeOut.value = "12:00";
            } else if (dayType === 'Suspended') {
                timeIn.value = "";
                timeOut.value = "";
                timeIn.disabled = true;
                timeOut.disabled = true;
            } else {
                timeIn.disabled = false;
                timeOut.disabled = false;
                timeIn.value = "08:00";
                timeOut.value = "16:00";
                timeOut.removeAttribute('min');
                timeOut.removeAttribute('max');
            }
        }
    </script>


    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/alert-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2 style="color: #000">Are you sure you want to delete?</h2>
            <input type="hidden" id="delete-date" value="">
            <input type="hidden" id="delete-company-id" value="">
            <div style="display: flex; justify-content: space-around; margin-top: 10px; margin-bottom: 20px">
                <button class="confirm-btn" onclick="confirmDelete('')">Confirm</button>
                <button class="cancel-btn" onclick="closeDeleteModal('deleteModal')">Cancel</button>
            </div>
        </div>
    </div>


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


    <!-- Success Modal for Schedule Update -->
    <div id="scheduleSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Schedule Set Successfully!</h2>
            <p>Your schedule has been added successfully for <span
                    style="color: #095d40; font-size: 20px;"><?php echo $_SESSION['full_name']; ?>!</span></p>
            <button class="proceed-btn" onclick="closeModal('scheduleSuccessModal')">Close</button>
        </div>
    </div>
    <!-- Success Modal for Delete Update -->
    <div id="deleteSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/delete.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Schedule Delete Successfully!</h2>
            <p>Your schedule has been deleted successfully for <span
                    style="color: #095d40; font-size: 20px;"><?php echo $_SESSION['full_name']; ?>!</span></p>
            <button class="proceed-btn" onclick="closeDeleteSuccessModal('deleteSuccessModal')">Close</button>
        </div>
    </div>

    <script>
        let selectedDate = null;
        let suspendeds = []; // Array to hold suspended dates

        // Open modal function for future dates
        function openModal(dateStr) {
            selectedDate = dateStr;
            document.getElementById('selectedDate').innerText = "Set Schedule for " + dateStr;
            document.getElementById('scheduleDate').value = dateStr;
            document.getElementById('scheduleModal').style.display = 'flex';
        }

        // Close modal function for future dates
        function closeModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }


        // Open modal for past dates
        function openPastDateModal() {
            document.getElementById('pastDateModal').style.display = 'flex';
        }

        // Close modal for past dates
        function closePastDateModal() {
            document.getElementById('pastDateModal').style.display = 'none';
        }

        // document.addEventListener('DOMContentLoaded', function () {
        //     var calendarEl = document.getElementById('calendar');
        //     var calendar = new FullCalendar.Calendar(calendarEl, {
        //         initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth', // Switch view based on screen size
        //         headerToolbar: {
        //             left: 'prev,next today',
        //             center: 'title',
        //             right: 'dayGridMonth,listWeek'
        //         },
        //         windowResize: function (view) {
        //             var newView = window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth';
        //             calendar.changeView(newView);
        //         },
        //         height: 'auto', // Makes the calendar content height adjust dynamically
        //     });
        //     calendar.render();
        // });



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
                events: function (fetchInfo, successCallback, failureCallback) {
                    const companyId = <?php echo $_SESSION['user_id']; ?>;

                    // Fetch suspended, holidays, regular, and halfday 
                    Promise.all([
                        fetch(`./calendar/fetch_daytype.php?company_id=${companyId}`).then(response => response.json()),
                        fetch(`./calendar/fetch_holidays.php`).then(response => response.json())
                    ])
                        .then(([scheduleData, holidays]) => {
                            // Map each event type
                            const suspendedEvents = scheduleData.suspendeds.map(date => ({
                                title: 'Suspended',
                                start: date,
                                display: 'background',
                                backgroundColor: '#FFA500',
                                borderColor: '#FFA500'
                            }));

                            const regularEvents = scheduleData.regulars.map(date => ({
                                title: 'Regular',
                                start: date,
                                display: 'background',
                                backgroundColor: '#008000',
                                borderColor: '#008000'
                            }));

                            const halfdayEvents = scheduleData.halfdays.map(date => ({
                                title: 'Halfday',
                                start: date,
                                display: 'background',
                                backgroundColor: '#FFFF00',
                                borderColor: '#FFFF00'
                            }));

                            const holidayEvents = holidays.map(holiday => ({
                                start: holiday.holiday_date,
                                title: holiday.holiday_name,
                                display: 'background',
                                backgroundColor: '#FF0000',
                                borderColor: '#FF0000'
                            }));

                            // Combine all events into a single array
                            let allEvents = [...suspendedEvents, ...regularEvents, ...halfdayEvents, ...holidayEvents];

                            // Filter to ensure holidays dominate
                            let filteredEvents = [];
                            let eventDates = new Set();

                            holidayEvents.forEach(holiday => {
                                eventDates.add(holiday.start); // Track holiday dates
                            });

                            allEvents.forEach(event => {
                                if (eventDates.has(event.start) && event.backgroundColor !== '#FF0000') {
                                    // Skip non-holiday events if there's already a holiday on the same date
                                    return;
                                }
                                filteredEvents.push(event);
                            });

                            successCallback(filteredEvents);
                        })
                        .catch(error => console.error('Error fetching events:', error));
                },
                dateClick: function (info) {
                    var clickedDate = new Date(info.date);
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);

                    if (clickedDate < today) {
                        openPastDateModal();
                    } else {
                        fetchSchedule(info.dateStr);
                    }
                }
            });

            calendar.render();
        });


        function openDeleteModal(date, companyId) {
            document.getElementById('deleteModal').style.display = 'flex';

            // Store the date and company ID in hidden inputs
            document.getElementById('delete-date').value = date;
            document.getElementById('delete-company-id').value = companyId;
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        function closeDeleteSuccessModal() {
            document.getElementById('deleteSuccessModal').style.display = 'none';
            window.location.reload();
        }

        function confirmDelete() {
            // Get values from the input fields
            var deleteDate = document.getElementById('delete-date').value;
            var companyId = document.getElementById('delete-company-id').value;

            // Check if the inputs are valid
            if (deleteDate === "" || companyId === "") {
                alert("Please provide both date and company ID.");
                return;
            }

            // AJAX request to delete_schedule.php
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "./calendar/delete_schedule.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Close the delete modal
                    closeDeleteModal('deleteModal');
                    showModal('deleteSuccessModal');

                }

            };

            // Send the data to delete_schedule.php
            xhr.send("date=" + encodeURIComponent(deleteDate) + "&company_id=" + encodeURIComponent(companyId));
        }

        function fetchSchedule(dateStr) {
            const companyId = <?php echo $_SESSION['user_id']; ?>;

            // Fetch both holiday and suspended schedule data in parallel
            const holidayFetch = fetch(`./calendar/get_holiday_schedule.php?date=${dateStr}`);
            const scheduleFetch = fetch(`./calendar/get_schedule.php?date=${dateStr}&company_id=${companyId}`);

            Promise.all([holidayFetch, scheduleFetch])
                .then(async ([holidayResponse, scheduleResponse]) => {
                    const holidayData = await holidayResponse.json();
                    const scheduleData = await scheduleResponse.json();

                    const formattedDate = new Date(dateStr).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    // Log data for debugging
                    console.log("Holiday Data: ", holidayData);
                    console.log("Schedule Data: ", scheduleData);

                    if (holidayData) {
                        document.getElementById('qr-date').innerText =
                            `${holidayData.holiday_name}\n${formattedDate} - No Entry`;
                        document.getElementById('time-in').innerText = "";
                        document.getElementById('time-out').innerText = "";

                        document.getElementById('time-in').style.color = "#FF0000";
                        document.getElementById('qr-date').style.color = "#FF0000";

                        const qrPath = holidayData.generated_qr_code || '../qr-code-error.png';
                        document.getElementById('qr-code-img').src = qrPath;
                        document.getElementById('action-buttons').style.display = 'none';
                    } else if (scheduleData && scheduleData.day_type === "Suspended") {
                        document.getElementById('qr-date').innerText =
                            `${formattedDate} - No Entry`;
                        document.getElementById('time-in').innerText = "";
                        document.getElementById('time-out').innerText = "";

                        document.getElementById('time-in').style.color = "#FFA500";
                        document.getElementById('qr-date').style.color = "#FFA500";

                        const qrPath = scheduleData.generated_qr_code || '../qr-code-error.png';
                        document.getElementById('qr-code-img').src = qrPath;

                        document.getElementById('action-buttons').style.display = 'block';
                        document.querySelector('.delete-button').setAttribute('onclick', `openDeleteModal('${dateStr}', ${companyId})`);
                        document.querySelector('.edit-button').setAttribute('onclick',
                            `openEditModal(${scheduleData.schedule_id}, '${scheduleData.time_in}', '${scheduleData.time_out}', '${scheduleData.day_type}')`);
                    } else if (scheduleData && scheduleData.day_type === "Halfday") {
                        const timeIn = formatTime(scheduleData.time_in);
                        const timeOut = formatTime(scheduleData.time_out);

                        document.getElementById('qr-date').innerText = formattedDate;
                        document.getElementById('time-in').innerText = `Time-in: ${timeIn}`;
                        document.getElementById('time-out').innerText = `Time-out: ${timeOut}`;

                        document.getElementById('time-in').style.color = "";
                        document.getElementById('qr-date').style.color = "#F6BE00";

                        const qrPath = scheduleData.generated_qr_code;
                        document.getElementById('qr-code-img').src = qrPath;

                        document.getElementById('action-buttons').style.display = 'block';
                        document.querySelector('.delete-button').setAttribute('onclick', `openDeleteModal('${dateStr}', ${companyId})`);
                        document.querySelector('.edit-button').setAttribute('onclick',
                            `openEditModal(${scheduleData.schedule_id}, '${scheduleData.time_in}', '${scheduleData.time_out}', '${scheduleData.day_type}')`);
                    } else if (scheduleData) {
                        const timeIn = formatTime(scheduleData.time_in);
                        const timeOut = formatTime(scheduleData.time_out);

                        document.getElementById('qr-date').innerText = formattedDate;
                        document.getElementById('time-in').innerText = `Time-in: ${timeIn}`;
                        document.getElementById('time-out').innerText = `Time-out: ${timeOut}`;

                        document.getElementById('time-in').style.color = "";
                        document.getElementById('qr-date').style.color = "";

                        const qrPath = scheduleData.generated_qr_code;
                        document.getElementById('qr-code-img').src = qrPath;

                        document.getElementById('action-buttons').style.display = 'block';
                        document.querySelector('.delete-button').setAttribute('onclick', `openDeleteModal('${dateStr}', ${companyId})`);
                        document.querySelector('.edit-button').setAttribute('onclick',
                            `openEditModal(${scheduleData.schedule_id}, '${scheduleData.time_in}', '${scheduleData.time_out}', '${scheduleData.day_type}')`);
                    } else {
                        document.getElementById('action-buttons').style.display = 'none';
                        openModal(dateStr);
                    }

                    document.getElementById('qr-code-img').onerror = function () {
                        console.error('QR Code image not found:', this.src);
                        this.src = '../img/qr-code-error.png';
                    };
                })
                .catch(error => {
                    document.getElementById('action-buttons').style.display = 'none';
                });
        }


        function formatTime(time) {
            const [hours, minutes] = time.split(':').map(Number);
            const period = hours >= 12 ? 'pm' : 'am';
            const formattedHours = hours % 12 || 12;
            return `${formattedHours}:${minutes.toString().padStart(2, '0')}${period}`;
        }

        // Show modal
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
        <?php endif; ?>

        function openEditModal(scheduleId, timeIn, timeOut, dayType) {
            document.getElementById('scheduleId').value = scheduleId;
            document.getElementById('editTimeIn').value = timeIn;
            document.getElementById('editTimeOut').value = timeOut;
            document.getElementById('editDayType').value = dayType;

            document.getElementById('editScheduleModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editScheduleModal').style.display = 'none';
        }
    </script>

    <!-- Edit Schedule Modal -->
    <div id="editScheduleModal" class="modal">
        <div class="modal-content-date">
            <h2>Edit Schedule</h2>
            <form id="editScheduleForm" action="update_schedule.php" method="POST">
                <input type="hidden" id="scheduleId" name="schedule_id">
                <input type="hidden" name="company_id" value="<?php echo $_SESSION['user_id']; ?>">

                <div>
                    <label for="editTimeIn">Time In:</label>
                    <input type="time" id="editTimeIn" name="time_in" value="08:00">
                </div>
                <div>
                    <label for="editTimeOut">Time Out:</label>
                    <input type="time" id="editTimeOut" name="time_out" value="16:00">
                </div>
                <div>
                    <label for="editDayType">Day Type:</label>
                    <select id="editDayType" name="day_type" required onchange="adjustEditTimeFields()">
                        <option value="Regular">Regular</option>
                        <option value="Halfday">Halfday</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
                <div class="modal-buttons" style="margin-top: 20px;">
                    <button type="submit" class="confirm-btn">Update Schedule</button>
                    <button type="button" class="cancel-btn"
                        onclick="closeEditModal('editScheduleModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function adjustEditTimeFields() {
            const dayType = document.getElementById('editDayType').value;
            const timeIn = document.getElementById('editTimeIn');
            const timeOut = document.getElementById('editTimeOut');

            if (dayType === 'Halfday') {
                timeIn.disabled = false;
                timeOut.disabled = false;
                timeIn.value = "08:00";
                timeOut.min = "09:00";
                timeOut.max = "13:00";
                timeOut.value = "12:00";
            } else if (dayType === 'Suspended') {
                timeIn.value = "";
                timeOut.value = "";
                timeIn.disabled = true;
                timeOut.disabled = true;
            } else {
                timeIn.disabled = false;
                timeOut.disabled = false;
                timeIn.value = "08:00";
                timeOut.value = "16:00";
                timeOut.removeAttribute('min');
                timeOut.removeAttribute('max');
            }
        }
    </script>
    <!-- Edit Success Modal -->
    <div id="editSuccessModal" class="modal">
        <div class="modal-content">
            <!-- Lottie Animation -->
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Schedule Updated Successfully!</h2>
            <p>Your schedule has been updated successfully for <span
                    style="color: #095d40; font-size: 20px;"><?php echo $_SESSION['full_name']; ?>!</span></p>
            <button class="proceed-btn" onclick="closeModal('editSuccessModal')">Close</button>
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