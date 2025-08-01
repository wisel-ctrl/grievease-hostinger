<?php
session_start();
require_once '../../db_connect.php';
date_default_timezone_set('Asia/Manila');

// Semaphore API credentials
define('SEMAPHORE_API_KEY', '024cb8782cdb71b2925fb933f6f8635f');
define('SEMAPHORE_SENDER_NAME', 'GrievEase');

// Function to send SMS via Semaphore
function sendSMS($phone_number, $message) {
    $api_key = SEMAPHORE_API_KEY;
    $sender_name = SEMAPHORE_SENDER_NAME;
    
    $ch = curl_init();
    $parameters = array(
        'apikey' => $api_key,
        'number' => $phone_number,
        'message' => $message,
        'sendername' => $sender_name
    );
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

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
$current_month = date('F Y');

try {
    // Start transaction
    $conn->begin_transaction();

    // Get the customsales_id and payment details
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

    // Update the payment request status to 'accepted'
    $update_query = "UPDATE custompayment_request_tb 
                    SET status = 'accepted', 
                        acceptdecline_date = CURRENT_TIMESTAMP 
                    WHERE payment_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("s", $payment_id);
    $stmt->execute();

    // Get customer details and phone number
    $customer_query = "SELECT cs.customer_id, cs.discounted_price, cs.payment_status, cs.branch_id, 
                      u.phone_number
                      FROM customsales_tb cs
                      JOIN users u ON cs.customer_id = u.id
                      WHERE cs.customsales_id = ?";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("i", $customsales_id);
    $stmt->execute();
    $customer_result = $stmt->get_result();
    $customer_data = $customer_result->fetch_assoc();

    $customer_id = $customer_data['customer_id'];
    $discounted_price = $customer_data['discounted_price'];
    $payment_status = $customer_data['payment_status'];
    $branch_id = $customer_data['branch_id'];
    $phone_number = $customer_data['phone_number'];

    // Get customer name
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

    // Send SMS notification
    if ($phone_number) {
        $sms_message = "Dear $client_name, your payment of ₱" . number_format($amount, 2) . " for $current_month has been approved. New balance: ₱" . number_format($after_payment_balance, 2) . ". Thank you!";
        sendSMS($phone_number, $sms_message);
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Send SMS notification for failed attempt
    if (isset($phone_number) && $phone_number) {
        $sms_message = "Dear $client_name, we encountered an issue processing your payment for $current_month. Please contact support. Error: " . $e->getMessage();
        sendSMS($phone_number, $sms_message);
    }
    
    // Return error response
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => $e->getMessage()]);
}
?>