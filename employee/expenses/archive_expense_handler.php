<?php
// archive_expense_handler.php
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if expense ID is provided
if (!isset($_POST['expense_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Expense ID is required']);
    exit();
}

// Database connection
require_once '../../db_connect.php';

// Sanitize the input
$expense_id = filter_var($_POST['expense_id'], FILTER_SANITIZE_NUMBER_INT);

// Prepare the update query
$query = "UPDATE expense_tb SET appearance = 'hidden' WHERE expense_ID = ? AND branch_id = ?";
$stmt = $conn->prepare($query);

// Get the user's branch
$branch = $_SESSION['branch_loc'] ?? null;
if (!$branch) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'User branch not found']);
    exit();
}

// Bind parameters and execute
$stmt->bind_param("ii", $expense_id, $branch);
$success = $stmt->execute();

if ($success) {
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Expense archived successfully']);
    } else {
        // No rows affected - expense not found or already archived
        echo json_encode(['success' => false, 'message' => 'Expense not found or already archived']);
    }
} else {
    // Database error
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Failed to archive expense']);
}

$stmt->close();
$conn->close();
?>