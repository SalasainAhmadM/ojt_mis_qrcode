<?php
session_start();
require '../conn/connection.php';

if (isset($_GET['id'])) {
    $journalId = $_GET['id'];

    $query = "DELETE FROM student_journal WHERE journal_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $journalId);
        $stmt->execute();

        $_SESSION['journal_delete_success'] = true;

        $stmt->close();
    }

    header("Location: journal.php");
}
?>