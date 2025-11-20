<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';
require_once '../../db_connect.php';

date_default_timezone_set('Asia/Manila');

// Function to generate a secure reset token
function generateResetToken($user_id) {
    // Generate a cryptographically secure random token
    $token = bin2hex(random_bytes(32)); // 64-character hex token
    
    // Store token in database with expiration
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour
    
    $stmt = $GLOBALS['conn']->prepare("UPDATE users SET 
        reset_token = ?, 
        reset_token_expiry = ? 
        WHERE id = ?");
    $stmt->bind_param("ssi", $token, $expiry, $user_id);
    $stmt->execute();
    
    return $token;
}

// Function to send password reset link
function sendPasswordResetLink($email, $reset_token) {
    $reset_link = "http://grievease.com/Landing_Page/reset_password/reset_password_page.php?token=" . $reset_token;

    $mail = new PHPMailer(true);

    try {
        // SMTP configuration (same as before)
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'relova@grievease.com';
        $mail->Password   = 'Grievease_2k25';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('relova@grievease.com', 'GrievEase Password Reset');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Link for GrievEase';
        $mail->Body    = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2>Password Reset Request</h2>
                <p>You have requested to reset your password for your GrievEase account.</p>
                <p>Click the link below to reset your password:</p>
                <a href='{$reset_link}' style='display: inline-block; 
                    background-color: black; 
                    color: white; 
                    padding: 10px 20px; 
                    text-decoration: none; 
                    border-radius: 5px;'>
                    Reset Password
                </a>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email.</p>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle reset password request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Invalid email address'
        ]);
        exit;
    }

    // Check if email exists in users table and check reset limit
    $stmt = $conn->prepare("SELECT id, last_password_reset FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if user has reset password within the last month (PH time)
        if ($user['last_password_reset']) {
            $lastReset = new DateTime($user['last_password_reset'], new DateTimeZone('Asia/Manila'));
            $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
            $interval = $lastReset->diff($now);
            
            // Check if less than 30 days have passed
            if ($interval->days < 30) {
                $daysLeft = 30 - $interval->days;
                echo json_encode([
                    'status' => 'error',
                    'message' => "You can only reset your password once per month. You can request a new reset in $daysLeft day(s)."
                ]);
                exit;
            }
        }
        
        // Generate reset token
        $reset_token = generateResetToken($user['id']);

        // Send reset link via email
        if (sendPasswordResetLink($email, $reset_token)) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Password reset link sent to your email'
            ]);
        } else {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Could not send reset link'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'No account found with this email address'
        ]);
    }
    exit;
}

?>