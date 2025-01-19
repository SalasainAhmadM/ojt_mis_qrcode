<?php
session_start();

require '../conn/connection.php'; // Assuming your database connection is in this file

// Set timezone to Manila
date_default_timezone_set('Asia/Manila');
$database->query("SET time_zone = '+08:00'");
// Function to format hours and minutes
function formatTime($hours, $minutes)
{
    $hoursString = $hours . ($hours === 1 ? " hour" : " hours");
    $minutesString = $minutes . ($minutes === 1 ? " minute" : " minutes");
    return $hoursString . ' ' . $minutesString;
}

// Check if the user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php"); // Redirect if not logged in
    exit();
}

// Get the student ID from the session
$student_id = $_SESSION['user_id'];

// Fetch student details based on the student_id from the session
$studentQuery = "SELECT student_image, student_firstname, student_middle, student_lastname, wmsu_id, company, student_email 
                 FROM student 
                 WHERE student_id = ?";
if ($studentStmt = $database->prepare($studentQuery)) {
    $studentStmt->bind_param("i", $student_id);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();

    if ($studentResult->num_rows > 0) {
        $student = $studentResult->fetch_assoc();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Student not found'
        ]);
        exit();
    }
}

// Get the company_id from the QR data (posted from JS)
$data = json_decode(file_get_contents('php://input'), true);
$qr_code_company_id = $data['company_id'];
$current_date = date('Y-m-d'); // Get today's date

// Check if the QR code's company_id matches the student's assigned company_id
if ((int) $student['company'] !== (int) $qr_code_company_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Scan your company QR code.'
    ]);
    exit();
}

