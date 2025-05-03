<?php
//editAccount/check_edit_phone.php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$phone = $_GET['phone'] ?? '';
$current_user = $_GET['current_user'] ?? 0;
$user_type = (int)($_GET['user_type'] ?? 0); // 2 for employee, 3 for customer

if (empty($phone)) {
    echo json_encode(['exists' => false, 'message' => '']);
    exit;
}

// Check if phone exists for any user of the same type EXCEPT the current one
$stmt = $conn->prepare("SELECT id FROM users WHERE phone_number = ? AND id != ? AND user_type = ?");
$stmt->bind_param("sii", $phone, $current_user, $user_type);
$stmt->execute();
$result = $stmt->get_result();

$exists = $result->num_rows > 0;
$message = $exists ? 
    ($user_type == 2 ? 'Phone already registered to another employee' : 'Phone already registered to another customer') : 
    '';

echo json_encode(['exists' => $exists, 'message' => $message]);
?>