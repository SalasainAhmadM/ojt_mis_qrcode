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
    <title>Company - Message</title>
    <link rel="icon" href="../img/ccs.png" type="image/icon type">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/mobile.css">
    <!-- <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/mobile.css"> -->
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
                <a href="message.php" class="active">
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
        <!-- </div> -->

        <div class="chat-container">
            <!-- Adviser List -->
            <div class="adviser-list" id="adviserList">
                <?php
                // Fetch all advisers with unread message count
                $adviserQuery = "
                SELECT a.adviser_id, a.adviser_image, a.adviser_firstname, a.adviser_middle, a.adviser_lastname,
                COUNT(m.is_read) AS unread_count
                FROM adviser a
                LEFT JOIN messages m 
                ON m.sender_id = a.adviser_id AND m.receiver_id = ? AND m.sender_type = 'adviser' AND m.is_read = 0
                GROUP BY a.adviser_id, a.adviser_image, a.adviser_firstname, a.adviser_middle, a.adviser_lastname";

                if ($adviserStmt = $database->prepare($adviserQuery)) {
                    $adviserStmt->bind_param("i", $_SESSION['user_id']);
                    $adviserStmt->execute();
                    $adviserResult = $adviserStmt->get_result();

                    while ($adviser = $adviserResult->fetch_assoc()) {
                        $fullName = htmlspecialchars($adviser['adviser_firstname'] . ' ' . $adviser['adviser_middle'] . ' ' . $adviser['adviser_lastname']);
                        $adviserImage = !empty($adviser['adviser_image']) ? htmlspecialchars($adviser['adviser_image']) : 'user.png';
                        $unreadCount = $adviser['unread_count'];

                        echo '
                             <div class="adviser-item" data-adviser-id="' . $adviser['adviser_id'] . '">
                             <img src="../uploads/adviser/' . $adviserImage . '" alt="Adviser Image">
                             <div class="adviser-name">' . $fullName . '</div>';

                        // Display the unread message count if there are unread messages
                        if ($unreadCount > 0) {
                            echo '<div class="unread-count">
                                     <i class="fas fa-envelope"></i>
                                     <span class="count">' . $unreadCount . '</span>
                                 </div>';
                        }
                        echo '</div>';
                    }
                    $adviserStmt->close();
                }
                ?>
            </div>


            <!-- Chat Box -->
            <div class="chat-box" id="chatBox">
                <div class="chat-header" id="chatHeader">
                    <img src="../uploads/adviser/user.png" alt="Adviser Image" id="chatAdviserImage">
                    <span id="chatAdviserName">Select an adviser to start chatting</span>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <!-- Messages will be loaded here dynamically -->
                </div>
                <div class="chat-input">
                    <input type="text" id="messageInput" placeholder="Type a message..." disabled>
                    <button id="sendMessageBtn" disabled><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
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
        document.getElementById('messageInput').addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                document.getElementById('sendMessageBtn').click();
            }
        });



        const socket = io('http://localhost:3000');

        // Listen for new messages
        socket.on('receiveMessage', (data) => {
            const chatMessages = document.getElementById('chatMessages');
            const messageElement = document.createElement('div');
            messageElement.className = 'message';
            messageElement.textContent = `${data.senderName}: ${data.message}`;
            chatMessages.appendChild(messageElement);
        });

        // Handle typing status
        socket.on('typing', (senderId) => {
            const typingElement = document.getElementById('typingStatus');
            typingElement.textContent = `User ${senderId} is typing...`;
        });

        // Emit message
        document.getElementById('sendMessageBtn').addEventListener('click', () => {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value;

            const data = {
                senderId: currentUserId,
                receiverId: currentChatId,
                message,
            };

            socket.emit('sendMessage', data);
            messageInput.value = ''; // Clear input field
        });

        // Emit typing status
        document.getElementById('messageInput').addEventListener('input', () => {
            socket.emit('typing', { senderId: currentUserId, receiverId: currentChatId });
        });

    </script>
    <script src="../js/server.js"></script>
    <script src="./js/script.js"></script>
    <script src="./js/message.js"></script>
    <script src="../js/sy.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <script src="https://cdn.socket.io/4.5.1/socket.io.min.js"></script>

</body>

</html>