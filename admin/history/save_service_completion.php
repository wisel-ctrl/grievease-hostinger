<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../../db_connect.php';

$response = ['success' => false, 'message' => ''];
$stmt = null;
$getPriceStmt = null;
$updateStmt = null;

try {
    // Input validation
    $json = file_get_contents('php://input');
    if ($json === false) {
        throw new Exception('Failed to read input data');
    }
    
    $data = json_decode($json, true);
    if ($data === null) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    if (empty($data['sales_id'])) {
        throw new Exception('Sales ID is required');
    }

    $conn->begin_transaction();

    // Get service price
    $getPriceStmt = $conn->prepare("SELECT discounted_price FROM sales_tb WHERE sales_id = ?");
    if ($getPriceStmt === false) {
        throw new Exception('Failed to prepare price query: ' . $conn->error);
    }
    
    $getPriceStmt->bind_param("i", $data['sales_id']);
    if (!$getPriceStmt->execute()) {
        throw new Exception('Failed to get service price: ' . $getPriceStmt->error);
    }
    
    $priceResult = $getPriceStmt->get_result();
    if ($priceResult->num_rows === 0) {
        throw new Exception('Service not found');
    }
    
    $serviceData = $priceResult->fetch_assoc();
    $discountedPrice = $serviceData['discounted_price'];

    // Insert staff payments if any
    if (!empty($data['staff_data']) && is_array($data['staff_data'])) {
        $stmt = $conn->prepare("INSERT INTO employee_service_payments 
                              (sales_id, employeeID, service_stage, income, notes, payment_date) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            throw new Exception('Failed to prepare SQL statement: ' . $conn->error);
        }

        $successful_inserts = 0;
        foreach ($data['staff_data'] as $staff) {
            if (empty($staff['employee_id']) || empty($staff['salary'])) {
                continue;
            }
            
            $stmt->bind_param("iisdss", 
                $data['sales_id'],
                $staff['employee_id'],
                $data['service_stage'],
                $staff['salary'],
                $data['notes'],
                $data['completion_date']
            );
            
            if ($stmt->execute()) {
                $successful_inserts++;
            } else {
                error_log("Failed to insert record for employee ID {$staff['employee_id']}: " . $stmt->error);
            }
        }
    }

    // Prepare update parameters
    $updateFields = ["status = 'Completed'"];
    $params = [];
    $types = "";
    
    if (!empty($data['balance_settled'])) {
        $updateFields[] = "balance = 0";
        $updateFields[] = "payment_status = 'Fully Paid'";
        $updateFields[] = "amount_paid = ?";
        $types .= "d";
        $params[] = $discountedPrice;
    }
    
    
    $types .= "i";
    $params[] = $data['sales_id'];
    
    $updateSql = "UPDATE sales_tb SET " . implode(", ", $updateFields) . " WHERE sales_id = ?";
    
    $updateStmt = $conn->prepare($updateSql);
    if ($updateStmt === false) {
        throw new Exception('Failed to prepare update statement: ' . $conn->error);
    }
    
    // Dynamic parameter binding
    $bindParams = [$types];
    foreach ($params as &$param) {
        $bindParams[] = &$param;
    }
    call_user_func_array([$updateStmt, 'bind_param'], $bindParams);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update service status: ' . $updateStmt->error);
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = "Service completed successfully" . 
                         (isset($successful_inserts) ? " with $successful_inserts staff records" : "");

} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        $conn->rollback();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Service completion error: ' . $e->getMessage());
    http_response_code(500);
} finally {
    // Close statements only if they exist and haven't been closed already
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($getPriceStmt) && $getPriceStmt instanceof mysqli_stmt) {
        $getPriceStmt->close();
    }
    if (isset($updateStmt) && $updateStmt instanceof mysqli_stmt) {
        $updateStmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo json_encode($response);
exit;
?>