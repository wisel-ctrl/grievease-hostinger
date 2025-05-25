<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

// Check if required data is present
if (!isset($_POST['booking_id']) || empty($_FILES['death_cert'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$booking_id = $_POST['booking_id'];
$user_id = $_SESSION['user_id'];

// Validate that the booking belongs to the user
$stmt = $conn->prepare("SELECT * FROM booking_tb WHERE booking_id = ? AND customerID = ? AND status = 'Accepted'");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found or not eligible for upload']);
    exit();
}

$booking = $result->fetch_assoc();

// File upload handling
$target_dir = "../booking/uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$file_name = 'death_cert_' . time() . '_' . basename($_FILES["death_cert"]["name"]);
$target_file = $target_dir . $file_name;
$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check if image file is a actual image
$check = getimagesize($_FILES["death_cert"]["tmp_name"]);
if ($check === false) {
    echo json_encode(['success' => false, 'message' => 'File is not an image']);
    exit();
}

// Check file size (5MB max)
if ($_FILES["death_cert"]["size"] > 5000000) {
    echo json_encode(['success' => false, 'message' => 'File is too large (max 5MB)']);
    exit();
}

// Allow certain file formats
if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG & PNG files are allowed']);
    exit();
}

// Upload file
if (move_uploaded_file($_FILES["death_cert"]["tmp_name"], $target_file)) {
    // Update database with file path
    $update_stmt = $conn->prepare("UPDATE booking_tb SET deathcert_url = ? WHERE booking_id = ?");
    $update_stmt->bind_param("si", $file_name, $booking_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Death certificate uploaded successfully']);
    } else {
        // Delete the uploaded file if database update fails
        unlink($target_file);
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
}
?>