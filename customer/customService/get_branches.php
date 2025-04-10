<?php
// Include database connection
require_once '../../db_connect.php';

// Log request for debugging
error_log("get_branches.php called");

// Query to get branches from branch_tb table
$stmt = $conn->prepare("SELECT branch_id, branch_name FROM branch_tb");
if ($stmt === false) {
    error_log("SQL Prepare Error: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Error preparing statement', 'error' => $conn->error]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$branches = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $branches[] = [
            'id' => $row['branch_id'],
            'name' => $row['branch_name']
        ];
    }
    echo json_encode(['success' => true, 'branches' => $branches]);
} else {
    error_log("Error fetching branches: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Failed to get branches', 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>