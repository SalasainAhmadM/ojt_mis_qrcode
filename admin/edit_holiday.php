<?php
session_start();
require '../conn/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $holiday_id = $_POST['holidayId'];
    $holiday_name = $_POST['holidayName'];
    $memo_file = $_FILES['holidayMemo'];

    // Validate input
    if (empty($holiday_id) || empty($holiday_name)) {
        $_SESSION['error_message'] = "Invalid input. Please make sure all fields are filled.";
        header('Location: calendar.php');
        exit;
    }

    // Check if a file is uploaded
    $new_file_name = null;
    if (!empty($memo_file['name'])) {
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $file_extension = strtolower(pathinfo($memo_file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['error_message'] = "Invalid file type. Only PDF, JPG, PNG, and Word documents are allowed.";
            header('Location: calendar.php');
            exit;
        }

        // Set file upload directory and ensure it exists
        $upload_dir = '../uploads/admin/memos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        // Generate a unique file name
        $new_file_name = uniqid() . "." . $file_extension;
        $file_path = $upload_dir . $new_file_name;

        // Upload the file
        if (!move_uploaded_file($memo_file['tmp_name'], $file_path)) {
            $_SESSION['error_message'] = "Failed to upload file. Please try again.";
            header('Location: calendar.php');
            exit;
        }

        // Optionally delete the old file here (if tracked in the database).
    }

    // Update holiday in the database
    if ($new_file_name) {
        // If a new file was uploaded, update both name and memo
        $sql = "UPDATE holiday SET holiday_name = ?, memo = ? WHERE holiday_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("ssi", $holiday_name, $new_file_name, $holiday_id);
    } else {
        // If no new file, only update the name
        $sql = "UPDATE holiday SET holiday_name = ? WHERE holiday_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("si", $holiday_name, $holiday_id);
    }

    if ($stmt->execute()) {
        // Success: Redirect with success message
        $_SESSION['edit_success'] = true;
        header('Location: calendar.php');
    } else {
        // Error updating holiday
        $_SESSION['error_message'] = "Failed to update the holiday. Please try again.";
        header('Location: calendar.php');
    }

    // Close statement and connection
    $stmt->close();
    $database->close();
}
?>