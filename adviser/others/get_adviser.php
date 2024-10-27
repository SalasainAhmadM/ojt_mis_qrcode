<?php
require '../conn/connection.php';

if (isset($_POST['course_section_id'])) {
    $courseSectionId = $_POST['course_section_id'];

    // Query to fetch the adviser for the selected course section by ID
    $query = "SELECT a.adviser_id, a.adviser_firstname, a.adviser_middle, a.adviser_lastname
              FROM course_sections cs
              JOIN adviser a ON cs.adviser_id = a.adviser_id
              WHERE cs.id = ?";  // Use the course_section_id for the query

    $stmt = $database->prepare($query);
    $stmt->bind_param('i', $courseSectionId);  // Bind the course_section_id as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $adviser = $result->fetch_assoc();
        $adviser_fullname = $adviser['adviser_firstname'] . ' ' . $adviser['adviser_middle'] . '. ' . $adviser['adviser_lastname'];
        $adviser_id = $adviser['adviser_id'];

        // Return the adviser data (fullname and ID) as JSON
        echo json_encode(['success' => true, 'adviser_fullname' => $adviser_fullname, 'adviser_id' => $adviser_id]);
    } else {
        echo json_encode(['success' => false]);
    }

    $stmt->close();
}

$database->close();
?>