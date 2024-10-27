<?php
session_start();
require '../conn/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $holiday_date = $_POST['date'];
    $holiday_name = $_POST['holidayName']; 

    // Validate input
    if (empty($holiday_date) || empty($holiday_name)) {
        $_SESSION['error_message'] = "Invalid input. Please make sure all fields are filled.";
        header('Location: calendar.php');
        exit;
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
        $sql = "INSERT INTO holiday (holiday_date, holiday_name) VALUES (?, ?)";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("ss", $holiday_date, $holiday_name);

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
