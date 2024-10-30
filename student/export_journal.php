<?php
require '../conn/connection.php';
require '../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

$student_id = $_GET['student_id'];
$journal_ids = explode(',', $_GET['journal_ids']); // Array of selected journal IDs

// Prepare SQL query to fetch the selected journals
$sql = "SELECT journal_name, journal_date, journal_description, journal_image1, journal_image2, journal_image3 
        FROM student_journal 
        WHERE student_id = ? AND journal_id IN (" . implode(',', array_fill(0, count($journal_ids), '?')) . ")";
$stmt = $database->prepare($sql);
$params = array_merge([$student_id], $journal_ids);
$stmt->bind_param(str_repeat('i', count($params)), ...$params);
$stmt->execute();

$result = $stmt->get_result();

// Fetch student, company, course section, and adviser details
$sqlStudent = "
    SELECT 
        s.student_firstname, s.student_middle, s.student_lastname, 
        c.course_section_name, 
        cm.company_name, 
        CONCAT(cm.company_rep_firstname, ' ', cm.company_rep_middle, ' ', cm.company_rep_lastname) AS company_rep, 
        CONCAT(a.adviser_firstname, ' ', a.adviser_middle, ' ', a.adviser_lastname) AS adviser_name 
    FROM student s
    LEFT JOIN company cm ON s.company = cm.company_id
    LEFT JOIN course_sections c ON s.course_section = c.id
    LEFT JOIN adviser a ON c.adviser_id = a.adviser_id
    WHERE s.student_id = ?
";
$stmtStudent = $database->prepare($sqlStudent);
$stmtStudent->bind_param("i", $student_id);
$stmtStudent->execute();
$student = $stmtStudent->get_result()->fetch_assoc();

// Initialize PHPWord
$phpWord = new PhpWord();
$phpWord->setDefaultFontSize(11); // Set default font size

// Define styles for header and text
$headerFontStyle = ['bold' => true, 'size' => 10];
$subHeaderFontStyle = ['bold' => true, 'size' => 9];
$headerParagraphStyle = ['alignment' => 'center'];
$labelStyle = ['bold' => true, 'size' => 11];
$valueStyle = ['size' => 11];
$textParagraphStyle = ['alignment' => 'both'];

// Paths to logos
$logoLeftPath = '../img/wmsu.png'; // Replace with the actual path to the left logo
$logoRightPath = '../img/ccs.png'; // Replace with the actual path to the right logo

while ($row = $result->fetch_assoc()) {
    $journalName = $row['journal_name'];
    $journalDate = date('F j, Y', strtotime($row['journal_date']));
    $journalDescription = $row['journal_description'];
    $images = array_filter([$row['journal_image1'], $row['journal_image2'], $row['journal_image3']], fn($img) => !empty ($img));

    // Add a new section for each journal
    $section = $phpWord->addSection();

    // Header with logos and school name
    $header = $section->addHeader();
    $headerTable = $header->addTable();
    $headerTable->addRow();

    // Left logo
    $headerTable->addCell(2500)->addImage($logoLeftPath, ['width' => 50, 'height' => 50]);

    // Center text with font size adjustments
    $headerCell = $headerTable->addCell(5000);
    $headerCell->addText("Western Mindanao State University", $headerFontStyle, $headerParagraphStyle);
    $headerCell->addText("College of Computing Studies", $headerFontStyle, $headerParagraphStyle);
    $headerCell->addText("DEPARTMENT OF INFORMATION TECHNOLOGY", $subHeaderFontStyle, $headerParagraphStyle);
    $headerCell->addText("Zamboanga City", $headerFontStyle, $headerParagraphStyle);

    // Right logo aligned to the top right
    $headerTable->addCell(2500)->addImage($logoRightPath, ['width' => 50, 'height' => 50, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);

    // Green line after header
    $lineTable = $section->addTable();
    $lineTable->addRow();
    $lineTable->addCell(9000, ['borderBottomSize' => 18, 'borderBottomColor' => '095d40']); // Green line
    $section->addTextBreak(); // Add margin after green line

    // Student and journal details in rows
    $infoTable = $section->addTable();

    // Row 1: Name and Date
    $infoTable->addRow();
    $infoTable->addCell(4500)->addText("Name: " . $student['student_firstname'] . ' ' . $student['student_middle'] . ' ' . $student['student_lastname'], $labelStyle);
    $infoTable->addCell(4500)->addText("Date: $journalDate", $labelStyle);

    // Row 2: Course and Company
    $infoTable->addRow();
    $infoTable->addCell(4500)->addText("Course and Section: " . $student['course_section_name'], $labelStyle);
    $infoTable->addCell(4500)->addText("Company: " . $student['company_name'], $labelStyle);

    // Row 3: Adviser and Representative
    $infoTable->addRow();
    $infoTable->addCell(4500)->addText("Adviser: " . $student['adviser_name'], $labelStyle);
    $infoTable->addCell(4500)->addText("Representative: " . $student['company_rep'], $labelStyle);

    $section->addTextBreak();
    // Journal Title
    $section->addText($journalName, ['bold' => true, 'size' => 14], ['alignment' => 'center']);
    $section->addTextBreak();
    // Journal description content
    $section->addText($journalDescription, $valueStyle, $textParagraphStyle);
    $section->addTextBreak(2);

    // Adjust image sizes based on the number of images
    if (count($images) === 1) {
        $section->addImage($images[0], ['width' => 150, 'height' => 150, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
    } elseif (count($images) === 2) {
        $imageTable = $section->addTable();
        $imageTable->addRow();
        foreach ($images as $imagePath) {
            $cell = $imageTable->addCell(4500, ['alignment' => 'center']);
            $cell->addImage($imagePath, ['width' => 150, 'height' => 150]);
        }
    } else {
        $imageTable = $section->addTable();
        $imageTable->addRow();
        foreach ($images as $imagePath) {
            $cell = $imageTable->addCell(3000, ['alignment' => 'center']);
            $cell->addImage($imagePath, ['width' => 100, 'height' => 100]);
        }
    }

    $section->addPageBreak();
}

// Save the Word document to a temporary file
$fileName = 'journals_export_' . date('Ymd_His') . '.docx';
$tempFilePath = sys_get_temp_dir() . '/' . $fileName;

$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($tempFilePath);

// Force download of the generated Word document
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempFilePath));
readfile($tempFilePath);

// Delete the temporary file after download
unlink($tempFilePath);
exit;
?>