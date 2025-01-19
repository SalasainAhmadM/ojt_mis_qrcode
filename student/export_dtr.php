<?php
require '../conn/connection.php';
require '../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// Fetch student and DTR details
$student_id = $_GET['student_id'];

// Query to fetch student information
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
$student = $stmtStudent->get_result()->fetch_assoc() ?: [
    'student_firstname' => 'Unknown',
    'student_middle' => '',
    'student_lastname' => 'User',
    'course_section_name' => 'Unknown',
    'company_name' => 'Unknown',
    'adviser_name' => 'Unknown',
    'company_rep' => 'Unknown',
];

// Query to fetch DTR details with total hours
$sqlDTR = "
    SELECT 
        s.date AS schedule_date, 
        MIN(a.time_in) AS first_time_in, 
        MAX(a.time_out) AS last_time_out,
        COALESCE(ar.remark_type, 'Regular') AS remark_type, 
        COALESCE(ar.remark, '') AS remark, 
        COALESCE(ar.status, 'Present') AS status,
        SUM(a.ojt_hours) AS total_hours
    FROM schedule s
    LEFT JOIN attendance a ON s.schedule_id = a.schedule_id
    LEFT JOIN attendance_remarks ar ON a.schedule_id = ar.schedule_id AND a.student_id = ar.student_id
    WHERE a.student_id = ?
    GROUP BY s.date, a.schedule_id, ar.remark_type, ar.remark, ar.status
    ORDER BY s.date ASC
";

$stmtDTR = $database->prepare($sqlDTR);
$stmtDTR->bind_param("i", $student_id);
$stmtDTR->execute();
$dtrEntries = $stmtDTR->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

// Initialize PHPWord
$phpWord = new PhpWord();
$phpWord->setDefaultFontSize(11); // Set default font size

// Define styles for header and text
$headerFontStyle = ['bold' => true, 'size' => 10];
$subHeaderFontStyle = ['bold' => true, 'size' => 9];
$headerParagraphStyle = ['alignment' => 'center'];
$labelStyle = ['bold' => true, 'size' => 11];
$valueStyle = ['size' => 11];

// Define table and cell styles
$tableStyle = [
    'borderSize' => 6,
    'borderColor' => '999999',
    'cellMargin' => 50,
];
$cellStyle = [
    'valign' => 'center',
    'borderBottomColor' => '999999',
    'borderBottomSize' => 6,
];
$textAlignCenter = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];

// Paths to logo images
$logoLeftPath = file_exists('../img/wmsu.png') ? '../img/wmsu.png' : null;
$logoRightPath = file_exists('../img/ccs.png') ? '../img/ccs.png' : null;

// Create a new section
$section = $phpWord->addSection();

// Add document header
$header = $section->addHeader();
$headerTable = $header->addTable();
$headerTable->addRow();

if ($logoLeftPath) {
    $headerTable->addCell(2500)->addImage($logoLeftPath, ['width' => 50, 'height' => 50]);
}

$headerCell = $headerTable->addCell(5000);
$headerCell->addText("Western Mindanao State University", $headerFontStyle, $headerParagraphStyle);
$headerCell->addText("College of Computing Studies", $headerFontStyle, $headerParagraphStyle);
$headerCell->addText("DEPARTMENT OF INFORMATION TECHNOLOGY", $subHeaderFontStyle, $headerParagraphStyle);
$headerCell->addText("Zamboanga City", $headerFontStyle, $headerParagraphStyle);

if ($logoRightPath) {
    $headerTable->addCell(2500)->addImage($logoRightPath, [
        'width' => 50,
        'height' => 50,
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,
    ]);
}

$lineTable = $section->addTable();
$lineTable->addRow();
$lineTable->addCell(9000, ['borderBottomSize' => 18, 'borderBottomColor' => '095d40']);
$section->addTextBreak();

$infoTable = $section->addTable();
$infoTable->addRow();
$infoTable->addCell(4500)->addText("Name: {$student['student_firstname']} {$student['student_middle']} {$student['student_lastname']}", $labelStyle);
$infoTable->addCell(4500)->addText("Date: " . date('F j, Y'), $labelStyle);

$infoTable->addRow();
$infoTable->addCell(4500)->addText("Course and Section: {$student['course_section_name']}", $labelStyle);
$infoTable->addCell(4500)->addText("Company: {$student['company_name']}", $labelStyle);

$infoTable->addRow();
$infoTable->addCell(4500)->addText("Adviser: {$student['adviser_name']}", $labelStyle);
$infoTable->addCell(4500)->addText("Representative: {$student['company_rep']}", $labelStyle);

$section->addTextBreak(1);
$section->addText("DTR", ['bold' => true, 'size' => 14], ['alignment' => 'center']);

// Add DTR table
$phpWord->addTableStyle('DTR Table', $tableStyle);
$table = $section->addTable('DTR Table');

// Add table headers
$table->addRow();
$table->addCell(2000, $cellStyle)->addText("Date", $labelStyle, $textAlignCenter);
$table->addCell(1500, $cellStyle)->addText("First Time-in", $labelStyle, $textAlignCenter);
$table->addCell(1500, $cellStyle)->addText("Last Time-out", $labelStyle, $textAlignCenter);
$table->addCell(1500, $cellStyle)->addText("Remark Type", $labelStyle, $textAlignCenter);
$table->addCell(1500, $cellStyle)->addText("Total Hours", $labelStyle, $textAlignCenter);
$table->addCell(1000, $cellStyle)->addText("Status", $labelStyle, $textAlignCenter);

// Populate table rows
foreach ($dtrEntries as $entry) {
    $first_time_in = isset($entry['first_time_in']) ? date("g:i A", strtotime($entry['first_time_in'])) : 'N/A';
    $last_time_out = isset($entry['last_time_out']) ? date("g:i A", strtotime($entry['last_time_out'])) : 'N/A';

    if ($entry['remark_type'] === 'Absent') {
        $total_hours_formatted = "N/A";
    } else {
        $total_hours_decimal = $entry['total_hours'] ?? 0;
        $hours = floor($total_hours_decimal);
        $minutes = round(($total_hours_decimal - $hours) * 60);

        $total_hours_formatted = ($hours > 0 ? "{$hours} hr" . ($hours > 1 ? "s" : "") : "") .
            ($minutes > 0 ? " {$minutes} min" . ($minutes > 1 ? "s" : "") : "");
    }

    $table->addRow();
    $table->addCell(2000, $cellStyle)->addText($entry['schedule_date'] ?? 'N/A', $valueStyle, $textAlignCenter);
    $table->addCell(1500, $cellStyle)->addText($first_time_in, $valueStyle, $textAlignCenter);
    $table->addCell(1500, $cellStyle)->addText($last_time_out, $valueStyle, $textAlignCenter);
    $table->addCell(1500, $cellStyle)->addText($entry['remark_type'] ?? 'N/A', $valueStyle, $textAlignCenter);
    $table->addCell(1500, $cellStyle)->addText($total_hours_formatted, $valueStyle, $textAlignCenter);
    $table->addCell(1000, $cellStyle)->addText($entry['status'], $valueStyle, $textAlignCenter);
}

// Save the document and output it for download
$fileName = 'dtr_' . strtolower($student['student_lastname']) . '_' . $student_id . '.docx';
$tempFilePath = sys_get_temp_dir() . '/' . $fileName;

$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($tempFilePath);

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempFilePath));
readfile($tempFilePath);

unlink($tempFilePath);
exit;
?>