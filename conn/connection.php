<?php
$host = "localhost";
$username = "root";
$password = "";
$database_name = "ccs_ojt";

$database = new mysqli($host, $username, $password, $database_name);

if ($database->connect_error) {
    die("Connection failed: " . $database->connect_error);
}

$database->set_charset("utf8mb4");

// Set PHP timezone
date_default_timezone_set("Asia/Manila");
?>