<?php
require '../conn/connection.php';

$journal_id = isset($_GET['journal_id']) ? (int) $_GET['journal_id'] : 0;

// Prepare and execute the query to fetch journal images
$sql = "SELECT journal_image1, journal_image2, journal_image3 FROM student_journal WHERE journal_id = ?";
$stmt = $database->prepare($sql);
$stmt->bind_param('i', $journal_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize an array to store image paths
$images = [];

// Fetch the result and push non-empty image paths to the array
if ($row = $result->fetch_assoc()) {
    foreach (['journal_image1', 'journal_image2', 'journal_image3'] as $imageColumn) {
        if (!empty($row[$imageColumn])) {
            $images[] = ['path' => $row[$imageColumn]];
        }
    }
}

// Close databaseections
$stmt->close();
$database->close();

// Send the images as a JSON response
header('Content-Type: application/json');
echo json_encode($images);
?>