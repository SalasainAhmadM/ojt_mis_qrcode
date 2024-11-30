<?php
require '../conn/connection.php'; // Update with your actual connection script

// Get remark_id from the query parameters
$remark_id = isset($_GET['remark_id']) ? intval($_GET['remark_id']) : null;

$response = ['proof_image' => null];

if ($remark_id) {
    // Prepare and execute the SQL query
    $stmt = $database->prepare("SELECT proof_image FROM attendance_remarks WHERE remark_id = ?");
    $stmt->bind_param("i", $remark_id);
    $stmt->execute();
    $stmt->bind_result($proof_image);
    $stmt->fetch();
    $stmt->close();

    // Add proof image to the response if available
    if ($proof_image) {
        $response['proof_image'] = $proof_image;
    }
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>