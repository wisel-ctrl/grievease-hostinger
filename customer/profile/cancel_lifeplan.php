<?php
// Debug mode - remove in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

require_once '../../db_connect.php';

// Verify database connection
if (!isset($conn) || !($conn instanceof mysqli)) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Simple session start for debugging
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

function logToFile($message) {
    $logFile = __DIR__ . '/lifeplan_cancellation_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Simplified input handling
    $data = $_POST;
    logToFile("Received data: " . print_r($data, true));

    // Validate required fields
    $required = ['booking_id', 'otp', 'cancel_reason'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Verify OTP session data exists
    if (!isset($_SESSION['cancel_otp']) || !isset($_SESSION['cancel_booking_id']) || !isset($_SESSION['otp_created_at'])) {
        logToFile("OTP session data missing. Full session: " . print_r($_SESSION, true));
        throw new Exception("OTP expired or not requested");
    }

    // Verify booking ID matches
    if ($_SESSION['cancel_booking_id'] != $data['booking_id']) {
        throw new Exception("Invalid booking for OTP verification");
    }

    // Verify OTP expiration
    if (time() - $_SESSION['otp_created_at'] > 600) {
        unset($_SESSION['cancel_otp'], $_SESSION['cancel_booking_id'], $_SESSION['otp_created_at']);
        throw new Exception("OTP has expired");
    }

    // Verify OTP value
    if (strcasecmp($_SESSION['cancel_otp'], $data['otp']) !== 0) {
        throw new Exception("Invalid OTP");
    }

    // Begin transaction
    $conn->begin_transaction();

    // Debug: Check current booking status
    $checkStmt = $conn->prepare("SELECT booking_status FROM lifeplan_booking_tb WHERE lpbooking_id = ? AND customer_id = ?");
    $checkStmt->bind_param("ii", $data['booking_id'], $_SESSION['user_id']);
    $checkStmt->execute();
    $checkStmt->bind_result($currentStatus);
    $checkStmt->fetch();
    $checkStmt->close();
    logToFile("Current booking status: $currentStatus");

    // Update query
    $query = "UPDATE lifeplan_booking_tb SET 
          booking_status = 'cancel', 
          cancel_date = NOW(), 
          cancel_reason = ? 
          WHERE lpbooking_id = ? 
          AND booking_status IN ('pending', 'accepted') 
          AND customer_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $data['cancel_reason'], $data['booking_id'], $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Booking not found, already cancelled, or not in cancellable state");
    }

    $conn->commit();

    // Clear OTP data
    unset(
        $_SESSION['cancel_otp'],
        $_SESSION['cancel_booking_id'],
        $_SESSION['otp_created_at'],
        $_SESSION['otp_email']
    );

    $response['success'] = true;
    $response['message'] = 'Cancellation successful';

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli && $conn->errno === 0) {
        $conn->rollback();
    }
    $response['message'] = $e->getMessage();
    logToFile("ERROR: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString());
    
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    ob_end_flush();
    echo json_encode($response);
    logToFile("Final response: " . json_encode($response));
}
?>