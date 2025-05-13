<?php
// add_expense_handler.php
session_start();
date_default_timezone_set('Asia/Manila');

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

if ($_SESSION['user_type'] != 2) {
    header("Location: unauthorized.php");
    exit();
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../employee_expenses.php");
    exit();
}

// Database connection
require_once '../../db_connect.php';

// Get branch_id from user session
$branch_id = isset($_POST['branch_id']) ? (int) $_POST['branch_id'] : 0;


// Get and sanitize input data
$expense_name = isset($_POST['expenseDescription']) ? trim($_POST['expenseDescription']) : '';
$category = isset($_POST['expenseCategory']) ? trim($_POST['expenseCategory']) : '';
$price = isset($_POST['expenseAmount']) ? floatval($_POST['expenseAmount']) : 0;
$date = isset($_POST['expenseDate']) ? trim($_POST['expenseDate']) : '';
$status = isset($_POST['expenseStatus']) ? trim($_POST['expenseStatus']) : '';
$notes = isset($_POST['expenseNotes']) ? trim($_POST['expenseNotes']) : '';

// Validate required fields
if (empty($expense_name) || empty($category) || empty($date) || empty($status) || $price <= 0) {
    $_SESSION['error'] = "Please fill all required fields with valid data.";
    header("Location: ../employee_expenses.php");
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $_SESSION['error'] = "Invalid date format. Please use YYYY-MM-DD.";
    header("Location: ../employee_expenses.php");
    exit();
}

// Prepare the insert statement
$query = "INSERT INTO expense_tb (expense_name, category, branch_id, date, status, price, notes) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: ../employee_expenses.php");
    exit();
}

// Bind parameters and execute
$stmt->bind_param("ssissds", $expense_name, $category, $branch_id, $date, $status, $price, $notes);

if ($stmt->execute()) {
    $_SESSION['success'] = "Expense added successfully!";
} else {
    $_SESSION['error'] = "Error adding expense: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to expenses page
header("Location: ../employee_expenses.php");
exit();
?>