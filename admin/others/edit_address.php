<?php
session_start();
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['address_id'], $_POST['barangay_name'])) {
    $address_id = $_POST['address_id'];
    $barangay_name = trim($_POST['barangay_name']);
    // Check if the address already exists to avoid duplicates
    $query = "SELECT * FROM address WHERE address_barangay = ? AND address_id != ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $barangay_name, $address_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error_3'] = "Address already exists.";
            header("Location: ../others.php");
            exit();
        }

        $stmt->close();
    }

    // Update the address
    $query = "UPDATE address SET address_barangay = ? WHERE address_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $barangay_name, $address_id);
        if ($stmt->execute()) {
            $_SESSION['address_edit_success'] = "Address updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update address.";
        }
        $stmt->close();
    }

    header("Location: ../others.php");
    exit();
}

?>