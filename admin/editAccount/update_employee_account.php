<?php
require_once '../../db_connect.php';
session_start();

header('Content-Type: application/json');

$userId = $_POST['user_id'] ?? 0;
$firstName = $_POST['first_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$middleName = $_POST['middle_name'] ?? '';
$email = $_POST['email'] ?? '';
$phoneNumber = $_POST['phone_number'] ?? '';
$branchLoc = $_POST['branch_loc'] ?? '';

// Basic validation
if (empty($firstName) || empty($lastName) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Check if email has changed and needs OTP verification
$originalEmailQuery = "SELECT email FROM users WHERE id = ? AND user_type = 2";
$stmt = $conn->prepare($originalEmailQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$originalUser = $result->fetch_assoc();
$stmt->close();

if ($email !== $originalUser['email']) {
    // Verify OTP session
    if (!isset($_SESSION['admin_otp_email'])) {
        echo json_encode(['success' => false, 'message' => 'OTP verification required for email change']);
        exit;
    }
    
    if ($_SESSION['admin_otp_email'] !== $email) {
        echo json_encode(['success' => false, 'message' => 'OTP verification does not match the new email']);
        exit;
    }
    
    if (!isset($_SESSION['admin_otp_verified']) || $_SESSION['admin_otp_verified'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Email change requires OTP verification']);
        exit;
    }
}

$query = "UPDATE users SET 
          first_name = ?, 
          last_name = ?, 
          middle_name = ?, 
          email = ?, 
          phone_number = ?,
          branch_loc = ?,
          updated_at = NOW()
          WHERE id = ? AND user_type = 2";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssi", $firstName, $lastName, $middleName, $email, $phoneNumber, $branchLoc, $userId);
$result = $stmt->execute();

if ($result) {
    // Clear OTP session after successful update
    if (isset($_SESSION['admin_otp_verified'])) {
        unset($_SESSION['admin_otp_verified']);
        unset($_SESSION['admin_otp_email']);
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update employee']);
}

$stmt->close();
$conn->close();
?>