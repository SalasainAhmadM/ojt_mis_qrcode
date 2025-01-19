<?php
require '../conn/connection.php';
session_start();

if (isset($_GET['feedback_id']) && isset($_GET['question_field'])) {
    $feedbackId = $_GET['feedback_id'];
    $questionField = $_GET['question_field'];

    preg_match('/\d+/', $questionField, $matches);
    $questionNumber = (int) $matches[0];

    $sql = "UPDATE feedback_questions SET $questionField = NULL WHERE id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $feedbackId);

    if ($stmt->execute()) {
        // Now, shift the questions from the next one onwards
        for ($i = $questionNumber + 1; $i <= 10; $i++) {
            $currentField = "question$i";
            $nextField = "question" . ($i - 1);

            // Update the current question to move its value to the previous question's field
            $updateSql = "UPDATE feedback_questions SET $nextField = $currentField WHERE id = ?";
            $updateStmt = $database->prepare($updateSql);
            $updateStmt->bind_param("i", $feedbackId);
            $updateStmt->execute();

            // Optionally, clear the last question field
            if ($i == 10) {
                $clearSql = "UPDATE feedback_questions SET $currentField = NULL WHERE id = ?";
                $clearStmt = $database->prepare($clearSql);
                $clearStmt->bind_param("i", $feedbackId);
                $clearStmt->execute();
            }
        }

        // Set success message
        $_SESSION['delete_success'] = true;
        header("Location: ./feedback.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
} else {
    echo "Invalid request.";
}
?>