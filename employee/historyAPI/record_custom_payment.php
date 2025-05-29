<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['sales_id']) || !isset($data['customerID']) || !isset($data['branch_id']) || 
    !isset($data['payment_amount']) || !isset($data['method_of_payment']) || !isset($data['payment_date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert into custom_payments table
    $stmt = $conn->prepare("INSERT INTO custom_payments (customsales_id, customerID, branch_id, payment_amount, method_of_payment, payment_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiidsss", 
        $data['sales_id'],
        $data['customerID'],
        $data['branch_id'],
        $data['payment_amount'],
        $data['method_of_payment'],
        $data['payment_date'],
        $data['notes']
    );
    $stmt->execute();

    // Update the balance in custom_sales table
    $stmt = $conn->prepare("UPDATE custom_sales SET balance = ?, amount_paid = amount_paid + ? WHERE customsales_id = ?");
    $stmt->bind_param("ddi", 
        $data['after_payment_balance'],
        $data['payment_amount'],
        $data['sales_id']
    );
    $stmt->execute();

    // Get the new total amount paid
    $stmt = $conn->prepare("SELECT amount_paid FROM custom_sales WHERE customsales_id = ?");
    $stmt->bind_param("i", $data['sales_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $new_amount_paid = $row['amount_paid'];

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment recorded successfully',
        'new_amount_paid' => $new_amount_paid
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error recording payment: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 