<?php
session_start();
require '../conn/connection.php';

// Get email and password from POST request
$email = $_POST['email'];
$password = $_POST['password'];

// Prepare SQL queries for different user types
$queries = [
    "student" => "SELECT * FROM student WHERE student_email = ?",
    "adviser" => "SELECT * FROM adviser WHERE adviser_email = ?",
    "company" => "SELECT * FROM company WHERE company_email = ?",
    "admin" => "SELECT * FROM admin WHERE admin_email = ?",
];

// Iterate over each user type and check credentials
foreach ($queries as $role => $query) {
    if ($stmt = $database->prepare($query)) {
        $stmt->bind_param("s", $email); // Bind email parameter
        $stmt->execute(); // Execute the query
        $result = $stmt->get_result(); // Get the result

        if ($result->num_rows > 0) {
            // If user is found, fetch data
            $user = $result->fetch_assoc();

            // Check the hashed password
            $password_field = $role . '_password';
            if (password_verify($password, $user[$password_field])) {
                // Check if the user is a student and if they are verified
                if ($role === 'student' && !empty($user['verification_code'])) {
                    $_SESSION['login_error'] = "Your account has not been verified yet.";
                    header("Location: ../index.php?login=not_verified");
                    exit();
                }

                // If password is correct and student is verified (or not a student), set session variables
                $_SESSION['role'] = $role;
                $_SESSION['user_id'] = $user[$role . '_id'];

                // Set the full name or company name in the session
                if ($role === 'company') {
                    $_SESSION['full_name'] = $user['company_name'];
                } else {
                    $_SESSION['full_name'] = $user[$role . '_firstname'] . ' ' . $user[$role . '_middle'] . '.' . ' ' . $user[$role . '_lastname'];
                }

                $_SESSION['login_success'] = true; // Set login success session variable

                // Check for student role and redirect based on wmsu_id
                if ($role === 'student') {
                    if (empty($user['wmsu_id'])) {
                        header("Location: ../student/home.php?login=success");
                    } else {
                        header("Location: ../student/index.php?login=success");
                    }
                } elseif ($role === 'adviser') {
                    header("Location: ../adviser/index.php?login=success");
                } elseif ($role === 'company') {
                    header("Location: ../company/index.php?login=success");
                } elseif ($role === 'admin') {
                    header("Location: ../admin/index.php?login=success");
                }
                exit();
            }
        }
        $stmt->close(); // Close the statement
    }
}

// If no match found, set an error session variable and redirect to login page with error
$_SESSION['login_error'] = "Invalid email or password!";
header("Location: ../index.php?login=error");
exit();
?>