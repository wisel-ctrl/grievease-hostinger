<?php
require_once '../../addressDB.php';

header('Content-Type: application/json');

if (isset($_GET['province_id'])) {
    $province_id = (int)$_GET['province_id'];
    $query = "SELECT municipality_id, municipality_name FROM table_municipality WHERE province_id = ?";
    $stmt = $addressDB->prepare($query);
    $stmt->bind_param("i", $province_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cities = [];
    while ($row = $result->fetch_assoc()) {
        $cities[] = $row;
    }
    
    echo json_encode($cities);
    exit();
}

echo json_encode([]);
?>