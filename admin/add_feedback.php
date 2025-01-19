<?php
require '../conn/connection.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedbackText = trim($_POST['feedback_text']);

    if (!empty($feedbackText)) {
        // Determine the first empty feedback question column
        $sqlCheck = "SELECT * FROM feedback_questions WHERE question1 IS NULL OR question2 IS NULL OR question3 IS NULL OR question4 IS NULL OR question5 IS NULL OR question6 IS NULL OR question7 IS NULL OR question8 IS NULL OR question9 IS NULL OR question10 IS NULL LIMIT 1";
        $result = $database->query($sqlCheck);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            for ($i = 1; $i <= 10; $i++) { // Check columns question1 to question10
                if (empty($row["question$i"])) {
                    $field = "question$i";
                    break;
                }
            }

            // Update the first empty field
            $sqlUpdate = "UPDATE feedback_questions SET $field = ? WHERE id = ?";
            $stmt = $database->prepare($sqlUpdate);
            $stmt->bind_param("si", $feedbackText, $row['id']);

            if ($stmt->execute()) {
                $_SESSION['add_success'] = true;
                header("Location: ./feedback.php");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
        } else {
            echo "Error: No available space for new questions. Please add a new row in the database.";
        }
    } else {
        echo "Feedback text cannot be empty.";
    }
}
?>