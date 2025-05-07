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

// Check if payment_id and sales_id are provided
if (!isset($_GET['payment_id']) || !isset($_GET['sales_id']) || !isset($_GET['amount'])) {
    header("Location: payment_acceptance.php?error=missing_parameters");
    exit();
}

$payment_id = $_GET['payment_id'];
$sales_id = $_GET['sales_id'];
$amount = floatval($_GET['amount']); // Convert to float for safety
$current_datetime = date('Y-m-d H:i:s');

// Start transaction
mysqli_begin_transaction($conn);

try {
    // First, get the current amount_paid from sales_tb
    $get_amount_paid = "
    SELECT 
        amount_paid, 
        branch_id,
        balance, 
        CONCAT(fname, ' ', mname, ' ', lname, ' ', suffix) AS full_name 
    FROM 
        sales_tb 
    WHERE 
        sales_id = ?";
    $stmt = mysqli_prepare($conn, $get_amount_paid);
    mysqli_stmt_bind_param($stmt, "i", $sales_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception("Sales record not found");
    }
    
    $row = mysqli_fetch_assoc($result);
    $current_amount_paid = floatval($row['amount_paid']);
    $branch_id = $row['branch_id'];
    $balance = floatval($row['balance']);
    $full_name = $row['full_name'];
    $new_amount_paid = $current_amount_paid + $amount;
    $new_balance = $balance - $amount;
    
    // Update payment status to 'approved'
    $update_payment = "UPDATE installment_request_tb SET status = 'accepted', acceptdecline_date = ? WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $update_payment);
    mysqli_stmt_bind_param($stmt, "si", $current_datetime, $payment_id);
    mysqli_stmt_execute($stmt);
    
    // Update sales with new amount_paid and status
    $update_sales = "UPDATE sales_tb SET amount_paid = ?, status = 'paid', balance = ? WHERE sales_id = ?";
    $stmt = mysqli_prepare($conn, $update_sales);
    mysqli_stmt_bind_param($stmt, "ddi", $new_amount_paid, $new_balance ,$sales_id);
    mysqli_stmt_execute($stmt);
    
    // Insert into installment_tb
    $insert_installment = "
    INSERT INTO installment_tb (
        CustomerID, 
        Branch_id, 
        Client_Name, 
        Before_Balance, 
        After_Payment_Balance, 
        Payment_Amount, 
        Payment_Timestamp, 
        Method_of_Payment, 
        sales_ID
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $insert_installment);
    $method_of_payment = 'Online Payment';
    mysqli_stmt_bind_param(
        $stmt, 
        "iisddsssi", 
        $_SESSION['user_id'], 
        $branch_id, 
        $full_name, 
        $balance, 
        $new_balance, 
        $amount, 
        $current_datetime, 
        $method_of_payment, 
        $sales_id
    );
    mysqli_stmt_execute($stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect back with success message
    header("Location: ../payment_acceptance.php?success=payment_approved");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    header("Location: payment_acceptance.php?error=approval_failed");
    exit();
}
?>