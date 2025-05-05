<?php
header('Content-Type: application/json');
require_once '../../db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No booking ID provided']);
    exit();
}

$bookingId = $_GET['id'];

try {
    // Prepare the main booking query
    $query = "SELECT 
                    b.*, 
                    CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', COALESCE(u.suffix, '')) AS customer_name, 
                    s.service_name, 
                    i.item_name,
                    i.price as casket_price, 
                    i.inventory_img as casket_image,
                    u.phone_number, 
                    u.email 
                FROM 
                    booking_tb b 
                JOIN 
                    users u ON b.customerID = u.id 
                LEFT JOIN 
                    inventory_tb i ON b.casket_id = i.inventory_id
                LEFT JOIN 
                    services_tb s ON b.service_id = s.service_id 
                WHERE 
                    b.booking_id = ? AND b.service_id IS NULL";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();

    if (!$booking) {
        echo json_encode(['error' => 'Booking not found']);
        exit();
    }

    // Get inclusions if they exist
    $inclusions = [];
    if (!empty($booking['inclusion'])) {
        $inclusionIds = json_decode($booking['inclusion'], true);
        if (is_array($inclusionIds) && !empty($inclusionIds)) {
            $placeholders = implode(',', array_fill(0, count($inclusionIds), '?'));
            $types = str_repeat('i', count($inclusionIds));
            $inclusionQuery = "SELECT * FROM inclusions_tb WHERE inclusion_id IN ($placeholders)";
            
            $inclusionStmt = $conn->prepare($inclusionQuery);
            $inclusionStmt->bind_param($types, ...$inclusionIds);
            $inclusionStmt->execute();
            $inclusionResult = $inclusionStmt->get_result();
            $inclusions = $inclusionResult->fetch_all(MYSQLI_ASSOC);
        }
    }

    // Prepare the response data
    $response = [
        'booking_id' => $booking['booking_id'],
        'customer_name' => $booking['customer_name'],
        'contact_number' => $booking['phone_number'],
        'email' => $booking['email'],
        'address' => $booking['address'],
        'booking_date' => $booking['booking_date'],
        'status' => $booking['status'],
        'service_name' => $booking['service_name'],
        'initial_price' => $booking['initial_price'],
        'amount_paid' => $booking['amount_paid'],
        'deathcert_url' => $booking['deathcert_url'],
        'payment_url' => $booking['payment_url'],
        'receipt_number' => $booking['receipt_number'],
        'reference_code' => $booking['reference_code'],
        'deceased_fname' => $booking['deceased_fname'],
        'deceased_midname' => $booking['deceased_midname'],
        'deceased_lname' => $booking['deceased_lname'],
        'deceased_suffix' => $booking['deceased_suffix'],
        'deceased_address' => $booking['deceased_address'],
        'deceased_birth' => $booking['deceased_birth'],
        'deceased_dodeath' => $booking['deceased_dodeath'],
        'deceased_dateOfBurial' => $booking['deceased_dateOfBurial'],
        'casket_id' => $booking['casket_id'],
        'casket_name' => $booking['casket_name'],
        'casket_description' => $booking['casket_description'],
        'casket_price' => $booking['casket_price'],
        'casket_image' => $booking['casket_image'],
        'flower_id' => $booking['flower_id'],
        'flower_name' => $booking['flower_name'],
        'flower_description' => $booking['flower_description'],
        'flower_price' => $booking['flower_price'],
        'flower_image' => $booking['flower_image'],
        'inclusions' => $inclusions,
        'with_cremate' => $booking['with_cremate'],
        'reason_for_decline' => $booking['reason_for_decline'],
        'booking_notes' => $booking['booking_notes']
    ];

    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>