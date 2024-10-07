<?php
require '../conn/connection.php';

if (isset($_POST['course_section_id'])) {
    $course_section_id = $_POST['course_section_id'];

    // Query to get the adviser's details based on the course section
    $query = "
        SELECT adviser.adviser_id, adviser.adviser_firstname, adviser.adviser_middle, adviser.adviser_lastname
        FROM course_sections
        LEFT JOIN adviser ON course_sections.adviser_id = adviser.adviser_id
        WHERE course_sections.id = ?
    ";

    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $course_section_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $adviser = $result->fetch_assoc();
            echo json_encode($adviser);
        } else {
            echo json_encode(['error' => 'Adviser not found']);
        }
        $stmt->close();
    }
}
?>