<?php
require_once '../../db_connect.php';

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

// Get the lifeplan_id from the request
if (!isset($_GET['lifeplan_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'LifePlan ID not provided']);
    exit;
}

$lifeplan_id = $_GET['lifeplan_id'];

// First, get the branch_id from lifeplan_tb
$branch_query = "SELECT branch_id FROM lifeplan_tb WHERE lifeplan_id = ?";
$branch_stmt = $conn->prepare($branch_query);

if (!$branch_stmt) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database query preparation failed']);
    exit;
}

$branch_stmt->bind_param("i", $lifeplan_id);
$branch_stmt->execute();
$branch_result = $branch_stmt->get_result();

if ($branch_result->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'LifePlan not found']);
    exit;
}

$branch_data = $branch_result->fetch_assoc();
$branch_id = $branch_data['branch_id'];
$branch_stmt->close();

// Now get services for this branch
$services_query = "SELECT service_id, service_name FROM services_tb WHERE branch_id = ?";
$services_stmt = $conn->prepare($services_query);

if (!$services_stmt) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database query preparation failed']);
    exit;
}

$services_stmt->bind_param("i", $branch_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();

$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}

echo json_encode($services);

// Close statement and connection
$services_stmt->close();
$conn->close();
?>