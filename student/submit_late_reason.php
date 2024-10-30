<?php
session_start();
require '../conn/connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$student_id = $data['student_id'];
$schedule_id = $data['schedule_id'];
$reason = $data['reason'];

// Insert the late reason into the attendance_remarks table
$query = "INSERT INTO attendance_remarks (student_id, schedule_id, remark_type, remark) VALUES (?, ?, 'Late', ?)";
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("iis", $student_id, $schedule_id, $reason);
    $success = $stmt->execute();
    $stmt->close();

    // Set session variable to display the success modal
    if ($success) {
        $_SESSION['late_response_success'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>