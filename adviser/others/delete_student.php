<?php
session_start();
require '../../conn/connection.php';

if (isset($_POST['id'])) {
    $student_id = intval($_POST['id']);

    $sql = "DELETE FROM student WHERE student_id = ?";

    if ($stmt = $database->prepare($sql)) {
        $stmt->bind_param("i", $student_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Student deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Student not found']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to execute deletion']);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the SQL statement']);
    }
    $database->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'No student ID provided']);
}
?>