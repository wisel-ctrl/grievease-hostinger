<?php
require_once '../../../addressDB.php';

header('Content-Type: application/json');

if (isset($_GET['region_id'])) {
    $region_id = intval($_GET['region_id']);
    $sql = "SELECT province_id, province_name FROM table_province WHERE region_id = ? ORDER BY province_name";
    $stmt = $addressDB->prepare($sql);
    $stmt->bind_param("i", $region_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $provinces = [];
    while ($row = $result->fetch_assoc()) {
        $provinces[] = $row;
    }
    
    echo json_encode($provinces);
} else {
    echo json_encode([]);
}
?>