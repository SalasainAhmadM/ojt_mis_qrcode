<?php
session_start();
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['course_section_name'], $_POST['adviser_id'])) {
    $course_section_id = $_POST['id'];
    $course_section_name = trim($_POST['course_section_name']);
    $adviser_id = (int) $_POST['adviser_id'];

    // Check if the course_section name already exists (to avoid duplicates)
    $query = "SELECT * FROM course_sections WHERE course_section_name = ? AND id != ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $course_section_name, $course_section_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error_2'] = "Course section name already exists.";
            // Redirect if duplicate found
            header("Location: ../others.php");
            exit();
        }

        $stmt->close();
    }

    // Update course_section name and adviser_id if no duplicates found
    $query = "UPDATE course_sections SET course_section_name = ?, adviser_id = ? WHERE id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("sii", $course_section_name, $adviser_id, $course_section_id);
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