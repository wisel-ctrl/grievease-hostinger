<?php
require_once '../../db_connect.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {
    $userOtp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_STRING);
    
    // Check if OTP exists in session
    if (!isset($_SESSION['edit_account_otp']) || !isset($_SESSION['edit_account_otp_time']) || !isset($_SESSION['edit_account_otp_email'])) {
        echo json_encode(['success' => false, 'message' => 'OTP session expired. Please try again.']);
        exit;
    }
    
    // Check if OTP is expired (10 minutes = 600 seconds)
    if (time() - $_SESSION['edit_account_otp_time'] > 600) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
        // Clear session variables
        unset($_SESSION['edit_account_otp']);
        unset($_SESSION['edit_account_otp_time']);
        unset($_SESSION['edit_account_otp_email']);
        exit;
    }
    
    // Verify OTP
    if ($_SESSION['edit_account_otp'] == $userOtp) {
        // Set verification flag and store email that was verified
        $_SESSION['edit_account_otp_verified'] = true;
        $_SESSION['verified_email'] = $_SESSION['edit_account_otp_email'];
        echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>