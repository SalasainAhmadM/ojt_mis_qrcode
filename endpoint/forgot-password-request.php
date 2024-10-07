<?php
include '../conn/connection.php';
require_once '../src/PHPMailer.php'; // Adjust based on your PHPMailer path
require_once '../src/SMTP.php';
require_once '../src/Exception.php';
require_once './config.php'; // Include the file where sendPasswordResetEmail is defined

if (isset($_POST['email'])) {
    $email = strtolower(trim($_POST['email'])); // Normalize email

    // Check if email exists in admin, student, adviser, or company
    $roles = ['admin', 'student', 'adviser', 'company'];
    $userFound = false; // Flag to check if user is found

    foreach ($roles as $role) {
        $stmt = $database->prepare("SELECT * FROM $role WHERE {$role}_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // User found, proceed with password reset
            $userFound = true;

            // Generate token and expiration date
            $token = bin2hex(random_bytes(32));
            $expires_at = new DateTime('now', new DateTimeZone('Asia/Manila'));
            $expires_at->modify('+1 hour');
            $expires_at_formatted = $expires_at->format('Y-m-d H:i:s');



            // Insert or update the token
            $stmt = $database->prepare("REPLACE INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expires_at_formatted);
            if ($stmt->execute()) {
                // Send password reset email using sendPasswordResetEmail function
                if (sendPasswordResetEmail($email, $token)) {
                    $message = 'Password reset link has been sent to your email.';
                } else {
                    $message = 'Failed to send password reset email.';
                }
            } else {
                $message = 'Failed to store reset token.';
            }
            echo "<script type='text/javascript'>
                alert('$message');
                window.location.href = '../endpoint/forgotpassword.php';
            </script>";
            exit;
        }
    }

    if (!$userFound) {
        $message = 'No user found with that email.';
        echo "<script type='text/javascript'>
            alert('$message');
            window.location.href = '../endpoint/forgotpassword.php';
        </script>";
    }
}
?>