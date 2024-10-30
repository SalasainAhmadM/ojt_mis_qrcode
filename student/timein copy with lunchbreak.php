<?php
session_start();
require '../conn/connection.php';

function formatTime($hours, $minutes)
{
    $hoursString = $hours . ($hours === 1 ? " hour" : " hours");
    $minutesString = $minutes . ($minutes === 1 ? " minute" : " minutes");
    return $hoursString . ' ' . $minutesString;
}

function isLunchBreakTime()
{
    $currentTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $hour = (int) $currentTime->format('H'); // Get hour in 24-hour format

    // Check if the current time falls between 11 AM to 1 PM
    return $hour >= 11 && $hour < 13;
}

$data = json_decode(file_get_contents('php://input'), true);
$qrData = $data['qrData'] ?? null;

if ($qrData) {
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
            $student_id = $student['student_id'];
            $date = date('Y-m-d');

            $checkQuery = "SELECT * FROM attendance WHERE student_id = ? AND DATE(time_in) = ? AND time_out IS NULL";
            if ($checkStmt = $database->prepare($checkQuery)) {
                $checkStmt->bind_param("is", $student_id, $date);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult->num_rows > 0) {
                    $attendanceRecord = $checkResult->fetch_assoc();
                    $attendance_id = $attendanceRecord['attendance_id'];

                    $updateQuery = "UPDATE attendance SET time_out = NOW() WHERE attendance_id = ?";
                    if ($updateStmt = $database->prepare($updateQuery)) {
                        $updateStmt->bind_param("i", $attendance_id);
                        if ($updateStmt->execute()) {
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

                                    $total_minutes = $timeOutRow['total_minutes'];
                                    $hours = floor($total_minutes / 60);
                                    $minutes = $total_minutes % 60;
                                    $ojt_hours = formatTime($hours, $minutes);

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

                                        // Check if this is a lunch break
                                        if (isLunchBreakTime()) {
                                            echo json_encode([
                                                'success' => true,
                                                'message' => 'Lunch Break Time-out detected!',
                                                'event_type' => 'Lunch Break',
                                                'student_name' => $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname'],
                                                'student_image' => $student['student_image'],
                                                'time_out' => $time_out,
                                                'ojt_hours' => $ojt_hours,
                                                'total_ojt_hours' => $total_ojt_hours
                                            ]);
                                        } else {
                                            echo json_encode([
                                                'success' => true,
                                                'message' => 'Time-out successful',
                                                'event_type' => 'Time-out',
                                                'student_name' => $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname'],
                                                'student_image' => $student['student_image'],
                                                'time_out' => $time_out,
                                                'ojt_hours' => $ojt_hours,
                                                'total_ojt_hours' => $total_ojt_hours
                                            ]);
                                        }
                                        $totalHoursStmt->close();
                                    }
                                }
                                $timeOutStmt->close();
                            }
                        }
                        $updateStmt->close();
                    }
                } else {
                    $insertQuery = "INSERT INTO attendance (student_id, time_in) VALUES (?, NOW())";
                    if ($insertStmt = $database->prepare($insertQuery)) {
                        $insertStmt->bind_param("i", $student_id);
                        if ($insertStmt->execute()) {
                            $timeInQuery = "SELECT time_in FROM attendance WHERE attendance_id = ?";
                            if ($timeInStmt = $database->prepare($timeInQuery)) {
                                $attendance_id = $insertStmt->insert_id;
                                $timeInStmt->bind_param("i", $attendance_id);
                                $timeInStmt->execute();
                                $timeInResult = $timeInStmt->get_result();
                                if ($timeInRow = $timeInResult->fetch_assoc()) {
                                    date_default_timezone_set('Asia/Manila');
                                    $time_in = (new DateTime($timeInRow['time_in']))->format('h:i A');
                                    $date_in = (new DateTime($timeInRow['time_in']))->format('F j, Y');

                                    echo json_encode([
                                        'success' => true,
                                        'message' => 'Time-in successful',
                                        'event_type' => 'Time-in',
                                        'student_name' => $student['student_firstname'] . ' ' . $student['student_middle'] . '.' . ' ' . $student['student_lastname'],
                                        'time_in' => $time_in,
                                        'total_ojt_hours' => 0
                                    ]);
                                }
                                $timeInStmt->close();
                            }
                        }
                        $insertStmt->close();
                    }
                }
                $checkStmt->close();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No student found for the scanned QR code.']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid QR code.']);
}
?>