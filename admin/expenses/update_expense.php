<?php
// Include database connection
require_once '../../db_connect.php';

// Set header to return JSON response
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if all required fields are provided
$requiredFields = ['expense_id', 'description', 'branch', 'category', 'amount', 'date', 'status'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize and validate input data
$expense_id = mysqli_real_escape_string($conn, $_POST['expense_id']);
$description = mysqli_real_escape_string($conn, $_POST['description']);
$branch = mysqli_real_escape_string($conn, $_POST['branch']);
$category = mysqli_real_escape_string($conn, $_POST['category']);
$amount = mysqli_real_escape_string($conn, $_POST['amount']);
$date = mysqli_real_escape_string($conn, $_POST['date']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';

// Validate expense ID (should be numeric)
if (!is_numeric($expense_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
    exit;
}

// Validate amount (should be numeric)
if (!is_numeric(str_replace(',', '.', $amount))) {
    echo json_encode(['success' => false, 'message' => 'Amount must be a number']);
    exit;
}

// Validate date format
$date_obj = DateTime::createFromFormat('Y-m-d', $date);
if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Please use YYYY-MM-DD']);
    exit;
}

// Prepare and execute the update query
$query = "UPDATE expense_tb SET 
            expense_name = ?, 
            branch_id  = ?, 
            category = ?, 
            price = ?, 
            date = ?, 
            status = ?, 
            notes = ? 
          WHERE expense_ID = ?";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'sssdsssi', 
        $description, 
        $branch, 
        $category, 
        $amount, 
        $date, 
        $status, 
        $note, 
        $expense_id
    );
    
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        // Check if any rows were affected
        if (mysqli_affected_rows($conn) > 0) {
            echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or expense not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_stmt_error($stmt)]);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . mysqli_error($conn)]);
}

// Close database connection
mysqli_close($conn);
?>