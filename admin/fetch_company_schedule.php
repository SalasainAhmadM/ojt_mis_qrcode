<?php
require '../conn/connection.php';

$company_id = $_GET['company_id'] ?? null;
$schedule = [];
$holidays = [];

// Fetch all holidays
$holidayQuery = "SELECT holiday_id, holiday_date, holiday_name FROM holiday";
$holidayResult = mysqli_query($database, $holidayQuery);

while ($row = mysqli_fetch_assoc($holidayResult)) {
    $holidays[] = [
        'holidayId' => $row['holiday_id'],
        'start' => $row['holiday_date'],
        'title' => $row['holiday_name'],
        'color' => '#FF0000', // Red color for holiday background
        'display' => 'background' // Display as background event
    ];
}

// Fetch schedule for the specified company
if ($company_id) {
    $query = "SELECT * FROM schedule WHERE company_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $scheduleDate = $row['date'];

            // Check if there is a holiday on this date
            $isHoliday = false;
            foreach ($holidays as $holiday) {
                if ($holiday['start'] == $scheduleDate) {
                    $isHoliday = true;
                    break;
                }
            }

            // If it's not a holiday, add the schedule event
            if (!$isHoliday) {
                $event = [
                    'title' => '', // Default title, will be set below
                    'start' => $scheduleDate,
                    'display' => 'background', // Apply color to entire date cell
                ];

                // Determine the title and color based on day_type
                switch ($row['day_type']) {
                    case 'Regular':
                        $timeIn = $row['time_in'] ?? '08:00:00';
                        $timeOut = $row['time_out'] ?? '16:00:00';
                        $event['title'] = 'Regular - ' . date('g:ia', strtotime($timeIn)) . ' to ' . date('g:ia', strtotime($timeOut));
                        $event['color'] = 'green';
                        break;

                    case 'Halfday':
                        $timeIn = $row['time_in'] ?? '08:00:00';
                        $timeOut = $row['time_out'] ?? '12:00:00';
                        $event['title'] = 'Halfday - ' . date('g:ia', strtotime($timeIn)) . ' to ' . date('g:ia', strtotime($timeOut));
                        $event['color'] = 'yellow';
                        break;

                    case 'Suspended':
                        $event['title'] = 'Suspended';
                        $event['color'] = 'orange';
                        break;
                }

                $schedule[] = $event;
            }
        }

        $stmt->close();
    }
}

$allEvents = array_merge($holidays, $schedule);

header('Content-Type: application/json');
echo json_encode($allEvents);
?>