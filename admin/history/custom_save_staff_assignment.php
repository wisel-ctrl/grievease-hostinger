<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Manila');
$current_datetime = date('Y-m-d H:i:s');

function exception_error_handler($severity, $message, $file, $line) {
    error_log("Error: $message in $file on line $line");
    return true;
}
set_error_handler("exception_error_handler");
header('Content-Type: application/json');
require_once '../../db_connect.php';

$response = ['success' => false, 'message' => ''];

try {
    $json = file_get_contents('php://input');
    if ($json === false) {
        throw new Exception('Failed to read input data');
    }
    
    $data = json_decode($json, true);
    if ($data === null) {
        throw new Exception('Invalid JSON data');
    }

    if (empty($data['customsales_id'])) {
        throw new Exception('Custom Sales ID is required');
    }
    
    if (empty($data['staff_data']) || !is_array($data['staff_data'])) {
        throw new Exception('No staff data provided');
    }

    $conn->begin_transaction();

    // Use a single prepared statement and execute multiple times
    $stmt = $conn->prepare("INSERT INTO employee_service_payments 
                           (sales_id, employeeID, service_stage, income, notes, sales_type, payment_date) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        throw new Exception('Failed to prepare SQL statement: ' . $conn->error);
    }

    $successful_inserts = 0;
    foreach ($data['staff_data'] as $staff) {
        if (empty($staff['employee_id']) || empty($staff['salary'])) {
            continue; // Skip invalid entries instead of failing the whole operation
        }
        
        $service_stage = 'initial';
        $notes = $data['notes'] ?? '';
        $service_type = 'custom';
        
        $stmt->bind_param("iisdsss", 
            $data['customsales_id'],
            $staff['employee_id'],
            $service_stage,
            $staff['salary'],
            $notes,
            $service_type,
            $current_datetime
        );
        
        if ($stmt->execute()) {
            $successful_inserts++;
        } else {
            error_log("Failed to insert record for employee ID {$staff['employee_id']}: " . $stmt->error);
        }
    }

    if ($successful_inserts === 0) {
        throw new Exception('No records were inserted');
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = "$successful_inserts staff assignments saved successfully to custom service";

} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        $conn->rollback();
    }
    $response['message'] = $e->getMessage();
    http_response_code(400);
} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo json_encode($response);
exit;
?>