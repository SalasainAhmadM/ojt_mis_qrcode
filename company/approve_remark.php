<?php
require '../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the remark ID from POST data
    $remark_id = isset($_POST['remark_id']) ? intval($_POST['remark_id']) : 0;

    if ($remark_id > 0) {
        // Update the remark status to 'Approved'
        $sql = "UPDATE attendance_remarks SET status = 'Approved' WHERE remark_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param('i', $remark_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Remark approved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve the remark.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid remark ID.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$database->close();


?>