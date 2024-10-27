<?php
include('../../phpqrcode/qrlib.php');
require '../../conn/connection.php';

if (isset($_POST['student_name']) && isset($_POST['student_email'])) {
    $studentName = $_POST['student_name'];
    $studentEmail = $_POST['student_email'];

    // Fetch the student's last name and WMSU ID
    $query = "SELECT student_lastname, wmsu_id FROM student WHERE student_email = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("s", $studentEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $studentData = $result->fetch_assoc();
            $studentLastName = $studentData['student_lastname'];
            $wmsuId = $studentData['wmsu_id'];

            // Check if the student has a WMSU ID
            if (empty($wmsuId)) {
                // Redirect with error if WMSU ID is missing
                header('Location: create-qr.php?status=error_no_wmsu_id');
                exit();
            }
        } else {
            // Redirect with error if the student is not found
            header('Location: create-qr.php?status=error_student_not_found');
            exit();
        }
        $stmt->close();
    } else {
        // Redirect with error if the query fails
        header('Location: create-qr.php?status=error_query_failed');
        exit();
    }

    // Prepare the data for the QR code
    $qrData = $wmsuId;

    // Directory where QR code images will be stored
    $qrCodeDir = "../../uploads/qrcodes/";

    // Create the directory if it doesn't exist
    if (!is_dir($qrCodeDir)) {
        mkdir($qrCodeDir, 0755, true);
    }

    // Create the QR code filename based on student's last name and WMSU ID
    $fileName = $qrCodeDir . "qr-code-" . $studentLastName . "-" . $wmsuId . '.png';

    // Generate the QR code image
    QRcode::png($qrData, $fileName, QR_ECLEVEL_L, 10);

    // Update the database with the new QR code filename
    $query = "UPDATE student SET generated_qr_code = ? WHERE student_email = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("ss", $fileName, $studentEmail);
        $stmt->execute();
        $stmt->close();

        // Redirect to the success page with the student's name
        header('Location: create-qr2.php?status=qr_success&name=' . urlencode($studentName));
        exit();
    } else {
        // Redirect with error if the update query fails
        header('Location: create-qr.php?status=error_update_failed');
        exit();
    }
} else {
    // Redirect with error if the required POST data is missing
    header('Location: create-qr.php?status=error_missing_data');
    exit();
}
?>