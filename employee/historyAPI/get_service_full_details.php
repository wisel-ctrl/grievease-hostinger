<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['sales_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sales ID is required']);
    exit;
}

$sales_id = $_GET['sales_id'];

try {
    // Get basic service information
    $query = "SELECT s.*, 
                     u.first_name as fname, u.middle_name as mname, u.last_name as lname, u.suffix,
                     b.branch_name,
                     sv.service_name
              FROM sales_tb s
              LEFT JOIN users u ON s.customer_id = u.id
              LEFT JOIN branches b ON s.branch_id = b.id
              LEFT JOIN services sv ON s.service_id = sv.id
              WHERE s.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $sales_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();

    if (!$service) {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
        exit;
    }

    // Get initial staff information
    $initial_staff_query = "SELECT 
        GROUP_CONCAT(DISTINCT CASE WHEN p.position = 'Embalmer' THEN CONCAT(e.first_name, ' ', e.last_name) END) as embalmers,
        GROUP_CONCAT(DISTINCT CASE WHEN p.position = 'Driver' THEN CONCAT(e.first_name, ' ', e.last_name) END) as drivers,
        GROUP_CONCAT(DISTINCT CASE WHEN p.position = 'Personnel' THEN CONCAT(e.first_name, ' ', e.last_name) END) as personnel,
        is.date,
        is.notes
        FROM initial_staff is
        LEFT JOIN personnel p ON is.personnel_id = p.id
        LEFT JOIN employees e ON p.employee_id = e.id
        WHERE is.sales_id = ?
        GROUP BY is.id";

    $stmt = $conn->prepare($initial_staff_query);
    $stmt->bind_param("i", $sales_id);
    $stmt->execute();
    $initial_staff_result = $stmt->get_result();
    $initial_staff = $initial_staff_result->fetch_assoc();

    if ($initial_staff) {
        $initial_staff['embalmers'] = $initial_staff['embalmers'] ? explode(',', $initial_staff['embalmers']) : [];
        $initial_staff['drivers'] = $initial_staff['drivers'] ? explode(',', $initial_staff['drivers']) : [];
        $initial_staff['personnel'] = $initial_staff['personnel'] ? explode(',', $initial_staff['personnel']) : [];
    }

    // Get burial staff information
    $burial_staff_query = "SELECT 
        GROUP_CONCAT(DISTINCT CASE WHEN p.position = 'Driver' THEN CONCAT(e.first_name, ' ', e.last_name) END) as drivers,
        GROUP_CONCAT(DISTINCT CASE WHEN p.position = 'Personnel' THEN CONCAT(e.first_name, ' ', e.last_name) END) as personnel,
        bs.date,
        bs.notes
        FROM burial_staff bs
        LEFT JOIN personnel p ON bs.personnel_id = p.id
        LEFT JOIN employees e ON p.employee_id = e.id
        WHERE bs.sales_id = ?
        GROUP BY bs.id";

    $stmt = $conn->prepare($burial_staff_query);
    $stmt->bind_param("i", $sales_id);
    $stmt->execute();
    $burial_staff_result = $stmt->get_result();
    $burial_staff = $burial_staff_result->fetch_assoc();

    if ($burial_staff) {
        $burial_staff['drivers'] = $burial_staff['drivers'] ? explode(',', $burial_staff['drivers']) : [];
        $burial_staff['personnel'] = $burial_staff['personnel'] ? explode(',', $burial_staff['personnel']) : [];
    }

    // Calculate outstanding balance
    $balance_query = "SELECT 
        s.total_amount - COALESCE(SUM(p.amount), 0) as balance
        FROM sales_tb s
        LEFT JOIN payments p ON s.id = p.sales_id
        WHERE s.id = ?
        GROUP BY s.id";

    $stmt = $conn->prepare($balance_query);
    $stmt->bind_param("i", $sales_id);
    $stmt->execute();
    $balance_result = $stmt->get_result();
    $balance = $balance_result->fetch_assoc();

    // Prepare response
    $response = [
        'success' => true,
        'sales_id' => $service['id'],
        'fname' => $service['fname'],
        'mname' => $service['mname'],
        'lname' => $service['lname'],
        'suffix' => $service['suffix'],
        'service_name' => $service['service_name'],
        'branch_name' => $service['branch_name'],
        'date_of_burial' => $service['date_of_burial'],
        'status' => $service['status'],
        'balance' => $balance['balance'] ?? 0,
        'initial_staff' => $initial_staff,
        'burial_staff' => $burial_staff
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 