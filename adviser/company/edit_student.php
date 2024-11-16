<?php
session_start();
require '../../conn/connection.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $student_firstname = $_POST['student_firstname'];
    $student_middle = $_POST['student_middle'];
    $student_lastname = $_POST['student_lastname'];
    $student_email = $_POST['student_email'];
    $student_number = $_POST['contact_number'];
    $department = $_POST['student_department'];
    $student_course_section = $_POST['course_section_id'];
    $student_department = $_POST['department'];
    $student_batch_year = $_POST['batch_year'];
    $student_address = $_POST['student_address'];

    $company_id = $_POST['company_id'];
    function generateFileName($student_lastname, $student_id)
    {
        return strtolower($student_lastname) . '_' . $student_id . '.' . pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION);
    }

    // Fetch the current image from the database if no new image is uploaded
    if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] == 0) {
        $uploadDir = '../../uploads/student/';
        $student_image = generateFileName($student_lastname, $student_id);
        $uploadFile = $uploadDir . $student_image;

        if (move_uploaded_file($_FILES['student_image']['tmp_name'], $uploadFile)) {
            // Image uploaded successfully
        } else {
            die('Failed to upload image.');
        }
    } else {
        $sql = "SELECT student_image FROM student WHERE student_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param('s', $student_id);
        $stmt->execute();
        $stmt->bind_result($student_image);
        $stmt->fetch();
        $stmt->close();
    }

    // Update the student's details
    $sql = "UPDATE student 
        SET student_firstname = ?, student_middle = ?, student_lastname = ?, student_image = ?, 
            student_email = ?, contact_number = ?, course_section = ?, department = ?, batch_year = ?, 
            department = ?, student_address = ? 
        WHERE student_id = ?";

    $stmt = $database->prepare($sql);
    $stmt->bind_param(
        'sssssssssssi',
        $student_firstname,
        $student_middle,
        $student_lastname,
        $student_image,
        $student_email,
        $student_number,
        $student_course_section,
        $student_department,
        $student_batch_year,
        $department,
        $student_address,
        $student_id
    );

    if ($stmt->execute()) {
        $_SESSION['edit_student_success'] = "Student information updated successfully.";
        header("Location: ./company-intern.php?company_id=$company_id");
        exit;
    } else {
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();

}
?>