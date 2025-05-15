<?php
// save_expense_changes_handler.php
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Database connection
require_once '../../db_connect.php';

// Get and sanitize input data
$expenseId = isset($_POST['editExpenseId']) ? intval($_POST['editExpenseId']) : 0;
$expenseName = isset($_POST['editExpenseDescription']) ? trim($_POST['editExpenseDescription']) : '';
$category = isset($_POST['editExpenseCategory']) ? trim($_POST['editExpenseCategory']) : '';
$price = isset($_POST['editExpenseAmount']) ? floatval($_POST['editExpenseAmount']) : 0;
$date = isset($_POST['editExpenseDate']) ? trim($_POST['editExpenseDate']) : '';
$status = isset($_POST['editExpenseStatus']) ? trim($_POST['editExpenseStatus']) : '';
$notes = isset($_POST['editExpenseNotes']) ? trim($_POST['editExpenseNotes']) : '';

// Validate required fields
if ($expenseId <= 0 || empty($expenseName) || empty($category) || $price <= 0 || empty($date) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

// Prepare the update query
$query = "UPDATE expense_tb SET 
          expense_name = ?, 
          category = ?, 
          price = ?, 
          date = ?, 
          status = ?, 
          notes = ? 
          WHERE expense_ID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssdsssi", $expenseName, $category, $price, $date, $status, $notes, $expenseId);

// Execute the query
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
    } else {
        // No rows affected - either expense doesn't exist or data is identical
        echo json_encode(['success' => false, 'message' => 'No changes made or expense not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating expense: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>