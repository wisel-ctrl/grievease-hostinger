<?php
require_once '../../../addressDB.php';

header('Content-Type: application/json');

if (isset($_GET['municipality_id'])) {
    $municipality_id = intval($_GET['municipality_id']);
    $sql = "SELECT barangay_id, barangay_name FROM table_barangay WHERE municipality_id = ? ORDER BY barangay_name";
    $stmt = $addressDB->prepare($sql);
    $stmt->bind_param("i", $municipality_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $barangays = [];
    while ($row = $result->fetch_assoc()) {
        $barangays[] = $row;
    }
    
    echo json_encode($barangays);
} else {
    echo json_encode([]);
}
?>