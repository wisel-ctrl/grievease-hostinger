<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../../db_connect.php';

$response = ['success' => false, 'message' => ''];

// Logger function
function logData($message, $data = []) {
    $logFile = __DIR__ . '/lifeplan_checkout_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";

    if (!empty($data)) {
        $logEntry .= print_r($data, true) . "\n";
    }

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

try {
    // Log incoming POST data
    logData('Received lifeplan checkout submission', $_POST);

    // Validate required fields
    $requiredFields = [
        'clientFirstName', 'clientLastName', 'clientPhone',
        'beneficiaryFirstName', 'beneficiaryLastName', 'beneficiaryRelationship',
        'beneficiaryAddress', 'service_id', 'branch_id',
        'paymentTerm', 'service_price', 'totalPrice'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Prepare data
    $service_id = intval($_POST['service_id']);
    $branch_id = intval($_POST['branch_id']);
    $sellerID = 1; // Assuming admin ID is 1

    // Client information
    $fname = trim($_POST['clientFirstName']);
    $mname = !empty($_POST['clientMiddleName']) ? trim($_POST['clientMiddleName']) : null;
    $lname = trim($_POST['clientLastName']);
    $suffix = !empty($_POST['clientSuffix']) ? trim($_POST['clientSuffix']) : null;
    $email = !empty($_POST['clientEmail']) ? trim($_POST['clientEmail']) : null;
    $phone = trim($_POST['clientPhone']);

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
    $payment_duration = trim($_POST['paymentTerm']);

    // Pricing
    $initial_price = floatval($_POST['service_price']);
    $custom_price = floatval($_POST['totalPrice']);
    $amount_paid = !empty($_POST['amountPaid']) ? floatval($_POST['amountPaid']) : 0.00;

    // Calculate dates and status
    $initial_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+{$payment_duration} years"));
    $payment_status = ($custom_price - $amount_paid) > 0 ? 'ongoing' : 'paid';

    // Log prepared data
    logData('Prepared data for insertion', [
        'service_id' => $service_id,
        'branch_id' => $branch_id,
        'client_name' => "$fname $mname $lname $suffix",
        'beneficiary_name' => "$beneficiary_fname $beneficiary_mname $beneficiary_lname $beneficiary_suffix",
        'initial_price' => $initial_price,
        'custom_price' => $custom_price,
        'amount_paid' => $amount_paid,
        'payment_status' => $payment_status
    ]);

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
        "iiissssssssssssssssiddds",
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
        logData('Insert successful', ['insert_id' => $stmt->insert_id]);
    } else {
        logData('Insert failed', ['error' => $stmt->error]);
        throw new Exception("Database error: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    logData('Exception caught', ['error' => $e->getMessage()]);
}

$conn->close();
echo json_encode($response);
?>
