<?php
session_start();
require '../../conn/connection.php';

if (isset($_GET['id'])) {
    $street_id = $_GET['id'];

    // Delete the street
    $query = "DELETE FROM street WHERE street_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $street_id);
        if ($stmt->execute()) {
            $_SESSION['street_delete'] = "Street deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete Street.";
        }
        $stmt->close();
    }
    header("Location: ../others.php");
}
?>