<?php
header('Content-Type: application/json');
require_once '../../db_connect.php';

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // Validate required fields
    $requiredFields = [
        'clientFirstName', 'clientLastName', 'clientPhone',
        'beneficiaryFirstName', 'beneficiaryLastName', 'beneficiaryRelationship',
        'beneficiaryAddress', 'service_id', 'branch_id',
        'paymentTerm', 'service_price', 'totalPrice'
    ];

    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Prepare data
    $service_id = intval($input['service_id']);
    $branch_id = intval($input['branch_id']);
    $sellerID = intval($input['sold_by']);
    
    // Client information
    $fname = trim($input['clientFirstName']);
    $mname = !empty($input['clientMiddleName']) ? trim($input['clientMiddleName']) : null;
    $lname = trim($input['clientLastName']);
    $suffix = !empty($input['clientSuffix']) ? trim($input['clientSuffix']) : null;
    $email = !empty($input['clientEmail']) ? trim($input['clientEmail']) : null;
    $phone = trim($input['clientPhone']);
    
    // Beneficiary information (note spelling matches database)
    $beneficiary_fname = trim($input['beneficiaryFirstName']);
    $beneficiary_mname = !empty($input['beneficiaryMiddleName']) ? trim($input['beneficiaryMiddleName']) : null;
    $beneficiary_lname = trim($input['beneficiaryLastName']);
    $beneficiary_suffix = !empty($input['beneficiarySuffix']) ? trim($input['beneficiarySuffix']) : null;
    $beneficiary_dob = !empty($input['beneficiaryDateOfBirth']) ? $input['beneficiaryDateOfBirth'] : date('Y-m-d');
    $beneficiary_address = trim($input['beneficiaryAddress']);
    $relationship_to_client = trim($input['beneficiaryRelationship']);
    
    // Service details
    $with_cremate = ($input['withCremation'] === 'on') ? 'yes' : 'no';
    $payment_method = $input['paymentMethod'] ?? 'Installment';
    $payment_duration = trim($input['paymentTerm']);
    
    // Pricing
    $initial_price = floatval($input['service_price']);
    $custom_price = floatval($input['totalPrice']);
    $amount_paid = !empty($input['amountPaid']) ? floatval($input['amountPaid']) : 0.00;
    
    // Calculate dates and status
    $initial_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+{$payment_duration} years"));
    $payment_status = ($custom_price - $amount_paid) > 0 ? 'ongoing' : 'paid';

    $stmt = $conn->prepare("
        INSERT INTO lifeplan_tb (
            service_id, branch_id, sellerID, fname, mname, lname, suffix,
            email, phone, initial_date, end_date, benefeciary_fname,
            benefeciary_mname, benefeciary_lname, benefeciary_suffix,
            benefeciary_dob, benefeciary_address, relationship_to_client,
            with_cremate, payment_method, payment_duration, initial_price,
            custom_price, amount_paid, payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiiissssssssssssssssssdddss",
        $service_id, $branch_id, $sellerID, $fname, $mname, $lname, $suffix,
        $email, $phone, $initial_date, $end_date, $beneficiary_fname,
        $beneficiary_mname, $beneficiary_lname, $beneficiary_suffix,
        $beneficiary_dob, $beneficiary_address, $relationship_to_client,
        $with_cremate, $payment_method, $payment_duration, $initial_price,
        $custom_price, $amount_paid, $payment_status
    );

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
    error_log($e->getMessage());
}

echo json_encode($response);
?>