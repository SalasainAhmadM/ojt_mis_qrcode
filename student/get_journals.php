<?php
require '../conn/connection.php';

$student_id = $_GET['student_id'];

$sql = "SELECT journal_id, journal_name, DATE_FORMAT(journal_date, '%Y-%m-%d') AS journal_date 
        FROM student_journal 
        WHERE student_id = ?";
$stmt = $database->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

$journals = [];
while ($row = $result->fetch_assoc()) {
    $journals[] = $row;
}

header('Content-Type: application/json');
echo json_encode($journals);
?>