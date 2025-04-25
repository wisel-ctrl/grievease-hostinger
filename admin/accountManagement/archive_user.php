<?php
// update_user_status.php
header('Content-Type: application/json');

// Include database connection
require_once '../../db_connect.php';

// Check if required parameters are provided
if (!isset($_POST['user_id']) || empty($_POST['user_id']) || 
    !isset($_POST['status']) || $_POST['status'] === '') {
    echo json_encode([
        'success' => false,
        'message' => 'User ID and status are required'
    ]);
    exit;
}

$userId = $_POST['user_id'];
$status = $_POST['status'];
$userType = isset($_POST['user_type']) ? $_POST['user_type'] : null;

// Prepare and execute the update query
try {
    // If user_type is provided, use it as an additional condition
    if ($userType !== null) {
        $stmt = $conn->prepare("UPDATE users SET is_verified = ? WHERE id = ? AND user_type = ?");
        $stmt->bind_param("iii", $status, $userId, $userType);
    } else {
        $stmt = $conn->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $userId);
    }
    
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update user status: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>