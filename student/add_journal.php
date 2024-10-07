<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_SESSION['user_id'];
    $journalTitle = $_POST['journalTitle'];
    $journalDate = $_POST['journalDate'];
    $journalDescription = $_POST['journalDescription'];
    $journalSize = strlen($journalDescription);

    // Check if the student has already uploaded a journal for the selected date
    $checkQuery = "SELECT COUNT(*) FROM student_journal WHERE student_id = ? AND journal_date = ?";
    if ($checkStmt = $database->prepare($checkQuery)) {
        $checkStmt->bind_param("is", $student_id, $journalDate);
        $checkStmt->execute();
        $checkStmt->bind_result($journalCount);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($journalCount > 0) {
            // Journal already exists for the selected date
            $_SESSION['journal_error'] = "You have already uploaded a journal for this date.";
            header("Location: journal.php");
            exit();
        } else {
            // Proceed with inserting the new journal entry
            $query = "INSERT INTO student_journal (student_id, journal_name, journal_date, journal_description, file_size) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $database->prepare($query)) {
                $stmt->bind_param("isssi", $student_id, $journalTitle, $journalDate, $journalDescription, $journalSize);
                $stmt->execute();

                $_SESSION['journal_success'] = true;
                header("Location: journal.php");
                $stmt->close();
            } else {
                echo "Error: Could not prepare query.";
            }
        }
    } else {
        echo "Error: Could not prepare check query.";
    }
} else {
    echo "Invalid request.";
}
?>