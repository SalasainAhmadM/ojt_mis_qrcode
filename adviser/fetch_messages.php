<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$adviser_id = $_SESSION['user_id'];
$company_id = $_POST['company_id'] ?? null;

if (!$company_id) {
    echo json_encode(['error' => 'Company ID is required']);
    exit();
}

// Mark unread messages as read when the conversation is opened
$markAsReadQuery = "UPDATE messages SET is_read = 1 
                    WHERE receiver_id = ? AND sender_id = ? AND sender_type = 'company' AND is_read = 0";
if ($stmt = $database->prepare($markAsReadQuery)) {
    $stmt->bind_param("ii", $adviser_id, $company_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch messages between the selected company and the adviser
$query = "SELECT * FROM messages 
          WHERE (sender_id = ? AND receiver_id = ? AND sender_type = 'adviser')
          OR (sender_id = ? AND receiver_id = ? AND sender_type = 'company')
          ORDER BY timestamp ASC";

if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("iiii", $adviser_id, $company_id, $company_id, $adviser_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];

    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'message' => htmlspecialchars($row['message']),
            'timestamp' => $row['timestamp'],
            'sender_type' => $row['sender_type']
        ];
    }

    echo json_encode($messages);
    $stmt->close();
} else {
    echo json_encode(['error' => 'Query failed']);
}
?>