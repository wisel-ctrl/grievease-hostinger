<?php
session_start();
// Include database connection
require_once '../../db_connect.php';

// Log request for debugging
error_log("get_user_branch.php called");

// Get user ID from session or query parameter
$userId = $_SESSION['user_id'] ?? $_GET['user_id'] ?? null;
error_log("User ID: " . $userId);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID not provided']);
    exit;
}

// Get the user's branch from the database
$stmt = $conn->prepare("SELECT branch_loc FROM users WHERE id = ?");
if ($stmt === false) {
    error_log("SQL Prepare Error: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Error preparing statement', 'error' => $conn->error]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $branch = $row['branch_loc'];
    error_log("Retrieved branch: " . $branch);
    
    // If branch is null or empty, return 'unknown'
    if ($branch === null || $branch === '') {
        $branch = 'unknown';
    }
    
    echo json_encode(['success' => true, 'branch' => $branch]);
} else {
    error_log("Error fetching branch: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Failed to get user branch', 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>