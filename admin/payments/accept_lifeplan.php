<?php
session_start();
require_once '../../db_connect.php';
date_default_timezone_set('Asia/Manila');

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
$current_month = date('F Y'); // Gets the full month name and year (e.g., "June 2023")

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. First, get customer_id, current balance, amount_paid, and custom_price from lifeplan_tb
    $get_lifeplan_info = "SELECT customerID, balance, amount_paid, custom_price FROM lifeplan_tb WHERE lifeplan_id = ?";
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
    $new_balance = $current_balance - $amount;
    $new_amount_paid = $amount_paid + $amount;
    
    // 2. Insert into lifeplan_logs_tb
    $insert_log = "INSERT INTO lifeplan_logs_tb (lifeplan_id, customer_id, installment_amount, current_balance, new_balance, log_date, notes) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $notes = "payment for $current_month submitted via online payment";
    $stmt = mysqli_prepare($conn, $insert_log);
    mysqli_stmt_bind_param($stmt, "iidddss", $lifeplan_id, $customer_id, $amount, $current_balance, $new_balance, $current_datetime, $notes);
    mysqli_stmt_execute($stmt);
    
    // 3. Update lifeplan_tb - balance and amount_paid
    $payment_status = ($new_amount_paid >= $custom_price) ? 'paid' : 'pending';
    
    $update_lifeplan = "UPDATE lifeplan_tb 
                        SET balance = ?, 
                            amount_paid = ?, 
                            payment_status = ?
                        WHERE lifeplan_id = ?";
    $stmt = mysqli_prepare($conn, $update_lifeplan);
    mysqli_stmt_bind_param($stmt, "ddss", $new_balance, $new_amount_paid, $payment_status, $lifeplan_id);
    mysqli_stmt_execute($stmt);
    
    // 4. Update payment request - status and acceptdecline_date
    $update_payment = "UPDATE lifeplanpayment_request_tb 
                       SET status = 'accepted', 
                           acceptdecline_date = ?
                       WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $update_payment);
    mysqli_stmt_bind_param($stmt, "si", $current_datetime, $payment_id);
    mysqli_stmt_execute($stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect back with success message
    header("Location: ../payment_acceptance.php?success=payment_approved");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    header("Location: ../payment_acceptance.php?error=approval_failed");
    exit();
}
?>