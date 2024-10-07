<?php
session_start();
require '../conn/connection.php';

if (isset($_GET['id'])) {
    $announcement_id = $_GET['id'];

    // Prepare the SQL query to fetch the announcement details
    $query = "SELECT announcement_id, announcement_name, announcement_description, announcement_date, adviser_id FROM adviser_announcement WHERE announcement_id = ?";

    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $announcement_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $announcement = $result->fetch_assoc();
                // Return the data as JSON
                header('Content-Type: application/json');
                echo json_encode($announcement);
            } else {
                // No announcement found
                echo json_encode(["error" => "Announcement not found."]);
            }
        } else {
            // Query execution error
            echo json_encode(["error" => "Failed to execute query."]);
        }
        $stmt->close();
    } else {
        // Prepare statement error
        echo json_encode(["error" => "Error preparing the statement."]);
    }
} else {
    echo json_encode(["error" => "No announcement ID provided."]);
}
?>