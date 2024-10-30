<?php
require '../conn/connection.php';

if (isset($_GET['id'])) {
    $journalId = $_GET['id'];

    $query = "SELECT journal_id, journal_name, journal_date, journal_description, journal_image1, journal_image2, journal_image3 FROM student_journal WHERE journal_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $journalId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $journal = $result->fetch_assoc();
            echo json_encode($journal);
        } else {
            echo json_encode(['error' => 'Journal not found']);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => 'Error preparing statement']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>