<?php
require_once '../../db_connect.php';  

session_start();  

// addEmployee/verify_employee_otp.php
// Include database connection 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {     
    $userOtp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_STRING);          
    
    // Check if OTP exists in session     
    if (!isset($_SESSION['emp_otp']) || !isset($_SESSION['emp_otp_time']) || !isset($_SESSION['emp_otp_email'])) {         
        echo json_encode(['success' => false, 'message' => 'OTP session expired. Please try again.']);         
        exit;     
    }          
    
    // Check if OTP is expired (10 minutes = 600 seconds)     
    if (time() - $_SESSION['emp_otp_time'] > 600) {         
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);         
        // Clear session variables         
        unset($_SESSION['emp_otp']);         
        unset($_SESSION['emp_otp_time']);         
        exit;     
    }          
    
    // Verify OTP     
    if ($_SESSION['emp_otp'] == $userOtp) {         
        echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);     
    } else {         
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);     
    } 
} else {     
    echo json_encode(['success' => false, 'message' => 'Invalid request']); 
}
?>