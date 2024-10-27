<?php
require '../../conn/connection.php';

$date = $_GET['date'] ?? null;

if ($date) {
    $query = "SELECT holiday_date, holiday_name FROM holiday WHERE holiday_date = ?";
    $stmt = $database->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $holidayData = $result->fetch_assoc();

    if ($holidayData) {
        $response = [
            'date' => $holidayData['holiday_date'],
            'day_type' => 'Holiday',
            'holiday_name' => $holidayData['holiday_name'],
            'generated_qr_code' => '../img/holiday.png' 
        ];
    } else {
        $response = null; 
    }

    header('Content-Type: application/json');
    echo json_encode($response);

    $stmt->close();
} else {
    echo json_encode(null); 
}

$database->close();
?>
