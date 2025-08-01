<?php
require_once '../../db_connect.php';

function sendAdminSMSNotification($conn, $customerID, $amount, $paymentType) {
    try {
        // Get admin phone numbers (user_type = 1)
        $adminQuery = $conn->prepare("SELECT phone_number, first_name, last_name FROM users WHERE user_type = 1");
        $adminQuery->execute();
        $adminResult = $adminQuery->get_result();
        
        if ($adminResult->num_rows === 0) {
            throw new Exception("No admin accounts found");
        }
        
        // Get customer name
        $customerQuery = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $customerQuery->bind_param("i", $customerID);
        $customerQuery->execute();
        $customerResult = $customerQuery->get_result();
        
        if ($customerResult->num_rows === 0) {
            throw new Exception("Customer not found");
        }
        
        $customerData = $customerResult->fetch_assoc();
        $customerName = $customerData['first_name'] . ' ' . $customerData['last_name'];
        
        // Prepare SMS message
        $message = "Payment Notification: {$customerName} paid PHP {$amount} for {$paymentType}.";
        
        // Semaphore API configuration
        $apiKey = '024cb8782cdb71b2925fb933f6f8635f';
        $senderName = 'GrievEase';
        $url = 'https://api.semaphore.co/api/v4/messages';
        
        // Send SMS to each admin
        while ($admin = $adminResult->fetch_assoc()) {
            $phoneNumber = $admin['phone_number'];
            
            // Prepare POST data
            $postData = [
                'apikey' => $apiKey,
                'number' => $phoneNumber,
                'message' => $message,
                'sendername' => $senderName
            ];
            
            // Initialize cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            // Execute cURL
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("Failed to send SMS to {$phoneNumber}: HTTP {$httpCode} - {$response}");
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("SMS Notification Error: " . $e->getMessage());
        return false;
    }
}
?>