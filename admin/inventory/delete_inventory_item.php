<?php
include '../../db_connect.php';

header('Content-Type: application/json'); // Set proper content type

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['inventory_id'])) {
    $inventory_id = $_POST['inventory_id'];
    
    try {
        $query = "UPDATE inventory_tb SET status = 0 WHERE inventory_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $inventory_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Item archived successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating status'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>