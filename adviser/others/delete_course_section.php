<?php
session_start();
require '../../conn/connection.php';

if (isset($_GET['id'])) {
    $course_section_id = $_GET['id'];

    // Delete the course_section
    $query = "DELETE FROM course_sections WHERE id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $course_section_id);
        if ($stmt->execute()) {
            $_SESSION['course_section_delete'] = "course_section deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete course_section.";
        }
        $stmt->close();
    }
    header("Location: ../others.php");
}
?>