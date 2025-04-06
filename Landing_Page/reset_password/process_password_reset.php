<?php
require_once '../../db_connect.php';

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

    // Validate token
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
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

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password and clear reset token
    $stmt = $conn->prepare("UPDATE users SET 
        password = ?, 
        reset_token = NULL, 
        reset_token_expiry = NULL 
        WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user['id']);

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