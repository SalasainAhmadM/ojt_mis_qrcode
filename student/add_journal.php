<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_SESSION['user_id'];
    $company_id = $_POST['company'];
    $journalTitle = $_POST['journalTitle'];
    $journalDate = $_POST['journalDate'];
    $journalDescription = $_POST['journalDescription'];
    $journalSize = strlen($journalDescription);

    // Check for suspended day
    $suspendedQuery = "
        SELECT COUNT(*) 
        FROM schedule 
        WHERE company_id = ? 
        AND date = ? 
        AND day_type = 'Suspended'
    ";

    if ($suspendedStmt = $database->prepare($suspendedQuery)) {
        $suspendedStmt->bind_param("is", $company_id, $journalDate);
        $suspendedStmt->execute();
        $suspendedStmt->bind_result($isSuspended);
        $suspendedStmt->fetch();
        $suspendedStmt->close();

        if ($isSuspended > 0) {
            $_SESSION['journal_suspended'] = true;
            header("Location: journal.php");
            exit();
        }
    } else {
        echo "Error: Could not prepare suspended query.";
    }

    // Check for holiday
    $holidayQuery = "SELECT COUNT(*) FROM holiday WHERE holiday_date = ?";
    if ($holidayStmt = $database->prepare($holidayQuery)) {
        $holidayStmt->bind_param("s", $journalDate);
        $holidayStmt->execute();
        $holidayStmt->bind_result($isHoliday);
        $holidayStmt->fetch();
        $holidayStmt->close();

        if ($isHoliday > 0) {
            $_SESSION['journal_holiday'] = true;
            header("Location: journal.php");
            exit();
        }
    }

    // Check for absence on the journal date
    $absentQuery = "
        SELECT COUNT(*) 
        FROM attendance_remarks 
        WHERE student_id = ? 
        AND remark_type = 'Absent'
        AND schedule_id = (
            SELECT schedule_id FROM schedule WHERE date = ? AND company_id = ?
        )
    ";
    if ($absentStmt = $database->prepare($absentQuery)) {
        $absentStmt->bind_param("isi", $student_id, $journalDate, $company_id);
        $absentStmt->execute();
        $absentStmt->bind_result($isAbsent);
        $absentStmt->fetch();
        $absentStmt->close();

        if ($isAbsent > 0) {
            $_SESSION['journal_absent'] = true;
            header("Location: journal.php");
            exit();
        }
    } else {
        echo "Error: Could not prepare absent query.";
    }

    // Check if the student has already uploaded a journal for the selected date
    $checkQuery = "SELECT COUNT(*) FROM student_journal WHERE student_id = ? AND journal_date = ?";
    if ($checkStmt = $database->prepare($checkQuery)) {
        $checkStmt->bind_param("is", $student_id, $journalDate);
        $checkStmt->execute();
        $checkStmt->bind_result($journalCount);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($journalCount > 0) {
            $_SESSION['journal_error'] = "You have already uploaded a journal for this date.";
            header("Location: journal.php");
            exit();
        } else {
            // Insert the new journal entry
            $query = "INSERT INTO student_journal (student_id, journal_name, journal_date, journal_description, file_size) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $database->prepare($query)) {
                $stmt->bind_param("isssi", $student_id, $journalTitle, $journalDate, $journalDescription, $journalSize);
                $stmt->execute();

                $_SESSION['journal_success'] = true;
                header("Location: journal.php");
                $stmt->close();
            } else {
                echo "Error: Could not prepare query.";
            }
        }
    } else {
        echo "Error: Could not prepare check query.";
    }
} else {
    echo "Invalid request.";
}
?>