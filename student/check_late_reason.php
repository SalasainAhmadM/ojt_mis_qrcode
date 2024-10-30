<?php
session_start();
require '../conn/connection.php';

// Set the response header to return JSON
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['student_id'], $data['schedule_id'])) {
    $student_id = $data['student_id'];
    $schedule_id = $data['schedule_id'];

    $query = "SELECT 1 FROM attendance_remarks WHERE student_id = ? AND schedule_id = ? AND remark_type = 'Late' AND DATE(created_at) = CURDATE()";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("ii", $student_id, $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode(["alreadySubmitted" => $result->num_rows > 0]);
        $stmt->close();
    } else {
        echo json_encode(["error" => "Database query error."]);
    }
} else {
    echo json_encode(["error" => "Invalid input data."]);
}
?>