<?php
session_start();
require '../conn/connection.php';

function formatTime($hours, $minutes)
{
    $hoursString = $hours . ($hours === 1 ? " hour" : " hours");
    $minutesString = $minutes . ($minutes === 1 ? " minute" : " minutes");
    return $hoursString . ' ' . $minutesString;
}

// Get the QR data from the request
$data = json_decode(file_get_contents('php://input'), true);
$qrData = $data['qrData'] ?? null;

if ($qrData) {
    // Fetch student details based on the QR code (wmsu_id)
    $query = "
    SELECT 
          s.*, 
          d.department_name AS department, 
          c.company_name AS company, 
          a.adviser_firstname, 
          a.adviser_middle,
          a.adviser_lastname,
          cs.course_section_name AS course_section
    FROM student s
    LEFT JOIN departments d ON s.department = d.department_id
    LEFT JOIN company c ON s.company = c.company_id
    LEFT JOIN adviser a ON s.adviser = a.adviser_id
    LEFT JOIN course_sections cs ON s.course_section = cs.id
    WHERE s.wmsu_id = ?";

    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("s", $qrData);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();

            // Check if the student already has a time-in record today
            $student_id = $student['student_id'];
            $date = date('Y-m-d');
            $checkQuery = "SELECT * FROM attendance WHERE student_id = ? AND DATE(time_in) = ? AND time_out IS NULL";
            if ($checkStmt = $database->prepare($checkQuery)) {
                $checkStmt->bind_param("is", $student_id, $date);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult->num_rows > 0) {
                    // Student is timing out 
                    $attendanceRecord = $checkResult->fetch_assoc();
                    $attendance_id = $attendanceRecord['attendance_id'];

                    // Update the attendance record with time-out and calculate the OJT hours
                    $updateQuery = "UPDATE attendance SET time_out = NOW() WHERE attendance_id = ?";
                    if ($updateStmt = $database->prepare($updateQuery)) {
                        $updateStmt->bind_param("i", $attendance_id);
                        if ($updateStmt->execute()) {
                            // Fetch the updated time-out timestamp and OJT hours
                            $timeOutQuery = "
                            SELECT time_in, time_out, TIMESTAMPDIFF(MINUTE, time_in, time_out) AS total_minutes 
                            FROM attendance 
                            WHERE attendance_id = ?";
                            if ($timeOutStmt = $database->prepare($timeOutQuery)) {
                                $timeOutStmt->bind_param("i", $attendance_id);
                                $timeOutStmt->execute();
                                $timeOutResult = $timeOutStmt->get_result();
                                if ($timeOutRow = $timeOutResult->fetch_assoc()) {
                                    date_default_timezone_set('Asia/Manila');
                                    $time_out = (new DateTime($timeOutRow['time_out']))->format('h:i A');
                                    $date_in = (new DateTime($timeOutRow['time_in']))->format('F j, Y');

                                    // Convert total minutes to hours and minutes
                                    $total_minutes = $timeOutRow['total_minutes'];
                                    $hours = floor($total_minutes / 60);
                                    $minutes = $total_minutes % 60;

                                    // Format the OJT hours
                                    $ojt_hours = formatTime($hours, $minutes);

                                    // Fetch total OJT hours for the student
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
                                            'student_name' => $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname'],
                                            'student_image' => $student['student_image'],
                                            'wmsu_id' => $student['wmsu_id'],
                                            // 'course_section' => $student['course_section'],
                                            'email' => $student['student_email'],
                                            // 'contact' => $student['contact_number'],
                                            // 'batch_year' => $student['batch_year'],
                                            // 'department' => $student['department'],
                                            // 'company' => $student['company'],
                                            // 'adviser' => $student['adviser_firstname'] . ' ' . $student['adviser_middle'] . '.' . ' ' . $student['adviser_lastname'],
                                            // 'barangay' => $student['address_barangay'],
                                            // 'street' => $student['address_street'],
                                            'time_out' => $time_out,
                                            'date_in' => $date_in,
                                            'ojt_hours' => $ojt_hours,
                                            'total_ojt_hours' => $total_ojt_hours
                                        ]);
                                        $totalHoursStmt->close();
                                    }
                                }
                                $timeOutStmt->close();
                            }
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => 'Error saving time-out record.'
                            ]);
                        }
                        $updateStmt->close();
                    }
                } else {
                    // Insert time-in for the student (no time-in today)
                    $insertQuery = "INSERT INTO attendance (student_id, time_in) VALUES (?, NOW())";
                    if ($insertStmt = $database->prepare($insertQuery)) {
                        $insertStmt->bind_param("i", $student_id);
                        if ($insertStmt->execute()) {
                            // Fetch the time-in timestamp
                            $timeInQuery = "
                            SELECT time_in 
                            FROM attendance 
                            WHERE attendance_id = ?";
                            if ($timeInStmt = $database->prepare($timeInQuery)) {
                                $attendance_id = $insertStmt->insert_id; // Get the inserted attendance ID
                                $timeInStmt->bind_param("i", $attendance_id);
                                $timeInStmt->execute();
                                $timeInResult = $timeInStmt->get_result();
                                if ($timeInRow = $timeInResult->fetch_assoc()) {
                                    date_default_timezone_set('Asia/Manila');
                                    $time_in = (new DateTime($timeInRow['time_in']))->format('h:i A');
                                    $date_in = (new DateTime($timeInRow['time_in']))->format('F j, Y');

                                    // Fetch total OJT hours for the student
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
                                            'message' => 'Time-in successful',
                                            'event_type' => 'Time-in',
                                            'student_image' => $student['student_image'],
                                            'student_name' => $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname'],
                                            'wmsu_id' => $student['wmsu_id'],
                                            'course_section' => $student['course_section'],
                                            'email' => $student['student_email'],
                                            // 'contact' => $student['contact_number'],
                                            // 'batch_year' => $student['batch_year'],
                                            // 'department' => $student['department'],
                                            // 'company' => $student['company'],
                                            // 'adviser' => $student['adviser_firstname'] . ' ' . $student['adviser_middle'] . '.' . ' ' . $student['adviser_lastname'],
                                            // 'barangay' => $student['address_barangay'],
                                            // 'street' => $student['address_street'],
                                            'time_in' => $time_in,
                                            'date_in' => $date_in,
                                            'total_ojt_hours' => $total_ojt_hours
                                        ]);
                                        $totalHoursStmt->close();
                                    }
                                }
                                $timeInStmt->close();
                            }
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => 'Error saving time-in record.'
                            ]);
                        }
                        $insertStmt->close();
                    }
                }
                $checkStmt->close();
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No student found for the scanned QR code.'
            ]);
        }
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid QR code.'
    ]);
}
?>