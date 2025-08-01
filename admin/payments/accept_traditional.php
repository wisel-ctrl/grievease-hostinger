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

// Check if payment_id, sales_id, and amount are provided
if (!isset($_GET['payment_id']) || !isset($_GET['sales_id']) || !isset($_GET['amount'])) {
    header("Location: ../payment_acceptance.php?error=missing_parameters");
    exit();
}

$payment_id = $_GET['payment_id'];
$sales_id = $_GET['sales_id'];
$amount = floatval($_GET['amount']); // Convert to float for safety
$current_datetime = date('Y-m-d H:i:s');
$current_month = date('F Y'); // Get current month and year for notes

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Fetch customer details including phone number
    $get_customer_details = "
    SELECT 
        s.amount_paid, 
        s.branch_id,
        s.balance, 
        CONCAT(u.first_name, ' ', IFNULL(u.middle_name, ''), ' ', u.last_name, ' ', IFNULL(u.suffix, '')) AS full_name,
        u.phone_number
    FROM 
        sales_tb s
    JOIN installment_request_tb ir ON s.sales_id = ir.sales_id
    JOIN users u ON ir.customer_id = u.id
    WHERE 
        s.sales_id = ?";
    $stmt = mysqli_prepare($conn, $get_customer_details);
    mysqli_stmt_bind_param($stmt, "i", $sales_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception("Sales record or customer not found");
    }
    
    $row = mysqli_fetch_assoc($result);
    $current_amount_paid = floatval($row['amount_paid']);
    $branch_id = $row['branch_id'];
    $balance = floatval($row['balance']);
    $full_name = trim($row['full_name']);
    $phone_number = $row['phone_number'];
    $new_amount_paid = $current_amount_paid + $amount;
    $new_balance = $balance - $amount;
    
    // Validate if amount is greater than balance
    if ($amount > $balance) {
        header("Location: ../payment_acceptance.php?error=amount_exceeds_balance&balance=" . number_format($balance, 2));
        exit();
    }
    
    // Update payment status to 'accepted'
    $update_payment = "UPDATE installment_request_tb SET status = 'accepted', acceptdecline_date = ? WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $update_payment);
    mysqli_stmt_bind_param($stmt, "si", $current_datetime, $payment_id);
    mysqli_stmt_execute($stmt);
    
    // Update sales with new amount_paid and status
    $update_sales = "UPDATE sales_tb SET amount_paid = ?, status = 'paid', balance = ? WHERE sales_id = ?";
    $stmt = mysqli_prepare($conn, $update_sales);
    mysqli_stmt_bind_param($stmt, "ddi", $new_amount_paid, $new_balance, $sales_id);
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
        sales_ID,
        notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $notes = "payment for $current_month submitted via online payment";
    $method_of_payment = 'Online Payment';
    
    $stmt = mysqli_prepare($conn, $insert_installment);
    mysqli_stmt_bind_param(
        $stmt, 
        "iisddsssis", 
        $_SESSION['user_id'], 
        $branch_id, 
        $full_name, 
        $balance, 
        $new_balance, 
        $amount, 
        $current_datetime, 
        $method_of_payment, 
        $sales_id,
        $notes
    );
    mysqli_stmt_execute($stmt);
    
    // Send SMS notification
    if ($phone_number) {
        $api_key = '024cb8782cdb71b2925fb933f6f8635f';
        $sender_name = 'GrievEase';
        $message = "Dear $full_name, your payment of ₱" . number_format($amount, 2) . " for $current_month has been accepted. Thank you!";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'apikey' => $api_key,
            'number' => $phone_number,
            'message' => $message,
            'sendername' => $sender_name
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code != 200) {
            // Log SMS failure (optional, you can log to a file or database)
            error_log("SMS failed to send to $phone_number: HTTP $http_code, Response: $response");
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect back with success message
    header("Location: ../payment_acceptance.php?success=payment_approved");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    header("Location: ../payment_acceptance.php?error=approval_failed&message=" . urlencode($e->getMessage()));
    exit();
}
?>