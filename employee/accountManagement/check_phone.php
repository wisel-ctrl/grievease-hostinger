<?php
require_once('../../db_connect.php');

if (!isset($_GET['phone']) || !isset($_GET['current_user'])) {
    die(json_encode(['available' => false, 'message' => 'Missing parameters']));
}

$phone = $_GET['phone'];
$current_user = (int)$_GET['current_user'];

// Clean phone number (remove non-numeric characters)
$cleanedPhone = preg_replace('/[^0-9]/', '', $phone);

// Validate phone format (Philippines mobile number)
if (!preg_match('/^09\d{9}$/', $cleanedPhone)) {
    die(json_encode(['available' => false, 'message' => 'Invalid phone format']));
}

// Check if phone exists (excluding current user)
$stmt = $conn->prepare("SELECT id FROM users WHERE phone_number = ? AND id != ?");
$stmt->bind_param("si", $cleanedPhone, $current_user);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/json');
echo json_encode([
    'available' => $result->num_rows === 0,
    'message' => $result->num_rows === 0 ? 'Phone available' : 'Phone already in use'
]);
?>