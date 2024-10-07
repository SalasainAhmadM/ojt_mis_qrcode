<?php
session_start();
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['course_section_name'])) {
    $course_section_id = $_POST['id'];
    $course_section_name = $_POST['course_section_name'];

    // Check if the course_section name already exists (to avoid duplicates)
    $query = "SELECT * FROM course_sections WHERE course_section_name = ? AND id != ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $course_section_name, $course_section_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error_2'] = "Course section name already exists.";
            // Redirect or just stop further processing
            header("Location: ../others.php");
            exit();
        }

        $stmt->close();
    }

    // Update course_section name if no duplicates found
    $query = "UPDATE course_sections SET course_section_name = ? WHERE id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $course_section_name, $course_section_id);
        if ($stmt->execute()) {
            $_SESSION['course_section_edit_success'] = "Course section updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update course section.";
        }
        $stmt->close();
    }
    header("Location: ../others.php");
    exit();
}
?>