<?php
require '../conn/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $date_start = $_POST['date_start'];

    // Validate inputs
    if (empty($student_id) || empty($date_start)) {
        echo "Invalid input. Please try again.";
        exit();
    }

    // Update the `date_start` value for the student
    $query = "UPDATE student SET date_start = ? WHERE student_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $date_start, $student_id);
        if ($stmt->execute()) {
            // Set session variable to trigger success modal
            $_SESSION['update_success'] = true;
            header("Location: intern.php");
            exit();
        } else {
            echo "Failed to update start date. Please try again.";
        }
        $stmt->close();
    } else {
        echo "Error preparing the query.";
    }
    $database->close();
}
?>