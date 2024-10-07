<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../src/PHPMailer.php';
require_once __DIR__ . '/../src/SMTP.php';
require_once __DIR__ . '/../src/Exception.php';

function sendMail($toEmail, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'ccs.ojtmanagementsystem@gmail.com'; // Your email
        $mail->Password = 'nduzhvqatgwpczyl'; // Your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Disable SSL certificate verification (for troubleshooting purposes)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('ccs.ojtmanagementsystem@gmail.com', 'CCS OJT Management System');
        $mail->addAddress($toEmail); // Add a recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}
function sendPasswordResetEmail($toEmail, $token)
{
    $resetLink = "http://localhost/ojt_mis_qrcode/endpoint/reset-password.php?token=" . $token;

    // Set the subject and body for the password reset email
    $subject = "Password Reset Request";
    $body = "Hello,<br><br>You requested to reset your password. Please click the link below to reset your password:<br><br>";
    $body .= "<a href='" . $resetLink . "'>Reset Password</a><br><br>";
    $body .= "If you did not request this, please ignore this email.";

    // Use the sendMail function to send the email
    return sendMail($toEmail, $subject, $body);
}

