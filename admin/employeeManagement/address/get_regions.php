<?php
require_once '../../../addressDB.php';

header('Content-Type: application/json');

$sql = "SELECT region_id, region_name FROM table_region ORDER BY region_name";
$result = $addressDB->query($sql);

$regions = [];
while ($row = $result->fetch_assoc()) {
    $regions[] = $row;
}

echo json_encode($regions);
?>