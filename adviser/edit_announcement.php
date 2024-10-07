<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $announcement_id = isset($_POST['announcementId']) ? $_POST['announcementId'] : '';
    $adviser_id = isset($_POST['adviserId']) ? $_POST['adviserId'] : '';
    $title = isset($_POST['editAnnouncementTitle']) ? $_POST['editAnnouncementTitle'] : '';
    $description = isset($_POST['editAnnouncementDescription']) ? $_POST['editAnnouncementDescription'] : '';
    $date = isset($_POST['editAnnouncementDate']) ? $_POST['editAnnouncementDate'] : '';

    if (empty($announcement_id) || empty($title) || empty($description) || empty($date)) {

        $_SESSION['announcement_update_error'] = "All fields are required.";
        header("Location: announcement.php");
        exit();
    }

    $query = "UPDATE adviser_announcement SET announcement_name = ?, announcement_description = ?, announcement_date = ? WHERE announcement_id = ? AND adviser_id = ?";

    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("sssii", $title, $description, $date, $announcement_id, $adviser_id);

        if ($stmt->execute()) {

            $_SESSION['announcement_update_success'] = true;
        } else {

            $_SESSION['announcement_update_error'] = "Failed to update announcement.";
        }

        $stmt->close();
    } else {
        $_SESSION['announcement_update_error'] = "Error preparing the update statement.";
    }

    header("Location: announcement.php");
    exit();
}
