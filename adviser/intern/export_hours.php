<?php
session_start();
require '../../conn/connection.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../index.php");
    exit();
}

// Fetch adviser details
$adviser_id = $_SESSION['user_id'];

// Fetch required OJT hours
$sqlRequiredHours = "SELECT required_hours FROM required_hours WHERE required_hours_id = 1";
$resultRequiredHours = $database->query($sqlRequiredHours);
$requiredHoursRow = $resultRequiredHours->fetch_assoc();
$requiredHours = $requiredHoursRow['required_hours'] ?? 0;

// Fetch students' data only under the logged-in adviser
$sqlStudents = "
    SELECT 
        s.student_firstname, s.student_middle, s.student_lastname, s.student_image, 
        SUM(a.ojt_hours) AS total_ojt_hours
    FROM student s
    LEFT JOIN attendance a ON s.student_id = a.student_id
    WHERE s.adviser = ?
    GROUP BY s.student_id
    ORDER BY s.student_lastname ASC";

$stmt = $database->prepare($sqlStudents);
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Initialize PHPWord
$phpWord = new PhpWord();
$phpWord->setDefaultFontSize(11);

// Define styles for header and text
$headerFontStyle = ['bold' => true, 'size' => 10];
$subHeaderFontStyle = ['bold' => true, 'size' => 9];
$headerParagraphStyle = ['alignment' => 'center'];
$labelStyle = ['bold' => true, 'size' => 11];
$valueStyle = ['size' => 11];
$textParagraphStyle = [
    'alignment' => 'both',
    'indentation' => ['firstLine' => 720] // Adds a first-line indent (720 twips = 0.5 inch)
];

$logoLeftPath = '../../img/wmsu.png';
$logoRightPath = '../../img/ccs.png';

// Add section
$section = $phpWord->addSection();

// Header Section
$header = $section->addHeader();
$headerTable = $header->addTable();
$headerTable->addRow();
$headerTable->addCell(2500)->addImage($logoLeftPath, ['width' => 50, 'height' => 50]);
$headerCell = $headerTable->addCell(5000);
$headerCell->addText("Western Mindanao State University", $headerFontStyle, $headerParagraphStyle);
$headerCell->addText("College of Computing Studies", $headerFontStyle, $headerParagraphStyle);
$headerCell->addText("DEPARTMENT OF INFORMATION TECHNOLOGY", $subHeaderFontStyle, $headerParagraphStyle);
$headerCell->addText("Zamboanga City", $headerFontStyle, $headerParagraphStyle);
$headerTable->addCell(2500)->addImage($logoRightPath, ['width' => 50, 'height' => 50, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);

// Add a horizontal line
$lineTable = $section->addTable();
$lineTable->addRow();
$lineTable->addCell(9000, ['borderBottomSize' => 18, 'borderBottomColor' => '095d40']);
$section->addTextBreak();

// Add title
$section->addText("Student Hours Report", $headerFontStyle, ['alignment' => 'center']);
$section->addText("Required OJT Hours: " . $requiredHours . " hours", $valueStyle, ['alignment' => 'center']);
$section->addTextBreak();

// Define table styles
$tableStyle = ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 50];
$cellStyle = ['valign' => 'center'];
$phpWord->addTableStyle('HoursTable', $tableStyle);

// Add table
$table = $section->addTable('HoursTable');

// Add table headers
$table->addRow();
$table->addCell(1000, $cellStyle)->addText("Profile", $labelStyle);
$table->addCell(4000, $cellStyle)->addText("Intern Name", $labelStyle);
$table->addCell(3000, $cellStyle)->addText("Current Hours", $labelStyle);

// Function to format hours as "X hours Y mins"
function formatHoursMinutes($totalHours)
{
    $hours = floor($totalHours);
    $minutes = round(($totalHours - $hours) * 60);
    return "{$hours} hour" . ($hours !== 1 ? "s" : "") . " {$minutes} min" . ($minutes !== 1 ? "s" : "");
}

// Populate table rows
foreach ($students as $student) {
    $profileImage = !empty($student['student_image'])
        ? "../../uploads/student/" . htmlspecialchars($student['student_image'])
        : "../../uploads/student/user.png";

    $studentName = htmlspecialchars(
        $student['student_firstname'] . ' ' . $student['student_middle'] . ' ' . $student['student_lastname']
    );

    $currentHours = formatHoursMinutes($student['total_ojt_hours']); // Format hours

    $table->addRow();

    // Profile image
    $cell = $table->addCell(1000, $cellStyle);
    if (file_exists($profileImage) && @getimagesize($profileImage)) {
        $cell->addImage($profileImage, ['width' => 40, 'height' => 40]);
    } else {
        $cell->addText("No Image", $valueStyle);
    }

    // Student name
    $table->addCell(4000, $cellStyle)->addText($studentName, $valueStyle);

    // Current hours
    $table->addCell(3000, $cellStyle)->addText($currentHours, $valueStyle);
}

// Save Word document
$fileName = 'student_hours_report.docx';
$tempFilePath = sys_get_temp_dir() . '/' . $fileName;

$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($tempFilePath);

// Output file for download
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempFilePath));
readfile($tempFilePath);

// Clean up
unlink($tempFilePath);
exit;
?>