// Step 1: Find the schedule that matches the company_id and today's date
$query = "SELECT schedule_id FROM schedule WHERE company_id = ? AND date = ?";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("is", $qr_code_company_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid QR code scanned. Please scan the correct QR code for your schedule.'
        ]);
        exit();
    }

    $schedule = $result->fetch_assoc();
    $schedule_id = $schedule['schedule_id'];

    // Step 2: Check if the student already has a time-in for today without a time-out
    $checkQuery = "SELECT * FROM attendance WHERE student_id = ? AND schedule_id = ? AND DATE(time_in) = ? AND time_out IS NULL";
    if ($checkStmt = $database->prepare($checkQuery)) {
        $checkStmt->bind_param("iis", $student_id, $schedule_id, $current_date);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        // Set timezone to Manila
        date_default_timezone_set('Asia/Manila');

        if ($checkResult->num_rows > 0) {
            // Student is timing out
            $attendanceRecord = $checkResult->fetch_assoc();
            $attendance_id = $attendanceRecord['attendance_id'];

            // Update the attendance record with time-out and calculate OJT hours
            $updateQuery = "UPDATE attendance SET time_out = NOW() WHERE attendance_id = ?";
            if ($updateStmt = $database->prepare($updateQuery)) {
                $updateStmt->bind_param("i", $attendance_id);
                if ($updateStmt->execute()) {
                    // Fetch time-out and OJT hours details
                    $timeOutQuery = "
                        SELECT time_in, time_out, TIMESTAMPDIFF(MINUTE, time_in, time_out) AS total_minutes 
                        FROM attendance 
                        WHERE attendance_id = ?";
                    if ($timeOutStmt = $database->prepare($timeOutQuery)) {
                        $timeOutStmt->bind_param("i", $attendance_id);
                        $timeOutStmt->execute();
                        $timeOutResult = $timeOutStmt->get_result();
                        if ($timeOutRow = $timeOutResult->fetch_assoc()) {
                            $time_out = (new DateTime($timeOutRow['time_out']))->format('h:i A');
                            $date_in = (new DateTime($timeOutRow['time_in']))->format('F j, Y');

                            // Convert total minutes to hours and minutes
                            $total_minutes = $timeOutRow['total_minutes'];
                            $hours = floor($total_minutes / 60);
                            $minutes = $total_minutes % 60;
                            $ojt_hours = formatTime($hours, $minutes);

                            // Fetch total OJT hours
                            $totalHoursQuery = "
                                SELECT SUM(TIMESTAMPDIFF(MINUTE, time_in, time_out)) AS total_ojt_minutes 
                                FROM attendance 
                                WHERE student_id = ? AND time_out IS NOT NULL";
                            if ($totalHoursStmt = $database->prepare($totalHoursQuery)) {
                                $totalHoursStmt->bind_param("i", $student_id);
                                $totalHoursStmt->execute();
                                $totalHoursResult = $totalHoursStmt->get_result();
                                $totalOjtHoursRow = $totalHoursResult->fetch_assoc();
                                $total_minutes = $totalOjtHoursRow['total_ojt_minutes'];
                                $total_hours = floor($total_minutes / 60);
                                $total_remaining_minutes = $total_minutes % 60;
                                $total_ojt_hours = formatTime($total_hours, $total_remaining_minutes);

                                echo json_encode([
                                    'success' => true,
                                    'message' => 'Time-out successful',
                                    'event_type' => 'Time-out',
                                    'student_image' => $student['student_image'],
                                    'student_name' => $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname'],
                                    'wmsu_id' => $student['wmsu_id'],
                                    'email' => $student['student_email'],
                                    'time_out' => $time_out,
                                    'date_in' => $date_in,
                                    'ojt_hours' => $ojt_hours,
                                    'attendance_id' => $attendance_id,
                                    'total_ojt_hours' => $total_ojt_hours
                                ]);
                            }
                        }
                    }
                }
            }
        } else {
            // Student is timing in
            $insertQuery = "INSERT INTO attendance (student_id, schedule_id, time_in) VALUES (?, ?, NOW())";
            if ($insertStmt = $database->prepare($insertQuery)) {
                $insertStmt->bind_param("ii", $student_id, $schedule_id);

                if ($insertStmt->execute()) {
                    $attendance_id = $insertStmt->insert_id;

                    // Fetch time-in details
                    $timeInQuery = "SELECT time_in FROM attendance WHERE attendance_id = ?";
                    if ($timeInStmt = $database->prepare($timeInQuery)) {
                        $timeInStmt->bind_param("i", $attendance_id);
                        $timeInStmt->execute();
                        $timeInResult = $timeInStmt->get_result();

                        if ($timeInRow = $timeInResult->fetch_assoc()) {
                            $time_in = (new DateTime($timeInRow['time_in']))->format('h:i A');
                            $date_in = (new DateTime($timeInRow['time_in']))->format('F j, Y');

                            // Calculate total OJT hours
                            $totalHoursQuery = "
                                SELECT SUM(TIMESTAMPDIFF(MINUTE, time_in, time_out)) AS total_ojt_minutes 
                                FROM attendance 
                                WHERE student_id = ? AND time_out IS NOT NULL";
                            if ($totalHoursStmt = $database->prepare($totalHoursQuery)) {
                                $totalHoursStmt->bind_param("i", $student_id);
                                $totalHoursStmt->execute();
                                $totalHoursResult = $totalHoursStmt->get_result();
                                $totalOjtHoursRow = $totalHoursResult->fetch_assoc();

                                $total_minutes = $totalOjtHoursRow['total_ojt_minutes'] ?? 0;
                                $total_hours = floor($total_minutes / 60);
                                $total_remaining_minutes = $total_minutes % 60;
                                $total_ojt_hours = sprintf("%d hours, %d minutes", $total_hours, $total_remaining_minutes);

                                echo json_encode([
                                    'success' => true,
                                    'message' => 'Time-in successful',
                                    'event_type' => 'Time-in',
                                    'student_image' => $student['student_image'],
                                    'student_name' => $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname'],
                                    'wmsu_id' => $student['wmsu_id'],
                                    'email' => $student['student_email'],
                                    'time_in' => $time_in,
                                    'date_in' => $date_in,
                                    'total_ojt_hours' => $total_ojt_hours
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }
}
?>