<?php
require '../../conn/connection.php';

$query = "SELECT holiday_date, holiday_name FROM holiday";
$stmt = $database->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $database->error);
}

$stmt->execute();
$result = $stmt->get_result();

$holidays = [];

while ($row = $result->fetch_assoc()) {
    $holidays[] = [
        'holiday_date' => $row['holiday_date'],
        'holiday_name' => $row['holiday_name']
    ];
}

header('Content-Type: application/json');
echo json_encode($holidays);

$stmt->close();
$database->close();
?>
