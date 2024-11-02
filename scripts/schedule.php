<?php
include('../phpqrcode/qrlib.php');  // Include PHPQRCode library
require '../conn/connection.php';


// Get today's date
$today = date("Y-m-d");

// Get companies without today's schedule
$sql = "SELECT company_id FROM company WHERE company_id NOT IN 
        (SELECT company_id FROM schedule WHERE date = ?)";
$stmt = $database->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $companyId = $row['company_id'];

    // Generate the QR code content and file path
    $qrContent = "QR-" . $companyId . "-" . $today;
    $qrFileName = "../uploads/company/qrcodes/" . $qrContent . ".png";

    // Generate and save the QR code
    QRcode::png($qrContent, $qrFileName, QR_ECLEVEL_L, 4);

    // Insert the schedule into the database
    $insertSQL = "INSERT INTO schedule (company_id, date, time_in, time_out, generated_qr_code, day_type)
                  VALUES (?, ?, '08:00:00', '16:00:00', ?, 'Regular')";
    $insertStmt = $database->prepare($insertSQL);
    $insertStmt->bind_param("iss", $companyId, $today, $qrFileName);
    $insertStmt->execute();
}

// Close databaseections
$stmt->close();
$insertStmt->close();
$database->close();

echo "Daily schedules generated with QR codes.";
?>