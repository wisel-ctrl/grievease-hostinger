<?php
// unarchive_inventory.php
require_once '../../db_connect.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$inventoryId = $input['inventory_id'] ?? null;

// Validate input
if (!$inventoryId || !is_numeric($inventoryId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid inventory ID']);
    exit();
}

try {
    // Update status to 1 (active)
    $query = "UPDATE inventory_tb SET status = 1 WHERE inventory_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $inventoryId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Inventory item unarchived successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No inventory item found with that ID']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error unarchiving inventory item: ' . $e->getMessage()]);
}
?>