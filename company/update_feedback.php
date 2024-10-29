<?php
session_start();  // Start session to store session variables
require '../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $question_1 = intval($_POST['question_1']);
    $question_2 = intval($_POST['question_2']);
    $question_3 = intval($_POST['question_3']);
    $question_4 = intval($_POST['question_4']);
    $question_5 = intval($_POST['question_5']);

    // Check if feedback already exists
    $check_query = "SELECT feedback_id FROM feedback WHERE student_id = ?";
    $stmt = $database->prepare($check_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update feedback if it exists
        $update_query = "
            UPDATE feedback 
            SET question_1 = ?, question_2 = ?, question_3 = ?, 
                question_4 = ?, question_5 = ?, feedback_date = NOW() 
            WHERE student_id = ?";
        $stmt = $database->prepare($update_query);
        $stmt->bind_param(
            "iiiiii",
            $question_1,
            $question_2,
            $question_3,
            $question_4,
            $question_5,
            $student_id
        );

        if ($stmt->execute()) {
            $_SESSION['edit_success'] = 'Feedback updated successfully.';
        } else {
            $_SESSION['feedback_error'] = 'Error updating feedback.';
        }
    } else {
        // Insert new feedback if not found
        $insert_query = "
            INSERT INTO feedback (student_id, question_1, question_2, 
                                  question_3, question_4, question_5) 
            VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $database->prepare($insert_query);
        $stmt->bind_param(
            "iiiiii",
            $student_id,
            $question_1,
            $question_2,
            $question_3,
            $question_4,
            $question_5
        );

        if ($stmt->execute()) {
            $_SESSION['edit_success'] = 'Feedback added successfully.';
        } else {
            $_SESSION['feedback_error'] = 'Error adding feedback.';
        }
    }

    $stmt->close();
    $database->close();

    // Redirect to the feedback page to trigger the modal
    header("Location: feedback.php");
    exit();
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>