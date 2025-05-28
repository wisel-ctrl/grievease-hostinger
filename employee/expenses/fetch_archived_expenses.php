<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$branch = $_SESSION['branch_employee'];
$query = "SELECT expense_ID, expense_name, category, price, date, status 
          FROM expense_tb 
          WHERE branch_id = ? AND appearance = 'hidden'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $branch);
$stmt->execute();
$result = $stmt->get_result();
$expenses = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['expenses' => $expenses]);
$stmt->close();
$conn->close();
?>