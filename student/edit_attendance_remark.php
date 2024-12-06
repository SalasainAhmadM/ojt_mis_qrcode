<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;
    $remark = $_POST['remark'] ?? null;

    // Validate input
    if ($schedule_id && $student_id && $remark) {
        $query = "
            UPDATE attendance_remarks
            SET remark = ?
            WHERE schedule_id = ? AND student_id = ?
        ";

        if ($stmt = $database->prepare($query)) {
            $stmt->bind_param("sii", $remark, $schedule_id, $student_id); // Bind parameters
            if ($stmt->execute()) {
                $_SESSION['success'] = true; // Set success flag
                $stmt->close();
                header("Location: dtr.php"); // Redirect to the DTR page
                exit();
            } else {
                $_SESSION['error'] = "Error updating attendance remark: " . $stmt->error;
            }
        } else {
            $_SESSION['error'] = "Error preparing statement: " . $database->error;
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
    }

    header("Location: dtr.php"); // Redirect back to the DTR page in case of errors
    exit();
} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: dtr.php");
    exit();
}
?>