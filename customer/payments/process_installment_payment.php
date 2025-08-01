<?php
session_start();
require_once '../../db_connect.php';
require_once 'send_sms_helper.php';

$response = ['success' => false, 'message' => ''];

try {
    $type = $_POST['type'] ?? '';
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'] ?? '';
    $customerID = $_SESSION['user_id'];
    $salesId = $_POST['sales_id'] ?? null;
    
    // Validate required fields
    if (empty($type) || empty($amount) || empty($method) || empty($salesId)) {
        throw new Exception("Required fields are missing");
    }
    
    // First, check the balance from sales_tb
    $balanceCheck = $conn->prepare("SELECT balance FROM sales_tb WHERE sales_id = ?");
    $balanceCheck->bind_param("i", $salesId);
    $balanceCheck->execute();
    $balanceResult = $balanceCheck->get_result();
    
    if ($balanceResult->num_rows === 0) {
        throw new Exception("Sales record not found");
    }
    
    $salesData = $balanceResult->fetch_assoc();
    $balance = floatval($salesData['balance']);
    
    // Validate if amount is greater than balance
    if ($amount > $balance) {
        throw new Exception("The amount you entered is greater than your balance");
    }
    
    // Handle file upload if exists
    $receiptPath = '';
    if (isset($_FILES['receipt'])) {
        $uploadDir = 'uploads/receipts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = uniqid() . '_' . basename($_FILES['receipt']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetPath)) {
            $receiptPath = $targetPath;
        }
    }
    
    // Insert into installment_request_tb
    $stmt = $conn->prepare("INSERT INTO installment_request_tb 
                           (customer_id, sales_id, amount, payment_method, payment_url, request_date) 
                           VALUES (?, ?, ?, ?, ?, ?)");

    date_default_timezone_set('Asia/Manila');
    $now = date('Y-m-d H:i:s');
    $stmt->bind_param("iidsss", $customerID, $salesId, $amount, $method, $receiptPath, $now);
    $stmt->execute();
    
    // Send SMS notification to admins
    sendAdminSMSNotification($conn, $customerID, $amount, 'Installment Payment');
    
    $response['success'] = true;
    $response['message'] = 'Installment request submitted successfully';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>