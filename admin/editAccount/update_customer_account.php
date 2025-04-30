<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$userId = $_POST['user_id'] ?? 0;
$firstName = $_POST['first_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$middleName = $_POST['middle_name'] ?? '';
$email = $_POST['email'] ?? '';
$phoneNumber = $_POST['phone_number'] ?? '';
$branchLoc = $_POST['branch_loc'] ?? null;  // Add this line

// Basic validation
if (empty($firstName) || empty($lastName) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
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
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user']);
}

$stmt->close();
$conn->close();
?>