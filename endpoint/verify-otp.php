<?php
session_start();
require '../conn/connection.php';
require './config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];

    if (isset($_SESSION['otp'], $_SESSION['email'], $_SESSION['verification_code'])) {
        $session_otp = $_SESSION['otp'];
        $email = $_SESSION['email'];
        $verification_code = $_SESSION['verification_code'];

        if ($entered_otp == $session_otp) {
            $sql = "UPDATE student SET verification_code = NULL WHERE student_email = '$email' AND verification_code = '$verification_code'";

            if (mysqli_query($database, $sql)) {
                unset($_SESSION['otp']);
                unset($_SESSION['verification_code']);

                // Redirect to the verification page with success query parameter
                header("Location: ../endpoint/verify.php?verification=success");
                exit();
            } else {
                $error_message = "Error updating record: " . mysqli_error($database);
            }
        } else {
            $error_message = "Invalid OTP. Please try again.";
        }
    } else {
        $error_message = "Session expired or invalid access. Please try again.";
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