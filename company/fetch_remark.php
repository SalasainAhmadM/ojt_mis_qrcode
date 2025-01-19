<?php
require '../conn/connection.php';

// Get parameters from the request
$student_id = $_GET['student_id'] ?? null;
$remark_type = $_GET['remark_type'] ?? null;
$remark_id = $_GET['remark_id'] ?? null;

// Validate inputs
if (!$student_id || !$remark_type || !$remark_id) {
    echo "Invalid request parameters.";
    exit;
}

// Query to fetch the remark based on the provided IDs and type
$query = "SELECT remark FROM attendance_remarks 
          WHERE student_id = ? AND remark_id = ? AND remark_type = ?";
$stmt = $database->prepare($query);
$stmt->bind_param("iis", $student_id, $remark_id, $remark_type); // Bind parameters
$stmt->execute();
$stmt->bind_result($remark);
$stmt->fetch();

// Check if a remark is found
if ($remark) {
    echo $remark;
} else {
    echo "No remark available.";
}

$stmt->close();
$database->close();
?>