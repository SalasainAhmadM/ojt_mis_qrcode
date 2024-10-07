<?php
session_start();
require '../conn/connection.php';
require '../endpoint/config.php';

// Check if the email is set in the session
if (!isset($_SESSION['email'])) {
    echo "<script type='text/javascript'>
    alert('Session expired. Please register again.');
    window.location.href = '../index.php';
    </script>";
    exit();
}

// Regenerate the OTP
$otp = rand(100000, 999999); // Generate a new 6-digit OTP
$_SESSION['otp'] = $otp; // Update the session with the new OTP

$email = $_SESSION['email'];
$verification_code = $_SESSION['verification_code'];

// Update the OTP in the database if needed (optional)

$subject = 'Your Resent OTP Code';
$body = "Your OTP code is <b>$otp</b>. Please enter this code to verify your email address.";

// Resend the OTP via email
if (sendMail($email, $subject, $body)) {
    echo "<script type='text/javascript'>
    alert('A new OTP has been sent to your email.');
    window.location.href = './verify.php?otp=resent';
    </script>";
} else {
    echo "<script type='text/javascript'>
    alert('Failed to resend OTP. Please try again.');
    window.location.href = './verify.php?otp=resend_error';
    </script>";
}
?>