<?php
session_start(); // Start session to use session variables
require '../../../conn/connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../../src/PHPMailer.php';
require_once __DIR__ . '/../../../src/SMTP.php';
require_once __DIR__ . '/../../../src/Exception.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form inputs
    $adviser_firstname = $_POST['adviser_firstname'];
    $adviser_middle = $_POST['adviser_middle'];
    $adviser_lastname = $_POST['adviser_lastname'];
    $adviser_email = $_POST['adviser_email'];
    $adviser_number = $_POST['adviser_number'];
    $adviser_department = $_POST['adviser_department'];

    // Check for duplicate adviser_email
    $sql = "SELECT COUNT(*) FROM adviser WHERE adviser_email = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param('s', $adviser_email);
    $stmt->execute();
    $stmt->bind_result($email_count);
    $stmt->fetch();
    $stmt->close();

    if ($email_count > 0) {
        // Email is already in use by another adviser
        $_SESSION['error2'] = 'Email is already in use!';
        header('Location: ../adviser.php'); // Redirect back to the form page
        exit();
    }

    // Auto-generate a random password
    $random_password = bin2hex(random_bytes(4)); // Generate an 8-character random password
    $hashed_password = password_hash($random_password, PASSWORD_BCRYPT);

    // Clean up name fields for the filename (remove spaces, special characters)
    $adviser_fullname = preg_replace('/[^a-zA-Z0-9]/', '', $adviser_lastname . $adviser_firstname . $adviser_middle);

    // Handle profile image upload
    $adviser_image = 'user.png'; // Default image if no file uploaded
    if (isset($_FILES['adviser_image']) && $_FILES['adviser_image']['error'] == 0) {
        $uploadDir = '../../../uploads/adviser/';
        $fileExtension = pathinfo($_FILES['adviser_image']['name'], PATHINFO_EXTENSION);
        $adviser_image = 'adviser' . $adviser_fullname . '.' . $fileExtension;
        $uploadFile = $uploadDir . $adviser_image;
        move_uploaded_file($_FILES['adviser_image']['tmp_name'], $uploadFile);
    }

    // Insert the adviser into the database
    $sql = "INSERT INTO adviser (adviser_image, adviser_firstname, adviser_middle, adviser_lastname, adviser_number, adviser_email, department, adviser_password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $database->prepare($sql);
    $stmt->bind_param('ssssssss', $adviser_image, $adviser_firstname, $adviser_middle, $adviser_lastname, $adviser_number, $adviser_email, $adviser_department, $hashed_password);

    if ($stmt->execute()) {
        // Send email with the password
        $mail = new PHPMailer(true);

        try {
            // Email server settings
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

            // Email content
            $mail->setFrom('ccs.ojtmanagementsystem@gmail.com', 'Admin');
            $mail->addAddress($adviser_email, $adviser_firstname . ' ' . $adviser_lastname);
            $mail->Subject = 'Your Adviser Account Credentials';
            $mail->Body = "Welcome! $adviser_firstname $adviser_lastname,\n\n" .
                "Your adviser account has been successfully created.\n\n" .
                "Here are your login credentials:\n" .
                "Email: $adviser_email\n" .
                "Password: $random_password\n\n" .
                "Please change your password after logging in.\n\n" .
                "Best regards,\nAdmin";

            $mail->send();

            // Set a session variable for success
            $_SESSION['add_adviser_success'] = true;
            // Redirect to the adviser page 
            header('Location: ../adviser.php');
            exit();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        // Handle error
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
    $database->close();
}
?>