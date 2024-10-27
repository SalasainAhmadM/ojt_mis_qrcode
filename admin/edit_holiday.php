<?php
session_start();
require '../conn/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $holiday_id = $_POST['holidayId'];
    $holiday_name = $_POST['holidayName'];

    // Validate input
    if (empty($holiday_id) || empty($holiday_name)) {
        $_SESSION['error_message'] = "Invalid input. Please make sure all fields are filled.";
        header('Location: calendar.php');
        exit;
    }

    // Update holiday in the database
    $sql = "UPDATE holiday SET holiday_name = ? WHERE holiday_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("si", $holiday_name, $holiday_id);

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
