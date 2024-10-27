<?php
require '../../conn/connection.php';

if (isset($_GET['date']) && isset($_GET['company_id'])) {
    $date = $_GET['date'];
    $company_id = intval($_GET['company_id']);

    $sql = "SELECT * FROM schedule WHERE date = ? AND company_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("si", $date, $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());  
    } else {
        echo json_encode(null); 
    }

    $stmt->close();
}
?>