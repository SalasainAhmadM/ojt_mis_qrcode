<?php
require '../conn/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];

    $ratings = [
        'Strongly Agree' => 100,
        'Agree' => 80,
        'Neutral' => 60,
        'Disagree' => 40,
        'Strongly Disagree' => 20
    ];

    // Get individual question ratings
    $question_1 = $ratings[$_POST['question_1']];
    $question_2 = $ratings[$_POST['question_2']];
    $question_3 = $ratings[$_POST['question_3']];
    $question_4 = $ratings[$_POST['question_4']];
    $question_5 = $ratings[$_POST['question_5']];

    // Calculate total score as the average
    $total_score = ($question_1 + $question_2 + $question_3 + $question_4 + $question_5) / 5;

    // Insert into the feedback table
    $stmt = $database->prepare("INSERT INTO feedback (student_id, question_1, question_2, question_3, question_4, question_5, total_score) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiiii", $student_id, $question_1, $question_2, $question_3, $question_4, $question_5, $total_score);

    if ($stmt->execute()) {
        $_SESSION['feedback_success'] = true; // Set session variable
        header("Location: feedback.php"); // Redirect to feedback page
        exit();
    } else {
        $_SESSION['feedback_error'] = "Error submitting feedback: " . $stmt->error;
        header("Location: feedback.php");
        exit();
    }
}
?>