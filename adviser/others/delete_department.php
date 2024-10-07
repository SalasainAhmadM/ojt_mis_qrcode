<?php
session_start();
require '../../conn/connection.php';

if (isset($_GET['id'])) {
    $department_id = $_GET['id'];

    // Delete the department
    $query = "DELETE FROM departments WHERE department_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $department_id);
        if ($stmt->execute()) {
            $_SESSION['department_delete'] = "Department deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete department.";
        }
        $stmt->close();
    }
    header("Location: ../others.php");
}
?>