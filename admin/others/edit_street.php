<?php
session_start();
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['street_id'], $_POST['street_name'])) {
    $street_id = $_POST['street_id'];
    $name = trim($_POST['street_name']);
    // Check if the street already exists to avoid duplicates
    $query = "SELECT * FROM street WHERE name = ? AND street_id != ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $name, $street_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error_4'] = "street already exists.";
            header("Location: ../others.php");
            exit();
        }

        $stmt->close();
    }

    // Update the street
    $query = "UPDATE street SET name = ? WHERE street_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $name, $street_id);
        if ($stmt->execute()) {
            $_SESSION['street_edit_success'] = "street updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update street.";
        }
        $stmt->close();
    }

    header("Location: ../others.php");
    exit();
}

?>