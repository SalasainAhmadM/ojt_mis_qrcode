<?php
include '../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token'])) {
        echo "<script>alert('Invalid or expired token.'); window.location.href = '../endpoint/forgotpassword.php';</script>";
        exit;
    }

    date_default_timezone_set('Asia/Manila'); // Set to Philippine timezone

    $token = $_POST['token']; // Capture the token from the hidden input field
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('Please fill in all fields.'); window.location.href = '../endpoint/forgotpassword.php';</script>";
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.location.href = '../endpoint/forgotpassword.php';</script>";
        exit;
    }

    // Check if the token exists and is still valid
    $stmt = $database->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()");
    if (!$stmt) {
        echo "<script>alert('Error preparing statement: " . $database->error . "'); window.location.href = '../endpoint/forgotpassword.php';</script>";
        exit;
    }

    $stmt->bind_param("s", $token);
    if (!$stmt->execute()) {
        echo "<script>alert('Error executing statement: " . $stmt->error . "'); window.location.href = '../endpoint/forgotpassword.php';</script>";
        exit;
    }

    $result = $stmt->get_result();
    if (!$result) {
        echo "<script>alert('Error getting result: " . $stmt->error . "'); window.location.href = '../endpoint/forgotpassword.php';</script>";
        exit;
    }

    $token_data = $result->fetch_assoc();
    if (!$token_data) {
        echo "<script>alert('Invalid or expired token.'); window.location.href = '../endpoint/forgotpassword.php';</script>";
        exit;
    }

    $email = $token_data['email'];
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

    $password_updated = false;
    $roles = ['admin', 'student', 'adviser', 'company'];

    foreach ($roles as $role) {
        $stmt = $database->prepare("UPDATE $role SET {$role}_password = ? WHERE {$role}_email = ?");
        if (!$stmt) {
            echo "<script>alert('Error preparing update statement for role $role: " . $database->error . "'); window.location.href = '../endpoint/forgotpassword.php';</script>";
            exit;
        }

        $stmt->bind_param("ss", $password_hash, $email);
        if (!$stmt->execute()) {
            echo "<script>alert('Error executing update statement for role $role: " . $stmt->error . "'); window.location.href = '../endpoint/forgotpassword.php';</script>";
            exit;
        }

        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            $password_updated = true;
            // Password successfully updated, delete the token
            $stmt = $database->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
            if (!$stmt) {
                echo "<script>alert('Error preparing delete statement: " . $database->error . "'); window.location.href = '../endpoint/forgotpassword.php';</script>";
                exit;
            }

            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                echo "<script>alert('Error executing delete statement: " . $stmt->error . "'); window.location.href = '../endpoint/forgotpassword.php';</script>";
                exit;
            }

            echo "<script>alert('Password successfully updated.'); window.location.href = '../index.php';</script>";
            exit;
        }
    }

    if (!$password_updated) {
        echo "<script>alert('Password reset failed. No rows updated. Please try again.'); window.location.href = '../endpoint/forgotpassword.php';</script>";
    }
}
?>