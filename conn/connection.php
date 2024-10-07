<?php
$host = "localhost";
$username = "root";
$password = "";
$database_name = "ccs_ojt";

// Create a new MySQLi connection
$database = new mysqli($host, $username, $password, $database_name);

// Check the connection
if ($database->connect_error) {
    die("Connection failed: " . $database->connect_error);
}

// Set character set to UTF-8
$database->set_charset("utf8mb4");
?>