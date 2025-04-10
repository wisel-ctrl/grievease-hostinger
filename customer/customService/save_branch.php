<?php
session_start();
// Include database connection
require_once '../../db_connect.php';
// Log received data for debugging
$json_data = file_get_contents('php://input');
error_log("Received data: " . $json_data);
$data = json_decode($json_data, true);
// Get user ID from session
$userId = $_SESSION['user_id'];
error_log("Session user_id: " . $userId);
// Check if branch is provided
if (!isset($data['branch'])) {
    echo json_encode(['success' => false, 'message' => 'Missing branch information']);
    exit;
}
$branchId = $data['branch'];
error_log("Selected branch ID: " . $branchId);

// First verify the branch exists
$stmt = $conn->prepare("SELECT branch_id FROM branch_tb WHERE branch_id = ?");
$stmt->bind_param("i", $branchId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch selected']);
    exit;
}

// Update the user's branch location in the database
$stmt = $conn->prepare("UPDATE users SET branch_loc = ? WHERE id = ?");
if ($stmt === false) {
    error_log("SQL Prepare Error: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Error preparing statement', 'error' => $conn->error]);
    exit;
}
$stmt->bind_param("si", $branchId, $userId);
$result = $stmt->execute();
if ($result) {
    // Also update the session value if you need it elsewhere
    $_SESSION['branch_loc'] = $branchId;

    error_log("Branch updated successfully for user ID: " . $userId);
    echo json_encode([
        'success' => true, 
        'message' => 'Branch updated successfully',
        'userId' => $userId,
        'branch' => $branchId
    ]);
} else {
    error_log("Branch update failed: " . $conn->error);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update branch', 
        'error' => $conn->error
    ]);
}
$stmt->close();
$conn->close();
?>