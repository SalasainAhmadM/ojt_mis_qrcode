<?php
session_start();
require '../conn/connection.php';

if (isset($_GET['id'])) {
    $announcementId = $_GET['id'];

    $query = "DELETE FROM adviser_announcement WHERE announcement_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $announcementId);
        $stmt->execute();

        $_SESSION['announcement_delete_success'] = true;

        $stmt->close();
    }

    header("Location: announcement.php");
}
?>