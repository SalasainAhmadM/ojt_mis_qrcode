<?php
session_start();
require '../conn/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["feedback_id"];
    $field = $_POST["feedback_field"];
    $text = $database->real_escape_string($_POST["feedback_text"]);

    $sql = "UPDATE feedback_questions SET $field = '$text' WHERE id = $id";

    if ($database->query($sql) === TRUE) {
        $_SESSION['update_success'] = true;
    } else {
        $_SESSION['update_success'] = false;
    }

    $database->close();
    header("Location: feedback.php");
    exit();
}
?>