<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (!isset($data['sales_id']) || !isset($data['customerID']) || !isset($data['branch_id']) || 
        !isset($data['payment_amount']) || !isset($data['method_of_payment']) || !isset($data['payment_date'])) {
        throw new Exception('Missing required fields');
    }

    // First, get current amount_paid
    $getAmountQuery = "SELECT amount_paid FROM customsales_tb WHERE customsales_id = ?";
    $getAmountStmt = $conn->prepare($getAmountQuery);
    $getAmountStmt->bind_param("i", $data['sales_id']);
    $getAmountStmt->execute();
    $result = $getAmountStmt->get_result();
    $currentAmountPaid = $result->fetch_assoc()['amount_paid'];
    $newAmountPaid = $currentAmountPaid + $data['payment_amount'];

    // Insert into custom_installment_tb
    $query = "INSERT INTO custom_installment_tb (
        customsales_id, 
        customerID, 
        client_name,
        branch_id, 
        before_balance,
        after_payment_balance,
        payment_amount, 
        method_of_payment,  
        notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "iiidsss", 
        $data['sales_id'],
        $data['customerID'],
        $data['client_name'],
        $data['branch_id'],
        $data['before_balance'],
        $data['after_payment_balance'],
        $data['payment_amount'],
        $data['method_of_payment'],
        $data['notes']
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to record payment: ' . $conn->error);
    }

    // Update custom_sales balance and amount_paid
    $updateQuery = "UPDATE customsales_tb SET 
                    balance = ?, 
                    amount_paid = ? 
                    WHERE customsales_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ddi", 
        $data['after_payment_balance'],
        $newAmountPaid,
        $data['sales_id']
    );

    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update balance: ' . $conn->error);
    }

    // Update payment_status if balance is 0
    if ($data['after_payment_balance'] == 0) {
        $updateStatus = "UPDATE customsales_tb SET payment_status = 'Fully Paid' WHERE customsales_id = ?";
        $stmt2 = $conn->prepare($updateStatus);
        $stmt2->bind_param("i", $data['sales_id']);
        
        if (!$stmt2->execute()) {
            throw new Exception('Failed to update payment status: ' . $conn->error);
        }
    }

    // Commit the transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment recorded successfully',
        'new_amount_paid' => $newAmountPaid
    ]);
    
} catch (Exception $e) {
    // Roll back the transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>