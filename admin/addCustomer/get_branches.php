<?php
// Include database connection
require_once '../../db_connect.php';

// SQL query to fetch branch locations
$sql = "SELECT branch_id, branch_name FROM branch_tb";
$result = $conn->query($sql);

// Check if query was successful
if ($result) {
    $branches = [];
    
    // Fetch all branches
    while ($row = $result->fetch_assoc()) {
        $branches[] = [
            'branch_id' => $row['branch_id'],
            'branch_name' => ucwords(strtolower($row['branch_name']))
        ];
    }
    
    // Return branch data as JSON
    header('Content-Type: application/json');
    echo json_encode($branches);
} else {
    // If query fails
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Could not fetch branch locations']);
}

// Close connection
$conn->close();
?>