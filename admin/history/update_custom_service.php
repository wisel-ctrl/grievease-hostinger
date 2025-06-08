<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../db_connect.php';

$response = ['success' => false, 'message' => ''];

// Define upload settings
$allowedDeathCertTypes = ['jpg', 'jpeg', 'png', 'pdf'];
$maxDeathCertSize = 500 * 1024 * 1024; // 500MB
$deathCertUploadPath = '../../customer/booking/uploads/';

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get form data
    $customsales_id = $_POST['customsales_id'] ?? null;
    $customer_id = $_POST['customer_id'] ?? null;

    // Validate required fields
    if (empty($customsales_id) || empty($customer_id)) {
        throw new Exception('Missing required fields');
    }

    // Prepare data for update
    $fname_deceased = trim($_POST['editCustomDeceasedFirstName'] ?? '');
    $mname_deceased = trim($_POST['editCustomDeceasedMiddleName'] ?? '');
    $lname_deceased = trim($_POST['editCustomDeceasedLastName'] ?? '');
    $suffix_deceased = trim($_POST['editCustomDeceasedSuffix'] ?? '');
    $date_of_bearth = $_POST['editCustomBirthDate'] ?? null;
    $date_of_death = $_POST['editCustomDeathDate'] ?? null;
    $date_of_burial = $_POST['editCustomBurialDate'] ?? null;
    $flower_design = trim($_POST['editCustomFlowerArrangement'] ?? '');
    $inclusion = trim($_POST['editCustomAdditionalServices'] ?? '');
    $discounted_price = floatval($_POST['editCustomServicePrice'] ?? 0);
    $with_cremate = isset($_POST['editCustomWithCremation']) ? 'yes' : 'no';

    // Handle address
    $deceased_address = '';
    if (!empty($_POST['editCustomCurrentAddressDisplay'] ?? '')) {
        $deceased_address = $_POST['editCustomCurrentAddressDisplay'];
    } else {
        $street = trim($_POST['editCustomStreetInput'] ?? '');
        $barangay = trim($_POST['editCustomBarangaySelect'] ?? '');
        $city = trim($_POST['editCustomCitySelect'] ?? '');
        $province = trim($_POST['editCustomProvinceSelect'] ?? '');
        $region = trim($_POST['editCustomRegionSelect'] ?? '');
        $zip = trim($_POST['editCustomZipCodeInput'] ?? '');

        $address_parts = array_filter([$street, $barangay, $city, $province, $region, $zip]);
        $deceased_address = implode(', ', $address_parts);
    }

    // Handle file upload
    $death_cert_image = null;
    if (isset($_FILES['editCustomDeathCertificate']) && $_FILES['editCustomDeathCertificate']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['editCustomDeathCertificate'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('death_cert_', true) . '.' . $fileExt;
        $targetPath = $deathCertUploadPath . $fileName;

        // Check file type
        if (!in_array($fileExt, $allowedDeathCertTypes)) {
            throw new Exception('Invalid file type. Only PDF, JPG, JPEG, PNG are allowed.');
        }

        // Check file size
        if ($file['size'] > $maxDeathCertSize) {
            throw new Exception('File is too large. Maximum size is ' . ($maxDeathCertSize / 1024 / 1024) . 'MB');
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $death_cert_image = $fileName;

            // Delete old file if exists
            $sql = "SELECT death_cert_image FROM customsales_tb WHERE customsales_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $customsales_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $oldFile = $row['death_cert_image'] ?? null;

            if ($oldFile) {
                // Remove "uploads/" prefix if it exists
                $fileNameOnly = str_replace('uploads/', '', $oldFile);

                // Build full file path
                $fullPath = $deathCertUploadPath . $fileNameOnly;

                // Delete the file if it exists
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        } else {
            throw new Exception('Failed to upload file');
        }
    }

    // Prepare SQL update
    $sql = "UPDATE customsales_tb SET 
            fname_deceased = ?,
            mname_deceased = ?,
            lname_deceased = ?,
            suffix_deceased = ?,
            date_of_bearth = ?,
            date_of_death = ?,
            date_of_burial = ?,
            flower_design = ?,
            inclusion = ?,
            discounted_price = ?,
            deceased_address = ?,
            with_cremate = ?";
    
    if ($death_cert_image !== null) {
        $sql .= ", death_cert_image = ?";
    }

    $sql .= " WHERE customsales_id = ? AND customer_id = ?";

    $stmt = $conn->prepare($sql);

    $types = "sssssssssdss";
    $params = [
        $fname_deceased,
        $mname_deceased,
        $lname_deceased,
        $suffix_deceased,
        $date_of_bearth,
        $date_of_death,
        $date_of_burial,
        $flower_design,
        $inclusion,
        $discounted_price,
        $deceased_address,
        $with_cremate,
    ];

    if ($death_cert_image !== null) {
        $types = "sssssssssdsss";
        $params[] = $death_cert_image;
    }

    $params[] = $customsales_id;
    $params[] = $customer_id;

    $stmt->bind_param($types, ...$params);

    // Execute update
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Custom service updated successfully';
    } else {
        throw new Exception('Failed to update custom service: ' . $stmt->error);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
