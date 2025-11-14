<?php
require_once '../../db_connect.php';

$bookingId = $_GET['booking_id'] ?? 0;

$query = "SELECT b.*, 
                 IFNULL(s.service_name, 'Customize Package') as service_name, 
                 IFNULL(s.selling_price, 0) as selling_price, 
                 br.branch_name, 
                 b.deathcert_url, b.payment_url, b.amount_paid, 
                 b.reference_code, b.receipt_number,
                 b.accepted_date, b.decline_date
          FROM booking_tb b
          LEFT JOIN services_tb s ON b.service_id = s.service_id
          JOIN branch_tb br ON b.branch_id = br.branch_id
          WHERE b.booking_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    // Check if this is a custom package
    $is_custom_package = is_null($booking['service_id']);
    
    if ($is_custom_package) {
        // Fetch custom package details from customsales_tb
        $custom_query = "SELECT initial_price, amount_paid, balance 
                        FROM customsales_tb 
                        WHERE customer_id = ? 
                        AND fname_deceased = ? 
                        AND lname_deceased = ? 
                        AND date_of_burial = ?
                        LIMIT 1";
        
        $custom_stmt = $conn->prepare($custom_query);
        $custom_stmt->bind_param("isss", 
            $booking['customerID'], // Use customerID from booking_tb
            $booking['deceased_fname'],
            $booking['deceased_lname'],
            $booking['deceased_dateOfBurial']
        );
        $custom_stmt->execute();
        $custom_result = $custom_stmt->get_result();
        
        if ($custom_result->num_rows > 0) {
            $custom_data = $custom_result->fetch_assoc();
            // Override with custom package data
            $booking['selling_price'] = $custom_data['initial_price'];
            $booking['amount_paid'] = $custom_data['amount_paid'] ?? 0;
            $custom_balance = $custom_data['balance'] ?? $custom_data['initial_price'];
        } else {
            // Fallback if custom data not found
            $booking['selling_price'] = $booking['initial_price'] ?? 0;
            $booking['amount_paid'] = $booking['amount_paid'] ?? 0;
            $custom_balance = $booking['selling_price'] - $booking['amount_paid'];
        }
        $custom_stmt->close();
        
        // Store the custom balance for calculation
        $booking['custom_balance'] = $custom_balance;
    }
    
    // Construct full URLs
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    
    $deathcert_url = $booking['deathcert_url'] ? $base_url . '/customer/booking/uploads/' . basename($booking['deathcert_url']) : '';
    $payment_url = $booking['payment_url'] ? $base_url . '/customer/booking/uploads/' . basename($booking['payment_url']) : '';
    
    echo json_encode([
        'success' => true, 
        ...$booking,
        'death_certificate' => $deathcert_url,
        'payment_proof' => $payment_url,
        'is_custom_package' => $is_custom_package
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