<?php
require '../conn/connection.php';

$student_id = $_GET['student_id'] ?? null;
$remark_type = $_GET['remark_type'] ?? null;
$remark = '';

if ($student_id && $remark_type) {
    $stmt = $database->prepare("SELECT remark FROM attendance_remarks WHERE student_id = ? AND remark_type = ?");
    $stmt->bind_param("is", $student_id, $remark_type);
    $stmt->execute();
    $stmt->bind_result($remark);
    $stmt->fetch();
    $stmt->close();
}

echo htmlspecialchars($remark);
?>