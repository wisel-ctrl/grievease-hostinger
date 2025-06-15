<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

// Get branch_id from query parameter
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;

if (!$branch_id) {
    echo json_encode([]);
    exit;
}

// Query to fetch archived services for the specified branch
$sql = "SELECT service_id, service_name 
        FROM services_tb 
        WHERE status = 'Inactive' AND branch_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = [
        'service_id' => $row['service_id'],
        'service_name' => $row['service_name']
    ];
}

echo json_encode($services);

$stmt->close();
$conn->close();
?>