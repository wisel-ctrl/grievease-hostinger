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
$branchLoc = $_POST['branch_loc'] ?? null;

// Basic validation
if (empty($firstName) || empty($lastName) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Get original email
$originalEmailQuery = "SELECT email FROM users WHERE id = ?";
$stmt = $conn->prepare($originalEmailQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$originalUser = $result->fetch_assoc();
$stmt->close();

// Check if email changed and verify OTP
if ($email !== $originalUser['email']) {
    if (!isset($_SESSION['edit_account_otp_verified']) || $_SESSION['edit_account_otp_verified'] !== true || 
        !isset($_SESSION['edit_account_otp_email']) || $_SESSION['edit_account_otp_email'] !== $email) {
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
          WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssi", $firstName, $lastName, $middleName, $email, $phoneNumber, $branchLoc, $userId);
$result = $stmt->execute();

if ($result) {
    // Clear OTP session if email was changed
    if ($email !== $originalUser['email']) {
        unset($_SESSION['edit_account_otp']);
        unset($_SESSION['edit_account_otp_email']);
        unset($_SESSION['edit_account_otp_time']);
        unset($_SESSION['edit_account_otp_verified']);
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>