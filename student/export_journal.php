<?php
require_once '../PHPWord-master/src/PhpWord/PhpWord.php';
require_once '../conn/connection.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

$phpWord = new PhpWord();
$section = $phpWord->addSection();

$table = $section->addTable();

$table->addRow();
$table->addCell(2000)->addText('Title');
$table->addCell(4000)->addText('Description');
$table->addCell(2000)->addText('Date Submitted');
$table->addCell(1000)->addText('Size');

$student_id = $_GET['student_id'];
$query = "SELECT journal_name, journal_description, journal_date, file_size FROM student_journal WHERE student_id = ? ORDER BY journal_date DESC";

if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Add journal data as rows in the table
        while ($row = $result->fetch_assoc()) {
            $table->addRow();
            $table->addCell(2000)->addText(htmlspecialchars($row['journal_name']));
            $table->addCell(4000)->addText(htmlspecialchars($row['journal_description']));
            $table->addCell(2000)->addText(date('M d Y', strtotime($row['journal_date'])));
            $table->addCell(1000)->addText(htmlspecialchars($row['file_size']));
        }
    } else {
        $section->addText("No journal entries found.");
    }
    $stmt->close();
}

// Save the document as a Word file
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="journal_entries.docx"');
header('Cache-Control: max-age=0');

$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');
exit;
?>