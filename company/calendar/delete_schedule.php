<?php
// Start the session
session_start();

include('../../conn/connection.php');  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get date and company_id from the POST request
    $date = $_POST['date'];
    $company_id = intval($_POST['company_id']);

    // Prepare the DELETE query
    $sql = "DELETE FROM schedule WHERE date = ? AND company_id = ?";

    // Prepare and execute the statement
    if ($stmt = $database->prepare($sql)) {
        $stmt->bind_param('si', $date, $company_id);
        
        if ($stmt->execute()) {
            // Set session variable to indicate success
            $_SESSION['delete_success'] = true;
            // Redirect to the calendar page
            header("Location: ../calendar.php");
            exit(); // Stop the script after redirection
        } else {
            echo "Error deleting schedule: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing the statement: " . $database->error;
    }

    // Close the database connection
    $database->close();
} else {
    echo "Invalid request method.";
}
?>
