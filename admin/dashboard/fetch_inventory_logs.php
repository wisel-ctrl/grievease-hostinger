<?php
include '../../db_connect.php';

header('Content-Type: application/json');

// Pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM inventory_logs");
    $countStmt->execute();
    $totalResult = $countStmt->get_result()->fetch_assoc();
    $totalItems = $totalResult['total'];
    
    // Get paginated data with your exact query
    $stmt = $conn->prepare("
        SELECT 
            il.log_id,
            il.inventory_id,
            i.item_name,
            il.old_quantity,
            il.new_quantity,
            il.quantity_change,
            il.activity_type,
            il.activity_date,
            b.branch_name
        FROM inventory_logs il
        JOIN inventory_tb i ON il.inventory_id = i.inventory_id
        LEFT JOIN branch_tb b ON il.branch_id = b.branch_id
        ORDER BY il.activity_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'total' => $totalItems,
        'page' => $page,
        'perPage' => $perPage
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>