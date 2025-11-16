<?php
session_start();
require_once '../../db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get current profile picture
    $query = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && !empty($user['profile_picture'])) {
        $profile_picture_path = '../profile_picture/' . $user['profile_picture'];
        
        // Delete the file if it exists
        if (file_exists($profile_picture_path)) {
            unlink($profile_picture_path);
        }
        
        // Update database to remove profile picture
        $update_query = "UPDATE users SET profile_picture = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $user_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Profile picture removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No profile picture to remove']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>