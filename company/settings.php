<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_id = $_POST['company_id'];
    $company_name = $_POST['company_name'];
    $company_firstname = $_POST['company_rep_firstname'];
    $company_middle = $_POST['company_rep_middle'];
    $company_lastname = $_POST['company_rep_lastname'];
    $company_position = $_POST['company_rep_position'];
    $company_email = $_POST['company_email'];
    $contact = $_POST['company_number'];
    $address = $_POST['address'];

    $hashed_password = null;
    if (!empty($_POST['company_password']) && $_POST['company_password'] === $_POST['company_cpassword']) {
        $hashed_password = password_hash($_POST['company_password'], PASSWORD_DEFAULT);
    }

    function generateFileName($company_name)
    {
        date_default_timezone_set('Asia/Manila');
        $date_today = date('Ymd');
        $random_number = rand(1000, 9999);
        $file_extension = pathinfo($_FILES['company_image']['name'], PATHINFO_EXTENSION);
        return 'company_' . ucfirst($company_name) . '_' . $date_today . '_' . $random_number . '.' . $file_extension;
    }



    // Handle image upload if an image was submitted
    $company_image = null;
    if (!empty($_FILES['company_image']['name'])) {
        $new_file_name = generateFileName($company_name);
        $target_dir = "../uploads/company/";
        $target_file = $target_dir . basename($new_file_name);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_extensions)) {
            if (move_uploaded_file($_FILES['company_image']['tmp_name'], $target_file)) {
                // Update company_image with the new file name
                $company_image = $new_file_name;
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
        $query = "SELECT company_image FROM company WHERE company_id = ?";
        if ($stmt = $database->prepare($query)) {
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $stmt->bind_result($company_image);
            $stmt->fetch();
            $stmt->close();
        }
    }

    $query = "UPDATE company SET 
              company_name = ?,
              company_rep_firstname = ?, 
              company_rep_middle = ?, 
              company_rep_lastname = ?,
              company_rep_position = ?, 
              company_email = ?, 
              company_number = ?,
              company_address = ?";

    $types = "ssssssss";
    $params = [
        $company_name,
        $company_firstname,
        $company_middle,
        $company_lastname,
        $company_position,
        $company_email,
        $contact,
        $address
    ];

    if ($hashed_password) {
        $query .= ", company_password = ?";
        $types .= "s";
        $params[] = $hashed_password;
    }

    if ($company_image) {
        $query .= ", company_image = ?";
        $types .= "s";
        $params[] = $company_image;
    }

    $query .= " WHERE company_id = ?";
    $types .= "i";
    $params[] = $company_id;

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