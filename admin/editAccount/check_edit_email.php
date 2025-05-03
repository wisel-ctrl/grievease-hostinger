<?php
//editAccount/check_edit_email.php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$email = $_GET['email'] ?? '';
$current_user = (int)($_GET['current_user'] ?? 0);
$user_type = (int)($_GET['user_type'] ?? 0); // 2 for employee, 3 for customer

if (empty($email)) {
    echo json_encode(['exists' => false, 'message' => '']);
    exit;
}

// Check if email exists for any user of the same type EXCEPT the current one
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND user_type = ? LIMIT 1");
$stmt->bind_param("sii", $email, $current_user, $user_type);
$stmt->execute();
$result = $stmt->get_result();

$exists = $result->num_rows > 0;
$message = $exists ? 
    ($user_type == 2 ? 'Email already registered to another employee' : 'Email already registered to another customer') : 
    '';

echo json_encode(['exists' => $exists, 'message' => $message]);
?>