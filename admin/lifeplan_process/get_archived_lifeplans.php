<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

try {
    $query = "SELECT 
                lp.lifeplan_id,
                lp.service_id,
                lp.customerID,
                CONCAT_WS(' ',
                    lp.benefeciary_fname,
                    NULLIF(lp.benefeciary_mname, ''),
                    lp.benefeciary_lname,
                    NULLIF(lp.benefeciary_suffix, '')
                ) AS benefeciary_fullname,
                lp.payment_duration,
                lp.custom_price,
                lp.payment_status,
                s.service_name
            FROM 
                lifeplan_tb lp
            JOIN 
                services_tb s ON lp.service_id = s.service_id
            WHERE
                lp.archived = 'hidden'";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception($conn->error);
    }
    
    $archivedPlans = [];
    while ($row = $result->fetch_assoc()) {
        $archivedPlans[] = $row;
    }
    
    echo json_encode($archivedPlans);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>