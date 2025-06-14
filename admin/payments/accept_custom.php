<?php
session_start();
require_once '../../db_connect.php';
date_default_timezone_set('Asia/Manila');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Check if required parameters are present
if (!isset($_GET['payment_id']) || !isset($_GET['amount'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$payment_id = $_GET['payment_id'];
$amount = $_GET['amount'];
$current_month = date('F Y'); // Get current month and year for notes

try {
    // Start transaction
    $conn->begin_transaction();

    // Get the customsales_id and payment details first to validate the amount
    $sales_query = "SELECT cr.customsales_id, cr.payment_method, cs.balance 
                   FROM custompayment_request_tb cr
                   JOIN customsales_tb cs ON cr.customsales_id = cs.customsales_id
                   WHERE cr.payment_id = ?";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param("s", $payment_id);
    $stmt->execute();
    $sales_result = $stmt->get_result();
    
    if ($sales_result->num_rows === 0) {
        throw new Exception("Payment record not found");
    }
    
    $sales_data = $sales_result->fetch_assoc();
    $customsales_id = $sales_data['customsales_id'];
    $payment_method = $sales_data['payment_method'];
    $balance = $sales_data['balance'];

    // Validate that the amount is not greater than the balance
    if ($amount > $balance) {
        throw new Exception("Input amount should not be greater than the current balance of ₱" . number_format($balance, 2));
    }

    // Update the payment request status to 'accepted' and set accept date
    $update_query = "UPDATE custompayment_request_tb 
                    SET status = 'accepted', 
                        acceptdecline_date = CURRENT_TIMESTAMP 
                    WHERE payment_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("s", $payment_id);
    $stmt->execute();

    // Get customer details from customsales_tb
    $customer_query = "SELECT customer_id, discounted_price, payment_status, branch_id 
                      FROM customsales_tb 
                      WHERE customsales_id = ?";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("i", $customsales_id);
    $stmt->execute();
    $customer_result = $stmt->get_result();
    $customer_data = $customer_result->fetch_assoc();

    $customer_id = $customer_data['customer_id'];
    $discounted_price = $customer_data['discounted_price'];
    $payment_status = $customer_data['payment_status'];
    $branch_id = $customer_data['branch_id'];

    // Get customer name from users table
    $name_query = "SELECT CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, ''), ' ', COALESCE(last_name, ''), ' ', COALESCE(suffix, '')) 
                  AS client_name 
                  FROM users 
                  WHERE id = ?";
    $stmt = $conn->prepare($name_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $name_result = $stmt->get_result();
    $name_data = $name_result->fetch_assoc();
    $client_name = $name_data['client_name'];

    // Prepare notes
    $notes = "payment for $current_month submitted via online payment";

    // Insert into custom_installment_tb
    $after_payment_balance = $balance - $amount;
    $insert_query = "INSERT INTO custom_installment_tb 
                    (customerID, branch_id, client_name, before_balance, after_payment_balance, 
                     payment_amount, payment_timestamp, method_of_payment, notes, customsales_id) 
                    VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iisddsssi", $customer_id, $branch_id, $client_name, $balance, $after_payment_balance, 
                     $amount, $payment_method, $notes, $customsales_id);
    $stmt->execute();

    // Update balance in customsales_tb
    $update_balance_query = "UPDATE customsales_tb 
                            SET balance = balance - ? 
                            WHERE customsales_id = ?";
    $stmt = $conn->prepare($update_balance_query);
    $stmt->bind_param("di", $amount, $customsales_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Return error response
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => $e->getMessage()]);
}
?>