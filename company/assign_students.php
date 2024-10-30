<?php
session_start();
require '../conn/connection.php';

// Retrieve and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);
$company_id = $input['company_id'];
$student_ids = $input['student_ids'];

// Check if company_id and student_ids are valid
if ($company_id && !empty($student_ids)) {
    // Prepare SQL query to update students
    $query = "UPDATE student SET company = ? WHERE student_id = ?";
    $stmt = $database->prepare($query);

    // Bind parameters and execute the query for each student
    foreach ($student_ids as $student_id) {
        $stmt->bind_param("ii", $company_id, $student_id);
        $stmt->execute();
    }
    $stmt->close();

    // Respond with success
    echo json_encode(['success' => true]);
} else {
    // Respond with failure
    echo json_encode(['success' => false]);
}
?>