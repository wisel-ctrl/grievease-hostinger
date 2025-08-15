<?php
header('Content-Type: application/json');

require_once "../../db_connect.php";

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Query to fetch add-ons data
$sql = "SELECT 
            a.addOns_id,
            a.addOns_name,
            a.icon,
            b.branch_name,
            a.price,
            a.status,
            a.creation_date,
            a.update_date
        FROM AddOnsService_tb AS a
        JOIN branch_tb AS b 
            ON a.branch_id = b.branch_id
        WHERE a.status IN ('active', 'inactive')";

$result = $conn->query($sql);

if (!$result) {
    die(json_encode(['error' => 'Query failed: ' . $conn->error]));
}

$addOns = [];
while ($row = $result->fetch_assoc()) {
    $addOns[] = $row;
}

$conn->close();

echo json_encode(['data' => $addOns]);
?>