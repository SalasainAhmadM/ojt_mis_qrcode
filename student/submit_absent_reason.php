<?php
session_start();
require '../conn/connection.php';

// Retrieve JSON data from the POST request
$data = json_decode(file_get_contents('php://input'), true);

// Check if the necessary data is set
if (isset($data['student_id'], $data['schedule_ids'], $data['reason'])) {
    $student_id = $data['student_id'];
    $schedule_ids = $data['schedule_ids'];
    $reason = $data['reason'];

    $inserted = false; // Flag to track successful inserts

    // Prepare the insert statement for adding absence remarks
    $query = "INSERT INTO attendance_remarks (student_id, schedule_id, remark_type, remark) VALUES (?, ?, 'Absent', ?)";
    if ($stmt = $database->prepare($query)) {
        // Bind parameters and execute for each schedule_id
        foreach ($schedule_ids as $schedule_id) {
            $stmt->bind_param("iis", $student_id, $schedule_id, $reason);
            if ($stmt->execute()) {
                $inserted = true;
            }
        }
        $stmt->close();
    }

    // Return a JSON response based on whether any inserts were successful
    if ($inserted) {
        echo json_encode(['success' => true, 'message' => 'Absent reason submitted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit absent reason.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
}
?>