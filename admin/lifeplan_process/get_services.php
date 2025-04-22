<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$result = $conn->query("SELECT service_id, service_name FROM services_tb");
$services = [];

while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

echo json_encode($services);
?>