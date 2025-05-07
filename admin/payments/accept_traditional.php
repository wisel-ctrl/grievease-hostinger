<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

if ($_SESSION['user_type'] != 1) {
    header("Location: index.php");
    exit();
}

// Check if payment_id and sales_id are provided
if (!isset($_GET['payment_id']) || !isset($_GET['sales_id'])) {
    header("Location: payment_acceptance.php?error=missing_parameters");
    exit();
}

$payment_id = $_GET['payment_id'];
$sales_id = $_GET['sales_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update payment status to 'approved'
    $update_payment = "UPDATE installment_request_tb SET status = 'approved' WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $update_payment);
    mysqli_stmt_bind_param($stmt, "i", $payment_id);
    mysqli_stmt_execute($stmt);
    
    // Update sales status to 'paid' or whatever your business logic requires
    $update_sales = "UPDATE sales_tb SET status = 'paid' WHERE sales_id = ?";
    $stmt = mysqli_prepare($conn, $update_sales);
    mysqli_stmt_bind_param($stmt, "i", $sales_id);
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