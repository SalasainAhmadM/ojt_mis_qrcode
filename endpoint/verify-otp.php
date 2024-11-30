<?php
session_start();
require '../conn/connection.php';
require './config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];
    $student_id = $_POST['student_id'];

    if (empty($entered_otp) || empty($student_id)) {
        echo "<script type='text/javascript'>
            alert('Missing OTP or Student ID. Please try again.');
            window.location.href = '../endpoint/verify.php?error=true';
        </script>";
        exit();
    }

    // Query to match the student_id and otp
    $sql = "SELECT * FROM student WHERE student_id = '$student_id' AND otp = '$entered_otp'";
    $result = mysqli_query($database, $sql);

    if (mysqli_num_rows($result) > 0) {
        // Remove verification_code and clear the OTP if verification is successful
        $update_sql = "UPDATE student 
                       SET verification_code = NULL, otp = NULL 
                       WHERE student_id = '$student_id'";
        if (mysqli_query($database, $update_sql)) {
            // Redirect to the verification page with success query parameter
            echo "<script type='text/javascript'>
                window.location.href = '../endpoint/verify.php?verification=success';
            </script>";
            exit();
        } else {
            $error_message = "Error updating record: " . mysqli_error($database);
        }
    } else {
        $error_message = "Invalid OTP or Student ID. Please try again.";
    }
} else {
    $error_message = "Invalid request method.";
}

if (isset($error_message)) {
    // Pass the error message to the frontend using JavaScript
    echo "<script type='text/javascript'>
        alert('$error_message');
        window.location.href = '../endpoint/verify.php?error=true';
    </script>";
    exit();
}
?>