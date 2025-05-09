<?php
//sms_notification.php
require_once '../../db_connect.php';

function sendAdminSMSNotification($conn, $bookingDetails, $bookingId) {

    if (!$conn || $conn->connect_error) {
        error_log("SMS Notification: Database connection error");
        return false;
    }

    // Get admin phone numbers (users with user_type = 1)
    $query = "SELECT id, phone_number FROM users WHERE user_type = 1";
    $result = $conn->query($query);
    
    if (!$result) {
        error_log("SMS Notification: Query failed - " . $conn->error);
        return false;
    }
    
    if ($result->num_rows === 0) {
        error_log("SMS Notification: No admins found");
        return false;
    }
    
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    
    // Prepare message
    $message = "NEW BOOKING:\n";
    $message .= "Deceased: {$bookingDetails['deceased_fname']} {$bookingDetails['deceased_lname']}\n";
    $message .= "Service ID: {$bookingDetails['service_id']}\n";
    $message .= "Branch ID: {$bookingDetails['branch_id']}\n";
    $message .= "Amount: ₱" . number_format($bookingDetails['initial_price'], 2);
    

    
    // Send SMS to each admin
    $results = [];
    foreach ($admins as $admin) {
        $formattedNumber = formatPhoneNumber($admin['phone_number']);
        if ($formattedNumber) {
            $response = sendSMSviaSemaphore($conn, $formattedNumber, $message, $admin['id'], $bookingId);
            $results[] = $response;
        }
    }
    
    error_log("SMS Notification Results: " . print_r($results, true));
    
    // Check sms_logs table for detailed status
    $logCheck = $conn->query("SELECT * FROM sms_logs WHERE booking_id = $bookingId");
    while ($log = $logCheck->fetch_assoc()) {
        error_log("SMS Log Entry: " . print_r($log, true));
    }
        
    return $results;
}

function sendSMSviaSemaphore($conn, $number, $message, $userId = null, $bookingId = null) {
    $apiKey = '024cb8782cdb71b2925fb933f6f8635f';
    
    // Format number
    $formattedNumber = formatPhoneNumber($number);
    if (!$formattedNumber) {
        error_log("Invalid phone number format: $number");
        return ['status' => 'failed', 'error' => 'Invalid phone number'];
    }
    
    // Check message doesn't start with TEST
    if (strtoupper(substr(trim($message), 0, 4)) === 'TEST') {
        error_log("Message starts with TEST - will be ignored by Semaphore");
        $message = "Notification: " . $message;
    }
    
    // Prepare log
    $logData = [
        'phone_number' => $formattedNumber,
        'message' => $message,
        'status' => 'pending',
        'user_id' => $userId,
        'booking_id' => $bookingId
    ];
    
    $logId = insertSMSLog($conn, $logData);
    
    $ch = curl_init();
    
    $parameters = [
        'apikey' => $apiKey,
        'number' => $formattedNumber,
        'message' => $message,
        'sendername' => 'GrievEase' // Change to your registered sender name
    ];
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $output = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Parse response
    $response = json_decode($output, true);
    $status = ($httpCode == 200 && isset($response[0]['status']) && $response[0]['status'] == 'Queued') ? 'sent' : 'failed';
    
    // Update log
    updateSMSLog($conn, $logId, [
        'status' => $status,
        'response' => json_encode(['http_code' => $httpCode, 'api_response' => $response, 'curl_error' => $curlError])
    ]);
    
    if ($status === 'failed') {
        error_log("SMS Send Failed - HTTP Code: $httpCode, Response: " . print_r($response, true) . ", Curl Error: $curlError");
    }
    
    return [
        'status' => $status,
        'response' => $response,
        'log_id' => $logId
    ];
}

function formatPhoneNumber($number) {
    // Remove all non-digit characters
    $cleaned = preg_replace('/[^0-9]/', '', $number);
    
    // Philippine numbers - convert to +639 format
    if (strlen($cleaned) == 10 && $cleaned[0] == '9') {
        return '+63' . $cleaned;
    }
    
    // If starts with 0, convert to +63
    if (strlen($cleaned) == 11 && $cleaned[0] == '0') {
        return '+63' . substr($cleaned, 1);
    }
    
    // If already starts with 63, add +
    if (strlen($cleaned) > 10 && substr($cleaned, 0, 2) == '63') {
        return '+' . $cleaned;
    }
    
    // If starts with +, return as is
    if (substr($cleaned, 0, 1) == '+') {
        return $cleaned;
    }
    
    return false; // Invalid format
}

function insertSMSLog($conn, $data) {
    $stmt = $conn->prepare("
        INSERT INTO sms_logs (
            phone_number, message, status, user_id, booking_id
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "sssii",
        $data['phone_number'],
        $data['message'],
        $data['status'],
        $data['user_id'],
        $data['booking_id']
    );
    
    $stmt->execute();
    return $stmt->insert_id;
}

function updateSMSLog($conn, $id, $data) {
    $stmt = $conn->prepare("
        UPDATE sms_logs 
        SET status = ?, response = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param(
        "ssi",
        $data['status'],
        $data['response'],
        $id
    );
    
    return $stmt->execute();
}


//ID CONFIRMATION NOTIFICATION TO admin
function sendAdminIDUploadNotification($conn, $uploadDetails) {
    if (!$conn || $conn->connect_error) {
        error_log("ID Upload SMS Notification: Database connection error");
        return false;
    }

    // Get admin phone numbers (users with user_type = 1)
    $query = "SELECT id, phone_number FROM users WHERE user_type = 1";
    $result = $conn->query($query);
    
    if (!$result) {
        error_log("ID Upload SMS Notification: Query failed - " . $conn->error);
        return false;
    }
    
    if ($result->num_rows === 0) {
        error_log("ID Upload SMS Notification: No admins found");
        return false;
    }
    
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    
    // Prepare message
    $message = "NEW ID UPLOAD FOR VERIFICATION:\n";
    $message .= "User ID: {$uploadDetails['user_id']}\n";
    $message .= "Name: {$uploadDetails['first_name']} {$uploadDetails['last_name']}\n";
    $message .= "Uploaded at: " . date('M d, Y h:i A', strtotime($uploadDetails['upload_time']));
    $message .= "\n\nPlease review in admin panel.";
    
    // Send SMS to each admin
    $results = [];
    foreach ($admins as $admin) {
        $formattedNumber = formatPhoneNumber($admin['phone_number']);
        if ($formattedNumber) {
            $response = sendSMSviaSemaphore($conn, $formattedNumber, $message, $admin['id']);
            $results[] = $response;
        }
    }
    
    error_log("ID Upload SMS Notification Results: " . print_r($results, true));
    
    return $results;
}
?>