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
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, middle_name, birthdate, email, password, user_type, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
                $stmt->bind_param("ssssssi", 
                    $userData['firstName'], 
                    $userData['lastName'], 
                    $userData['middleName'], 
                    $userData['birthdate'], 
                    $userData['email'], 
                    $userData['hashed_password'],
                    $userData['userType']
                );
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Registration successful!';
                    $response['user_id'] = $conn->insert_id;
                    $response['is_verified'] = true;
                    
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

// Return JSON response - this should be the ONLY output from this file
echo json_encode($response);