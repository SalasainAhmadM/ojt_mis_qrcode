<?php
session_start();
require '../conn/connection.php';

$response = ['success' => false, 'message' => 'Invalid data received.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $schedule_ids = $_POST['schedule_ids'];
    $reason = $_POST['reason'];
    $proof_image = $_FILES['proof_image'];

    // Check required fields
    if (empty($student_id) || empty($schedule_ids) || empty($reason) || empty($proof_image)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    // Handle file upload
    $uploadDir = "../uploads/student/remark/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $proofImageName = $uploadDir . uniqid("proof_", true) . '.' . pathinfo($proof_image['name'], PATHINFO_EXTENSION);

    if (!move_uploaded_file($proof_image['tmp_name'], $proofImageName)) {
        $response['message'] = 'Failed to upload proof image.';
        echo json_encode($response);
        exit;
    }

    // Insert into database
    $inserted = false;
    $query = "INSERT INTO attendance_remarks (student_id, schedule_id, remark_type, remark, proof_image) 
              VALUES (?, ?, 'Absent', ?, ?)";

    if ($stmt = $database->prepare($query)) {
        foreach ($schedule_ids as $schedule_id) {
            $stmt->bind_param("iiss", $student_id, $schedule_id, $reason, $proofImageName);
            if ($stmt->execute()) {
                $inserted = true;
            }
        }
        $stmt->close();
    }

    if ($inserted) {
        $response = ['success' => true, 'message' => 'Absent reason submitted successfully.'];
    } else {
        $response['message'] = 'Failed to submit absent reason.';
    }
}

echo json_encode($response);
?>