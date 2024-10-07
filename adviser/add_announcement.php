<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../index.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $adviser_id = $_SESSION['user_id'];
    $announcementTitle = $_POST['announcementTitle'];
    $announcementDate = $_POST['announcementDate'];
    $announcementDescription = $_POST['announcementDescription'];
    $announcementSize = strlen($announcementDescription);

    $query = "INSERT INTO adviser_announcement (adviser_id, announcement_name, announcement_date, announcement_description) VALUES (?, ?, ?, ?)";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("isss", $adviser_id, $announcementTitle, $announcementDate, $announcementDescription);
        $stmt->execute();

        $_SESSION['announcement_success'] = true;

        header("Location: announcement.php");
        $stmt->close();
    } else {
        echo "Error: Could not prepare query.";
    }
} else {
    echo "Invalid request.";
}
?>