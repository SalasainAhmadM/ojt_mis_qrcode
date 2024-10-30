<?php
session_start();
require '../conn/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['journalId'], $_POST['editJournalTitle'], $_POST['editJournalDate'], $_POST['editJournalDescription'])) {
    $journalId = $_POST['journalId'];
    $journalTitle = $_POST['editJournalTitle'];
    $journalDate = $_POST['editJournalDate'];
    $journalDescription = $_POST['editJournalDescription'];
    $studentId = $_SESSION['user_id'];

    // Validate the input fields
    if (!empty($journalTitle) && !empty($journalDate) && !empty($journalDescription)) {

        // Prepare the main query for updating the journal text fields
        $query = "UPDATE student_journal SET journal_name = ?, journal_date = ?, journal_description = ? WHERE journal_id = ? AND student_id = ?";
        if ($stmt = $database->prepare($query)) {
            $stmt->bind_param("sssii", $journalTitle, $journalDate, $journalDescription, $journalId, $studentId);

            // Execute the statement
            if ($stmt->execute()) {
                // Define the directory for image uploads
                $uploadDir = '../uploads/student/journals/';
                // Process each image file input (image1, image2, image3)
                for ($i = 1; $i <= 3; $i++) {
                    $fileInputName = 'imageEdit' . $i;
                    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
                        $fileName = basename($_FILES[$fileInputName]['name']);
                        $targetFilePath = $uploadDir . uniqid() . '_' . $fileName;

                        // Move the uploaded file to the target directory
                        if (move_uploaded_file($fileTmpPath, $targetFilePath)) {
                            // Update the image column in the database
                            $imageColumn = "journal_image" . $i;
                            $imageQuery = "UPDATE student_journal SET $imageColumn = ? WHERE journal_id = ? AND student_id = ?";
                            if ($imageStmt = $database->prepare($imageQuery)) {
                                $imageStmt->bind_param("sii", $targetFilePath, $journalId, $studentId);
                                $imageStmt->execute();
                                $imageStmt->close();
                            }
                        } else {
                            $_SESSION['journal_error'] = "Error uploading image $i.";
                            header("Location: journal.php");
                            exit();
                        }
                    }
                }

                $_SESSION['journal_update_success'] = true;
                header("Location: journal.php");
            } else {
                $_SESSION['journal_error'] = "Error updating journal.";
                header("Location: journal.php");
            }

            $stmt->close();
        } else {
            $_SESSION['journal_error'] = "Error preparing update statement.";
            header("Location: journal.php");
        }
    } else {
        $_SESSION['journal_error'] = "Please fill out all fields.";
        header("Location: journal.php");
    }
} else {
    $_SESSION['journal_error'] = "Invalid request.";
    header("Location: journal.php");
}
?>