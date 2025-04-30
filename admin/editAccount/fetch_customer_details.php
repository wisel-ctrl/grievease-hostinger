<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$userId = $_GET['user_id'] ?? 0;

$query = "SELECT u.*, b.branch_name 
          FROM users u
          LEFT JOIN branch_tb b ON u.branch_loc = b.branch_id
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Fetch all branches for dropdown
    $branchesQuery = "SELECT branch_id, branch_name FROM branch_tb";
    $branchesResult = $conn->query($branchesQuery);
    $branches = [];
    while ($row = $branchesResult->fetch_assoc()) {
        $branches[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'branches' => $branches
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
}

$stmt->close();
$conn->close();
?>