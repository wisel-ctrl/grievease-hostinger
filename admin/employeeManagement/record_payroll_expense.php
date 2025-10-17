<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

date_default_timezone_set('Asia/Manila');

require_once('../../db_connect.php');

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Get the POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log the received data for debugging
error_log("Received payroll data: " . print_r($data, true));

// Validate required fields
if (!isset($data['branch_id']) || !isset($data['grand_total'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: branch_id and grand_total']);
    exit;
}

$branch_id = intval($data['branch_id']);
$grand_total = floatval($data['grand_total']);
$start_date = isset($data['start_date']) ? $data['start_date'] : null;
$end_date = isset($data['end_date']) ? $data['end_date'] : null;

// Validate data
if ($branch_id <= 0 || $grand_total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch_id or grand_total']);
    exit;
}

// Create expense name based on date range or current month
if ($start_date && $end_date) {
    // FIXED: Handle date formatting properly
    $start_date_obj = DateTime::createFromFormat('Y-m-d', $start_date);
    $end_date_obj = DateTime::createFromFormat('Y-m-d', $end_date);
    
    if (!$start_date_obj || !$end_date_obj) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }
    
    $expense_name = $start_date_obj->format('M j') . ' - ' . $end_date_obj->format('M j, Y') . ' Salary';
    $notes = "Salary for period " . $start_date . " to " . $end_date . " for branch " . $branch_id;
} else {
    $current_month = date('F Y');
    $expense_name = $current_month . "'s salary'";
    $notes = "This is the Salary for " . $current_month . " for branch " . $branch_id;
}

$date = date('Y-m-d H:i:s');

try {
    // Prepare SQL statement with your specific column names
    $sql = "INSERT INTO expense_tb (expense_name, category, branch_id, status, price, notes, date) 
            VALUES (?, 'Salaries', ?, 'paid', ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    // Bind parameters
    $stmt->bind_param("sidss", $expense_name, $branch_id, $grand_total, $notes, $date);
    
    // Execute the statement
    if ($stmt->execute()) {
        $expense_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Payroll expense recorded successfully!',
            'expense_id' => $expense_id,
            'branch_id' => $branch_id,
            'expense_name' => $expense_name,
            'price' => $grand_total,
            'notes' => $notes
        ]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Payroll expense error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error recording expense: ' . $e->getMessage()]);
}

$conn->close();
?>