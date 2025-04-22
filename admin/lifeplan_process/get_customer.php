<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$customerId = $_GET['id'];

$stmt = $conn->prepare("
    SELECT id, first_name, middle_name, last_name, suffix, email, phone_number 
    FROM users 
    WHERE id = ?
");
$stmt->bind_param('i', $customerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Customer not found']);
    exit;
}

echo json_encode($result->fetch_assoc());
?>