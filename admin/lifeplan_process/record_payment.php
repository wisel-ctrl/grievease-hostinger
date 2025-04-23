<?php
header('Content-Type: application/json');
require_once '../../db_connect.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['lifeplan_id'], $data['customer_id'], $data['installment_amount'], 
    $data['current_balance'], $data['new_balance'], $data['amount_paid'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Insert into lifeplan_logs_tb
    $stmt = $conn->prepare("INSERT INTO lifeplan_logs_tb (
        lifeplan_id, 
        customer_id, 
        installment_amount, 
        current_balance, 
        new_balance, 
        log_date,
        notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $logDate = isset($data['payment_date']) ? $data['payment_date'] : date('Y-m-d H:i:s');
    $notes = isset($data['notes']) ? $data['notes'] : null;
    
    $stmt->bind_param(
        "iidddss", 
        $data['lifeplan_id'],
        $data['customer_id'],
        $data['installment_amount'],
        $data['current_balance'],
        $data['new_balance'],
        $logDate,
        $notes
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert payment log: " . $stmt->error);
    }
    
    // Update amount_paid in lifeplan_tb
    $updateStmt = $conn->prepare("UPDATE lifeplan_tb SET 
        amount_paid = ?,
        balance = ?
        WHERE lifeplan_id = ?");
    
    $updateStmt->bind_param(
        "ddi", 
        $data['amount_paid'],
        $data['new_balance'],
        $data['lifeplan_id']
    );
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update lifeplan: " . $updateStmt->error);
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>