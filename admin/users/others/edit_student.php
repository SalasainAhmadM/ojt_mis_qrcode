<?php
session_start();
require '../../../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $wmsu_id = $_POST['wmsu_id'];
    $ojt_type = $_POST['ojt_type'];
    $student_firstname = $_POST['student_firstname'];
    $student_middle = $_POST['student_middle'];
    $student_lastname = $_POST['student_lastname'];
    $student_email = $_POST['student_email'];
    $student_number = $_POST['contact_number'];
    $department = $_POST['student_department'];
    $student_course_section = $_POST['course_section_id'];
    $student_company = $_POST['company'];
    $student_batch_year = $_POST['batch_year'];
    $student_address = $_POST['student_address'];
    $student_street = $_POST['student_street'];

    function generateFileName($student_lastname, $student_id)
    {
        return strtolower($student_lastname) . '_' . $student_id . '.' . pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION);
    }

    if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] == 0) {
        $uploadDir = '../../../uploads/student/';
        $student_image = generateFileName($student_lastname, $student_id);
        $uploadFile = $uploadDir . $student_image;

        if (move_uploaded_file($_FILES['student_image']['tmp_name'], $uploadFile)) {

        } else {
            die('Failed to upload image.');
        }
    } else {
        // Fetch the current student image from the database if no new image is uploaded
        $sql = "SELECT student_image FROM student WHERE student_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param('s', $student_id);
        $stmt->execute();
        $stmt->bind_result($student_image);
        $stmt->fetch();
        $stmt->close();
    }

    // Fetch the current adviser from the database
    $sql = "SELECT adviser FROM student WHERE student_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $stmt->bind_result($current_adviser_id);
    $stmt->fetch();
    $stmt->close();

    // Check if adviser needs to be updated
    $student_adviser = !empty($_POST['adviser_id']) && $_POST['adviser_id'] != $current_adviser_id
        ? $_POST['adviser_id']
        : $current_adviser_id;

    // Update the student's details, including the image (if changed)
    $sql = "UPDATE student 
            SET wmsu_id = ?, ojt_type = ?, student_firstname = ?, student_middle = ?, student_lastname = ?, student_image = ?, student_email = ?, 
                contact_number = ?, course_section = ?, company = ?, batch_year = ?, department = ?, adviser = ?, 
                student_address = ? , street = ? 
            WHERE student_id = ?";

    $stmt = $database->prepare($sql);
    $stmt->bind_param(
        'sssssssssssssssi',
        $wmsu_id,
        $ojt_type,
        $student_firstname,
        $student_middle,
        $student_lastname,
        $student_image,
        $student_email,
        $student_number,
        $student_course_section,
        $student_company,
        $student_batch_year,
        $department,
        $student_adviser,
        $student_address,
        $student_street,
        $student_id
    );

    if ($stmt->execute()) {
        $_SESSION['edit_student_success'] = "Student information updated successfully.";
        header('Location: ../student.php');
        exit;
    } else {
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
}
?>