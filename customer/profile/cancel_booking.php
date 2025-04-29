<?php
// Start output buffering
ob_start();

require_once '../../db_connect.php';

// Standardized session start
function secureSessionStart() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'use_strict_mode' => true,
            'cookie_httponly' => true,
            'cookie_secure' => true,
            'cookie_samesite' => 'Lax',
            'use_only_cookies' => 1,
            'cookie_lifetime' => 1800
        ]);
    }
    
    // Security checks
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > 1800) {
            session_unset();
            session_destroy();
            header("Location: ../Landing_Page/login.php?timeout=1");
            exit();
        }
    }
    $_SESSION['last_activity'] = time();
}

secureSessionStart();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

function logToFile($message) {
    $logFile = __DIR__ . '/cancellation_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Get input data (handling both JSON and form-data)
    $input = file_get_contents('php://input');
    if (strlen($input) > 0) {
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            parse_str($input, $data); // Fallback for form-data
        }
    } else {
        $data = $_POST;
    }

    // Validate required fields
    $required = ['booking_id', 'otp', 'cancel_reason'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Verify OTP session data exists
    if (!isset($_SESSION['cancel_otp']) || !isset($_SESSION['cancel_booking_id']) || !isset($_SESSION['otp_created_at'])) {
        throw new Exception("OTP expired or not requested");
    }

    // Verify booking ID matches
    if ($_SESSION['cancel_booking_id'] != $data['booking_id']) {
        throw new Exception("Invalid booking for OTP verification");
    }

    // Verify OTP expiration (10 minutes)
    if (time() - $_SESSION['otp_created_at'] > 600) {
        unset($_SESSION['cancel_otp'], $_SESSION['cancel_booking_id'], $_SESSION['otp_created_at']);
        throw new Exception("OTP has expired");
    }

    // Verify OTP value (case insensitive)
    if (strcasecmp($_SESSION['cancel_otp'], $data['otp']) !== 0) {
        throw new Exception("Invalid OTP");
    }

    // Begin transaction
    $conn->begin_transaction();

    // Update booking status
    $query = "UPDATE booking_tb SET 
          status = 'Cancelled', 
          cancelled_date = NOW(), 
          cancel_reason = ? 
          WHERE booking_id = ? AND status != 'Cancelled'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $data['cancel_reason'], $data['booking_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Booking not found or already cancelled");
    }

    // Commit transaction
    $conn->commit();

    // Clear only OTP-related session data
    unset(
        $_SESSION['cancel_otp'],
        $_SESSION['cancel_booking_id'],
        $_SESSION['otp_created_at'],
        $_SESSION['otp_email']
    );

    $response['success'] = true;
    $response['message'] = 'Booking cancelled successfully';
} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    $response['message'] = $e->getMessage();
    logToFile("Cancellation Error: " . $e->getMessage());
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
    ob_end_flush();
    echo json_encode($response);
}
?>