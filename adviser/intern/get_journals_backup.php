<?php
require '../../conn/connection.php';

if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);

    // Calculate the start (Monday) and end (Friday) dates of the current week
    $currentDate = new DateTime();
    $startOfWeek = clone $currentDate;
    $startOfWeek->modify('last Monday'); // Go to the last Monday
    $endOfWeek = clone $startOfWeek;
    $endOfWeek->modify('+4 days'); // Friday of the current week

    // Format dates for SQL
    $startDate = $startOfWeek->format('Y-m-d');
    $endDate = $endOfWeek->format('Y-m-d');

    // Fetch journals for the student within the current week excluding unviewed journals
    $query = "SELECT journal_id, journal_name, journal_date FROM student_journal 
              WHERE student_id = ? AND journal_date BETWEEN ? AND ? AND adviser_viewed != 0";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("iss", $student_id, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $journals = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success' => true, 'journals' => $journals]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'No journals found or invalid request']);
?>