<?php
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

// Database connection
require_once '../../db_connect.php';

// Set response header
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $requiredFields = [
        'lp-FirstName', 'lp-LastName', 'lp-Phone', 
        'beneficiaryFirstName', 'beneficiaryLastName', 'beneficiaryRelationship',
        'beneficiaryRegion', 'beneficiaryProvince', 'beneficiaryCity', 
        'beneficiaryBarangay', 'beneficiaryAddress', 'beneficiaryZip',
        'paymentMethod', 'totalPrice', 'amountPaid',
        'service_id', 'service_price', 'branch_id', 'sold_by'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize and validate input data
    $clientFirstName = trim($_POST['lp-FirstName']);
    $clientMiddleName = trim($_POST['lp-MiddleName'] ?? '');
    $clientLastName = trim($_POST['lp-LastName']);
    $clientSuffix = trim($_POST['lp-Suffix'] ?? '');
    $clientPhone = trim($_POST['lp-Phone']);
    $clientEmail = trim($_POST['lp-Email'] ?? '');

    $beneficiaryFirstName = trim($_POST['beneficiaryFirstName']);
    $beneficiaryMiddleName = trim($_POST['beneficiaryMiddleName'] ?? '');
    $beneficiaryLastName = trim($_POST['beneficiaryLastName']);
    $beneficiarySuffix = trim($_POST['beneficiarySuffix'] ?? '');
    $beneficiaryDob = !empty($_POST['beneficiaryDateOfBirth']) ? $_POST['beneficiaryDateOfBirth'] : null;
    $beneficiaryRelationship = trim($_POST['beneficiaryRelationship']);
    
    $beneficiaryAddress = trim($_POST['beneficiaryAddress']);
    $beneficiaryRegion = trim($_POST['beneficiaryRegion']);
    $beneficiaryProvince = trim($_POST['beneficiaryProvince']);
    $beneficiaryCity = trim($_POST['beneficiaryCity']);
    $beneficiaryBarangay = trim($_POST['beneficiaryBarangay']);
    $beneficiaryStreet = trim($_POST['beneficiaryStreet']);
    $beneficiaryZip = trim($_POST['beneficiaryZip']);

    $paymentMethod = trim($_POST['paymentMethod']);
    $paymentTerm = intval($_POST['paymentTerm'] ?? 1);
    $totalPrice = floatval($_POST['totalPrice']);
    $amountPaid = floatval($_POST['amountPaid']);
    $withCremation = isset($_POST['withCremation']) ? 1 : 0;

    $serviceId = intval($_POST['service_id']);
    $servicePrice = floatval($_POST['service_price']);
    $branchId = intval($_POST['branch_id']);
    $soldBy = intval($_POST['sold_by']);

    // Validate payment
    if ($amountPaid <= 0) {
        throw new Exception("Amount paid must be greater than 0");
    }

    if ($amountPaid > $totalPrice) {
        throw new Exception("Amount paid cannot exceed total price");
    }

    // Calculate minimum required payment (50% of total)
    $minimumPayment = $totalPrice * 0.5;
    if ($amountPaid < $minimumPayment) {
        throw new Exception("Initial payment must be at least 50% of the total price (₱" . number_format($minimumPayment, 2) . ")");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Insert client information
        $clientQuery = "INSERT INTO clients (
            first_name, middle_name, last_name, suffix, 
            phone, email, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($clientQuery);
        $stmt->bind_param("ssssss", 
            $clientFirstName, $clientMiddleName, $clientLastName, 
            $clientSuffix, $clientPhone, $clientEmail
        );
        $stmt->execute();
        $clientId = $conn->insert_id;
        $stmt->close();

        // 2. Insert beneficiary information
        $beneficiaryQuery = "INSERT INTO beneficiaries (
            first_name, middle_name, last_name, suffix, 
            date_of_birth, relationship, 
            address, region, province, city, barangay, street, zip_code,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($beneficiaryQuery);
        $stmt->bind_param("ssssssssssss", 
            $beneficiaryFirstName, $beneficiaryMiddleName, $beneficiaryLastName, $beneficiarySuffix,
            $beneficiaryDob, $beneficiaryRelationship,
            $beneficiaryAddress, $beneficiaryRegion, $beneficiaryProvince, 
            $beneficiaryCity, $beneficiaryBarangay, $beneficiaryStreet, $beneficiaryZip
        );
        $stmt->execute();
        $beneficiaryId = $conn->insert_id;
        $stmt->close();

        // 3. Insert lifeplan contract
        $contractQuery = "INSERT INTO lifeplan_contracts (
            client_id, beneficiary_id, service_id, 
            branch_id, sold_by, total_price, 
            payment_term, with_cremation, status,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW(), NOW())";
        
        $stmt = $conn->prepare($contractQuery);
        $stmt->bind_param("iiiisiii", 
            $clientId, $beneficiaryId, $serviceId,
            $branchId, $soldBy, $totalPrice,
            $paymentTerm, $withCremation
        );
        $stmt->execute();
        $contractId = $conn->insert_id;
        $stmt->close();

        // 4. Insert initial payment
        $paymentQuery = "INSERT INTO payments (
            contract_id, amount, payment_method, 
            payment_date, status, created_at, updated_at
        ) VALUES (?, ?, ?, NOW(), 'Completed', NOW(), NOW())";
        
        $stmt = $conn->prepare($paymentQuery);
        $stmt->bind_param("ids", $contractId, $amountPaid, $paymentMethod);
        $stmt->execute();
        $stmt->close();

        // 5. If payment term > 1 year, schedule future payments
        if ($paymentTerm > 1) {
            $remainingAmount = $totalPrice - $amountPaid;
            $installmentCount = $paymentTerm * 12 - 1; // minus 1 for the initial payment
            $installmentAmount = $remainingAmount / $installmentCount;
            
            $scheduleQuery = "INSERT INTO payment_schedules (
                contract_id, due_date, amount, status, created_at, updated_at
            ) VALUES (?, ?, ?, 'Pending', NOW(), NOW())";
            
            $stmt = $conn->prepare($scheduleQuery);
            
            // Schedule monthly payments starting next month
            for ($i = 1; $i <= $installmentCount; $i++) {
                $dueDate = date('Y-m-d', strtotime("+$i months"));
                $stmt->bind_param("isd", $contractId, $dueDate, $installmentAmount);
                $stmt->execute();
            }
            
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();

        // Generate order ID
        $orderId = "LP-" . str_pad($contractId, 6, "0", STR_PAD_LEFT);

        $response = [
            'success' => true,
            'message' => 'Lifeplan contract created successfully',
            'order_id' => $orderId,
            'contract_id' => $contractId
        ];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>