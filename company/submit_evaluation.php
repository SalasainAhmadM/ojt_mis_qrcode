<?php
require '../conn/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $feedback_comment = $_POST['feedback_comment']; // Capture the additional comment

    // Define the rating values
    $ratings = [
        'Strongly Agree' => 100,
        'Agree' => 80,
        'Neutral' => 60,
        'Disagree' => 40,
        'Strongly Disagree' => 20
    ];

    // Collect ratings for questions 1 to 10 (if provided)
    $questions = [];
    for ($i = 1; $i <= 10; $i++) {
        if (isset($_POST["question_$i"])) {
            $questions[$i] = $ratings[$_POST["question_$i"]];
        } else {
            $questions[$i] = null; // Optional question not answered
        }
    }

    // Prepare the SQL query
    $stmt = $database->prepare(
        "INSERT INTO feedback (
            student_id, question_1, question_2, question_3, question_4, question_5, 
            question_6, question_7, question_8, question_9, question_10, feedback_comment
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "iiiiiiiiiiis",
        $student_id,
        $questions[1],
        $questions[2],
        $questions[3],
        $questions[4],
        $questions[5],
        $questions[6],
        $questions[7],
        $questions[8],
        $questions[9],
        $questions[10],
        $feedback_comment
    );

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