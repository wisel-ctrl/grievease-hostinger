<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['branch_id'])) {
    echo json_encode(['success' => false, 'message' => 'No branch ID provided']);
    exit;
}

$branchId = $_GET['branch_id'];

$query = "SELECT service_id, service_name FROM services_tb WHERE branch_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $branchId);
$stmt->execute();
$result = $stmt->get_result();

$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

echo json_encode(['success' => true, 'services' => $services]);
?>