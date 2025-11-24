<?php
require_once '../../../addressDB.php';

header('Content-Type: application/json');

if (isset($_GET['province_id'])) {
    $province_id = intval($_GET['province_id']);
    $sql = "SELECT municipality_id, municipality_name FROM table_municipality WHERE province_id = ? ORDER BY municipality_name";
    $stmt = $addressDB->prepare($sql);
    $stmt->bind_param("i", $province_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $municipalities = [];
    while ($row = $result->fetch_assoc()) {
        $municipalities[] = $row;
    }
    
    echo json_encode($municipalities);
} else {
    echo json_encode([]);
}
?>