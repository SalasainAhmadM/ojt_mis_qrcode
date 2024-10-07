<?php
session_start();
require '../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['department_id'], $_POST['department_name'])) {
    $department_id = $_POST['department_id'];
    $department_name = $_POST['department_name'];

    // Check if the department name already exists (to avoid duplicates)
    $query = "SELECT * FROM departments WHERE department_name = ? AND department_id != ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $department_name, $department_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error_1'] = "Department name already exists.";
            header("Location: ../others.php");
            exit();
        }

        $stmt->close();
    }

    // Update department name
    $query = "UPDATE departments SET department_name = ? WHERE department_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("si", $department_name, $department_id);
        if ($stmt->execute()) {
            $_SESSION['department_edit_success'] = "Department updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update department.";
        }
        $stmt->close();
    }
    header("Location: ../others.php");
}
?>