<?php
session_start(); 

require '../conn/connection.php'; 

if (isset($_POST['holidayId'])) {
    $holidayId = $_POST['holidayId'];

    $deleteQuery = "DELETE FROM holiday WHERE holiday_id = ?";
    $stmt = $database->prepare($deleteQuery);

    if ($stmt) {
        $stmt->bind_param("i", $holidayId);

        if ($stmt->execute()) {
            $_SESSION['delete_success'] = true;
            header('Location: ./calendar.php?status=deleted');
        } else {
            $_SESSION['error_message'] = "Failed to delete the holiday. Please try again.";
            header('Location: ./calendar.php?status=error');
            exit(); 
        }

    } else {
        $_SESSION['error_message'] = "Failed to prepare the query. Please try again.";
        header('Location: ./calendar.php?status=error');
        exit(); 
    }
} else {
    $_SESSION['error_message'] = "No holiday selected for deletion.";
    header('Location: ./calendar.php?status=error');
    exit(); 
}
?>
