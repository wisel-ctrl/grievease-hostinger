<?php
require_once '../../db_connect.php';

$sales_id = $_GET['sales_id'];

// Get customerID, branch_id, and amount_paid from sales_tb
$query = "SELECT customerID, branch_id, amount_paid FROM sales_tb WHERE sales_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'customerID' => $row['customerID'],
        'branch_id' => $row['branch_id'],
        'amount_paid' => $row['amount_paid']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Sales record not found'
    ]);
}
?>