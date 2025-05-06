<?php
session_start();
require_once '../../db_connect.php';

$response = ['success' => false, 'message' => ''];

try {
    $type = $_POST['type'] ?? '';
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'] ?? '';
    $customerID = $_SESSION['user_id'];
    $salesId = $_POST['customsales_id'] ?? null;
    
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
    
    // Validate required fields
    if (empty($type) || empty($amount) || empty($method) || empty($salesId)) {
        throw new Exception("Required fields are missing");
    }
    
    // Insert into installment_request_tb
    $stmt = $conn->prepare("INSERT INTO custompayment_request_tb 
                           (customer_id, customsales_id, amount, payment_method, payment_url, request_date) 
                           VALUES (?, ?, ?, ?, ?, ?)");

                           date_default_timezone_set('Asia/Manila');
                           $now = date('Y-m-d H:i:s');
    $stmt->bind_param("iidsss", $customerID, $salesId, $amount, $method, $receiptPath, $now);
    $stmt->execute();
    
    $response['success'] = true;
    $response['message'] = 'Installment request submitted successfully';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>