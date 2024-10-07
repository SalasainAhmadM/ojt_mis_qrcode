<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$company_id = $_SESSION['user_id'];
$adviser_id = $_POST['adviser_id'] ?? null;
$message = $_POST['message'] ?? null;

if (!$adviser_id || !$message) {
    echo json_encode(['error' => 'Adviser ID and message are required']);
    exit();
}

$query = "INSERT INTO messages (sender_id, receiver_id, message, sender_type) 
          VALUES (?, ?, ?, 'company')";

if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("iis", $company_id, $adviser_id, $message);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to send message']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Query failed']);
}
