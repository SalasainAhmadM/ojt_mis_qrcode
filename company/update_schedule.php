<?php
session_start();
require_once '../conn/connection.php'; // Update this path to your actual connection file
include('../phpqrcode/qrlib.php');  // Include PHPQRCode library

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'];
    $company_id = $_POST['company_id'];
    $time_in = $_POST['time_in'] ?? null;
    $time_out = $_POST['time_out'] ?? null;
    $day_type = $_POST['day_type'] ?? null;

    // Fetch existing schedule data
    $query = "SELECT * FROM schedule WHERE schedule_id = ?";
    $stmt = $database->prepare($query);
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingSchedule = $result->fetch_assoc();

    // If the schedule is not found, set error message and redirect
    if (!$existingSchedule) {
        $_SESSION['error'] = "Schedule not found!";
        header("Location: ./calendar.php");
        exit();
    }

    // Use existing values if the input field is unchanged
    $time_in = empty($time_in) ? $existingSchedule['time_in'] : $time_in;
    $time_out = empty($time_out) ? $existingSchedule['time_out'] : $time_out;
    $day_type = empty($day_type) ? $existingSchedule['day_type'] : $day_type;

    // Determine the QR code path based on day_type
    $fileName = $existingSchedule['generated_qr_code']; // Use existing QR code as default
    if ($day_type === 'Suspended') {
        $fileName = "../img/qr-code-error.png";  // Set to error QR for suspended days
    } elseif ($day_type === 'Regular' || $day_type === 'Halfday') {
        // Generate a new QR code for non-Suspended days
        $qrData = "$company_id - {$existingSchedule['date']}";
        $qrCodeDir = "../uploads/company/qrcodes/";

        if (!is_dir($qrCodeDir)) {
            mkdir($qrCodeDir, 0755, true);
        }

        $fileName = $qrCodeDir . "qr-schedule-" . $company_id . "-" . $existingSchedule['date'] . ".png";
        QRcode::png($qrData, $fileName, QR_ECLEVEL_L, 10);
    }

    // Update the schedule with new values and QR code path
    $updateQuery = "UPDATE schedule SET time_in = ?, time_out = ?, day_type = ?, generated_qr_code = ? WHERE schedule_id = ?";
    $stmt = $database->prepare($updateQuery);
    $stmt->bind_param('ssssi', $time_in, $time_out, $day_type, $fileName, $schedule_id);

    if ($stmt->execute()) {
        $_SESSION['edit_success'] = "Schedule updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update schedule. Please try again.";
    }

    // Redirect back to the calendar page after updating
    header("Location: ./calendar.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request method!";
    header("Location: ./calendar.php");
    exit();
}
