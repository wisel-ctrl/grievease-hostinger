<?php
require_once '../../db_connect.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Check if email is different from original (should be handled by calling function)
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    
    // Store OTP in session with timestamp
    $_SESSION['edit_account_otp'] = $otp;
    $_SESSION['edit_account_otp_email'] = $email;
    $_SESSION['edit_account_otp_time'] = time();
    $_SESSION['edit_account_otp_verified'] = false;
    
    // Send email with OTP
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'relova@grievease.com';
        $mail->Password = 'Grievease_2k25';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        
        // Recipients
        $mail->setFrom('relova@grievease.com', 'GrievEase');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Account Update Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #333;'>Account Update Verification</h2>
                <p>Your verification code for updating your account is:</p>
                <h1 style='text-align: center; font-size: 32px; letter-spacing: 8px; background-color: #f5f5f5; padding: 15px; border-radius: 8px;'>{$otp}</h1>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this code, please contact support immediately.</p>
            </div>
        ";
        
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>