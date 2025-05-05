<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['inventory_id'])) {
    echo json_encode(['success' => false, 'message' => 'Inventory ID is required']);
    exit;
}

$inventoryId = $_POST['inventory_id'];

try {
    $stmt = $conn->prepare("UPDATE inventory_tb SET status = 0 WHERE inventory_id = ?");
    $stmt->bind_param("i", $inventoryId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to archive item']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>