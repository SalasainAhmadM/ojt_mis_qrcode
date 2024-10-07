<?php
session_start(); // Start session to use session variables
require '../../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form inputs
    $adviser_firstname = $_POST['adviser_firstname'];
    $adviser_middle = $_POST['adviser_middle'];
    $adviser_lastname = $_POST['adviser_lastname'];
    $adviser_email = $_POST['adviser_email'];
    $adviser_number = $_POST['adviser_number'];
    $adviser_department = $_POST['adviser_department'];

    // Check if the provided password and confirm password match
    if ($_POST['adviser_password'] !== $_POST['confirm_password']) {
        // Set a session variable for the error and redirect
        $_SESSION['error'] = 'Passwords do not match!';
        header('Location: ../adviser.php'); // Redirect back to the form page
        exit();
    }

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

    // Proceed with inserting the adviser if passwords match and email is not a duplicate
    $password = password_hash($_POST['adviser_password'], PASSWORD_BCRYPT);

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

    // Insert the adviser into the database, including the password
    $sql = "INSERT INTO adviser (adviser_image, adviser_firstname, adviser_middle, adviser_lastname, adviser_number, adviser_email, department, adviser_password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $database->prepare($sql);
    $stmt->bind_param('ssssssss', $adviser_image, $adviser_firstname, $adviser_middle, $adviser_lastname, $adviser_number, $adviser_email, $adviser_department, $password);

    if ($stmt->execute()) {
        // Set a session variable for success
        $_SESSION['add_adviser_success'] = true;
        // Redirect to the adviser page
        header('Location: ../adviser.php');
        exit();
    } else {
        // Handle error
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
    $database->close();
}
?>