<?php
session_start();
require '../../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    header("Location: ../../index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch company details from the database
$company_id = $_SESSION['user_id'];
$query = "SELECT * FROM company WHERE company_id = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $company = $result->fetch_assoc();
    } else {
        $company = [
            'company_name' => 'Unknown',
            'company_email' => 'unknown@wmsu.edu.ph'
        ];
    }
    $stmt->close();
}

// Get the selected day (or default to today)
$selected_day = isset($_GET['day']) ? $_GET['day'] : date('Y-m-d');

// Fetch attendance data for the selected day
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : null;

$attendance_query = "
    SELECT s.student_id, s.student_firstname, s.student_middle, s.student_lastname, s.student_image,
           a.time_in, a.time_out, a.ojt_hours, a.time_out_reason, a.reason
    FROM attendance a
    JOIN student s ON a.student_id = s.student_id
    WHERE DATE(a.time_in) = ? AND s.company = ?
";

// Add search condition if applicable
$query_params = [$selected_day, $company_id];
if ($search) {
    $attendance_query .= " AND (s.student_firstname LIKE ? OR s.student_lastname LIKE ?)";
    $query_params[] = $search;
    $query_params[] = $search;
}

$attendance_query .= " ORDER BY a.time_in ASC";

if ($stmt = $database->prepare($attendance_query)) {
    $stmt->bind_param(str_repeat("s", count($query_params)), ...$query_params);
    $stmt->execute();
    $result = $stmt->get_result();

    $attendances = [];
    while ($row = $result->fetch_assoc()) {
        $attendances[] = $row;
    }
    $stmt->close();
}
function formatDuration($hours)
{
    $totalMinutes = round($hours * 60);
    $hrs = floor($totalMinutes / 60);
    $mins = $totalMinutes % 60;

    $formatted = '';
    if ($hrs > 0) {
        $formatted .= $hrs . ' hr' . ($hrs > 1 ? 's' : '') . ' ';
    }
    if ($mins > 0) {
        $formatted .= $mins . ' min' . ($mins > 1 ? 's' : '');
    }

    return trim($formatted) ?: '0 mins';
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
    <title>Company - Attendance</title>
    <link rel="icon" href="../../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/mobile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>
        .whole-box tbody {
            display: block;
            max-height: 350px;
            overflow-y: scroll;
            width: calc(100% + 17px);
            margin-right: -17px;
        }

        @media (max-width: 768px) {
            .whole-box tbody {
                width: calc(100%);
                margin-right: 0px;
            }
        }

        .modal-content {
            max-height: 80%;
            overflow-y: auto;
        }

        .proof-modal-content {
            width: 400px;
            max-width: 90%;
            text-align: center;
        }

        .proof-image {
            width: 100%;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
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
            <img src="../../uploads/company/<?php echo !empty($company['company_image']) ? $company['company_image'] : 'user.png'; ?>"
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
                <a href="../index.php">
                    <i class="fa-solid fa-house"></i>
                    <span class="link_name">Home</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../index.php">Home</a></li>
                </ul>
            </li>
            <li>
                <a href="../qr-code.php">
                    <i class="fa-solid fa-qrcode"></i>
                    <span class="link_name">QR Scanner</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../qr-code.php">QR Scanner</a></li>
                </ul>
            </li>
            <li>
                <a href="../intern.php">
                    <i class="fa-solid fa-user"></i>
                    <span class="link_name">Interns</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../intern.php">Interns</a></li>
                </ul>
            </li>
            <!-- <li>
                <div class="iocn-link" class="active">
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
                <a href="../message.php">
                    <i class="fa-regular fa-comments"></i>
                    <span class="link_name">Message</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../message.php">Message</a></li>
                </ul>
            </li>
            <li>
                <a href="../feedback.php">
                    <i class="fa-regular fa-star"></i>
                    <span class="link_name">Feedback</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../feedback.php">Feedback</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link" class="active">
                    <a href="../attendance.php">
                        <i class="fa-regular fa-clock"></i>
                        <span class="link_name">Attendance</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="../attendance.php">Attendance</a></li>
                    <li><a href="./attendance.php">Monitoring</a></li>
                </ul>
            </li>
            <li>
                <a href="../calendar.php">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span class="link_name">Schedule</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../calendar.php">Manage Schedule</a></li>
                </ul>
            </li>
            <li>
                <a href="../setting.php">
                    <i class="fas fa-cog"></i>
                    <span class="link_name">Manage Profile</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../setting.php">Manage Profile</a></li>
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
                <label style="color: #a6a6a6; margin-left: 5px;">Attendance Monitoring</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <h2>Attendance -
                        <span style="color: #095d40;">
                            <?php echo date('F d, Y', strtotime($selected_day)); ?>
                        </span>
                    </h2>

                    <div class="filter-group">
                        <!-- Search Student Form -->
                        <form method="GET" action="">
                            <input type="hidden" name="day"
                                value="<?php echo htmlspecialchars($selected_day, ENT_QUOTES); ?>">
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
                        <form method="GET" action="">
                            <button type="submit" class="reset-bar-icon">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </form>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th class="image">Profile</th>
                                <th class="name">Intern Name</th>
                                <th class="timein">Time-in</th>
                                <th class="timeout">Time-out</th>
                                <th class="duration">Duration</th>
                                <th class="duration">Action</th>
                                <th class="duration">Reason</th>
                            </tr>
                        </thead>
                        <tbody style="max-height: 400px; overflow-y: auto;">
                            <?php if (!empty($attendances)): ?>
                                <?php foreach ($attendances as $attendance): ?>
                                    <tr>
                                        <td class="image">
                                            <img style="border-radius: 50%;"
                                                src="../../uploads/student/<?php echo !empty($attendance['student_image']) ? htmlspecialchars($attendance['student_image'], ENT_QUOTES) : 'user.png'; ?>"
                                                alt="Student Image">
                                        </td>
                                        <td class="name">
                                            <?php echo htmlspecialchars($attendance['student_firstname'] . ' ' . $attendance['student_middle'] . ' ' . $attendance['student_lastname'], ENT_QUOTES); ?>
                                        </td>
                                        <td class="timein">
                                            <?php echo $attendance['time_in'] ? date('h:i A', strtotime($attendance['time_in'])) : 'N/A'; ?>
                                        </td>
                                        <td class="timeout">
                                            <?php echo $attendance['time_out'] ? date('h:i A', strtotime($attendance['time_out'])) : 'N/A'; ?>
                                        </td>
                                        <td class="duration">
                                            <?php echo $attendance['ojt_hours'] > 0 ? formatDuration($attendance['ojt_hours']) : 'N/A'; ?>
                                        </td>
                                        <td class="duration">
                                            <?php echo $attendance['time_out_reason'] ?: 'N/A'; ?>
                                        </td>
                                        <td class="duration">
                                            <?php echo $attendance['reason'] ?: 'N/A'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No attendance records found for this day.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    <style>
        #remarkText {
            text-align: center;
            font-size: 1.2em;
            font-weight: 500;
            color: #555;
            margin-top: 10px;
        }
    </style>


    <!-- Remark Modal -->
    <div id="remarkModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/notice-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>

            <h2 id="remarkTypeTitle" data-remark-type="">Remark</h2>

            <p>Reason:</p>
            <p id="remarkText">Loading remark...</p>

            <button class="proof-btn" style="display: none;" onclick="handleProofClick()">
                <i class="fa-solid fa-image"></i>
            </button>
            <button class="approve-btn" id="approveBtn" style="display: none;" onclick="openApprovalModal()">
                <i class="fa-solid fa-check"></i>
            </button>
            <button class="closer-btn" onclick="closeModal('remarkModal')">Close</button>
        </div>
    </div>

    <!-- Proof Modal -->
    <div id="proofModal" class="modal" style="display: none;">
        <div class="modal-content proof-modal-content">
            <!-- Close Button -->
            <span class="close-btn" onclick="closeModal('proofModal')">&times;</span>

            <!-- Image -->
            <img src="" alt="Proof Image" class="proof-image">

            <button class="approve-btn" onclick="openApprovalModal()">
                Approve?
            </button>
        </div>
    </div>

    <!-- Approval Confirmation Modal -->
    <div id="approvalModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/notice-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Confirm Approval</h2>
            <p>Are you sure you want to approve this?</p>
            <button class="approve-btn" onclick="confirmApproval()">
                Confirm
            </button>
            <button class="closer-btn" onclick="closeModal('approvalModal')">Cancel</button>
        </div>
    </div>
    <script>
        function openRemarkModal(studentId, remarkType, remarkId) {
            const titleElement = document.getElementById('remarkTypeTitle');
            const remarkTextElement = document.getElementById('remarkText');
            const proofButton = document.querySelector('.proof-btn');
            const approveButton = document.getElementById('approveBtn');

            // Set the modal title and data attributes
            titleElement.innerText = `${remarkType}`;
            titleElement.setAttribute('data-remark-type', remarkType);
            titleElement.setAttribute('data-remark-id', remarkId); // Save remark ID for later use

            // Reset the remark text
            remarkTextElement.innerText = 'Loading remark...';

            // Adjust button visibility based on the remark type
            if (remarkType === 'Absent') {
                proofButton.style.display = 'inline-block'; // Show proof button
                approveButton.style.display = 'none'; // Hide approve button initially
            } else if (remarkType === 'Late') {
                proofButton.style.display = 'none'; // Hide proof button for "Late"
                approveButton.style.display = 'inline-block'; // Show approve button immediately
            } else {
                proofButton.style.display = 'none';
                approveButton.style.display = 'none';
            }

            // Fetch the remark text dynamically from the server
            fetch(`fetch_remark.php?student_id=${studentId}&remark_type=${remarkType}&remark_id=${remarkId}`)
                .then(response => response.text())
                .then(remark => {
                    remarkTextElement.innerText = remark || 'No remark available.';
                })
                .catch(() => {
                    remarkTextElement.innerText = 'Error loading remark.';
                });

            // Display the remark modal
            document.getElementById('remarkModal').style.display = 'block';
        }



        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function handleProofClick() {
            const proofButton = document.querySelector('.proof-btn');
            const remarkId = document.getElementById('remarkTypeTitle').getAttribute('data-remark-id');
            const proofModal = document.getElementById('proofModal');
            const proofImageElement = proofModal.querySelector('.proof-image');

            if (remarkId) {
                // Fetch proof image dynamically
                fetch(`fetch_proofimg.php?remark_id=${remarkId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.proof_image) {
                            proofImageElement.src = `${data.proof_image}`;
                            proofModal.style.display = 'block';
                        } else {
                            alert('No proof image available.');
                        }
                    })
                    .catch(() => {
                        alert('Error loading proof image.');
                    });
            } else {
                alert('Remark ID not found.');
            }
        }


        function showApproveButton() {
            closeModal('proofModal');
            const approveButton = document.getElementById('approveBtn');
            approveButton.style.display = 'inline-block'; // Show approve button after proof is confirmed.
        }

        function openApprovalModal() {
            document.getElementById('approvalModal').style.display = 'block';
        }

        function confirmApproval() {
            const remarkId = document.getElementById('remarkTypeTitle').getAttribute('data-remark-id');

            if (!remarkId) {
                alert("Invalid remark ID.");
                return;
            }

            // Send the request to approve the remark
            fetch('approve_remark.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `remark_id=${encodeURIComponent(remarkId)}`
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Server Response:', data); // Log the server response
                    if (data.success) {
                        // Show the success modal
                        document.getElementById('remarkApprovalSuccessModal').style.display = 'block';
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                    closeModal('approvalModal');
                    closeModal('remarkModal');
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('Error while approving remark.');
                });
        }

    </script>

    <!-- Remark Approval Success Modal -->
    <div id="remarkApprovalSuccessModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: center; align-items: center;">
                <lottie-player src="../animation/success-095d40.json" background="transparent" speed="1"
                    style="width: 150px; height: 150px;" loop autoplay>
                </lottie-player>
            </div>
            <h2>Remark Approved!</h2>
            <p>The remark has been successfully approved.</p>
            <button class="proceed-btn" onclick="closeModalAndReload('remarkApprovalSuccessModal')">Proceed</button>
        </div>
    </div>

    <script>
        // Function to close the modal and reload the page
        function closeModalAndReload(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none'; // Close the modal
            }
            location.reload(); // Reload the page
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
                <button class="confirm-btn" onclick="logout_intern()">Confirm</button>
                <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
            </div>
        </div>
    </div>
    <script src="../js/script.js"></script>
    <script src="../../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>