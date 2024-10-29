<?php
require '../conn/connection.php';

if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);

    $query = "SELECT question_1, question_2, question_3, question_4, question_5 
              FROM feedback WHERE student_id = ?";
    $stmt = $database->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $feedback = $result->fetch_assoc();
        echo json_encode($feedback);
    } else {
        echo json_encode([]); // Return empty object if no feedback found
    }

    $stmt->close();
    $database->close();
} else {
    echo json_encode(['error' => 'Student ID not provided.']);
}
?>