<?php
require_once('../../db_connect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$requiredFields = ['user_id', 'first_name', 'last_name', 'email', 'phone_number', 'branch_loc'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        die(json_encode(['success' => false, 'message' => "$field is required"]));
    }
}

$userId = (int)$_POST['user_id'];
$firstName = trim($_POST['first_name']);
$lastName = trim($_POST['last_name']);
$middleName = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : null;
$email = trim($_POST['email']);
$phoneNumber = trim($_POST['phone_number']);
$branchLoc = trim($_POST['branch_loc']); // Changed from (int) to trim() since it's varchar

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Invalid email format']));
}

// Check if email already exists for another user
$emailCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$emailCheck->bind_param("si", $email, $userId);
$emailCheck->execute();
$emailCheck->store_result();

if ($emailCheck->num_rows > 0) {
    die(json_encode(['success' => false, 'message' => 'Email already in use by another account']));
}

// Update user
$updateQuery = "UPDATE users SET 
    first_name = ?,
    last_name = ?,
    middle_name = ?,
    email = ?,
    phone_number = ?,
    branch_loc = ?
    WHERE id = ?";

$stmt = $conn->prepare($updateQuery);
// Changed the bind_param type for branch_loc from 'i' to 's'
$stmt->bind_param("ssssssi", $firstName, $lastName, $middleName, $email, $phoneNumber, $branchLoc, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update customer: ' . $conn->error]);
}
?>