<?php
require_once '../../db_connect.php';
require '../../vendor/autoload.php';

// Session handling with improved security
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_secure' => true, // Enable if using HTTPS
        'cookie_samesite' => 'Lax'
    ]);
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

function logToFile($message) {
    $logFile = __DIR__ . '/cancellation_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
    // Get input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['booking_id'])) {
        throw new Exception("Invalid request data");
    }

    // Fetch booking details
    $query = "SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.email
              FROM booking_tb b
              JOIN users u ON b.customerID = u.id
              WHERE b.booking_id = ? AND b.is_cancelled = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $data['booking_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Booking not found or already cancelled");
    }
    
    $booking = $result->fetch_assoc();
    
    // Generate secure OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in session
    $_SESSION = [
        'cancel_otp' => $otp,
        'cancel_booking_id' => $data['booking_id'],
        'otp_created_at' => time(),
        'otp_email' => $booking['email']
    ];
    
    // Regenerate session ID for security
    session_regenerate_id(true);

    // Send email
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'relova@grievease.com';
        $mail->Password = 'Grievease_2k25';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $mail->setFrom('relova@grievease.com', 'GrievEase');
        $mail->addAddress($booking['email'], $booking['full_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Your Booking Cancellation Verification Code';
$mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .otp-code { 
            font-size: 24px; 
            letter-spacing: 3px; 
            padding: 10px 15px; 
            background-color: #f8f9fa; 
            display: inline-block; 
            margin: 15px 0;
            border-radius: 4px;
        }
        .footer { 
            margin-top: 20px; 
            padding-top: 20px; 
            border-top: 1px solid #eee; 
            font-size: 12px; 
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>GrievEase Booking Cancellation</h2>
        </div>
        <div class="content">
            <p>Hello ' . htmlspecialchars($booking['full_name']) . ',</p>
            <p>We received a request to cancel your booking (ID: ' . $data['booking_id'] . ').</p>
            <p>Please use the following verification code to confirm the cancellation:</p>
            
            <div class="otp-code">' . $otp . '</div>
            
            <p>This code will expire in 10 minutes. If you didn\'t request this cancellation, please ignore this email or contact our support team immediately.</p>
            
            <p><strong>Important:</strong> Please note that your downpayment will not be refunded if you proceed with cancellation.</p>
        </div>
        <div class="footer">
            <p>© ' . date('Y') . ' GrievEase. All rights reserved.</p>
            <p>This is an automated message - please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>
';

$mail->AltBody = "Hello {$booking['full_name']},\n\n"
    . "We received a request to cancel your booking (ID: {$data['booking_id']}).\n\n"
    . "Your verification code is: {$otp}\n"
    . "This code will expire in 10 minutes.\n\n"
    . "Important: Please note that your downpayment will not be refunded if you proceed with cancellation.\n\n"
    . "If you didn't request this cancellation, please contact our support team immediately.\n\n"
    . "© " . date('Y') . " GrievEase. All rights reserved.";
        
        $mail->send();
        logToFile("OTP sent to {$booking['email']} for booking #{$data['booking_id']}");
        
        $response['success'] = true;
        $response['message'] = 'OTP sent successfully';
    } catch (Exception $e) {
        logToFile("Mailer Error: " . $e->getMessage());
        throw new Exception("Failed to send OTP. Please try again.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    logToFile("Error: " . $e->getMessage());
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
    echo json_encode($response);
}
?>