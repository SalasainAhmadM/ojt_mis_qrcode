<?php
require '../conn/connection.php';

$query = "SELECT student_id, student_firstname, student_middle, student_lastname FROM student WHERE company = '' OR company IS NULL";
$result = $database->query($query);

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'student_id' => $row['student_id'],
        'student_firstname' => $row['student_firstname'],
        'student_middle' => $row['student_middle'],
        'student_lastname' => $row['student_lastname']
    ];
}

header('Content-Type: application/json');
echo json_encode($students);
?>