<?php
header('Content-Type: application/json');
require_once '../../db_connect.php'; // Adjust path to your DB connection file

$customsales_id = isset($_GET['customsales_id']) ? intval($_GET['customsales_id']) : 0;

if ($customsales_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid custom sales ID']);
    exit;
}

// Fetch custom sales data
$query = "SELECT cs.customsales_id, cs.customer_id, cs.balance, u.phone_number, u.first_name, u.last_name 
          FROM customsales_tb cs 
          JOIN users u ON cs.customer_id = u.id 
          WHERE cs.customsales_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customsales_id);
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
    $message = "Dear {$customer_name}, Your outstanding balance for Custom Service #{$customsales_id} is ₱" . number_format($balance, 2) . ". Please settle at your earliest convenience.\n\n- GrievEase";

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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($output, true);

    if ($http_code == 200 && isset($response[0]['message_id'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'SMS failed: ' . ($response['message'] ?? 'Unknown error')]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Custom sales record not found']);
}
?>