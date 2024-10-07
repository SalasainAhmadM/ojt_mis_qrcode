<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$adviser_id = $_SESSION['user_id'];
$company_id = $_POST['company_id'] ?? null;
$message = $_POST['message'] ?? null;

if (!$company_id || !$message) {
    echo json_encode(['error' => 'Company ID and message are required']);
    exit();
}

$query = "INSERT INTO messages (sender_id, receiver_id, message, sender_type) 
          VALUES (?, ?, ?, 'adviser')";

if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("iis", $adviser_id, $company_id, $message);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to send message']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Query failed']);
}
?>