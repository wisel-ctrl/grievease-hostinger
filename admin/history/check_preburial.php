<?php
header('Content-Type: application/json');
require_once '../../db_connect.php'; // adjust path as needed

// Get the sales_id
$sales_id = $_POST['sales_id'] ?? null;

if (!$sales_id) {
    echo json_encode(['success' => false, 'message' => 'Sales ID is required']);
    exit;
}

// Query to count the service_stage = 'initial'
$query = "
    SELECT COUNT(*) AS cnt
    FROM employee_service_payments esp
    WHERE esp.sales_id = ?
      AND esp.sales_type = 'service'
      AND esp.service_stage = 'initial'
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $sales_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$count = (int)$result['cnt'];

// Return response based on count
if ($count >= 1) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'You need to assign staff for pre-burial first.'
    ]);
}
