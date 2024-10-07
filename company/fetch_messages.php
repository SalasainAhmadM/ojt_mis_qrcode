<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$company_id = $_SESSION['user_id'];
$adviser_id = $_POST['adviser_id'] ?? null;

if (!$adviser_id) {
    echo json_encode(['error' => 'Adviser ID is required']);
    exit();
}

// Mark unread messages as read when the conversation is opened
$markAsReadQuery = "UPDATE messages SET is_read = 1 
                    WHERE receiver_id = ? AND sender_id = ? AND sender_type = 'adviser' AND is_read = 0";
if ($stmt = $database->prepare($markAsReadQuery)) {
    $stmt->bind_param("ii", $company_id, $adviser_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch messages between the selected adviser and the company
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