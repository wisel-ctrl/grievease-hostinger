<?php
// update_password.php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Get and sanitize form inputs
    $current_password = trim($_POST['current-password']);
    $new_password = trim($_POST['new-password']);
    $confirm_password = trim($_POST['confirm-password']);
    
    // Basic validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    // Check if new password and confirm password match
    if ($new_password !== $confirm_password) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        exit();
    }
    
    // Password strength validation
    if (strlen($new_password) < 8 ||
        !preg_match('/[A-Z]/', $new_password) ||
        !preg_match('/[a-z]/', $new_password) ||
        !preg_match('/[0-9]/', $new_password) ||
        !preg_match('/[^A-Za-z0-9]/', $new_password)) {
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Password does not meet security requirements']);
        exit();
    }
    
    try {
        // Get the current password from database to verify
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('User not found');
        }
        
        $user = $result->fetch_assoc();
        $stored_password = $user['password'];
        
        // Verify the current password
        if (!password_verify($current_password, $stored_password)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit();
        }
        
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update the password in the database
        $update_query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        $update_stmt->execute();
        
        if ($update_stmt->affected_rows > 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            exit();
        } else {
            throw new Exception('No changes were made');
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating password: ' . $e->getMessage()]);
        exit();
    }
    
} else {
    // Return error for non-POST requests
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}
?>