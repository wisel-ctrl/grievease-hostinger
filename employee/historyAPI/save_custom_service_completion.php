<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../../db_connect.php';

$response = ['success' => false, 'message' => ''];
$stmt = null;
$getPriceStmt = null;
$updateStmt = null;
$getServiceStmt = null;
$getCasketStmt = null;
$updateInventoryStmt = null;
$insertAnalyticsStmt = null;

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

    if (empty($data['customsales_id'])) {
        throw new Exception('Custom Sales ID is required');
    }

    $conn->begin_transaction();

    // Get service price and casket_id
    $getPriceStmt = $conn->prepare("SELECT discounted_price, casket_id FROM customsales_tb WHERE customsales_id = ?");
    if ($getPriceStmt === false) {
        throw new Exception('Failed to prepare price query: ' . $conn->error);
    }
    
    $getPriceStmt->bind_param("i", $data['customsales_id']);
    if (!$getPriceStmt->execute()) {
        throw new Exception('Failed to get service price: ' . $getPriceStmt->error);
    }
    
    $priceResult = $getPriceStmt->get_result();
    if ($priceResult->num_rows === 0) {
        throw new Exception('Service not found');
    }
    
    $serviceData = $priceResult->fetch_assoc();
    $discountedPrice = $serviceData['discounted_price'];
    $casket_id = $serviceData['casket_id'];
    
    // Update inventory quantity if casket exists
    if (!empty($casket_id)) {
        $updateInventoryStmt = $conn->prepare("UPDATE inventory_tb SET quantity = quantity - 1 WHERE inventory_id = ?");
        if ($updateInventoryStmt === false) {
            throw new Exception('Failed to prepare inventory update: ' . $conn->error);
        }
        
        $updateInventoryStmt->bind_param("i", $casket_id);
        if (!$updateInventoryStmt->execute()) {
            throw new Exception('Failed to update inventory: ' . $updateInventoryStmt->error);
        }
    }

    // Insert staff payments if any
    if (!empty($data['staff_data']) && is_array($data['staff_data'])) {
        $stmt = $conn->prepare("INSERT INTO employee_service_payments 
                              (sales_id, employeeID, service_stage, income, notes, payment_date, sales_type) 
                              VALUES (?, ?, ?, ?, ?, ?, 'custom')");
        
        if ($stmt === false) {
            throw new Exception('Failed to prepare SQL statement: ' . $conn->error);
        }

        $successful_inserts = 0;
        foreach ($data['staff_data'] as $staff) {
            if (empty($staff['employee_id']) || empty($staff['salary'])) {
                continue;
            }
            
            $stmt->bind_param("iisdss", 
                $data['customsales_id'],
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
    $params[] = $data['customsales_id'];
    
    $updateSql = "UPDATE customsales_tb SET " . implode(", ", $updateFields) . " WHERE customsales_id = ?";
    
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

    // Get additional sales data needed for analytics
    $getSalesDataStmt = $conn->prepare("
        SELECT sale_date, deceased_address, branch_id, amount_paid 
        FROM customsales_tb 
        WHERE customsales_id = ?
    ");
    if ($getSalesDataStmt === false) {
        throw new Exception('Failed to prepare sales data query: ' . $conn->error);
    }
    
    $getSalesDataStmt->bind_param("i", $data['customsales_id']);
    if (!$getSalesDataStmt->execute()) {
        throw new Exception('Failed to get sales data: ' . $getSalesDataStmt->error);
    }
    
    $salesDataResult = $getSalesDataStmt->get_result();
    if ($salesDataResult->num_rows === 0) {
        throw new Exception('Sales data not found');
    }
    
    $salesData = $salesDataResult->fetch_assoc();
    $amount_paid = !empty($data['balance_settled']) ? $discountedPrice : $salesData['amount_paid'];
    
    // Insert into analytics_tb
    $insertAnalyticsStmt = $conn->prepare("
        INSERT INTO analytics_tb (
            sale_date,
            discounted_price,
            casket_id,
            address,
            branch_id,
            sales_id,
            amount_paid,
            sales_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'custom')
    ");
    if ($insertAnalyticsStmt === false) {
        throw new Exception('Failed to prepare analytics insert: ' . $conn->error);
    }
    
    $insertAnalyticsStmt->bind_param(
        "sdisids",
        $salesData['sale_date'],
        $discountedPrice,
        $casket_id,
        $salesData['deceased_address'],
        $salesData['branch_id'],
        $data['customsales_id'],
        $amount_paid
    );
    
    if (!$insertAnalyticsStmt->execute()) {
        throw new Exception('Failed to insert analytics data: ' . $insertAnalyticsStmt->error);
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
    if (isset($getServiceStmt) && $getServiceStmt instanceof mysqli_stmt) {
        $getServiceStmt->close();
    }
    if (isset($getCasketStmt) && $getCasketStmt instanceof mysqli_stmt) {
        $getCasketStmt->close();
    }
    if (isset($updateInventoryStmt) && $updateInventoryStmt instanceof mysqli_stmt) {
        $updateInventoryStmt->close();
    }
    if (isset($insertAnalyticsStmt) && $insertAnalyticsStmt instanceof mysqli_stmt) {
        $insertAnalyticsStmt->close();
    }
    if (isset($getSalesDataStmt) && $getSalesDataStmt instanceof mysqli_stmt) {
        $getSalesDataStmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo json_encode($response);
exit;
?>