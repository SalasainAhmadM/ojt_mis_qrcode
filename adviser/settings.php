<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $adviser_id = $_POST['adviser_id'];
    $adviser_firstname = $_POST['adviser_firstname'];
    $adviser_middle = $_POST['adviser_middle'];
    $adviser_lastname = $_POST['adviser_lastname'];
    $adviser_email = $_POST['adviser_email'];
    $contact = $_POST['adviser_number'];
    $department = $_POST['department'];

    $hashed_password = null;
    if (!empty($_POST['adviser_password']) && $_POST['adviser_password'] === $_POST['adviser_cpassword']) {
        $hashed_password = password_hash($_POST['adviser_password'], PASSWORD_DEFAULT);
    }
    function generateFileName($adviser_lastname, $adviser_firstname, $adviser_middle)
    {
        date_default_timezone_set('Asia/Manila');
        $date_today = date('Ymd');
        $random_number = rand(1000, 9999);
        $middle_initial = $adviser_middle ? strtoupper(substr($adviser_middle, 0, 1)) : '';
        $file_extension = pathinfo($_FILES['adviser_image']['name'], PATHINFO_EXTENSION);

        return 'adviser_' . ucfirst($adviser_lastname) . ucfirst($adviser_firstname) . $middle_initial . '_' . $date_today . '_' . $random_number . '.' . $file_extension;
    }


    // Handle image upload if an image was submitted
    $adviser_image = null;
    if (!empty($_FILES['adviser_image']['name'])) {
        $new_file_name = generateFileName($adviser_lastname, $adviser_firstname, $adviser_middle); // Adjusted function
        $target_dir = "../uploads/adviser/";
        $target_file = $target_dir . basename($new_file_name);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_extensions)) {
            if (move_uploaded_file($_FILES['adviser_image']['tmp_name'], $target_file)) {
                // Update adviser_image with the new file name
                $adviser_image = $new_file_name;
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
        $query = "SELECT adviser_image FROM adviser WHERE adviser_id = ?";
        if ($stmt = $database->prepare($query)) {
            $stmt->bind_param("i", $adviser_id);
            $stmt->execute();
            $stmt->bind_result($adviser_image);
            $stmt->fetch();
            $stmt->close();
        }
    }

    $query = "UPDATE adviser SET 
              adviser_firstname = ?, 
              adviser_middle = ?, 
              adviser_lastname = ?, 
              adviser_email = ?, 
              adviser_number = ?,
              department = ?";

    $types = "ssssss";
    $params = [
        $adviser_firstname,
        $adviser_middle,
        $adviser_lastname,
        $adviser_email,
        $contact,
        $department
    ];

    if ($hashed_password) {
        $query .= ", adviser_password = ?";
        $types .= "s";
        $params[] = $hashed_password;
    }

    if ($adviser_image) {
        $query .= ", adviser_image = ?";
        $types .= "s";
        $params[] = $adviser_image;
    }

    $query .= " WHERE adviser_id = ?";
    $types .= "i";
    $params[] = $adviser_id;

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