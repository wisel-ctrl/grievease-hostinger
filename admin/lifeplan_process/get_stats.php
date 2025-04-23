<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$stats = [
    'total_plans' => 0,
    'active_plans' => 0,
    'pending_payments' => 0,
    'total_revenue' => 0
];

if ($conn) {
    // Total Plans
    $query = "SELECT COUNT(*) as total FROM lifeplan_tb WHERE archived = 'show'";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_plans'] = (int)$row['total'];
        $result->free();
    }

    // Active Plans (status = 'paid' or 'ongoing')
    $query = "SELECT COUNT(*) as total FROM lifeplan_tb WHERE archived = 'show' AND payment_status IN ('paid', 'ongoing')";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['active_plans'] = (int)$row['total'];
        $result->free();
    }

    // Pending Payments (status = 'ongoing')
    $query = "SELECT COUNT(*) as total FROM lifeplan_tb WHERE archived = 'show' AND payment_status = 'ongoing'";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_payments'] = (int)$row['total'];
        $result->free();
    }

    // Total Revenue (sum of amount_paid)
    $query = "SELECT SUM(custom_price) as total FROM lifeplan_tb WHERE archived = 'show'";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_revenue'] = (float)$row['total'] ? (float)$row['total'] : 0;
        $result->free();
    }
}

echo json_encode($stats);
?>