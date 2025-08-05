<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

require_once '../../addressDB.php';
require_once '../booking/sms_notification.php';

date_default_timezone_set('Asia/Manila');

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
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit();
    }
    
    // Start transaction for consistent updates
    $conn->begin_transaction();
    
    try {
        // Initialize variable to track if ID was uploaded
        $id_uploaded = false;
        $validated_id = 'no'; // Default to no unless we have a valid ID upload
        $upload_at = null; // Initialize upload_at
        
        // Handle file upload and ID details if present
        if (isset($_FILES['id-upload']) && $_FILES['id-upload']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['id-upload'];
            $id_type = trim($_POST['idType'] ?? '');
            $id_number = trim($_POST['idNumber'] ?? '');
        
            // Validate ID Type and ID Number
            if (empty($id_type) || empty($id_number)) {
                throw new Exception('ID Type and ID Number are required when uploading an ID.');
            }
        
            // Validate ID Number format (numbers and dashes, 5-20 characters)
            if (!preg_match('/^[0-9\-]{5,20}$/', $id_number)) {
                throw new Exception('ID Number must be 5-20 characters and contain only numbers and dashes.');
            }
        
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $file['tmp_name']);
            finfo_close($file_info);
        
            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG and PNG images are allowed.');
            }
        
            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('File size exceeds 5MB limit.');
            }
        
            // Create uploads directory if it doesn't exist
            $upload_dir = '../../admin/uploads/valid_ids/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
        
            // Generate unique filename
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'id_' . $user_id . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $filename;
        
            // Move the uploaded file
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception('Failed to save uploaded file.');
            }
        
            // Insert or update user's ID details in valid_id_tb table
            $upload_at = date('Y-m-d H:i:s');
        
            $query = "INSERT INTO valid_id_tb (id, image_path, id_type, id_number, upload_at, is_validated, decline_reason, decline_at) 
                      VALUES (?, ?, ?, ?, ?, 'no', NULL, NULL) 
                      ON DUPLICATE KEY UPDATE 
                          image_path = VALUES(image_path),
                          id_type = VALUES(id_type),
                          id_number = VALUES(id_number),
                          upload_at = VALUES(upload_at),
                          is_validated = 'no',
                          decline_reason = NULL,
                          decline_at = NULL";
        
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issss", $user_id, $destination, $id_type, $id_number, $upload_at);
            $stmt->execute();
            $stmt->close();
        
            $validated_id = 'no';
            $id_uploaded = true; // Set flag to indicate ID was uploaded
        }
        
        // Update user's basic information
        $query = "UPDATE users SET 
                  first_name = ?, 
                  middle_name = ?, 
                  last_name = ?, 
                  suffix = ?, 
                  email = ?, 
                  phone_number = ?, 
                  birthdate = ?,
                  validated_id = ?,
                  updated_at = NOW()
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssi", 
            $first_name, 
            $middle_name, 
            $last_name, 
            $suffix, 
            $email, 
            $phone, 
            $birthdate,
            $validated_id,
            $user_id
        );
        
        $stmt->execute();
        $stmt->close();
        
        // Process address information if provided
        if ($region_id && $province_id && $city_id && $barangay_id) {
            // Get the names from respective tables
            $region_name = '';
            $query = "SELECT region_name FROM table_region WHERE region_id = ?";
            $stmt = $addressDB->prepare($query);
            $stmt->bind_param("i", $region_id);
            $stmt->execute();
            $stmt->bind_result($region_name);
            $stmt->fetch();
            $stmt->close();

            $province_name = '';
            $query = "SELECT province_name FROM table_province WHERE province_id = ?";
            $stmt = $addressDB->prepare($query);
            $stmt->bind_param("i", $province_id);
            $stmt->execute();
            $stmt->bind_result($province_name);
            $stmt->fetch();
            $stmt->close();

            $city_name = '';
            $query = "SELECT municipality_name FROM table_municipality WHERE municipality_id = ?";
            $stmt = $addressDB->prepare($query);
            $stmt->bind_param("i", $city_id);
            $stmt->execute();
            $stmt->bind_result($city_name);
            $stmt->fetch();
            $stmt->close();

            $barangay_name = '';
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
        
        // Send SMS notification only if an ID was uploaded
        if ($id_uploaded && $upload_at) {
            $idUploadDetails = [
                'user_id' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'upload_time' => $upload_at
            ];
            
            // Send SMS notification to admin about new ID upload
            $smsResults = sendAdminIDUploadNotification($conn, $idUploadDetails);
            
            // Log SMS results for debugging
            error_log("ID Upload SMS Results: " . print_r($smsResults, true));
        }
        
        // Commit the transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully!',
            'validated_id' => $validated_id
        ]);
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction if something failed
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating profile: ' . $e->getMessage()]);
        exit();
    }
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}
?>