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
                l.installment_amount, 
                l.log_date, 
                l.new_balance,
                l.customer_id,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.suffix
              FROM 
                lifeplan_logs_tb l
              JOIN 
                users u ON l.customer_id = u.id
              WHERE 
                l.lifeplan_id = ?
              ORDER BY 
                l.log_date DESC";
    
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