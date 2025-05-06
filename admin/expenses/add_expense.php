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

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

// Prepare and execute the SQL statement
$stmt = $conn->prepare("INSERT INTO expense_tb (category, expense_name, date, branch_id, status, price, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssisds", $category, $description, $date, $branch_id, $status, $amount, $note);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>