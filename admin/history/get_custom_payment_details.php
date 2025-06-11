<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['sales_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sales ID is required'
    ]);
    exit;
}

$sales_id = $_GET['sales_id'];

try {
    $stmt = $conn->prepare("SELECT customer_id, branch_id, amount_paid FROM customsales_tb WHERE customsales_id = ?");
    $stmt->bind_param("s", $sales_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'customerID' => $row['customer_id'],
            'branch_id' => $row['branch_id'],
            'amount_paid' => $row['amount_paid']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No record found'
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 