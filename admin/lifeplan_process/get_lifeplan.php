<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$lifeplanId = $_GET['id'];

$stmt = $conn->prepare("
    SELECT * FROM lifeplan_tb WHERE lifeplan_id = ?
");
$stmt->bind_param('i', $lifeplanId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'LifePlan not found']);
    exit;
}

echo json_encode($result->fetch_assoc());
?>