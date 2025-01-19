<?php
session_start();
include('../phpqrcode/qrlib.php');
require '../conn/connection.php';

// Set the default timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');
$database->set_charset("utf8mb4");

$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');
$currentDayOfWeek = date('N');

// Auto time-out functionality
// $autoTimeoutQuery = "
//     UPDATE attendance a
//     JOIN schedule s ON a.schedule_id = s.schedule_id
//     SET a.time_out = DATE_ADD(CONCAT(s.date, ' ', s.time_out), INTERVAL 2 HOUR),
//         a.time_out_reason = 'Time-Out'
//     WHERE a.time_out IS NULL AND CONCAT(s.date, ' ', s.time_out) <= NOW();
// ";

// if ($stmt = $database->prepare($autoTimeoutQuery)) {
//     $stmt->execute();
//     $stmt->close();
// }

// // Insert attendance remarks for auto time-out
// $insertRemarksQuery = "
//     INSERT INTO attendance_remarks (student_id, schedule_id, remark_type, remark, proof_image, status)
//     SELECT a.student_id, a.schedule_id, 'Forgot Time-out', 'Auto time-out applied', NULL, 'Pending'
//     FROM attendance a
//     WHERE a.time_out IS NOT NULL AND a.time_out_reason = 'Time-Out'
//       AND NOT EXISTS (
//           SELECT 1 FROM attendance_remarks ar
//           WHERE ar.student_id = a.student_id AND ar.schedule_id = a.schedule_id AND ar.remark_type = 'Forgot Time-out'
//       );
// ";

// if ($stmt = $database->prepare($insertRemarksQuery)) {
//     $stmt->execute();
//     $stmt->close();
// }

$isHoliday = false;
$holidayQuery = "SELECT * FROM holiday WHERE holiday_date = ?";

if ($stmt = $database->prepare($holidayQuery)) {
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $isHoliday = true;
    }
    $stmt->close();
}

if ($currentDayOfWeek >= 1 && $currentDayOfWeek <= 5 && !$isHoliday) { // Only execute on weekdays and non-holidays
    // Query to find companies without a schedule for the current date
    $checkScheduleQuery = "
        SELECT c.company_id 
        FROM company c 
        LEFT JOIN schedule s ON c.company_id = s.company_id AND s.date = ?
        WHERE s.schedule_id IS NULL
    ";

    if ($stmt = $database->prepare($checkScheduleQuery)) {
        $stmt->bind_param("s", $currentDate);
        $stmt->execute();
        $result = $stmt->get_result();

        // Prepare to insert default schedules
        $insertScheduleQuery = "
            INSERT INTO schedule (company_id, date, time_in, time_out, generated_qr_code, day_type) 
            VALUES (?, ?, '08:00:00', '16:00:00', ?, 'Regular')
        ";

        if ($insertStmt = $database->prepare($insertScheduleQuery)) {
            while ($row = $result->fetch_assoc()) {
                $companyId = $row['company_id'];

                // Generate QR code data and file
                $qrData = "$companyId - $currentDate";
                $qrCodeDir = "../uploads/company/qrcodes/";

                if (!is_dir($qrCodeDir)) {
                    mkdir($qrCodeDir, 0755, true);
                }

                $fileName = $qrCodeDir . "qr-schedule-" . $companyId . "-" . $currentDate . ".png";
                QRcode::png($qrData, $fileName, QR_ECLEVEL_L, 10);

                // Bind parameters and insert schedule
                $insertStmt->bind_param("iss", $companyId, $currentDate, $fileName);
                $insertStmt->execute();
            }
            $insertStmt->close();
        }
        $stmt->close();
    }
}
// Get email and password from POST request
$email = $_POST['email'];
$password = $_POST['password'];

// Prepare SQL queries for different user types
$queries = [
    "student" => "SELECT * FROM student WHERE student_email = ?",
    "adviser" => "SELECT * FROM adviser WHERE adviser_email = ?",
    "company" => "SELECT * FROM company WHERE company_email = ?",
    "admin" => "SELECT * FROM admin WHERE admin_email = ?",
];

// Iterate over each user type and check credentials
foreach ($queries as $role => $query) {
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("s", $email); // Bind email parameter
        $stmt->execute(); // Execute the query
        $result = $stmt->get_result(); // Get the result

        if ($result->num_rows > 0) {
            // If user is found, fetch data
            $user = $result->fetch_assoc();

            // Check the hashed password
            $password_field = $role . '_password';
            if (password_verify($password, $user[$password_field])) {
                // Check if the user is a student and if they are verified
                if ($role === 'student' && !empty($user['verification_code'])) {
                    $_SESSION['login_error'] = "Your account has not been verified yet.";
                    header("Location: ../index.php?login=not_verified");
                    exit();
                }

                // If password is correct and student is verified (or not a student), set session variables
                $_SESSION['role'] = $role;
                $_SESSION['user_id'] = $user[$role . '_id'];

                // Set the full name or company name in the session
                if ($role === 'company') {
                    $_SESSION['full_name'] = $user['company_name'];
                } else {
                    $_SESSION['full_name'] = $user[$role . '_firstname'] . ' ' . $user[$role . '_middle'] . '.' . ' ' . $user[$role . '_lastname'];
                }

                $_SESSION['login_success'] = true; // Set login success session variable

                // Check for student role and redirect based on wmsu_id
                if ($role === 'student') {
                    if (empty($user['wmsu_id'])) {
                        header("Location: ../student/home.php?login=success");
                    } else {
                        header("Location: ../student/index.php?login=success");
                    }
                } elseif ($role === 'adviser') {
                    header("Location: ../adviser/index.php?login=success");
                } elseif ($role === 'company') {
                    header("Location: ../company/index.php?login=success");
                } elseif ($role === 'admin') {
                    header("Location: ../admin/index.php?login=success");
                }
                exit();
            }
        }
        $stmt->close(); // Close the statement
    }
}

// If no match found, set an error session variable and redirect to login page with error
$_SESSION['login_error'] = "Invalid email or password!";
header("Location: ../index.php?login=error");
exit();
?>