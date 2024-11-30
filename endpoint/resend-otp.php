<?php
session_start();
require '../conn/connection.php';
require '../endpoint/config.php';

// Get the student_id from the query parameter
if (!isset($_GET['student_id'])) {
    $_SESSION['modal_error'] = 'Invalid access. Student ID is required.';
    header("Location: ./verify.php?otp=resend_error&student_id=");
    exit();
}

$student_id = $_GET['student_id'];

// Check if the student exists in the database
$sql = "SELECT student_email, verification_code FROM student WHERE student_id = '$student_id'";
$result = mysqli_query($database, $sql);

if (mysqli_num_rows($result) > 0) {
    $student = mysqli_fetch_assoc($result);
    $email = $student['student_email'];
    $verification_code = $student['verification_code'];

    if (empty($verification_code)) {
        $_SESSION['modal_error'] = 'This student has already been verified.';
        header("Location: ./verify.php?otp=resend_error&student_id=$student_id");
        exit();
    }

    // Generate a new OTP
    $otp = rand(100000, 999999);

    // Update the OTP in the database
    $update_sql = "UPDATE student SET otp = '$otp' WHERE student_id = '$student_id'";
    if (!mysqli_query($database, $update_sql)) {
        $_SESSION['modal_error'] = 'Failed to update OTP in the database.';
        header("Location: ./verify.php?otp=resend_error&student_id=$student_id");
        exit();
    }

    // Update session with the new OTP
    $_SESSION['otp'] = $otp;

    // Prepare email content
    $subject = 'Your Resent OTP Code';
    $verify_url = "http://localhost/ojt_mis_qrcode/endpoint/verify.php?student_id=$student_id";
    $body = "
        <p>Your new OTP code is <b>$otp</b>. Please enter this code to verify your email address.</p>
        <p>Alternatively, click the button below to input your new OTP code:</p>
        <a href='$verify_url' style='
            display: inline-block;
            background-color: #095d40;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
        '>Verify Page</a>
    ";

    // Send the email
    if (sendMail($email, $subject, $body)) {
        $_SESSION['modal_success'] = 'A new OTP has been sent to your email.';
        header("Location: ./verify.php?otp=resent&student_id=$student_id");
    } else {
        $_SESSION['modal_error'] = 'Failed to resend OTP. Please try again.';
        header("Location: ./verify.php?otp=resend_error&student_id=$student_id");
    }
} else {
    $_SESSION['modal_error'] = 'Student not found. Please check the student ID.';
    header("Location: ./verify.php?otp=resend_error&student_id=");
}
?>