<?php
header('Content-Type: application/json');

// Database connection
require_once '../../db_connect.php'; // Adjust path as needed

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $requiredFields = [
        'lp-clientFirstName', 'lp-clientLastName', 'lp-clientPhone',
        'beneficiaryFirstName', 'beneficiaryLastName', 'beneficiaryRelationship',
        'beneficiaryAddress', 'lp-service-id', 'lp-branch-id',
        'lp-paymentTerm', 'lp-service-price', 'lp-totalPrice'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Prepare data for insertion
    $service_id = intval($_POST['service_id']);
    $branch_id = intval($_POST['branch_id']);
    $sellerID = intval($_POST['sold_by']); // From your form data
    
    // Client information
    $fname = trim($_POST['lp-clientFirstName']);
    $mname = !empty($_POST['lp-clientMiddleName']) ? trim($_POST['lp-clientMiddleName']) : null;
    $lname = trim($_POST['lp-clientLastName']);
    $suffix = !empty($_POST['lp-clientSuffix']) ? trim($_POST['lp-clientSuffix']) : null;
    $email = !empty($_POST['lp-clientEmail']) ? trim($_POST['lp-clientEmail']) : null;
    $phone = trim($_POST['lp-clientPhone']);
    
    // Beneficiary information
    $beneficiary_fname = trim($_POST['beneficiaryFirstName']);
    $beneficiary_mname = !empty($_POST['beneficiaryMiddleName']) ? trim($_POST['beneficiaryMiddleName']) : null;
    $beneficiary_lname = trim($_POST['beneficiaryLastName']);
    $beneficiary_suffix = !empty($_POST['beneficiarySuffix']) ? trim($_POST['beneficiarySuffix']) : null;
    $beneficiary_dob = !empty($_POST['beneficiaryDateOfBirth']) ? $_POST['beneficiaryDateOfBirth'] : date('Y-m-d');
    $beneficiary_address = trim($_POST['beneficiaryAddress']);
    $relationship_to_client = trim($_POST['beneficiaryRelationship']);
    
    // Service details
    $with_cremate = ($_POST['withCremation'] === 'on') ? 'yes' : 'no';
    $payment_method = $_POST['paymentMethod'] ?? 'Installment';
    $payment_duration = trim($_POST['lp-paymentTerm']);
    
    // Pricing
    $initial_price = floatval($_POST['lp-service-price']);
    $custom_price = floatval($_POST['lp-totalPrice']);
    $amount_paid = !empty($_POST['lp-amountPaid']) ? floatval($_POST['lp-amountPaid']) : 0.00;
    
    // Calculate dates
    $initial_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+{$payment_duration} years"));
    
    // Determine payment status
    $payment_status = ($custom_price - $amount_paid) > 0 ? 'ongoing' : 'paid';

    // Prepare SQL statement
    $stmt = $conn->prepare("
        INSERT INTO lifeplan_tb (
            service_id, branch_id, sellerID, fname, mname, lname, suffix,
            email, phone, initial_date, end_date, benefeciary_fname,
            benefeciary_mname, benefeciary_lname, benefeciary_suffix,
            benefeciary_dob, benefeciary_address, relationship_to_client,
            with_cremate, payment_method, payment_duration, initial_price,
            custom_price, amount_paid, payment_status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    // Bind parameters
    $stmt->bind_param(
        "iiiissssssssssssssssssdddss",
        $service_id, $branch_id, $sellerID, $fname, $mname, $lname, $suffix,
        $email, $phone, $initial_date, $end_date, $beneficiary_fname,
        $beneficiary_mname, $beneficiary_lname, $beneficiary_suffix,
        $beneficiary_dob, $beneficiary_address, $relationship_to_client,
        $with_cremate, $payment_method, $payment_duration, $initial_price,
        $custom_price, $amount_paid, $payment_status
    );

    // Execute the query
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Life plan successfully saved!';
        $response['record_id'] = $stmt->insert_id;
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log($e->getMessage()); // Log the error for debugging
}

$conn->close();
echo json_encode($response);
?>