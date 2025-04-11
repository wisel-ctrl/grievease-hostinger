<?php
require_once '../../addressDB.php';

header('Content-Type: application/json');

if (isset($_GET['city_id'])) {
    $city_id = (int)$_GET['city_id'];
    $query = "SELECT barangay_id, barangay_name FROM table_barangay WHERE municipality_id = ?";
    $stmt = $addressDB->prepare($query);
    $stmt->bind_param("i", $city_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $barangays = [];
    while ($row = $result->fetch_assoc()) {
        $barangays[] = $row;
    }
    
    echo json_encode($barangays);
}
?>