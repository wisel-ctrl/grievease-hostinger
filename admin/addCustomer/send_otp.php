<?php
// Include database connection
require_once '../../db_connect.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check for your specific PHPMailer include path
require '../../vendor/autoload.php'; // Adjust path as needed

// Check if request is AJAX and POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    // Get and sanitize email
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Check if email already exists
    $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkEmailQuery);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    
    // Store OTP in session or database with timestamp
    session_start();
    $_SESSION['admin_otp'] = $otp;
    $_SESSION['admin_otp_email'] = $email;
    $_SESSION['admin_otp_time'] = time(); // For expiration check
    
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
        $mail->Subject = 'Account Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #333;'>Account Verification</h2>
                <p>Your verification code is:</p>
                <h1 style='text-align: center; font-size: 32px; letter-spacing: 8px; background-color: #f5f5f5; padding: 15px; border-radius: 8px;'>{$otp}</h1>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this code, please ignore this email.</p>
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