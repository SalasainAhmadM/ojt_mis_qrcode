<?php
session_start();
require '../conn/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = $_POST['admin_id'];
    $admin_firstname = $_POST['admin_firstname'];
    $admin_middle = $_POST['admin_middle'];
    $admin_lastname = $_POST['admin_lastname'];
    $admin_email = $_POST['admin_email'];

    // Initialize password variable
    $hashed_password = null;

    // Check if passwords match before hashing
    if (!empty($_POST['admin_password']) && $_POST['admin_password'] === $_POST['admin_cpassword']) {
        $hashed_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
    } else if (!empty($_POST['admin_password'])) {
        // If passwords do not match, set a session variable to trigger the modal
        $_SESSION['password_error'] = true;
        header("Location: setting.php");
        exit();
    }

    // Function to generate the image file name based on user details
    function generateFileName($admin_lastname, $admin_firstname, $admin_middle)
    {
        date_default_timezone_set('Asia/Manila');
        $date_today = date('Ymd');
        $random_number = rand(1000, 9999);
        $middle_initial = $admin_middle ? strtoupper(substr($admin_middle, 0, 1)) : '';
        $file_extension = pathinfo($_FILES['admin_image']['name'], PATHINFO_EXTENSION);

        return 'admin_' . ucfirst($admin_lastname) . ucfirst($admin_firstname) . $middle_initial . '_' . $date_today . '_' . $random_number . '.' . $file_extension;
    }


    // Handle image upload if an image is submitted
    $admin_image = null;
    if (!empty($_FILES['admin_image']['name'])) {
        $new_file_name = generateFileName($admin_lastname, $admin_firstname, $admin_middle);
        $target_dir = "../uploads/admin/";
        $target_file = $target_dir . basename($new_file_name);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image file extension
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_extensions)) {
            if (move_uploaded_file($_FILES['admin_image']['tmp_name'], $target_file)) {
                $admin_image = $new_file_name;
            } else {
                echo "Sorry, there was an error uploading your file.";
                exit();
            }
        } else {
            echo "Invalid file format. Allowed types: jpg, jpeg, png, gif.";
            exit();
        }
    } else {
        // Retain old image if no new image is uploaded
        $query = "SELECT admin_image FROM admin WHERE admin_id = ?";
        if ($stmt = $database->prepare($query)) {
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $stmt->bind_result($admin_image);
            $stmt->fetch();
            $stmt->close();
        }
    }

    // Build the update query dynamically
    $query = "UPDATE admin SET 
              admin_firstname = ?, 
              admin_middle = ?, 
              admin_lastname = ?, 
              admin_email = ?";

    $types = "ssss";
    $params = [
        $admin_firstname,
        $admin_middle,
        $admin_lastname,
        $admin_email
    ];

    // Append the password if it was updated
    if ($hashed_password) {
        $query .= ", admin_password = ?";
        $types .= "s";
        $params[] = $hashed_password;
    }

    // Append the image file name if a new image was uploaded
    if ($admin_image) {
        $query .= ", admin_image = ?";
        $types .= "s";
        $params[] = $admin_image;
    }

    // Complete the query with the WHERE clause
    $query .= " WHERE admin_id = ?";
    $types .= "i";
    $params[] = $admin_id;

    // Prepare and execute the update query
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $_SESSION['settings_success'] = true; // Success message flag
            header("Location: setting.php"); // Redirect after success
            exit();
        } else {
            echo "Error executing the query: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing the query: " . $database->error;
    }
}
?>