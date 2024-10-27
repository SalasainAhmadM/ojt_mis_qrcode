<?php
session_start();
require '../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $company_id = intval($_POST['company_id']);
    $date = $_POST['date'];
    $time_in = isset($_POST['time_in']) && !empty($_POST['time_in']) ? $_POST['time_in'] : null;
    $time_out = isset($_POST['time_out']) && !empty($_POST['time_out']) ? $_POST['time_out'] : null;
    $day_type = $_POST['day_type'];

    // Prepare the SQL statement
    $sql = "INSERT INTO schedule (company_id, date, time_in, time_out, day_type) VALUES (?, ?, ?, ?, ?)";

    // Use prepared statements to prevent SQL injection
    if ($stmt = $database->prepare($sql)) {
        // Bind the parameters to the statement, using 's' type for NULLs
        $stmt->bind_param("issss", $company_id, $date, $time_in, $time_out, $day_type);

        // Execute the statement 
        if ($stmt->execute()) {
            // Set session flag for success
            $_SESSION['schedule_success'] = true;
            // Redirect to calendar page or the page you want with success notification
            header("Location: calendar.php");
            exit();
        } else {
            // Handle the error
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        // Handle errors if the statement couldn't be prepared
        echo "Error: Could not prepare the SQL statement.";
    }
} else {
    echo "Invalid request method.";
}
?>
