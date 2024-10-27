<?php
require '../conn/connection.php';

$holidays = [];
$query = "SELECT holiday_id, holiday_date, holiday_name FROM holiday";
$result = mysqli_query($database, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $holidays[] = [
        'holidayId' => $row['holiday_id'],
        'start' => $row['holiday_date'],
        'title' => $row['holiday_name'],
        'color' => '#FF0000', 
        'display' => 'background'
    ];
}

header('Content-Type: application/json');
echo json_encode($holidays);
?>
