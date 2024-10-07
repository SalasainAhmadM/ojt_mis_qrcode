<?php
session_start(); // Start session to use session variables
require '../../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form inputs
    $company_name = $_POST['company_name'];
    $company_firstname = $_POST['company_rep_firstname'];
    $company_middle = $_POST['company_rep_middle'];
    $company_lastname = $_POST['company_rep_lastname'];
    $company_email = $_POST['company_email'];
    $company_number = $_POST['company_number'];
    $company_address = $_POST['company_address'];

    // Check if the provided password and confirm password match
    if ($_POST['company_password'] !== $_POST['confirm_password']) {
        // Set a session variable for the error and redirect
        $_SESSION['error'] = 'Passwords do not match!';
        header('Location: ../company.php'); // Redirect back to the form page
        exit();
    }

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
        header('Location: ../company.php'); // Redirect back to the form page
        exit();
    }

    // Proceed with inserting the company if passwords match and email is not a duplicate
    $password = password_hash($_POST['company_password'], PASSWORD_BCRYPT);

    // Handle profile image upload
    $company_image = 'user.png'; // Default image if no file uploaded
    if (isset($_FILES['company_image']) && $_FILES['company_image']['error'] == 0) {
        $uploadDir = '../../../uploads/company/';
        $fileExtension = pathinfo($_FILES['company_image']['name'], PATHINFO_EXTENSION);
        $company_image = 'company' . $company_name . '.' . $fileExtension;
        $uploadFile = $uploadDir . $company_image;
        move_uploaded_file($_FILES['company_image']['tmp_name'], $uploadFile);
    }

    // Insert the company into the database, including the password
    $sql = "INSERT INTO company (company_image, company_name,  company_rep_firstname,  company_rep_middle, company_rep_lastname, company_number, company_email, company_address, company_password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $database->prepare($sql);
    $stmt->bind_param('sssssssss', $company_image, $company_name, $company_firstname, $company_middle, $company_lastname, $company_number, $company_email, $company_address, $password);

    if ($stmt->execute()) {
        // Set a session variable for success
        $_SESSION['add_company_success'] = true;
        // Redirect to the company page
        header('Location: ../company.php');
        exit();
    } else {
        // Handle error
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
    $database->close();
}
?>