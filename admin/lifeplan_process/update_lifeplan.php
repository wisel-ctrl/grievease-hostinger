<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get all POST data
$data = $_POST;

try {
    $stmt = $conn->prepare("
        UPDATE lifeplan_tb 
        SET 
            customerID = ?,
            fname = ?,
            mname = ?,
            lname = ?,
            suffix = ?,
            email = ?,
            phone = ?,
            benefeciary_fname = ?,
            benefeciary_mname = ?,
            benefeciary_lname = ?,
            benefeciary_suffix = ?,
            benefeciary_dob = ?,
            benefeciary_address = ?,
            relationship_to_client = ?,
            comaker_fname = ?,
            comaker_mname = ?,
            comaker_lname = ?,
            comaker_suffix = ?,
            comaker_work = ?,
            comaker_idtype = ?,
            comaker_idnumber = ?,
            comaker_address = ?,
            service_id = ?,
            payment_duration = ?,
            custom_price = ?,
            payment_status = ?
        WHERE lifeplan_id = ?
    ");
    
    $stmt->bind_param(
        'issssssssssssssssssssssidsi',
        $data['customerID'],
        $data['fname'],
        $data['mname'],
        $data['lname'],
        $data['suffix'],
        $data['email'],
        $data['phone'],
        $data['benefeciary_fname'],
        $data['benefeciary_mname'],
        $data['benefeciary_lname'],
        $data['benefeciary_suffix'],
        $data['benefeciary_dob'],
        $data['benefeciary_address'],
        $data['relationship_to_client'],
        $data['comaker_fname'],
        $data['comaker_mname'],
        $data['comaker_lname'],
        $data['comaker_suffix'],
        $data['comaker_occupation'],
        $data['comaker_license_type'],
        $data['comaker_license_number'],
        $data['comaker_address'],
        $data['service_id'],
        $data['payment_duration'],
        $data['custom_price'],
        $data['payment_status'],
        $data['lifeplan_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>