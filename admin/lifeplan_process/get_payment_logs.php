<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['lifeplan_id'])) {
    echo json_encode([]);
    exit();
}

$lifeplanId = $_GET['lifeplan_id'];

try {
    $query = "SELECT 
                installment_amount, 
                log_date, 
                new_balance
              FROM 
                lifeplan_logs_tb 
              WHERE 
                lifeplan_id = ?
              ORDER BY 
                log_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $lifeplanId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode($logs);
} catch (Exception $e) {
    echo json_encode([]);
}

$conn->close();
?>