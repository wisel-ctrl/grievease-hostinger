<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$lifeplanId = $_GET['id'];

$stmt = $conn->prepare("
    SELECT 
        l.*,
        u.birthdate,
        CONCAT_WS(', ',
            COALESCE(u.region, ''),
            COALESCE(u.province, ''),
            COALESCE(u.city, ''),
            COALESCE(u.barangay, ''),
            COALESCE(u.street_address, ''),
            COALESCE(u.zip_code, '')
        ) AS full_address
    FROM lifeplan_tb l
    JOIN users u ON l.customerID = u.id
    WHERE l.lifeplan_id = ?
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