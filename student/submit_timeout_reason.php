<?php
session_start();
require '../conn/connection.php';

$response = ['success' => false, 'message' => 'Invalid data received.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $schedule_ids = $_POST['schedule_ids'];
    $reason = $_POST['reason'];
    $remark = $_POST['remark'];
    $time_out_time = $_POST['time_out'];
    $proof_image = $_FILES['proof_image'] ?? null;

    if (empty($student_id) || empty($schedule_ids) || empty($reason) || empty($time_out_time)) {
        $response['message'] = 'Student ID, schedule IDs, reason, and time-out are required.';
        echo json_encode($response);
        exit;
    }

    // Handle file upload if proof image is provided
    $proofImageName = null;
    if ($proof_image && $proof_image['error'] === UPLOAD_ERR_OK) {
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
    }

    $time_out_reason = "Time-Out"; // Default time-out reason
    $updated = false;

    // Update the time_out value using the same date as time_in
    $updateQuery = "UPDATE attendance 
                    SET time_out = ?, time_out_reason = ? 
                    WHERE student_id = ? AND schedule_id = ? AND time_out IS NULL";

    foreach ($schedule_ids as $schedule_id) {
        // Retrieve the date from time_in
        $timeInQuery = "SELECT DATE(time_in) AS time_in_date FROM attendance WHERE student_id = ? AND schedule_id = ?";
        if ($timeInStmt = $database->prepare($timeInQuery)) {
            $timeInStmt->bind_param("ii", $student_id, $schedule_id);
            $timeInStmt->execute();
            $result = $timeInStmt->get_result();
            $timeInRow = $result->fetch_assoc();
            $timeInStmt->close();

            if ($timeInRow) {
                $time_in_date = $timeInRow['time_in_date']; // Extract the date from time_in
                $time_out = $time_in_date . ' ' . $time_out_time; // Combine date with inputted time
            } else {
                continue; // Skip if no time_in found
            }
        }

        // Update the attendance table with the constructed time_out value
        if ($updateStmt = $database->prepare($updateQuery)) {
            $updateStmt->bind_param("ssii", $time_out, $time_out_reason, $student_id, $schedule_id);
            if ($updateStmt->execute()) {
                $updated = true;
            }
            $updateStmt->close();
        }
    }

    // Insert into attendance_remarks table
    $inserted = false;
    $insertQuery = "INSERT INTO attendance_remarks (student_id, schedule_id, remark_type, remark, proof_image) 
                    VALUES (?, ?, ?, ?, ?)";

    if ($stmt = $database->prepare($insertQuery)) {
        foreach ($schedule_ids as $schedule_id) {
            $stmt->bind_param("iisss", $student_id, $schedule_id, $remark, $reason, $proofImageName);
            if ($stmt->execute()) {
                $inserted = true;
            }
        }
        $stmt->close();
    }

    // Prepare the final response
    if ($updated && $inserted) {
        $response = ['success' => true, 'message' => 'Time-out and reason updated successfully.'];
    } elseif ($updated) {
        $response = ['success' => true, 'message' => 'Time-out updated successfully, but failed to submit reason.'];
    } elseif ($inserted) {
        $response = ['success' => true, 'message' => 'Reason submitted successfully, but failed to update time-out.'];
    } else {
        $response['message'] = 'Failed to update time-out or submit reason.';
    }
}

echo json_encode($response);
?>