<?php
require_once('../../db_connect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Update the is_verified field to 1 (unarchive)
$sql = "UPDATE users SET is_verified = 1 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Account unarchived successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error unarchiving account']);
}

$conn->close();
?>