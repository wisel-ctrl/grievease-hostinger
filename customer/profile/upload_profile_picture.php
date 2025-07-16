<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];
$target_dir = "../../profile_picture/";
$max_file_size = 5 * 1024 * 1024; // 5MB

if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
}

if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
    $file = $_FILES['profile_picture'];
    $file_name = basename($file['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png'];
    
    // Validate file type
    if (!in_array($file_ext, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG and PNG are allowed.']);
        exit;
    }
    
    // Validate file size
    if ($file['size'] > $max_file_size) {
        echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
        exit;
    }
    
    // Generate unique filename
    $new_file_name = $user_id . '_' . time() . '.' . $file_ext;
    $target_file = $target_dir . $new_file_name;
    
    // Delete existing profile picture if it exists
    $query = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['profile_picture'] && file_exists($target_dir . $row['profile_picture'])) {
        unlink($target_dir . $row['profile_picture']);
    }
    
    // Upload new file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Update database
        $query = "UPDATE users SET profile_picture = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $new_file_name, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Profile picture uploaded successfully']);
        } else {
            unlink($target_file); // Remove uploaded file if database update fails
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}

$conn->close();
?>