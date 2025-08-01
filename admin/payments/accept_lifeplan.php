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
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

if ($_SESSION['user_type'] != 1) {
    header("Location: ../../index.php");
    exit();
}

// Check if payment_id, lifeplan_id, and amount are provided
if (!isset($_GET['payment_id']) || !isset($_GET['lifeplan_id']) || !isset($_GET['amount'])) {
    header("Location: ../payment_acceptance.php?error=missing_parameters");
    exit();
}

$payment_id = $_GET['payment_id'];
$lifeplan_id = $_GET['lifeplan_id'];
$amount = $_GET['amount'];
$current_datetime = date('Y-m-d H:i:s');
$current_month = date('F Y');

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get customer_id, current balance, amount_paid, custom_price, and phone_number
    $get_lifeplan_info = "SELECT lp.customerID, lp.balance, lp.amount_paid, lp.custom_price, 
                        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', COALESCE(u.suffix, '')) AS client_name,
                        u.phone_number
                        FROM lifeplan_tb lp
                        JOIN users u ON lp.customerID = u.id
                        WHERE lp.lifeplan_id = ?";
    $stmt = mysqli_prepare($conn, $get_lifeplan_info);
    mysqli_stmt_bind_param($stmt, "i", $lifeplan_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception("Life plan not found");
    }
    
    $lifeplan_data = mysqli_fetch_assoc($result);
    $customer_id = $lifeplan_data['customerID'];
    $current_balance = $lifeplan_data['balance'];
    $amount_paid = $lifeplan_data['amount_paid'];
    $custom_price = $lifeplan_data['custom_price'];
    $client_name = $lifeplan_data['client_name'];
    $phone_number = $lifeplan_data['phone_number'];
    
    // Check if amount exceeds balance
    if ($amount > $current_balance) {
        header("Location: ../payment_acceptance.php?error=amount_exceeds_balance&balance=" . $current_balance);
        exit();
    }
    
    $new_balance = $current_balance - $amount;
    $new_amount_paid = $amount_paid + $amount;
    
    // Insert into lifeplan_logs_tb
    $insert_log = "INSERT INTO lifeplan_logs_tb (lifeplan_id, customer_id, installment_amount, current_balance, new_balance, log_date, notes) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $notes = "payment for $current_month submitted via online payment";
    $stmt = mysqli_prepare($conn, $insert_log);
    mysqli_stmt_bind_param($stmt, "iidddss", $lifeplan_id, $customer_id, $amount, $current_balance, $new_balance, $current_datetime, $notes);
    mysqli_stmt_execute($stmt);
    
    // Update lifeplan_tb - balance and amount_paid
    $payment_status = ($new_amount_paid >= $custom_price) ? 'paid' : 'pending';
    
    $update_lifeplan = "UPDATE lifeplan_tb 
                        SET balance = ?, 
                            amount_paid = ?, 
                            payment_status = ?
                        WHERE lifeplan_id = ?";
    $stmt = mysqli_prepare($conn, $update_lifeplan);
    mysqli_stmt_bind_param($stmt, "ddss", $new_balance, $new_amount_paid, $payment_status, $lifeplan_id);
    mysqli_stmt_execute($stmt);
    
    // Update payment request - status and acceptdecline_date
    $update_payment = "UPDATE lifeplanpayment_request_tb 
                       SET status = 'accepted', 
                           acceptdecline_date = ?
                       WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $update_payment);
    mysqli_stmt_bind_param($stmt, "si", $current_datetime, $payment_id);
    mysqli_stmt_execute($stmt);
    
    // Send SMS notification
    if ($phone_number) {
        $sms_message = "Dear $client_name, your payment of ₱" . number_format($amount, 2) . " for $current_month has been approved. New balance: ₱" . number_format($new_balance, 2) . ". Thank you!";
        sendSMS($phone_number, $sms_message);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect back with success message
    header("Location: ../payment_acceptance.php?success=payment_approved");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    // Send SMS notification for failed attempt
    if (isset($phone_number) && $phone_number) {
        $sms_message = "Dear $client_name, we encountered an issue processing your payment for $current_month. Please contact support. Error: " . $e->getMessage();
        sendSMS($phone_number, $sms_message);
    }
    
    header("Location: ../payment_acceptance.php?error=approval_failed");
    exit();
}
?>