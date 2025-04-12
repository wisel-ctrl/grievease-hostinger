<?php
session_start();
require_once '../../db_connect.php';
// Don't set Content-Type header here - only set it before the actual JSON output

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

require_once '../../addressDB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Sanitize and validate inputs
    $first_name = trim($_POST['firstName']);
    $middle_name = trim($_POST['middleName'] ?? '');
    $last_name = trim($_POST['lastName']);
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $birthdate = trim($_POST['dob'] ?? '');
    
    // Address fields - only store IDs in variables for lookup
    $region_id = !empty($_POST['region']) ? intval($_POST['region']) : null;
    $province_id = !empty($_POST['province']) ? intval($_POST['province']) : null;
    $city_id = !empty($_POST['city']) ? intval($_POST['city']) : null;
    $barangay_id = !empty($_POST['barangay']) ? intval($_POST['barangay']) : null;
    $street_address = trim($_POST['street_address'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    
    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit();
    }
    
    // Start transaction for consistent updates
    $conn->begin_transaction();
    
    try {
        // Update user's basic information
        $query = "UPDATE users SET 
                  first_name = ?, 
                  middle_name = ?, 
                  last_name = ?, 
                  suffix = ?, 
                  email = ?, 
                  phone_number = ?, 
                  birthdate = ?,
                  updated_at = NOW()
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssi", 
            $first_name, 
            $middle_name, 
            $last_name, 
            $suffix, 
            $email, 
            $phone, 
            $birthdate,
            $user_id
        );
        
        $stmt->execute();
        $stmt->close();
        
        // Process address information if provided
        if ($region_id && $province_id && $city_id && $barangay_id) {
            // Get the names from respective tables
            $region_name = '';
            // Get region name
            $query = "SELECT region_name FROM table_region WHERE region_id = ?";
            $stmt = $addressDB->prepare($query);
            $stmt->bind_param("i", $region_id);
            $stmt->execute();
            $stmt->bind_result($region_name);
            $stmt->fetch();
            $stmt->close();

            $province_name = '';
            // Get province name
            $query = "SELECT province_name FROM table_province WHERE province_id = ?";
            $stmt = $addressDB->prepare($query);
            $stmt->bind_param("i", $province_id);
            $stmt->execute();
            $stmt->bind_result($province_name);
            $stmt->fetch();
            $stmt->close();

            $city_name = '';
            // Get municipality/city name
            $query = "SELECT municipality_name FROM table_municipality WHERE municipality_id = ?";
            $stmt = $addressDB->prepare($query);
            $stmt->bind_param("i", $city_id);
            $stmt->execute();
            $stmt->bind_result($city_name);
            $stmt->fetch();
            $stmt->close();

            $barangay_name = '';
            // Get barangay name
            $query = "SELECT barangay_name FROM table_barangay WHERE barangay_id = ?";
            $stmt = $addressDB->prepare($query);
            $stmt->bind_param("i", $barangay_id);
            $stmt->execute();
            $stmt->bind_result($barangay_name);
            $stmt->fetch();
            $stmt->close();
            
            // Check if user already has an address record
            $check_address = "SELECT id FROM users WHERE id = ?";
            $stmt = $conn->prepare($check_address);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing address - store only names
                $address_query = "UPDATE users SET 
                                  region = ?,
                                  province = ?,
                                  city = ?,
                                  barangay = ?,
                                  street_address = ?,
                                  zip_code = ?,
                                  updated_at = NOW()
                                  WHERE id = ?";
                
                $stmt = $conn->prepare($address_query);
                $stmt->bind_param("ssssssi", 
                    $region_name,
                    $province_name,
                    $city_name,
                    $barangay_name,
                    $street_address,
                    $zip,
                    $user_id
                );
            } else {
                // Insert new address - store only names
                $address_query = "INSERT INTO users 
                                 (id, region, province, city, barangay,
                                 street_address, zip_code, created_at, updated_at)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $stmt = $conn->prepare($address_query);
                $stmt->bind_param("issssss", 
                    $user_id,
                    $region_name,
                    $province_name,
                    $city_name,
                    $barangay_name,
                    $street_address,
                    $zip
                );
            }
            
            $stmt->execute();
            $stmt->close();
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Instead of redirecting, return a JSON success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully!'
        ]);
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction if something failed
        $conn->rollback();
        
        // Return error JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating profile: ' . $e->getMessage()]);
        exit();
    }
    
} else {
    // Return error for non-POST requests
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}
?>