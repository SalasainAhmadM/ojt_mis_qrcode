<?php
require '../../conn/connection.php';

$companyId = $_GET['company_id'] ?? null;

if ($companyId) {
    // Fetch all day types
    $query = "SELECT date, day_type FROM schedule WHERE company_id = ?";
    $stmt = $database->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $suspendeds = [];
    $regulars = [];
    $halfdays = [];

    while ($row = $result->fetch_assoc()) {
        if ($row['day_type'] === 'Suspended') {
            $suspendeds[] = $row['date'];
        } elseif ($row['day_type'] === 'Regular') {
            $regulars[] = $row['date'];
        } elseif ($row['day_type'] === 'Halfday') {
            $halfdays[] = $row['date'];
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'suspendeds' => $suspendeds,
        'regulars' => $regulars,
        'halfdays' => $halfdays
    ]);

    $stmt->close();
} else {
    echo json_encode([]);
}

$database->close();
?>
