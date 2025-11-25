<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['sales_id'])) {
    echo json_encode(['success' => false, 'message' => 'No sales ID provided']);
    exit;
}

$salesId = $_GET['sales_id'];

$query = "SELECT s.*, sv.branch_id, sv.inclusions, sv.flower_design 
          FROM sales_tb s
          JOIN services_tb sv ON s.service_id = sv.service_id
          WHERE s.sales_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $salesId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $service = $result->fetch_assoc();
    echo json_encode(['success' => true, ...$service]);
} else {
    echo json_encode(['success' => false, 'message' => 'Service not found']);
}
?>