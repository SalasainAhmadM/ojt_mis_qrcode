<?php
require '../conn/connection.php';

$student_id = $_GET['student_id'];

// Get all journals ordered by date, from oldest to newest
$sql = "SELECT journal_id, journal_name, 
               DATE_FORMAT(journal_date, '%Y-%m-%d') AS journal_date
        FROM student_journal 
        WHERE student_id = ? 
        ORDER BY journal_date ASC"; // Ascending order for the earliest date first

$stmt = $database->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

$journalsByWeek = [];
$weekCount = 1;
$currentWeekStartDate = null;

while ($row = $result->fetch_assoc()) {
    $journalDate = $row['journal_date'];

    // Determine if a new week should start based on the current journal date
    if (!$currentWeekStartDate || (strtotime($journalDate) - strtotime($currentWeekStartDate)) >= 7 * 24 * 60 * 60) {
        $currentWeekStartDate = $journalDate;
        $weekLabel = 'Week ' . $weekCount;
        $weekCount++;
    }

    // Append the journal to the appropriate week
    $journalsByWeek[$weekLabel][] = $row;
}

header('Content-Type: application/json');
echo json_encode($journalsByWeek);
?>