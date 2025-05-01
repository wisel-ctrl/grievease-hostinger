<?php
require_once('../../db_connect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$requiredFields = ['user_id', 'first_name', 'last_name', 'email', 'phone_number', 'branch_loc', 'current_user'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field])) {
        die(json_encode(['success' => false, 'message' => "$field is required"]));
    }
}

$userId = (int)$_POST['user_id'];
$currentUser = (int)$_POST['current_user'];
$firstName = trim($_POST['first_name']);
$lastName = trim($_POST['last_name']);
$middleName = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : null;
$email = trim($_POST['email']);
$phoneNumber = trim($_POST['phone_number']);
$branchLoc = trim($_POST['branch_loc']);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Invalid email format']));
}

// Check if email already exists for another user
$emailCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$emailCheck->bind_param("si", $email, $currentUser);
$emailCheck->execute();
$emailResult = $emailCheck->get_result();

if ($emailResult->num_rows > 0) {
    die(json_encode(['success' => false, 'message' => 'Email already in use by another account']));
}

// Check if phone number already exists for another user
$phoneCheck = $conn->prepare("SELECT id FROM users WHERE phone_number = ? AND id != ?");
$phoneCheck->bind_param("si", $phoneNumber, $currentUser);
$phoneCheck->execute();
$phoneResult = $phoneCheck->get_result();

if ($phoneResult->num_rows > 0) {
    die(json_encode(['success' => false, 'message' => 'Phone number already in use by another account']));
}

// Start transaction
$conn->begin_transaction();

try {
    // Update user
    $updateQuery = "UPDATE users SET 
        first_name = ?,
        last_name = ?,
        middle_name = ?,
        email = ?,
        phone_number = ?,
        branch_loc = ?,
        is_verified = 1
        WHERE id = ?";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssssi", $firstName, $lastName, $middleName, $email, $phoneNumber, $branchLoc, $userId);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update customer: ' . $conn->error);
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>