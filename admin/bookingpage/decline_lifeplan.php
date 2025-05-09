<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

if ($_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// SMS Function
function sendSMS($phoneNumber, $message, $bookingStatus) {
    $apiKey = '024cb8782cdb71b2925fb933f6f8635f';
    $senderName = 'GrievEase';
    
    // Sanitize phone number (remove any non-digit characters)
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    // Check if phone number starts with 0, if not prepend 0
    if (substr($phoneNumber, 0, 1) !== '0') {
        $phoneNumber = '0' . $phoneNumber;
    }
    
    // Prepare the message based on status
    $fullMessage = "GrievEase Life Plan Update: ";
    if ($bookingStatus === 'Accepted') {
        $fullMessage .= "Your life plan has been accepted. " . $message;
    } else {
        $fullMessage .= "Your life plan has been declined. " . $message;
    }
    
    $parameters = [
        'apikey' => $apiKey,
        'number' => $phoneNumber,
        'message' => $fullMessage,
        'sendername' => $senderName
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Main function to decline lifeplan
function declineLifeplan($conn) {
    // Validate required fields
    $requiredFields = ['lifeplanId', 'reason'];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            return ['success' => false, 'error' => "Missing required field: $field"];
        }
    }
    
    $lifeplanId = (int)$_POST['lifeplanId'];
    $reason = $conn->real_escape_string($_POST['reason']);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First, get the customer's phone number from the booking
        $getBookingQuery = "SELECT phone FROM lifeplan_booking_tb WHERE lpbooking_id = ?";
        $stmt = $conn->prepare($getBookingQuery);
        $stmt->bind_param("i", $lifeplanId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("LifePlan booking not found");
        }
        
        $booking = $result->fetch_assoc();
        $phoneNumber = $booking['phone'];
        
        // Update the lifeplan booking status to 'decline'
        $updateBookingQuery = "UPDATE lifeplan_booking_tb 
                      SET booking_status = 'decline', 
                          decline_reason = ?,
                          acceptdecline_date = CONVERT_TZ(NOW(), 'SYSTEM', '+08:00')
                      WHERE lpbooking_id = ?";
        $stmt = $conn->prepare($updateBookingQuery);
        $stmt->bind_param("si", $reason, $lifeplanId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to update lifeplan booking status");
        }
        
        // Send SMS notification
        if (!empty($phoneNumber)) {
            $message = "Reason: " . $reason;
            $smsResponse = sendSMS($phoneNumber, $message, 'Declined');
            // You might want to log $smsResponse for debugging
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => 'LifePlan declined successfully',
            'sms_sent' => !empty($phoneNumber)
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Process the decline lifeplan action
$response = declineLifeplan($conn);
header('Content-Type: application/json');
echo json_encode($response);
?>