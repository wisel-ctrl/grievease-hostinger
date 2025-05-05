<?php
require_once '../../db_connect.php';

// Check if user is logged in and has admin privileges
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

if ($_SESSION['user_type'] != 1) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

// Get employee ID from POST data
$employeeId = isset($_POST['employeeId']) ? intval($_POST['employeeId']) : 0;

if ($employeeId <= 0) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit();
}

// Update employee status to active
$sql = "UPDATE employee_tb SET status = 'active' WHERE EmployeeID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$success = $stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Employee restored successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to restore employee']);
}

$stmt->close();
$conn->close();
?>