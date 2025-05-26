<?php
header('Content-Type: application/json');
require_once '../../db_connect.php'; // Adjust path as needed

if (!isset($_GET['customsales_id'])) {
    echo json_encode(['success' => false, 'message' => 'No customsales ID provided']);
    exit;
}

$customsalesId = $_GET['customsales_id'];

try {
    // Query to get custom service details
    $query = "SELECT 
                cs.customer_id, 
                cs.fname_deceased, 
                cs.mname_deceased, 
                cs.lname_deceased, 
                cs.suffix_deceased, 
                cs.date_of_bearth, 
                cs.date_of_death, 
                cs.date_of_burial, 
                cs.branch_id, 
                cs.flower_design, 
                cs.inclusion, 
                cs.discounted_price, 
                cs.death_cert_image, 
                cs.with_cremate,
                c.first_name, 
                c.middle_name, 
                c.last_name, 
                c.suffix, 
                c.email, 
                c.phone_number,
                cs.deceased_address
              FROM `customsales_tb` as cs
              LEFT JOIN `users` as c ON cs.customer_id = c.id
              WHERE cs.customsales_id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$customsalesId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
        exit;
    }

    // Format the data for the modal
    $response = [
        'success' => true,
        'customsales_id' => $customsalesId,
        'customerID' => $service['customer_id'],
        
        // Customer Information
        'fname' => $service['fname'] ?? '',
        'mname' => $service['mname'] ?? '',
        'lname' => $service['lname'] ?? '',
        'suffix' => $service['suffix'] ?? '',
        'email' => $service['email'] ?? '',
        'phone' => $service['phone'] ?? '',
        
        // Service Information
        'discounted_price' => $service['discounted_price'] ?? '',
        'flower_design' => $service['flower_design'] ?? '',
        'inclusion' => $service['inclusion'] ?? '',
        'with_cremate' => $service['with_cremate'] ?? 0,
        
        // Deceased Information
        'fname_deceased' => $service['fname_deceased'] ?? '',
        'mname_deceased' => $service['mname_deceased'] ?? '',
        'lname_deceased' => $service['lname_deceased'] ?? '',
        'suffix_deceased' => $service['suffix_deceased'] ?? '',
        'date_of_birth' => $service['date_of_bearth'] ?? '',
        'date_of_death' => $service['date_of_death'] ?? '',
        'date_of_burial' => $service['date_of_burial'] ?? '',
        'deceased_address' => $service['deceased_address'] ?? '',
        'death_cert_image' => $service['death_cert_image'] ?? ''
    ];

    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>