<?php
require '../conn/connection.php';

require '../conn/connection.php';

$student_id = $_GET['student_id'];

// Get all journals ordered by date, from oldest to newest
$sql = "SELECT journal_id, journal_name, 
               DATE_FORMAT(journal_date, '%Y-%m-%d') AS journal_date
        FROM student_journal 
        WHERE student_id = ? 
        ORDER BY journal_date ASC";

$stmt = $database->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

$journalsByWeek = [];
$weekMap = []; // Maps calendar week to "Week N"
$weekCount = 1;

while ($row = $result->fetch_assoc()) {
    $journalDate = $row['journal_date'];

    // Determine the start of the calendar week (Monday)
    $timestamp = strtotime($journalDate);
    $weekStart = date('Y-m-d', strtotime('monday this week', $timestamp));

    // Assign "Week N" labels sequentially
    if (!isset($weekMap[$weekStart])) {
        $weekMap[$weekStart] = "Week $weekCount";
        $weekCount++;
    }

    $weekLabel = $weekMap[$weekStart];

    // Append the journal to the appropriate week
    $journalsByWeek[$weekLabel][] = $row;
}

header('Content-Type: application/json');
echo json_encode($journalsByWeek);


?>