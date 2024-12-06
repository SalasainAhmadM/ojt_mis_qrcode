<?php
session_start();
require '../conn/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $holiday_date = $_POST['date'];
    $holiday_name = $_POST['holidayName'];
    $memo_file = $_FILES['holidayMemo'];

    // Validate input
    if (empty($holiday_date) || empty($holiday_name)) {
        $_SESSION['error_message'] = "Invalid input. Please make sure all required fields are filled.";
        header('Location: calendar.php');
        exit;
    }

    $file_name = null; // Default value for the memo file

    // Check if a file is uploaded
    if (!empty($memo_file['name'])) {
        // Validate file upload
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
            mkdir($upload_dir, 0775, true); // Create directory with permissions if it doesn't exist
        }

        // Set file name
        $file_name = uniqid() . "." . $file_extension;
        $file_path = $upload_dir . $file_name;

        // Upload file
        if (!move_uploaded_file($memo_file['tmp_name'], $file_path)) {
            $_SESSION['error_message'] = "Failed to upload file. Please try again.";
            header('Location: calendar.php');
            exit;
        }
    }

    // Check if the holiday already exists in the database
    $sql = "SELECT * FROM holiday WHERE holiday_date = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("s", $holiday_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Holiday already exists
        $_SESSION['error_message'] = "This holiday is already scheduled.";
        header('Location: calendar.php');
    } else {
        // Insert new holiday into the `holiday` table
        $sql = "INSERT INTO holiday (holiday_date, holiday_name, memo) VALUES (?, ?, ?)";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("sss", $holiday_date, $holiday_name, $file_name);

        if ($stmt->execute()) {
            // Success: Redirect with success message
            $_SESSION['schedule_success'] = true;
            header('Location: calendar.php');
        } else {
            // Error inserting holiday
            $_SESSION['error_message'] = "Failed to schedule the holiday. Please try again.";
            header('Location: calendar.php');
        }
    }

    // Close statement and connection
    $stmt->close();
    $database->close();
}
?>