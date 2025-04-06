<?php
// Include database connection
include '../db_connect.php';

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get the form data
$serviceId = $_POST['serviceId'];
$serviceName = $_POST['serviceName'];
$capitalPrice = $_POST['capitalPrice'];
$sellingPrice = $_POST['sellingPrice'];
$serviceCategory = $_POST['serviceCategory'];
$casketType = $_POST['casketType'];
$urnType = $_POST['urnType'];
$branchId = $_POST['branch_id'];
$status = $_POST['status']; 
$flowerDesign = isset($_POST['flowerDesign']) ? $_POST['flowerDesign'] : '';
$inclusions = isset($_POST['inclusions']) ? $_POST['inclusions'] : ''; 
$currentImagePath = $_POST['currentImagePath'];
$description = $_POST['serviceDescription'] ?? null;
        

// Handle file upload
if ($_FILES['serviceImage']['error'] === UPLOAD_ERR_OK) {
    // Create uploads directory if it doesn't exist
    $uploadDir = 'uploads/services/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate a unique filename to prevent overwriting
    $fileExtension = pathinfo($_FILES['serviceImage']['name'], PATHINFO_EXTENSION);
    $uniqueFilename = uniqid('service_') . '.' . $fileExtension;
    $uploadFile = $uploadDir . $uniqueFilename;

    // Construct the relative path for database storage
    $relativeImagePath = 'uploads/services/' . $uniqueFilename;

    if (move_uploaded_file($_FILES['serviceImage']['tmp_name'], $uploadFile)) {
        $currentImagePath = $relativeImagePath;
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to upload image'
        ]);
        exit;
    }
}

// Prepare the SQL statement to update the service
$sql = "UPDATE services_tb SET 
        service_name = ?, 
        description = ?,
        capital_price = ?, 
        selling_price = ?, 
        service_categoryID = ?, 
        casket_id = ?, 
        urn_id = ?, 
        branch_id = ?, 
        status = ?, 
        flower_design = ?, 
        inclusions = ?, 
        image_url = ?
        WHERE service_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssddiiisssssi", 
    $serviceName, 
    $description,
    $capitalPrice, 
    $sellingPrice, 
    $serviceCategory, 
    $casketType, 
    $urnType, 
    $branchId, 
    $status, 
    $flowerDesign, 
    $inclusions, 
    $currentImagePath, 
    $serviceId
);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Service updated successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update service'
    ]);
}

$stmt->close();
$conn->close();
?>