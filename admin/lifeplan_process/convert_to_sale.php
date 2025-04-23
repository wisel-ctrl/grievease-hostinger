<?php
require_once '../../db_connect.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['lifeplan_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // 1. Create a new sale record with all fields
    $stmt = $conn->prepare("
        INSERT INTO sales_tb (
            customerID,
            fname,
            mname,
            lname,
            suffix,
            email,
            phone,
            fname_deceased,
            mname_deceased,
            lname_deceased,
            suffix_deceased,
            date_of_birth,
            deceased_address,
            date_of_death,
            date_of_burial,
            with_cremate,
            initial_price,
            discounted_price,
            amount_paid,
            balance,
            sold_by,
            payment_method,
            status,
            get_timestamp
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
    ");
    
    $stmt->bind_param(
        "isssssssssssssssddddisss",
        $data['customerID'],
        $data['fname'],
        $data['mname'],
        $data['lname'],
        $data['suffix'],
        $data['email'],
        $data['phone'],
        $data['fname_deceased'],
        $data['mname_deceased'],
        $data['lname_deceased'],
        $data['suffix_deceased'],
        $data['date_of_birth'],
        $data['deceased_address'],
        $data['date_of_death'],
        $data['burial_date'],
        $data['with_cremate'],
        $data['initial_price'],
        $data['discounted_price'],
        $data['amount_paid'],
        $data['balance'],
        $data['sold_by'],
        $data['payment_method']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create sale record: " . $conn->error);
    }
    $stmt->close();
    
    // 2. Update the LifePlan status to 'completed'
    $stmt = $conn->prepare("UPDATE lifeplan_tb SET payment_status = 'completed' WHERE lifeplan_id = ?");
    $stmt->bind_param("i", $data['lifeplan_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update LifePlan status");
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>