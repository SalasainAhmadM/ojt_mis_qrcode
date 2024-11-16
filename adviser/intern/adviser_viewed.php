<?php
require '../../conn/connection.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['journal_id'])) {
    $journalId = (int) $data['journal_id'];

    $stmt = $database->prepare("UPDATE student_journal SET adviser_viewed = 1 WHERE journal_id = ?");
    $stmt->bind_param("i", $journalId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid journal ID']);
}
?>