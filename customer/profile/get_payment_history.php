<?php
header('Content-Type: application/json');
require_once '../../db_connect.php'; // Include your database connection file

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$packageType = $data['packageType'] ?? '';
$id = $data['id'] ?? 0;

try {
    $query = '';
    
    // Determine the query based on package type
    switch ($packageType) {
        case 'traditional-funeral':
            $query = "SELECT 
                        installment_ID, 
                        Notes, 
                        Payment_Amount, 
                        After_Payment_Balance, 
                        Payment_Timestamp as payment_date
                      FROM `installment_tb` 
                      WHERE sales_ID = ? 
                      ORDER BY Payment_Timestamp DESC";
            break;
            
        case 'custom-package':
            $query = "SELECT 
                        custom_installment_id as installment_ID, 
                        Notes, 
                        Payment_Amount, 
                        After_Payment_Balance, 
                        Payment_Timestamp as payment_date
                      FROM `custom_installment_tb` 
                      WHERE customsales_id = ? 
                      ORDER BY Payment_Timestamp DESC";
            break;
            
        case 'life-plan':
            $query = "SELECT 
                        lplogs_id as installment_ID, 
                        notes as Notes, 
                        installment_amount as Payment_Amount, 
                        new_balance as After_Payment_Balance, 
                        log_date as payment_date
                      FROM `lifeplan_logs_tb` 
                      WHERE lifeplan_id = ? 
                      ORDER BY log_date DESC";
            break;
            
        default:
            throw new Exception('Invalid package type');
    }
    
    // Prepare the statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
    // Bind parameters and execute
    $stmt->bind_param('i', $id);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    
    // Fetch all rows as associative array
    $results = $result->fetch_all(MYSQLI_ASSOC);
    
    // Return the results as JSON
    echo json_encode($results);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}