<?php
session_start();
require '../conn/connection.php'; // Include the connection file

// Ensure that the student is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Get student ID from session
$student_id = $_SESSION['user_id'];

// Retrieve form data
$wmsu_id = $_POST['wmsu_id'];
$student_firstname = $_POST['student_firstname'];
$student_middle = $_POST['student_middle'];
$student_lastname = $_POST['student_lastname'];
$contact = $_POST['contact_number'];
$section = $_POST['course_section'];
$batch_year = $_POST['batch_year'];
$department = $_POST['department'];
$ojt_type = $_POST['ojt_type'];
$company = $_POST['company'];
$adviser = $_POST['adviser_id'];
$address = $_POST['address'];
$street = $_POST['street'];
$student_image = $_FILES['student_image']['name'];

// Function to generate the file name based on last name and WMSU ID
function generateFileName($student_lastname, $wmsu_id)
{
    // Return the generated file name
    return strtolower($student_lastname) . '_' . $wmsu_id . '.' . pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION);
}

// Handle image upload if an image was submitted
if ($student_image) {
    $new_file_name = generateFileName($student_lastname, $wmsu_id);
    $target_dir = "../uploads/student/";
    $target_file = $target_dir . basename($new_file_name);

    // Upload the file
    if (move_uploaded_file($_FILES['student_image']['tmp_name'], $target_file)) {
        // Update student_image with the new file name
        $student_image = $new_file_name;
    } else {
        echo "Sorry, there was an error uploading your file.";
        exit();
    }
} else {
    // If no new image is uploaded, retain the old one
    $query = "SELECT student_image FROM student WHERE student_id = ?";
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->bind_result($student_image);
        $stmt->fetch();
        $stmt->close();
    }
}

// Prepare the update query
$query = "UPDATE student SET wmsu_id = ?, contact_number = ?, course_section = ?, batch_year = ?, department = ?, ojt_type = ?,  company = ?, adviser = ?, student_address = ?, street = ?, student_image = ? WHERE student_id = ?";

// Prepare and execute the statement
if ($stmt = $database->prepare($query)) {
    $stmt->bind_param("sssssssssssi", $wmsu_id, $contact, $section, $batch_year, $department, $ojt_type, $company, $adviser, $address, $street, $student_image, $student_id);

    // Execute the query
    if ($stmt->execute()) {
        $_SESSION['profile_update_success'] = true;
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    $stmt->close(); // Close the statement
} else {
    echo "Error preparing statement: " . $database->error;
}

$database->close(); // Close the database connection

// Redirect back to the student profile page or wherever needed
header("Location: ./index.php");
exit();
?>