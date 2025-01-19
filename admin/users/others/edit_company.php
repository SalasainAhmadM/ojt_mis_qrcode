<?php
session_start(); // Start session to use session variables
require '../../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the company ID and form inputs
    $company_id = $_POST['company_id'];
    $company_name = $_POST['company_name'];
    $company_firstname = $_POST['company_rep_firstname'];
    $company_middle = $_POST['company_rep_middle'];
    $company_lastname = $_POST['company_rep_lastname'];
    $company_position = $_POST['company_rep_position'];
    $company_email = $_POST['company_email'];
    $company_number = $_POST['company_number'];
    $company_address = $_POST['company_address'];

    // Check if the provided password and confirm password match
    if (!empty($_POST['company_password']) && $_POST['company_password'] !== $_POST['company_confirm_password']) {
        $_SESSION['error'] = 'Passwords do not match!';
        header('Location: ../company.php');
        exit();
    }

    // Check for duplicate company_email, but exclude the current company
    $sql = "SELECT COUNT(*) FROM company WHERE company_email = ? AND company_id != ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param('si', $company_email, $company_id);
    $stmt->execute();
    $stmt->bind_result($email_count);
    $stmt->fetch();
    $stmt->close();

    if ($email_count > 0) {
        // Email is already in use by another company
        $_SESSION['error2'] = 'Email is already in use by another company!';
        header('Location: ../company.php');
        exit();
    }

    // Hash new password if provided
    if (!empty($_POST['company_password'])) {
        $company_password = password_hash($_POST['company_password'], PASSWORD_BCRYPT);
    } else {
        // Fetch the existing password from the database if no new password is provided
        $sql = "SELECT company_password FROM company WHERE company_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param('i', $company_id);
        $stmt->execute();
        $stmt->bind_result($company_password);
        $stmt->fetch();
        $stmt->close();
    }

    // Check if a new image is uploaded
    if (isset($_FILES['company_image']) && $_FILES['company_image']['error'] == 0) {
        // Handle profile image update
        $uploadDir = '../../../uploads/company/';
        $fileExtension = pathinfo($_FILES['company_image']['name'], PATHINFO_EXTENSION);
        $company_image = 'company' . $company_name . '.' . $fileExtension;
        $uploadFile = $uploadDir . $company_image;
        move_uploaded_file($_FILES['company_image']['tmp_name'], $uploadFile);
    } else {
        // If no new image is uploaded, retain the existing image
        $sql = "SELECT company_image FROM company WHERE company_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param('i', $company_id);
        $stmt->execute();
        $stmt->bind_result($company_image);
        $stmt->fetch();
        $stmt->close();
    }

    // Update the company information
    $sql = "UPDATE company
            SET company_image = ?, company_name = ?, company_rep_firstname = ?, company_rep_middle = ?, company_rep_lastname = ?, company_rep_position = ?, company_number = ?, company_email = ?, company_address = ?, company_password = ?
            WHERE company_id = ?";

    $stmt = $database->prepare($sql);
    $stmt->bind_param('ssssssssssi', $company_image, $company_name, $company_firstname, $company_middle, $company_lastname, $company_position, $company_number, $company_email, $company_address, $company_password, $company_id);

    if ($stmt->execute()) {
        // Set a session variable for success
        $_SESSION['edit_company_success'] = true;
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