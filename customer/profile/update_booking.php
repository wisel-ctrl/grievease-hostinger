<?php
require_once '../../db_connect.php';

$data = $_POST;

try {
    $query = "UPDATE booking_tb SET 
              service_id = ?, 
              branch_id = ?, 
              deceased_fname = ?, 
              deceased_midname = ?, 
              deceased_lname = ?, 
              deceased_suffix = ?, 
              deceased_birth = ?, 
              deceased_dodeath = ?, 
              deceased_dateOfBurial = ?, 
              deceased_address = ?, 
              with_cremate = ?,
              status = 'Pending' 
              WHERE booking_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "iissssssssii",
        $data['service_id'],
        $data['branch_id'],
        $data['deceased_fname'],
        $data['deceased_midname'],
        $data['deceased_lname'],
        $data['deceased_suffix'],
        $data['deceased_birth'],
        $data['deceased_dodeath'],
        $data['deceased_dateOfBurial'],
        $data['deceased_address'],
        $data['with_cremate'] ?? 0,
        $data['booking_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update booking']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();

?>