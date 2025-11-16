<?php
session_start();
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_profile_picture'])) {
    $admin_id = $_SESSION['user_id'];
    
    // Get current profile picture path
    $query = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    if (!empty($admin['profile_picture'])) {
        // Delete the physical file
        $file_path = "../" . $admin['profile_picture'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Update database to remove profile picture
        $update_query = "UPDATE users SET profile_picture = NULL, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $admin_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Profile picture removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating database: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No profile picture to remove']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>