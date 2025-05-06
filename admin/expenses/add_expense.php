<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

// Validate input data
if (!isset($_POST['description'], $_POST['branch'], $_POST['category'], $_POST['amount'], $_POST['date'], $_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Sanitize inputs
$description = trim($_POST['description']);
$branch_id = intval($_POST['branch']);
$category = trim($_POST['category']);
$amount = floatval($_POST['amount']);
$date = $_POST['date'];
$status = ($_POST['status'] === 'paid') ? 'paid' : 'To be paid';
$note = isset($_POST['note']) ? trim($_POST['note']) : '';
$imagePath = null;

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

// Handle file upload
if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('expense_') . '.' . $fileExtension;
    $destination = $uploadDir . $filename;
    
    // Validate file type (images only)
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.']);
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($_FILES['receipt']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.']);
        exit();
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['receipt']['tmp_name'], $destination)) {
        $imagePath = 'uploads/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit();
    }
}

// Prepare and execute the SQL statement
$stmt = $conn->prepare("INSERT INTO expense_tb (category, expense_name, date, branch_id, status, price, notes, expense_img) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssisdss", $category, $description, $date, $branch_id, $status, $amount, $note, $imagePath);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
} else {
    // Delete the uploaded file if database insertion failed
    if ($imagePath) {
        unlink('../../' . $imagePath);
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>