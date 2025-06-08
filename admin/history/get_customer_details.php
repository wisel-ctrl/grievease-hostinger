<?php
require_once '../../db_connect.php';

$customerId = $_GET['id'] ?? 0;

$query = "SELECT id, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name, ' ', COALESCE(suffix, '')) AS full_name, 
          email, phone_number 
          FROM users 
          WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'full_name' => trim($customer['full_name']),
        'email' => $customer['email'],
        'phone' => $customer['phone_number']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Customer not found'
    ]);
}
?>