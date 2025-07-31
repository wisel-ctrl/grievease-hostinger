<?php
session_start();
require_once '../../db_connect.php';

header('Content-Type: application/json');

$phone = $_POST['phone'] ?? '';
$user_id = $_POST['user_id'] ?? 0;

if (!$phone || !$user_id) {
    echo json_encode(['exists' => false, 'error' => 'Invalid input']);
    exit;
}

// Check if phone number exists for a different user
$query = "SELECT COUNT(*) as count FROM users WHERE phone_number = ? AND id != ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $phone, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$response = ['exists' => $row['count'] > 0];
echo json_encode($response);

$stmt->close();
$conn->close();
?>