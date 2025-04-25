<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['inventory_id'])) {
    echo json_encode(['success' => false, 'message' => 'Inventory ID is required']);
    exit;
}

try {
    $inventory_id = $_POST['inventory_id'];
    
    // Update status to 0 (archived) instead of deleting
    $stmt = $conn->prepare("UPDATE inventory_tb SET status = 0 WHERE inventory_id = ?");
    $stmt->bind_param("i", $inventory_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Item archived successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No item found with that ID']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>