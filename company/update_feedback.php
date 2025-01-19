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
    $question_6 = isset($_POST['question_6']) ? intval($_POST['question_6']) : null;
    $question_7 = isset($_POST['question_7']) ? intval($_POST['question_7']) : null;
    $question_8 = isset($_POST['question_8']) ? intval($_POST['question_8']) : null;
    $question_9 = isset($_POST['question_9']) ? intval($_POST['question_9']) : null;
    $question_10 = isset($_POST['question_10']) ? intval($_POST['question_10']) : null;
    $feedback_comment = isset($_POST['feedback_comment']) ? $_POST['feedback_comment'] : null;

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
                question_4 = ?, question_5 = ?, question_6 = ?, question_7 = ?, 
                question_8 = ?, question_9 = ?, question_10 = ?, 
                feedback_comment = ?, feedback_date = NOW() 
            WHERE student_id = ?";
        $stmt = $database->prepare($update_query);
        $stmt->bind_param(
            "iiiiiiiiissi",
            $question_1,
            $question_2,
            $question_3,
            $question_4,
            $question_5,
            $question_6,
            $question_7,
            $question_8,
            $question_9,
            $question_10,
            $feedback_comment,
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
                                  question_3, question_4, question_5, 
                                  question_6, question_7, question_8, 
                                  question_9, question_10, feedback_comment) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $database->prepare($insert_query);
        $stmt->bind_param(
            "iiiiiiiiiss",
            $student_id,
            $question_1,
            $question_2,
            $question_3,
            $question_4,
            $question_5,
            $question_6,
            $question_7,
            $question_8,
            $question_9,
            $question_10,
            $feedback_comment
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