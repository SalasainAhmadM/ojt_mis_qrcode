<?php
include '../conn/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the token is present
    if (!isset($_POST['token']) || empty($_POST['token'])) {
        header("Location: ../endpoint/reset-password.php?reset=invalid_token");
        exit;
    }

    date_default_timezone_set('Asia/Manila'); 

    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate that both password fields are filled
    if (empty($new_password) || empty($confirm_password)) {
        header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
        exit;
    }

    // Ensure passwords match
    if ($new_password !== $confirm_password) {
        header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
        exit;
    }

    // Fetch the reset token data and ensure it has not expired
    $stmt = $database->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()");
    if (!$stmt) {
        header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
        exit;
    }

    $stmt->bind_param("s", $token);
    if (!$stmt->execute()) {
        header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
        exit;
    }

    $result = $stmt->get_result();
    if (!$result) {
        header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
        exit;
    }

    $token_data = $result->fetch_assoc();
    if (!$token_data) {
        header("Location: ../endpoint/reset-password.php?reset=invalid_token");
        exit;
    }

    // Fetch email from the token data
    $email = $token_data['email'];
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

    $password_updated = false;
    $roles = ['admin', 'student', 'adviser', 'company'];

    // Try to update the password for each role
    foreach ($roles as $role) {
        $stmt = $database->prepare("UPDATE $role SET {$role}_password = ? WHERE {$role}_email = ?");
        if (!$stmt) {
            header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
            exit;
        }

        $stmt->bind_param("ss", $password_hash, $email);
        if (!$stmt->execute()) {
            header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
            exit;
        }

        if ($stmt->affected_rows > 0) {
            $password_updated = true;

            // Delete the token after a successful password reset
            $stmt = $database->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
            if (!$stmt) {
                header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
                exit;
            }

            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
                exit;
            }

            // Redirect to success page after successful password reset
            header("Location: ../endpoint/reset-password.php?reset=success&token=". urlencode($token));
            exit;
        }
    }

    // If no password update occurred, handle failure
    if (!$password_updated) {
        header("Location: ../endpoint/reset-password.php?reset=failure&token=" . urlencode($token));
        exit;
    }
}
?>
