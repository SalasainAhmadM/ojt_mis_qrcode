<?php
require '../conn/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semesterType = $_POST['semesterType'];

    // Update the semester type in the database
    $query = "UPDATE `semester` SET `type` = ? WHERE `id` = 1";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("s", $semesterType);
        if ($stmt->execute()) {
            $_SESSION['success'] = true;
        } else {
            $_SESSION['success'] = false;
        }
        $stmt->close();
    }

    header("Location: ./index.php");
    exit();
}
?>