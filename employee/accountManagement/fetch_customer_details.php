<?php
require_once('../../db_connect.php');

if (!isset($_GET['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'User ID is required']));
}

$userId = (int)$_GET['user_id'];

// Fetch user details
$userQuery = "SELECT * FROM users WHERE id = ? AND user_type = 3";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows === 0) {
    die(json_encode(['success' => false, 'message' => 'Customer not found']));
}

$user = $userResult->fetch_assoc();

// Fetch all branches for dropdown
$branchesQuery = "SELECT branch_id, branch_name FROM branch_tb";
$branchesResult = $conn->query($branchesQuery);
$branches = $branchesResult->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'user' => $user,
    'branches' => $branches
]);
?>