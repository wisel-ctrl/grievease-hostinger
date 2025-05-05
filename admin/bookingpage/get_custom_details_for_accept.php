<?php
require_once '../../db_connect.php'; // Adjust the path as needed

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
    exit;
}

$bookingId = (int)$_GET['id'];

try {
    // Prepare the query
    $query = "SELECT 
                b.*, 
                u.first_name, u.middle_name, u.last_name, u.suffix, 
                s.service_name, 
                i.item_name as casket_name,
                i.price as casket_price, 
                i.inventory_img as casket_image,
                u.phone_number, 
                CONCAT(
                    COALESCE(u.region, ''), ', ',
                    COALESCE(u.province, ''), ', ',
                    COALESCE(u.city, ''), ', ',
                    COALESCE(u.zip_code, ''), ', ',
                    COALESCE(u.barangay, ''), ', ',
                    COALESCE(u.street_address, '')
                ) AS address,
                u.email,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.suffix
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
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }

    $booking = $result->fetch_assoc();

    // Format dates for better readability
    $booking['booking_date_formatted'] = date('M j, Y', strtotime($booking['booking_date']));
    $booking['deceased_birth_formatted'] = $booking['deceased_birth'] ? date('M j, Y', strtotime($booking['deceased_birth'])) : null;
    $booking['deceased_dodeath_formatted'] = $booking['deceased_dodeath'] ? date('M j, Y', strtotime($booking['deceased_dodeath'])) : null;
    $booking['deceased_dateOfBurial_formatted'] = $booking['deceased_dateOfBurial'] ? date('M j, Y', strtotime($booking['deceased_dateOfBurial'])) : null;

    // Format price with currency
    $booking['initial_price_formatted'] = '₱' . number_format($booking['initial_price'], 2);
    $booking['casket_price_formatted'] = $booking['casket_price'] ? '₱' . number_format($booking['casket_price'], 2) : null;

    // Decode inclusions JSON if exists
    $booking['inclusions'] = $booking['inclusion'] ? json_decode($booking['inclusion'], true) : [];

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $booking
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>