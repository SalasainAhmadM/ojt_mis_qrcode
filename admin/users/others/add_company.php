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
    $company_name = $_POST['company_name'];
    $company_firstname = $_POST['company_rep_firstname'];
    $company_middle = $_POST['company_rep_middle'];
    $company_lastname = $_POST['company_rep_lastname'];
    $company_position = $_POST['company_rep_position'];
    $company_email = $_POST['company_email'];
    $company_number = $_POST['company_number'];
    $company_address = $_POST['company_address'];

    // Check for duplicate company_email
    $sql = "SELECT COUNT(*) FROM company WHERE company_email = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param('s', $company_email);
    $stmt->execute();
    $stmt->bind_result($email_count);
    $stmt->fetch();
    $stmt->close();

    if ($email_count > 0) {
        // Email is already in use by another company
        $_SESSION['error2'] = 'Email is already in use!';
        header('Location: ../company.php');
        exit();
    }

    // Auto-generate a random password
    $random_password = bin2hex(random_bytes(4)); // Generate an 8-character random password
    $hashed_password = password_hash($random_password, PASSWORD_BCRYPT);

    // Handle profile image upload
    $company_image = 'user.png'; // Default image if no file uploaded
    if (isset($_FILES['company_image']) && $_FILES['company_image']['error'] == 0) {
        $uploadDir = '../../../uploads/company/';
        $fileExtension = pathinfo($_FILES['company_image']['name'], PATHINFO_EXTENSION);
        $company_image = 'company' . preg_replace('/[^a-zA-Z0-9]/', '', $company_name) . '.' . $fileExtension;
        $uploadFile = $uploadDir . $company_image;
        move_uploaded_file($_FILES['company_image']['tmp_name'], $uploadFile);
    }

    // Insert the company into the database
    $sql = "INSERT INTO company (company_image, company_name, company_rep_firstname, company_rep_middle, company_rep_lastname, company_rep_position, company_number, company_email, company_address, company_password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $database->prepare($sql);
    $stmt->bind_param('ssssssssss', $company_image, $company_name, $company_firstname, $company_middle, $company_lastname, $company_position, $company_number, $company_email, $company_address, $hashed_password);

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
            $mail->addAddress($company_email, $company_firstname . ' ' . $company_lastname);
            $mail->Subject = 'Your Company Account Credentials';
            $mail->Body = "Welcome! $company_firstname $company_lastname,\n\n" .
                "Your company account has been successfully created.\n\n" .
                "Here are your login credentials:\n" .
                "Email: $company_email\n" .
                "Password: $random_password\n\n" .
                "Please change your password after logging in.\n\n" .
                "Best regards,\nAdmin";

            $mail->send();

            // Set a session variable for success
            $_SESSION['add_company_success'] = true;
            header('Location: ../company.php');
            exit();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
    $database->close();
}
?>