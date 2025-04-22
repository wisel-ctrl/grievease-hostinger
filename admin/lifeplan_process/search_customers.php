<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$term = '%' . $_GET['term'] . '%';

$stmt = $conn->prepare("
    SELECT id, first_name, middle_name, last_name, suffix, email, phone_number 
    FROM users 
    WHERE (CONCAT(first_name, ' ', last_name) LIKE ? OR email LIKE ?)
    AND user_type = 3
    LIMIT 10
");
$stmt->bind_param('ss', $term, $term);
$stmt->execute();
$result = $stmt->get_result();

$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

echo json_encode($customers);
?>