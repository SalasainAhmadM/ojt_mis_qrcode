<?php
session_start();
include('../phpqrcode/qrlib.php');  // Include PHPQRCode library
require '../conn/connection.php';

$database->query("SET time_zone = '+08:00'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = intval($_POST['company_id']);
    $date = $_POST['date'];
    $day_type = $_POST['day_type'];

    // Initialize values based on day type
    $time_in = ($day_type === 'Suspended') ? '00:00:00' : $_POST['time_in'];
    $time_out = ($day_type === 'Suspended') ? '00:00:00' : $_POST['time_out'];
    $fileName = '../img/qr-code-error.png';

    // Generate QR code only for non-Suspended days
    if ($day_type !== 'Suspended') {
        $qrData = "$company_id - $date";
        // $qrData = "Company: $company_id | Date: $date | Time In: $time_in | Time Out: $time_out | Type: $day_type";
        $qrCodeDir = "../uploads/company/qrcodes/";

        if (!is_dir($qrCodeDir)) {
            mkdir($qrCodeDir, 0755, true);
        }

        $fileName = $qrCodeDir . "qr-schedule-" . $company_id . "-" . $date . ".png";
        QRcode::png($qrData, $fileName, QR_ECLEVEL_L, 10);
    }

    // Prepare SQL statement to insert schedule
    $sql = "INSERT INTO schedule (company_id, date, time_in, time_out, generated_qr_code, day_type) 
            VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $database->prepare($sql)) {
        $stmt->bind_param("isssss", $company_id, $date, $time_in, $time_out, $fileName, $day_type);

        if ($stmt->execute()) {
            $_SESSION['schedule_success'] = true;
            header("Location: ./calendar.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: Could not prepare the SQL statement.";
    }
} else {
    echo "Invalid request method.";
}
?>