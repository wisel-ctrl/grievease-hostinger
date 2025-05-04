<?php
require_once '../../db_connect.php';

// Check if the ID parameter is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid lifeplan ID']);
    exit;
}

$lifeplanId = $_GET['id'];

// Query to get lifeplan details with all necessary information
$query = "SELECT 
            lb.*,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.suffix,
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
        WHERE lb.lpbooking_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lifeplanId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Lifeplan not found']);
    exit;
}

$lifeplanData = $result->fetch_assoc();

// Format dates for better readability
$initialDate = new DateTime($lifeplanData['initial_date']);
$endDate = new DateTime($lifeplanData['end_date']);

$lifeplanData['initial_date_formatted'] = $initialDate->format('M j, Y');
$lifeplanData['end_date_formatted'] = $endDate->format('M j, Y');
$lifeplanData['package_price_formatted'] = number_format($lifeplanData['package_price'], 2);

// Prepare the response
$response = [
    'success' => true,
    'data' => $lifeplanData
];

header('Content-Type: application/json');
echo json_encode($response);
?>