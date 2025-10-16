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
$getCurrentPaymentStmt = null;

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

    // Get service price, service_id, and current payment details
    $getPriceStmt = $conn->prepare("SELECT discounted_price, service_id, amount_paid, balance FROM sales_tb WHERE sales_id = ?");
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
    $originalDiscountedPrice = $serviceData['discounted_price'];
    $currentAmountPaid = $serviceData['amount_paid'];
    $currentBalance = $serviceData['balance'];
    $service_id = $serviceData['service_id'];

    // Calculate chapel cost and update discounted price
    $chapelCost = 0;
    $newDiscountedPrice = $originalDiscountedPrice;
    
    if (!empty($data['used_chapel']) && $data['used_chapel'] === 'Yes' && !empty($data['chapel_days'])) {
        $chapelCost = intval($data['chapel_days']) * 6000;
        $newDiscountedPrice = $originalDiscountedPrice + $chapelCost;
        
        // Recalculate balance based on the new discounted price
        $newBalance = $newDiscountedPrice - $currentAmountPaid;
    } else {
        // Keep the current balance if no chapel cost is added
        $newBalance = $currentBalance;
    }

    // Get casket_id from services_tb
    $getCasketStmt = $conn->prepare("SELECT casket_id FROM services_tb WHERE service_id = ?");
    if ($getCasketStmt === false) {
        throw new Exception('Failed to prepare casket query: ' . $conn->error);
    }
    
    $getCasketStmt->bind_param("i", $service_id);
    if (!$getCasketStmt->execute()) {
        throw new Exception('Failed to get casket ID: ' . $getCasketStmt->error);
    }
    
    $casketResult = $getCasketStmt->get_result();
    if ($casketResult->num_rows > 0) {
        $casketData = $casketResult->fetch_assoc();
        $casket_id = $casketData['casket_id'];
        
        // Update inventory quantity
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
    }

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

    // Add interment_place if provided
    if (!empty($data['interment_place'])) {
        $updateFields[] = "interment_place = ?";
        $types .= "s";
        $params[] = $data['interment_place'];
    }
    
    // Add chapel information
    if (!empty($data['used_chapel'])) {
        $updateFields[] = "use_chapel = ?";
        $types .= "s";
        $params[] = $data['used_chapel'];
    }
    
    if (!empty($data['chapel_days'])) {
        $updateFields[] = "chapel_days = ?";
        $types .= "i";
        $params[] = intval($data['chapel_days']);
    }
    
    // Update discounted price with chapel cost
    $updateFields[] = "discounted_price = ?";
    $types .= "d";
    $params[] = $newDiscountedPrice;
    
    if (!empty($data['balance_settled'])) {
        // If balance is settled, set balance to 0 and amount_paid to the new discounted price
        $updateFields[] = "balance = 0";
        $updateFields[] = "payment_status = 'Fully Paid'";
        $updateFields[] = "amount_paid = ?";
        $types .= "d";
        $params[] = $newDiscountedPrice;
    } else {
        // If balance is NOT settled, update the balance based on the new discounted price
        $updateFields[] = "balance = ?";
        $types .= "d";
        $params[] = $newBalance;
        
        // Update payment status based on the new balance
        if ($newBalance <= 0) {
            $updateFields[] = "payment_status = 'Fully Paid'";
            $updateFields[] = "amount_paid = ?";
            $types .= "d";
            $params[] = $newDiscountedPrice;
        } else if ($newBalance < $newDiscountedPrice) {
            $updateFields[] = "payment_status = 'Partially Paid'";
        } else {
            $updateFields[] = "payment_status = 'Unpaid'";
        }
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

    $analyticsStmt = $conn->prepare("
        INSERT INTO analytics_tb (
            sale_date, 
            discounted_price, 
            casket_id, 
            address, 
            service_id, 
            branch_id, 
            sales_id, 
            amount_paid
        )
        SELECT 
            DATE(get_timestamp) as sale_date,
            discounted_price,
            ? as casket_id,
            deceased_address,
            service_id,
            branch_id,
            sales_id,
            CASE 
                WHEN ? = 1 THEN discounted_price
                ELSE amount_paid
            END as amount_paid
        FROM sales_tb
        WHERE sales_id = ?
    ");

    if ($analyticsStmt === false) {
        throw new Exception('Failed to prepare analytics insert: ' . $conn->error);
    }

    // Handle case where casket_id might not exist
    $casket_id = $casket_id ?? null; // Set to null if not defined
    $balanceSettled = !empty($data['balance_settled']) ? 1 : 0;
    $analyticsStmt->bind_param("iii", $casket_id, $balanceSettled, $data['sales_id']);

    if (!$analyticsStmt->execute()) {
        throw new Exception('Failed to insert analytics data: ' . $analyticsStmt->error);
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = "Service completed successfully" . 
                         (isset($successful_inserts) ? " with $successful_inserts staff records" : "") .
                         ($chapelCost > 0 ? " and chapel cost of ₱" . number_format($chapelCost) : "") .
                         " | New Total: ₱" . number_format($newDiscountedPrice) . 
                         ($newBalance > 0 ? " | Balance: ₱" . number_format($newBalance) : " | Fully Paid");

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
    if (isset($analyticsStmt) && $analyticsStmt instanceof mysqli_stmt) {
        $analyticsStmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo json_encode($response);
exit;
?>