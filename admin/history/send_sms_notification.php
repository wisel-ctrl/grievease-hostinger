<?php
header('Content-Type: application/json');
require_once '../../db_connect.php'; // Your DB connection file

$sales_id = isset($_GET['sales_id']) ? intval($_GET['sales_id']) : 0;

if ($sales_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid sales ID']);
    exit;
}

// Fetch sales data
$query = "SELECT s.customerID, s.balance, u.phone_number, u.first_name, u.last_name 
          FROM sales_tb s 
          JOIN users u ON s.customerID = u.id 
          WHERE s.sales_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $phone_number = $row['phone_number'];
    $balance = $row['balance'];
    $customer_name = trim($row['first_name'] . ' ' . $row['last_name']);

    if (empty($phone_number)) {
        echo json_encode(['success' => false, 'message' => 'No phone number found for this customer']);
        exit;
    }

    if ($balance <= 0) {
        echo json_encode(['success' => false, 'message' => 'No outstanding balance']);
        exit;
    }

    // Construct message
    $message = "Dear {$customer_name}, Your outstanding balance for Service #{$sales_id} is ₱" . number_format($balance, 2) . ". Please settle at your earliest convenience.\n\n- GrievEase";

    // Semaphore API
    $api_key = '024cb8782cdb71b2925fb933f6f8635f';
    $sender_name = 'GrievEase';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://semaphore.co/api/v4/messages");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'apikey' => $api_key,
        'number' => $phone_number,
        'message' => $message,
        'sendername' => $sender_name
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($output, true);

    if (isset($response[0]['message_id'])) {
        // Success - you could log this if needed
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'SMS failed: ' . ($response['message'] ?? 'Unknown error')]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Sales record not found']);
}
?>