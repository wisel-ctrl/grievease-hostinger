<?php
require_once '../../db_connect.php'; // Adjust path as needed to your database connection file

// Check if ID parameter is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid or missing LifePlan ID']);
    exit;
}

$lifeplanId = (int)$_GET['id'];

try {
    // Query to get lifeplan booking details with customer information
    $query = "SELECT 
        lb.*,
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', COALESCE(u.suffix, '')) AS customer_name,
        u.email,
        u.phone_number AS contact_number,
        CONCAT_WS(', ',
            u.street_address,
            u.barangay,
            u.city,
            u.province,
            u.region,
            u.zip_code
        ) AS address,
        s.service_name,
        CONCAT(
            lb.benefeciary_fname, ' ',
            COALESCE(lb.benefeciary_mname, ''), ' ',
            lb.benefeciary_lname, ' ',
            COALESCE(lb.benefeciary_suffix, '')
        ) AS beneficiary_name,
        lb.relationship_to_client,
        lb.phone,
        lb.benefeciary_address,
        v.image_path
    FROM lifeplan_booking_tb lb
    JOIN users u ON lb.customer_id = u.id
    LEFT JOIN services_tb s ON lb.service_id = s.service_id
    LEFT JOIN valid_id_tb v ON v.id = u.id
    WHERE lb.lpbooking_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $lifeplanId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'LifePlan booking not found']);
        exit;
    }
    
    $lifeplanData = $result->fetch_assoc();
    
    // Format dates for display
    $lifeplanData['initial_date_formatted'] = date('M j, Y', strtotime($lifeplanData['initial_date']));
    $lifeplanData['package_price_formatted'] = number_format($lifeplanData['package_price'], 2);
    
    // Return the data as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $lifeplanData
    ]);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>