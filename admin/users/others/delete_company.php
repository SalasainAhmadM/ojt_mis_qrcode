<?php
session_start();
require '../../../conn/connection.php';

if (isset($_POST['id'])) {
    $company_id = intval($_POST['id']);

    $sql = "DELETE FROM company WHERE company_id = ?";

    if ($stmt = $database->prepare($sql)) {
        $stmt->bind_param("i", $company_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Company deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'company not found']);
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
    echo json_encode(['status' => 'error', 'message' => 'No company ID provided']);
}
?>