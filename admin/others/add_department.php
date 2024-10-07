<?php
session_start(); // Start the session at the beginning
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentName = trim($_POST['departmentName']);

    // Check if the department already exists
    $checkQuery = "SELECT * FROM departments WHERE department_name = ?";
    if ($stmt = $database->prepare($checkQuery)) {
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Department already exists, set session error message
            $_SESSION['error_1'] = "Department already exists. Please enter a new department.";
        } else {
            // Department doesn't exist, proceed to insert
            $insertQuery = "INSERT INTO departments (department_name) VALUES (?)";
            if ($stmt = $database->prepare($insertQuery)) {
                $stmt->bind_param("s", $departmentName);

                if ($stmt->execute()) {
                    // Success: set session variable and redirect
                    $_SESSION['department_success'] = "Department added successfully!";
                } else {
                    // Handle error in insertion
                    $_SESSION['error_try'] = "Error adding department. Please try again.";
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