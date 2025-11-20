<?php
require_once '../../db_connect.php';

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize inputs
    $token = $_POST['token'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    $confirm_password = $_POST['confirm_password'] ?? null;

    // Server-side validations
    if (!$token || !$new_password || !$confirm_password) {
        echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required'
        ]);
        exit;
    }

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Passwords do not match'
        ]);
        exit;
    }

    // Password strength validation
    if (strlen($new_password) < 8) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password must be at least 8 characters long'
        ]);
        exit;
    }

    // Validate token and check reset limit
    $stmt = $conn->prepare("SELECT id, email, last_password_reset FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired reset token'
        ]);
        exit;
    }

    $user = $result->fetch_assoc();

    // Check if user has reset password within the last month (PH time)
    if ($user['last_password_reset']) {
        $lastReset = new DateTime($user['last_password_reset'], new DateTimeZone('Asia/Manila'));
        $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $interval = $lastReset->diff($now);
        
        // Check if less than 30 days have passed
        if ($interval->days < 30) {
            $daysLeft = 30 - $interval->days;
            echo json_encode([
                'status' => 'error',
                'message' => "You can only reset your password once per month. Please try again in $daysLeft day(s)."
            ]);
            exit;
        }
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password, clear reset token, and set last reset timestamp
    $current_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE users SET 
        password = ?, 
        reset_token = NULL, 
        reset_token_expiry = NULL,
        last_password_reset = ?
        WHERE id = ?");
    $stmt->bind_param("ssi", $hashed_password, $current_time, $user['id']);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password reset successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to reset password'
        ]);
    }
}
?>