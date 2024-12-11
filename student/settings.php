<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $student_firstname = $_POST['student_firstname'];
    $student_middle = $_POST['student_middle'];
    $student_lastname = $_POST['student_lastname'];
    $student_email = $_POST['student_email'];
    $wmsu_id = $_POST['wmsu_id'];
    $contact = $_POST['contact_number'];
    $course_section = $_POST['course_section'];
    $batch_year = $_POST['batch_year'];
    $department = $_POST['department'];
    $company = $_POST['company'];
    $adviser = $_POST['adviser_id'];
    $address = $_POST['address'];
    $street = $_POST['street'];

    $hashed_password = null;
    if (!empty($_POST['student_password']) && $_POST['student_password'] === $_POST['student_cpassword']) {
        $hashed_password = password_hash($_POST['student_password'], PASSWORD_DEFAULT);
    }

    // Function to generate the file name based on last name and WMSU ID
    function generateFileName($student_lastname, $wmsu_id)
    {
        date_default_timezone_set('Asia/Manila');
        $date_today = date('Ymd');
        $random_number = rand(1000, 9999);
        $file_extension = pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION);
        return strtolower($student_lastname) . '_' . $wmsu_id . '_' . $date_today . '_' . $random_number . '.' . $file_extension;
    }



    // Handle image upload if an image was submitted
    $student_image = null;
    if (!empty($_FILES['student_image']['name'])) {
        $new_file_name = generateFileName($student_lastname, $wmsu_id);
        $target_dir = "../uploads/student/";
        $target_file = $target_dir . basename($new_file_name);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_extensions)) {
            if (move_uploaded_file($_FILES['student_image']['tmp_name'], $target_file)) {
                // Update student_image with the new file name
                $student_image = $new_file_name;
            } else {
                echo "Sorry, there was an error uploading your file.";
                exit();
            }
        } else {
            echo "Invalid file format. Allowed types: jpg, jpeg, png, gif.";
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

    $query = "UPDATE student SET 
              student_firstname = ?, 
              student_middle = ?, 
              student_lastname = ?, 
              student_email = ?, 
              wmsu_id = ?, 
              contact_number = ?,
              course_section = ?, 
              batch_year = ?, 
              department = ?, 
              company = ?, 
              adviser = ?, 
              student_address = ?,
              street = ?";

    $types = "sssssssssssss";
    $params = [
        $student_firstname,
        $student_middle,
        $student_lastname,
        $student_email,
        $wmsu_id,
        $contact,
        $course_section,
        $batch_year,
        $department,
        $company,
        $adviser,
        $address,
        $street
    ];

    if ($hashed_password) {
        $query .= ", student_password = ?";
        $types .= "s";
        $params[] = $hashed_password;
    }

    if ($student_image) {
        $query .= ", student_image = ?";
        $types .= "s";
        $params[] = $student_image;
    }

    $query .= " WHERE student_id = ?";
    $types .= "i";
    $params[] = $student_id;

    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $_SESSION['settings_success'] = true;
            header("Location: setting.php");
            exit();
        }

        $stmt->close();
    } else {
        echo "Error in preparing statement: " . $database->error;
    }
}
?>