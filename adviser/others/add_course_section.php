<?php
session_start();
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseName = trim($_POST['courseName']);

    // Validate the input to match the format (BSIT-4A, etc.)
    if (preg_match('/^[A-Z]{4}-[A-Z0-9]{2}$/', $courseName)) {
        // Check if the course section already exists
        $checkQuery = "SELECT * FROM course_sections WHERE course_section_name = ?";
        if ($stmt = $database->prepare($checkQuery)) {
            $stmt->bind_param("s", $courseName);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Course section already exists, set session error message
                $_SESSION['error_2'] = "Course and Section already exists. Please enter a new course and section.";
            } else {
                // Course section doesn't exist, proceed to insert
                $insertQuery = "INSERT INTO course_sections (course_section_name) VALUES (?)";
                if ($stmt = $database->prepare($insertQuery)) {
                    $stmt->bind_param("s", $courseName);

                    if ($stmt->execute()) {
                        // Success: set session variable and redirect
                        $_SESSION['course_section_success'] = "Course and Section added successfully!";
                    } else {
                        // Handle error in insertion
                        $_SESSION['error_try'] = "Error adding Course and Section. Please try again.";
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
    } else {
        // Handle invalid format
        $_SESSION['error_try'] = "Invalid Course and Section format. It must follow the BSIT-4A format.";
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