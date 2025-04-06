<?php
require_once '../../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

// Update sales_tb balance
$query = "UPDATE sales_tb SET balance = ? WHERE sales_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("di", $data['new_balance'], $data['sales_id']);

if ($stmt->execute()) {
    // Update payment_status if balance is 0
    if ($data['new_balance'] == 0) {
        $updateStatus = "UPDATE sales_tb SET payment_status = 'Fully Paid' WHERE sales_id = ?";
        $stmt2 = $conn->prepare($updateStatus);
        $stmt2->bind_param("i", $data['sales_id']);
        $stmt2->execute();
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update balance: ' . $conn->error
    ]);
}
?>