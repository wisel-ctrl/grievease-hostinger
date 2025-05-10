<?php
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');

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
    $withCremation = isset($_POST['withCremation']) ? 'yes' : 'no';

    $serviceId = intval($_POST['service_id']);
    $servicePrice = floatval($_POST['service_price']);
    $branchId = intval($_POST['branch_id']);
    $soldBy = intval($_POST['sold_by']);
    $defaultTime = date('Y-m-d H:i:s');
    
    // Calculate end date based on payment term
    $endDate = date('Y-m-d H:i:s', strtotime("+$paymentTerm years"));
    
    // Calculate balance
    $balance = $totalPrice - $amountPaid;
    
    // Determine payment status
    $paymentStatus = ($balance == 0) ? 'paid' : 'ongoing';

    // Validate payment
    if ($amountPaid <= 0) {
        throw new Exception("Amount paid must be greater than 0");
    }

    if ($amountPaid > $totalPrice) {
        throw new Exception("Amount paid cannot exceed total price");
    }

    // Calculate minimum required payment (50% of total)
    $minimumPayment = $totalPrice * 0.05;
    if ($amountPaid < $minimumPayment) {
        throw new Exception("Initial payment must be at least 50% of the total price (₱" . number_format($minimumPayment, 2) . ")");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Single INSERT into lifeplan_tb
        $lifeplanQuery = "INSERT INTO lifeplan_tb (
            service_id, branch_id, sellerID,
            fname, mname, lname, suffix, email, phone,
            initial_date, end_date,
            benefeciary_fname, benefeciary_mname, benefeciary_lname, benefeciary_suffix,
            benefeciary_dob, benefeciary_address, relationship_to_client,
            with_cremate, payment_method, payment_duration,
            initial_price, custom_price, amount_paid, balance, payment_status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($lifeplanQuery);
        $stmt->bind_param("iiisssssssssssssssssiddddss",
            $serviceId, $branchId, $soldBy,
            $clientFirstName, $clientMiddleName, $clientLastName, $clientSuffix, 
            $clientEmail, $clientPhone,
            $defaultTime, $endDate,
            $beneficiaryFirstName, $beneficiaryMiddleName, $beneficiaryLastName, $beneficiarySuffix,
            $beneficiaryDob, $beneficiaryAddress, $beneficiaryRelationship,
            $withCremation, $paymentMethod, $paymentTerm,
            $servicePrice, $totalPrice, $amountPaid, $balance, $paymentStatus,
            $defaultTime
        );
        $stmt->execute();
        $lifeplanId = $conn->insert_id;
        $stmt->close();

        // Commit transaction
        $conn->commit();

        // Generate order ID
        $orderId = "LP-" . str_pad($lifeplanId, 6, "0", STR_PAD_LEFT);

        $response = [
            'success' => true,
            'message' => 'Lifeplan created successfully',
            'order_id' => $orderId,
            'lifeplan_id' => $lifeplanId
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