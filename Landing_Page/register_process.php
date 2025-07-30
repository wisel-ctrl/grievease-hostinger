<?php
include '../db_connect.php';
// Remove any whitespace or content before this line
header('Content-Type: application/json');
session_start();

// Add this near the top of your register_process.php file
// Comment these out for production
// ini_set('display_startup_errors', 1);
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a log file function
function logToFile($message) {
    $logFile = __DIR__ . '/email_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Add logging throughout the code
function logError($message, $data = []) {
    file_put_contents('register_error.log', date('Y-m-d H:i:s') . " - " . $message . " - " . json_encode($data) . "\n", FILE_APPEND);
}

// Add this at key points in your code
try {
    // Your existing code
} catch (Exception $e) {
    logError("Error in verification process", ['error' => $e->getMessage()]);
    // Your existing error handling
}


// Initialize response array
$response = array();

try {
    // Check if the request method is POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // If we're in the OTP verification stage
        if (isset($_POST['otp_verification'])) {
            $entered_otp = $_POST['otp'];
            $email = $_POST['email'];
            
            // Verify OTP
            if (!isset($_SESSION['otp']) || !isset($_SESSION['user_data'])) {
                throw new Exception("OTP session expired. Please try registering again.");
            }
            
            if ($_SESSION['otp'] == $entered_otp) {
                // OTP is correct, proceed with registration
                $userData = $_SESSION['user_data'];
                
                // Insert user data into database
                // Add is_verified column with value 1
                date_default_timezone_set('Asia/Manila');
                $philippines_time = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, middle_name, birthdate, email, password, user_type, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
                $stmt->bind_param("ssssssis", 
                    $userData['firstName'], 
                    $userData['lastName'], 
                    $userData['middleName'], 
                    $userData['birthdate'], 
                    $userData['email'], 
                    $userData['hashed_password'],
                    $userData['userType'],
                    $philippines_time
                );
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    $response['success'] = true;
                    $response['message'] = 'Registration successful!';
                    $response['user_id'] = $user_id;
                    $response['is_verified'] = true;
                    
                    // Send SMS notification to admin
                    $customerName = $userData['firstName'] . ' ' . $userData['lastName'];
                    $smsMessage = "New customer registered: $customerName ({$userData['email']}). User ID: $user_id";
                    
                    try {
                        sendAdminSMS($smsMessage);
                    } catch (Exception $e) {
                        logToFile("Failed to send admin SMS notification: " . $e->getMessage());
                    }
                    
                    // Clear session data
                    unset($_SESSION['otp']);
                    unset($_SESSION['user_data']);
                } else {
                    throw new Exception("Error: " . $stmt->error);
                }
                
                // Close statement
                $stmt->close();
            } else {
                throw new Exception("Invalid OTP. Please try again.");
            }
        } else {
            // Initial registration stage
            // Get form data
            $firstName = $_POST['firstName'];
            $lastName = $_POST['lastName'];
            $middleName = isset($_POST['middleName']) ? $_POST['middleName'] : "";
            $birthdate = $_POST['birthdate'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $userType = 3; // Set default user type as 3 for 'customer'
            
            // First, check if the email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception("Email already exists. Please use a different email address.");
            }
            
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate OTP
            $otp = rand(100000, 999999);
            
            // Store user data and OTP in session
            $_SESSION['otp'] = $otp;
            $_SESSION['user_data'] = array(
                'firstName' => $firstName,
                'lastName' => $lastName,
                'middleName' => $middleName,
                'birthdate' => $birthdate,
                'email' => $email,
                'hashed_password' => $hashed_password,
                'userType' => $userType
            );
            
            // Send OTP via email
            require '../vendor/autoload.php'; // Include PHPMailer autoloader
            $mail_sent = sendOTP($email, $otp, $firstName);
            
            if ($mail_sent) {
                $response['success'] = true;
                $response['requires_otp'] = true;
                $response['message'] = 'OTP sent to your email. Please verify.';
                $response['email'] = $email;
            } else {
                throw new Exception("Failed to send OTP email. Please try again.");
            }
        }

        // Close connection
        $conn->close();
    } else {
        // Not a POST request
        throw new Exception("Invalid request method. Only POST requests are accepted.");
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

// Function to send OTP email
function sendOTP($email, $otp, $firstName) {
    // PHPMailer Implementation
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings for Hostinger
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'relova@grievease.com'; // Your DOMAIN email
        $mail->Password = 'Grievease_2k25'; // Password you set in Hostinger email
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        
        $mail->setFrom('relova@grievease.com', 'GrievEase');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'GrievEase Account Verification';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e4e9f0; border-radius: 5px;'>
                <h2 style='color: #1E1E1E;'>Welcome to GrievEase!</h2>
                <p>Hello {$firstName},</p>
                <p>Thank you for registering with GrievEase. To complete your registration, please use the following OTP:</p>
                <div style='background-color: #f1f5f9; padding: 15px; border-radius: 5px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px;'>
                    {$otp}
                </div>
                <p style='margin-top: 20px;'>This OTP will expire in 10 minutes. If you did not request this, please ignore this email.</p>
                <p>Warm regards,<br>The GrievEase Team</p>
            </div>
        ";
        
        $mail->send();
        logToFile("Email sent successfully to $email");
        return true;
    } catch (Exception $e) {
        // Log the error for debugging
        logToFile("Failed to send email to $email. Error: " . $e->getMessage());
        logToFile("SMTP Error: " . $mail->ErrorInfo);
        return false;
    }
}


function sendAdminSMS($message) {
    $apiKey = '024cb8782cdb71b2925fb933f6f8635f';
    $senderName = 'GrievEase';
    
    // Get all admin phone numbers (user_type = 1)
    global $conn;
    $adminNumbers = array();
    
    $stmt = $conn->prepare("SELECT phone_number FROM users WHERE user_type = 1 AND phone_number IS NOT NULL");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $adminNumbers[] = $row['phone_number'];
    }
    
    if (empty($adminNumbers)) {
        logToFile("No admin phone numbers found for SMS notification");
        return false;
    }
    
    // Send SMS to each admin
    foreach ($adminNumbers as $number) {
        // Clean the phone number (remove any non-digit characters)
        $cleanNumber = preg_replace('/[^0-9]/', '', $number);
        
        // Check if the number starts with 0 and has 11 digits (Philippines format)
        if (strlen($cleanNumber) === 11 && $cleanNumber[0] === '0') {
            // Convert to international format for Semaphore (e.g., 09171234567 -> +639171234567)
            $internationalNumber = '+63' . substr($cleanNumber, 1);
            
            $ch = curl_init();
            $parameters = array(
                'apikey' => $apiKey,
                'number' => $internationalNumber,
                'message' => $message,
                'sendername' => $senderName
            );
            
            curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $output = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                logToFile("Failed to send SMS to admin: " . $internationalNumber . " - Response: " . $output);
            }
        } else {
            logToFile("Invalid phone number format for admin: " . $number);
        }
    }
    
    return true;
}

// Return JSON response - this should be the ONLY output from this file
echo json_encode($response);