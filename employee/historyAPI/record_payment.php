<?php
require_once '../../db_connect.php';
date_default_timezone_set('Asia/Manila');
// Start transaction
$conn->begin_transaction();

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // First, get current amount_paid
    $getAmountQuery = "SELECT amount_paid FROM sales_tb WHERE sales_id = ?";
    $getAmountStmt = $conn->prepare($getAmountQuery);
    $getAmountStmt->bind_param("i", $data['sales_id']);
    $getAmountStmt->execute();
    $result = $getAmountStmt->get_result();
    $currentAmountPaid = $result->fetch_assoc()['amount_paid'];
    $newAmountPaid = $currentAmountPaid + $data['payment_amount'];

    // Insert into installment_tb
    $query = "INSERT INTO installment_tb (
        CustomerID, 
        sales_id, 
        Branch_id, 
        Client_Name, 
        Before_Balance, 
        After_Payment_Balance, 
        Payment_Amount, 
        Method_of_Payment, 
        Notes,
        Payment_Timestamp
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $currentDateTime = date('Y-m-d H:i:s');

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "iiisddssss", 
        $data['customerID'],
        $data['sales_id'],
        $data['branch_id'],
        $data['client_name'],
        $data['before_balance'],
        $data['after_payment_balance'],
        $data['payment_amount'],
        $data['method_of_payment'],
        $data['notes'],
        $currentDateTime
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to record payment: ' . $conn->error);
    }

    // Update sales_tb balance and amount_paid
    $updateQuery = "UPDATE sales_tb SET 
                    balance = ?, 
                    amount_paid = ? 
                    WHERE sales_id = ?";
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
        $updateStatus = "UPDATE sales_tb SET payment_status = 'Fully Paid' WHERE sales_id = ?";
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
?>