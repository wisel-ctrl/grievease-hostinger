<?php
// Include database connection
require_once '../../db_connect.php';

// Log request for debugging
error_log("get_branch_name.php called");

// Get branch ID from query parameter
$branchId = $_GET['branch_id'] ?? null;
error_log("Branch ID: " . $branchId);

if (!$branchId) {
    echo json_encode(['success' => false, 'message' => 'Branch ID not provided']);
    exit;
}

// Get the branch name from the database
$stmt = $conn->prepare("SELECT branch_name FROM branch_tb WHERE branch_id = ?");
if ($stmt === false) {
    error_log("SQL Prepare Error: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Error preparing statement', 'error' => $conn->error]);
    exit;
}

$stmt->bind_param("i", $branchId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $branchName = $row['branch_name'];
    error_log("Retrieved branch name: " . $branchName);

    echo json_encode(['success' => true, 'branch_name' => $branchName]);
} else {
    error_log("Error fetching branch name: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Failed to get branch name', 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>