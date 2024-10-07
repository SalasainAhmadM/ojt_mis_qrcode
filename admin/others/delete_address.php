<?php
session_start();
require '../../conn/connection.php';

if (isset($_GET['id'])) {
    $address_id = $_GET['id'];

    // Delete the address
    $query = "DELETE FROM address WHERE address_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $address_id);
        if ($stmt->execute()) {
            $_SESSION['address_delete'] = "address deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete address.";
        }
        $stmt->close();
    }
    header("Location: ../others.php");
}
?>