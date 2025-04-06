<?php
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expenseId = $_POST['expense_id'] ?? null;
    
    if ($expenseId) {
        // Update appearance to 'hidden' instead of deleting
        $stmt = $conn->prepare("UPDATE expense_tb SET appearance = 'hidden' WHERE expense_ID = ?");
        $stmt->bind_param("i", $expenseId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>