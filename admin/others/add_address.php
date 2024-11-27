<?php
session_start(); // Start the session at the beginning
require '../../conn/connection.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim and sanitize user input to avoid whitespace issues
    $barangayName = trim($_POST['barangayName']);

    // Check if the required fields are not empty
    if (empty($barangayName)) {
        $_SESSION['error_try'] = "Both Barangay Name are required.";
        header("Location: ../others.php");
        exit();
    }

    // Check if the address already exists
    $checkQuery = "SELECT * FROM address WHERE address_barangay = ?";
    if ($stmt = $database->prepare($checkQuery)) {
        // Bind the parameters to avoid SQL injection
        $stmt->bind_param("s", $barangayName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Address already exists, set session error message
            $_SESSION['error_3'] = "Address already exists. Please enter a new address.";
        } else {
            // Proceed to insert the address since it doesn't exist
            $insertQuery = "INSERT INTO address (address_barangay) VALUES (?)";
            if ($insertStmt = $database->prepare($insertQuery)) {
                $insertStmt->bind_param("s", $barangayName);

                if ($insertStmt->execute()) {
                    // Success: set session variable and redirect
                    $_SESSION['address_success'] = "Address added successfully!";
                } else {
                    // Handle error in insertion
                    $_SESSION['error_try'] = "Error adding address. Please try again.";
                }
                $insertStmt->close();
            } else {
                // Handle error preparing the insert statement
                $_SESSION['error_try'] = "Error preparing the insert query.";
            }
        }
        $stmt->close();
    } else {
        // Handle error preparing the select statement
        $_SESSION['error_try'] = "Error preparing the select query.";
    }

    // Redirect back to the form page with the session messages
    header("Location: ../others.php");
    exit();
} else {
    // If accessed without a POST request, redirect
    header("Location: ../others.php");
    exit();
}
?>