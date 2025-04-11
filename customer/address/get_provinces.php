<?php
require_once '../../addressDB.php';

header('Content-Type: application/json');

if (isset($_GET['region_id'])) {
    $region_id = (int)$_GET['region_id'];
    $query = "SELECT province_id, province_name FROM table_province WHERE region_id = ?";
    $stmt = $addressDB->prepare($query);
    $stmt->bind_param("i", $region_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $provinces = [];
    while ($row = $result->fetch_assoc()) {
        $provinces[] = $row;
    }
    
    echo json_encode($provinces);
}
?>