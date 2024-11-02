<?php
session_start();
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ojtHours'])) {
    $newOjtHours = (int) $_POST['ojtHours'];

    if ($newOjtHours > 0) {
        // Check if there is already an entry in required_hours
        $sqlCheck = "SELECT required_hours_id FROM required_hours LIMIT 1";
        $result = mysqli_query($database, $sqlCheck);

        if (mysqli_num_rows($result) > 0) {
            // Update the existing row
            $sql = "UPDATE required_hours SET required_hours = ? WHERE required_hours_id = 1";
        } else {
            // Insert a new row if none exists
            $sql = "INSERT INTO required_hours (required_hours) VALUES (?)";
        }

        $stmt = mysqli_prepare($database, $sql);
        mysqli_stmt_bind_param($stmt, "i", $newOjtHours);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['update_success'] = true; // Set success session variable
        } else {
            $_SESSION['error'] = "Failed to update OJT Hours: " . mysqli_error($database);
        }

        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Invalid OJT hours.";
    }
}

mysqli_close($database);
header('Location: ../index.php');
exit;
?>