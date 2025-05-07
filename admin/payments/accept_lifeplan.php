<?php
session_start();
require_once '../../db_connect.php';
date_default_timezone_set('Asia/Manila');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

if ($_SESSION['user_type'] != 1) {
    header("Location: index.php");
    exit();
}

// Check if payment_id and lifeplan_id are provided
if (!isset($_GET['payment_id']) || !isset($_GET['lifeplan_id'])) {
    header("Location: payment_acceptance.php?error=missing_parameters");
    exit();
}

$payment_id = $_GET['payment_id'];
$lifeplan_id = $_GET['lifeplan_id'];
$amount = $_GET['amount'];
$current_datetime = date('Y-m-d H:i:s');

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update payment status to 'approved'
    $update_payment = "UPDATE lifeplanpayment_request_tb SET status = 'accepted' WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $update_payment);
    mysqli_stmt_bind_param($stmt, "i", $payment_id);
    mysqli_stmt_execute($stmt);
    
    // Update lifeplan status to 'paid' or whatever your business logic requires
    $update_lifeplan = "UPDATE lifeplan_tb SET status = 'paid' WHERE lifeplan_id = ?";
    $stmt = mysqli_prepare($conn, $update_lifeplan);
    mysqli_stmt_bind_param($stmt, "i", $lifeplan_id);
    mysqli_stmt_execute($stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect back with success message
    header("Location: payment_acceptance.php?success=payment_approved");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    header("Location: payment_acceptance.php?error=approval_failed");
    exit();
}
?>