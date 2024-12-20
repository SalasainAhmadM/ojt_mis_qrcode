<?php
session_start();
include('../phpqrcode/qrlib.php');
require '../conn/connection.php';

// Set the default timezone to Asia/Manila
// date_default_timezone_set('Asia/Manila');
$database->set_charset("utf8mb4");

$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');
$currentDayOfWeek = date('N');

// Check if the current time is within the auto time-out window (6 PM to 8 AM)
if ($currentTime >= '18:00:00' || $currentTime < '08:00:00') {
    // Determine the date for time-out based on the current time
    $timeoutDate = $currentTime < '08:00:00' ? date('Y-m-d', strtotime('-1 day')) : $currentDate;

    // SQL to update attendance records with time_out = schedule.time_out for the specific date
    $timeoutQuery = "
        UPDATE attendance a
        JOIN schedule s ON a.schedule_id = s.schedule_id
        SET a.time_out = CONCAT(s.date, ' ', s.time_out),
            a.time_out_reason = 'Time-Out'
        WHERE a.time_out IS NULL
          AND s.date = ?
    ";

    // Prepare and execute the update query
    if ($stmt = $database->prepare($timeoutQuery)) {
        $stmt->bind_param("s", $timeoutDate);
        $stmt->execute();

        // Get the number of affected rows
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        // If rows were updated, insert remarks for each affected student
        if ($affectedRows > 0) {
            // SQL to insert into attendance_remarks while avoiding duplicates
            $remarkQuery = "
                INSERT INTO attendance_remarks (student_id, schedule_id, remark_type, remark, status)
                SELECT DISTINCT a.student_id, a.schedule_id, 'Forgot Time-out', 'Auto time-out applied.', 'Pending'
                FROM attendance a
                JOIN schedule s ON a.schedule_id = s.schedule_id
                WHERE a.time_out_reason = 'Time-Out'
                  AND s.date = ?
                  AND NOT EXISTS (
                      SELECT 1 
                      FROM attendance_remarks ar
                      WHERE ar.student_id = a.student_id
                        AND ar.schedule_id = a.schedule_id
                  )
            ";

            // Prepare and execute the insert query
            if ($stmt = $database->prepare($remarkQuery)) {
                $stmt->bind_param("s", $timeoutDate);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}



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