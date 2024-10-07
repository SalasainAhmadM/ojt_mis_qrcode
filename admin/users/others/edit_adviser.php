<?php
session_start(); // Start session to use session variables
require '../../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the adviser ID and form inputs
    $adviser_id = $_POST['adviser_id'];
    $adviser_firstname = $_POST['adviser_firstname'];
    $adviser_middle = $_POST['adviser_middle'];
    $adviser_lastname = $_POST['adviser_lastname'];
    $adviser_email = $_POST['adviser_email'];
    $adviser_number = $_POST['adviser_number'];
    $adviser_department = $_POST['adviser_department'];

    // Check if the provided password and confirm password match
    if (!empty($_POST['adviser_password']) && $_POST['adviser_password'] !== $_POST['adviser_confirm_password']) {
        $_SESSION['error'] = 'Passwords do not match!';
        header('Location: ../adviser.php');
        exit();
    }

    // Check for duplicate adviser_email, but exclude the current adviser
    $sql = "SELECT COUNT(*) FROM adviser WHERE adviser_email = ? AND adviser_id != ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param('si', $adviser_email, $adviser_id);
    $stmt->execute();
    $stmt->bind_result($email_count);
    $stmt->fetch();
    $stmt->close();

    if ($email_count > 0) {
        // Email is already in use by another adviser
        $_SESSION['error2'] = 'Email is already in use by another adviser!';
        header('Location: ../adviser.php');
        exit();
    }

    // Hash new password if provided
    if (!empty($_POST['adviser_password'])) {
        $adviser_password = password_hash($_POST['adviser_password'], PASSWORD_BCRYPT);
    } else {
        // Fetch the existing password from the database if no new password is provided
        $sql = "SELECT adviser_password FROM adviser WHERE adviser_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param('i', $adviser_id);
        $stmt->execute();
        $stmt->bind_result($adviser_password);
        $stmt->fetch();
        $stmt->close();
    }

    // Clean up name fields for the filename (remove spaces, special characters)
    $adviser_fullname = preg_replace('/[^a-zA-Z0-9]/', '', $adviser_lastname . $adviser_firstname . $adviser_middle);

    // Check if a new image is uploaded
    if (isset($_FILES['adviser_image']) && $_FILES['adviser_image']['error'] == 0) {
        // Handle profile image update
        $uploadDir = '../../../uploads/adviser/';
        $fileExtension = pathinfo($_FILES['adviser_image']['name'], PATHINFO_EXTENSION);
        $adviser_image = 'adviser' . $adviser_fullname . '.' . $fileExtension;
        $uploadFile = $uploadDir . $adviser_image;
        move_uploaded_file($_FILES['adviser_image']['tmp_name'], $uploadFile);
    } else {
        // If no new image is uploaded, retain the existing image
        $sql = "SELECT adviser_image FROM adviser WHERE adviser_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param('i', $adviser_id);
        $stmt->execute();
        $stmt->bind_result($adviser_image);
        $stmt->fetch();
        $stmt->close();
    }

    // Update the adviser information
    $sql = "UPDATE adviser
            SET adviser_image = ?, adviser_firstname = ?, adviser_middle = ?, adviser_lastname = ?, adviser_number = ?, adviser_email = ?, department = ?, adviser_password = ?
            WHERE adviser_id = ?";

    $stmt = $database->prepare($sql);
    $stmt->bind_param('ssssssssi', $adviser_image, $adviser_firstname, $adviser_middle, $adviser_lastname, $adviser_number, $adviser_email, $adviser_department, $adviser_password, $adviser_id);

    if ($stmt->execute()) {
        // Set a session variable for success
        $_SESSION['edit_adviser_success'] = true;
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