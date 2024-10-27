<?php
session_start();
require '../../conn/connection.php';

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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company - Create QR Code for Interns</title>
    <link rel="icon" href="../../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>

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
                <div style="background-color: #07432e;" class="iocn-link" class="active">
                    <a href="../intern.php">
                        <i class="fa-solid fa-user"></i>
                        <span class="link_name">Interns</span>
                    </a>
                    <i class="fas fa-chevron-down arrow"></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="../intern.php">Interns</a></li>
                    <li><a href="masterlist.php">Masterlist</a></li>
                    <li><a href="create-qr.php">Create QR</a></li>
                    <li><a href="create-id.php">Create ID</a></li>
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
                <a href="../feedback.php">
                    <i class="fa-regular fa-star"></i>
                    <span class="link_name">Feedback</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="../feedback.php">Feedback</a></li>
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
            <i class="fas fa-bars bx-menu"></i>
        </div>

        <div class="content-wrapper">

            <div class="header-box">
                <label style="color: #a6a6a6; margin-left: 5px;">Schedule Management</label>
            </div>
            <div class="main-box">
                <div class="whole-box">
                    <h2>
                        Schedule
                    </h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Day Name</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Time-in</th>
                                <th>Time-out</th>
                                <th class="action">QR Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Monday</td>
                                <td>September 30, 2024</td>
                                <td style="color: green;">Normal Day</td>
                                <td>Open</td>
                                <td>08:00 AM</td>
                                <td>05:00 PM</td>
                                <td class="action">
                                    <!-- QR Button -->
                                    <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-qrcode"></i>
                                    </button> <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Tuesday</td>
                                <td>October 1, 2024</td>
                                <td style="color: orange;">Holiday</td>
                                <td style="color: red;">No Entry</td>
                                <td style="color: red;">No Entry</td>
                                <td style="color: red;">No Entry</td>
                                <td class="action">
                                    <!-- QR Button -->
                                    <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-qrcode"></i>
                                    </button> <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Wednesday</td>
                                <td>October 2, 2024</td>
                                <td style="color: red;"><i>Suspended</i></td>
                                <td><i>Half Day</i></td>
                                <td>08:00 AM</td>
                                <td>12:00 NN</td>
                                <td class="action">
                                    <!-- QR Button -->
                                    <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-qrcode"></i>
                                    </button> <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Thursday</td>
                                <td>October 3, 2024</td>
                                <td style="color: green;">Normal Day</td>
                                <td>Open</td>
                                <td>08:00 AM</td>
                                <td>05:00 PM</td>
                                <td class="action">
                                    <!-- QR Button -->
                                    <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-qrcode"></i>
                                    </button> <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Friday</td>
                                <td>October 4, 2024</td>
                                <td style="color: red;"><i>Suspended</i></td>
                                <td style="color: red;">No Entry</td>
                                <td style="color: red;">No Entry</td>
                                <td style="color: red;">No Entry</td>
                                <td class="action">
                                    <!-- QR Button -->
                                    <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-qrcode"></i>
                                    </button>
                                    <button class="action-qr edit-btn">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>


                </div>
            </div>
        </div>

    </section>

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
                <button class="cancel-btn" onclick="closeModal_intern('logoutModal')">Cancel</button>
            </div>
        </div>
    </div>
    <script>
    </script>
    <script src="../js/script.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</body>

</html>