<?php
require_once('../../db_connect.php');

header('Content-Type: application/json');

// Debugging: Log the received parameters
error_log("Received email: " . $_GET['email'] . ", current_user: " . $_GET['current_user']);

if (!isset($_GET['email']) || !isset($_GET['current_user'])) {
    die(json_encode(['available' => false, 'message' => 'Missing parameters']));
}

$email = trim($_GET['email']);
$current_user = (int)$_GET['current_user'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['available' => false, 'message' => 'Invalid email format']));
}

// Prepare and execute the query
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die(json_encode(['available' => false, 'message' => 'Database error']));
}

$stmt->bind_param("si", $email, $current_user);
if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    die(json_encode(['available' => false, 'message' => 'Database error']));
}

$result = $stmt->get_result();
$num_rows = $result->num_rows;

error_log("Query returned $num_rows rows for email: $email");

echo json_encode([
    'available' => $num_rows === 0,
    'message' => $num_rows === 0 ? 'Email available' : 'Email already in use'
]);

$stmt->close();
?>