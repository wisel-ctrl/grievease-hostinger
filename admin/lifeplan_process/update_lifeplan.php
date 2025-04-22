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
            service_id = ?,
            payment_duration = ?,
            custom_price = ?,
            payment_status = ?
        WHERE lifeplan_id = ?
    ");
    
    $stmt->bind_param(
        'issssssssssssssidsi',
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