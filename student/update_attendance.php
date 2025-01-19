<?php
require '../conn/connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$attendance_id = $data['attendance_id'];
$reason = $data['reason'];
$inputreason = $data['inputreason'];

$updateQuery = "UPDATE attendance SET time_out_reason = ?, reason = ? WHERE attendance_id = ?";
if ($stmt = $database->prepare($updateQuery)) {
    $stmt->bind_param("ssi", $reason, $inputreason, $attendance_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reason updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update reason.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database query failed.']);
}
?>