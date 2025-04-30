<?php
//profile/fetch_booking_details.php
require_once '../../db_connect.php';

$bookingId = $_GET['booking_id'] ?? 0;

$query = "SELECT b.*, s.service_name, s.selling_price, br.branch_name, 
                 b.deathcert_url, b.payment_url, b.amount_paid, 
                 b.reference_code, b.receipt_number,
                 b.accepted_date, b.decline_date
          FROM booking_tb b
          JOIN services_tb s ON b.service_id = s.service_id
          JOIN branch_tb br ON b.branch_id = br.branch_id
          WHERE b.booking_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    // Construct full URLs
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    
    $deathcert_url = $booking['deathcert_url'] ? $base_url . '/customer/booking/uploads/' . basename($booking['deathcert_url']) : '';
    $payment_url = $booking['payment_url'] ? $base_url . '/customer/booking/uploads/' . basename($booking['payment_url']) : '';
    
    echo json_encode([
        'success' => true, 
        ...$booking,
        'death_certificate' => $deathcert_url,
        'payment_proof' => $payment_url
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}

function generateReceiptNumber($branchId) {
    $prefix = 'RCPT-' . str_pad($branchId, 3, '0', STR_PAD_LEFT) . '-';
    $random = strtoupper(bin2hex(random_bytes(3))); // 6 random alphanumeric chars
    $datePart = date('Ymd');
    return $prefix . $datePart . '-' . $random;
}

$stmt->close();
$conn->close();
?>