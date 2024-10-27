<?php
include '../conn/connection.php';
require_once '../src/PHPMailer.php'; 
require_once '../src/SMTP.php';
require_once '../src/Exception.php';
require_once './config.php'; 

if (isset($_POST['email'])) {
    $email = strtolower(trim($_POST['email'])); 
    $roles = ['admin', 'student', 'adviser', 'company'];
    $userFound = false; 

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
                    // Redirect to forgotpassword.php with success modal trigger
                    header("Location: ./forgotpassword.php?reset=success");
                    exit;
                } else {
                    // Redirect to forgotpassword.php with email failure modal trigger
                    header("Location: ./forgotpassword.php?reset=email_failure");
                    exit;
                }
            } else {
                // Redirect to forgotpassword.php with token storage failure modal trigger
                header("Location: ./forgotpassword.php?reset=token_failure");
                exit;
            }
        }
    }

    if (!$userFound) {
        // Redirect to forgotpassword.php with no user found modal trigger
        header("Location: ./forgotpassword.php?reset=no_user");
        exit;
    }
}
?>
