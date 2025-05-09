<?php
session_start();
require_once '../../db_connect.php';

date_default_timezone_set('Asia/Manila');

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
    $fullMessage = "GrievEase Booking Update: ";
    if ($bookingStatus === 'Accepted') {
        $fullMessage .= "Your custom booking has been accepted. " . $message;
    } else {
        $fullMessage .= "Your custom booking has been declined. " . $message;
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

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

if ($_SESSION['user_type'] != 1) { // 1 = admin
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Get the POST data
$bookingId = isset($_POST['bookingId']) ? intval($_POST['bookingId']) : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

// Validate inputs
if ($bookingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit();
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Reason for decline is required']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // First, get the customer ID from the booking
    $stmt = $conn->prepare("SELECT customerID FROM booking_tb WHERE booking_id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    if (!$booking) {
        throw new Exception("Booking not found");
    }
    
    $customerId = $booking['customerID'];
    
    // Prepare the update query
    $query = "UPDATE booking_tb 
              SET status = 'Declined', 
                  reason_for_decline = ?, 
                  accepter_decliner = ?, 
                  decline_date = ? 
              WHERE booking_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $adminId = $_SESSION['user_id'];
    $declineDate = date('Y-m-d H:i:s');
    $stmt->bind_param("sisi", $reason, $adminId, $declineDate, $bookingId);

    if (!$stmt->execute()) {
        throw new Exception('Error declining booking: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('No booking was updated - ID may not exist');
    }
    $stmt->close();
    
    // Get customer phone number for SMS
    $stmt = $conn->prepare("SELECT phone_number FROM users WHERE id = ?");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();
    
    if ($customer && !empty($customer['phone_number'])) {
        $message = "Reason: $reason. Please contact us for more information.";
        $smsResponse = sendSMS($customer['phone_number'], $message, 'Declined');
        // You might want to log $smsResponse for debugging
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Booking declined successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>