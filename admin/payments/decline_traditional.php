<?php
require_once '../../db_connect.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Get payment ID, sales ID, and reason from query parameters
$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : null;
$sales_id = isset($_GET['sales_id']) ? $_GET['sales_id'] : null;
$reason = isset($_GET['reason']) ? $_GET['reason'] : null;

// Validate required parameters
if (!$payment_id || !$sales_id || !$reason) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

try {
    // Fetch customer phone number
    $get_customer_details = "
    SELECT 
        CONCAT(u.first_name, ' ', IFNULL(u.middle_name, ''), ' ', u.last_name, ' ', IFNULL(u.suffix, '')) AS full_name,
        u.phone_number
    FROM 
        installment_request_tb ir
    JOIN users u ON ir.customer_id = u.id
    WHERE 
        ir.payment_id = ? AND ir.sales_id = ?";
    $stmt = mysqli_prepare($conn, $get_customer_details);
    mysqli_stmt_bind_param($stmt, "is", $payment_id, $sales_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception("Payment or customer not found");
    }
    
    $row = mysqli_fetch_assoc($result);
    $full_name = trim($row['full_name']);
    $phone_number = $row['phone_number'];
    
    // Update payment status to 'declined'
    $update_payment = "UPDATE installment_request_tb 
                       SET acceptdecline_date = NOW(), 
                           decline_reason = ?,
                           status = 'declined'
                       WHERE payment_id = ? AND sales_id = ?";
    $stmt = mysqli_prepare($conn, $update_payment);
    mysqli_stmt_bind_param($stmt, "sis", $reason, $payment_id, $sales_id);
    mysqli_stmt_execute($stmt);
    
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        // Send SMS notification
        if ($phone_number) {
            $api_key = '024cb8782cdb71b2925fb933f6f8635f';
            $sender_name = 'GrievEase';
            $message = "Dear $full_name, your payment request has been declined due to: $reason. Please contact us for more details.";
            
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
                // Log SMS failure (optional)
                error_log("SMS failed to send to $phone_number: HTTP $http_code, Response: $response");
            }
        }
        
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Payment not found or no changes made']);
    }
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>