<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$user_id = $_POST['user_id'] ?? 0;

if (isset($_POST['email'])) {
    $email = $_POST['email'];
    $query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['exists' => $result->num_rows > 0]);
}

if (isset($_POST['phone_number'])) {
    $phone_number = $_POST['phone_number'];
    $query = "SELECT id FROM users WHERE phone_number = ? AND id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $phone_number, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['exists' => $result->num_rows > 0]);
}
?>