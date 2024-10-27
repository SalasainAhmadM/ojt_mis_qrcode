<?php
session_start();
require '../conn/connection.php';

if (isset($_POST['id'])) {  // Use POST to retrieve the journal_id
    $journalId = $_POST['id'];

    $query = "DELETE FROM student_journal WHERE journal_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $journalId);
        $stmt->execute();

        $_SESSION['journal_delete_success'] = true;
        $stmt->close();

        // Return a JSON response
        echo json_encode(['status' => 'success']);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete journal entry.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>