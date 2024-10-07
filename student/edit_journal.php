<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['journalId'], $_POST['editJournalTitle'], $_POST['editJournalDate'], $_POST['editJournalDescription'])) {
    $journalId = $_POST['journalId'];
    $journalTitle = $_POST['editJournalTitle'];
    $journalDate = $_POST['editJournalDate'];
    $journalDescription = $_POST['editJournalDescription'];


    if (!empty($journalTitle) && !empty($journalDate) && !empty($journalDescription)) {

        $query = "UPDATE student_journal SET journal_name = ?, journal_date = ?, journal_description = ? WHERE journal_id = ? AND student_id = ?";

        if ($stmt = $database->prepare($query)) {
            $studentId = $_SESSION['user_id'];
            $stmt->bind_param("sssii", $journalTitle, $journalDate, $journalDescription, $journalId, $studentId);

            if ($stmt->execute()) {
                $_SESSION['journal_update_success'] = true;
                header("Location: journal.php");
            } else {
                $_SESSION['journal_error'] = "Error updating journal.";
                header("Location: journal.php");
            }

            $stmt->close();
        } else {
            $_SESSION['journal_error'] = "Error preparing update statement.";
            header("Location: journal.php");
        }
    } else {
        $_SESSION['journal_error'] = "Please fill out all fields.";
        header("Location: journal.php");
    }
} else {
    $_SESSION['journal_error'] = "Invalid request.";
    header("Location: journal.php");
}

?>