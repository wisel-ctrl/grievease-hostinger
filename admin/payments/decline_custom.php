<?php
require_once '../../db_connect.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Semaphore API credentials
define('SEMAPHORE_API_KEY', '024cb8782cdb71b2925fb933f6f8635f');
define('SEMAPHORE_SENDER_NAME', 'GrievEase');

// Function to send SMS via Semaphore
function sendSMS($phone_number, $message) {
    $api_key = SEMAPHORE_API_KEY;
    $sender_name = SEMAPHORE_SENDER_NAME;
    
    $ch = curl_init();
    $parameters = array(
        'apikey' => $api_key,
        'number' => $phone_number,
        'message' => $message,
        'sendername' => $sender_name
    );
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Get payment ID and reason from query parameters
$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : null;
$reason = isset($_GET['reason']) ? $_GET['reason'] : null;

// Validate required parameters
if (!$payment_id || !$reason) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

try {
    // Get customer phone number and name
    $query = "SELECT u.phone_number, 
                     CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', COALESCE(u.suffix, '')) AS client_name
              FROM custompayment_request_tb pr
              JOIN customsales_tb cs ON pr.customsales_id = cs.customsales_id
              JOIN users u ON cs.customer_id = u.id
              WHERE pr.payment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Payment record not found");
    }
    
    $data = $result->fetch_assoc();
    $phone_number = $data['phone_number'];
    $client_name = $data['client_name'];
    
    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE custompayment_request_tb 
                           SET acceptdecline_date = NOW(), 
                               decline_reason = ?,
                               status = 'declined'
                           WHERE payment_id = ?");
    
    // Bind parameters and execute
    $stmt->bind_param("ss", $reason, $payment_id);
    $stmt->execute();

    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        // Send SMS notification
        if ($phone_number) {
            $sms_message = "Dear $client_name, your payment request has been declined. Reason: $reason. Please contact support.";
            sendSMS($phone_number, $sms_message);
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