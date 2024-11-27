<?php
session_start(); // Start the session at the beginning
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $streetName = trim($_POST['streetName']);

    // Check if the street already exists
    $checkQuery = "SELECT * FROM street WHERE name = ?";
    if ($stmt = $database->prepare($checkQuery)) {
        $stmt->bind_param("s", $streetName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // street already exists, set session error message
            $_SESSION['error_4'] = "street already exists. Please enter a new street.";
        } else {
            // street doesn't exist, proceed to insert
            $insertQuery = "INSERT INTO street (name) VALUES (?)";
            if ($stmt = $database->prepare($insertQuery)) {
                $stmt->bind_param("s", $streetName);

                if ($stmt->execute()) {
                    // Success: set session variable and redirect
                    $_SESSION['street_success'] = "street added successfully!";
                } else {
                    // Handle error in insertion
                    $_SESSION['error_try'] = "Error adding street. Please try again.";
                }
            } else {
                // Handle error preparing the insert statement
                $_SESSION['error_try'] = "Error preparing the database query.";
            }
        }

        $stmt->close();
    } else {
        // Handle error preparing the select statement
        $_SESSION['error_try'] = "Error preparing the database query.";
    }

    // Redirect back to others.php with the session messages
    header("Location: ../others.php");
    exit();
} else {
    // If accessed without form submission, redirect
    header("Location: ../others.php");
    exit();
}
?>