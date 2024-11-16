<?php
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $journal_id = $data['journal_id'] ?? null;

    if ($journal_id) {
        $stmt = $database->prepare("SELECT * FROM student_journal WHERE journal_id = ?");
        $stmt->bind_param("i", $journal_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $journal = $result->fetch_assoc();
            echo json_encode(['success' => true, 'journal' => $journal]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Journal not found']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid journal ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$database->close();
?>