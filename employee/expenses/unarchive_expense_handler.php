<?php
session_start();
require_once '../../db_connect.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['expense_id'])) {
    $expense_id = (int)$_POST['expense_id'];
    $branch = $_SESSION['branch_employee'];

    // Verify the expense belongs to the user's branch
    $check_query = "SELECT branch_id FROM expense_tb WHERE expense_ID = ? AND branch_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $expense_id, $branch);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        $response['message'] = 'Expense not found or unauthorized';
        echo json_encode($response);
        exit();
    }

    // Update the appearance to 'visible'
    $query = "UPDATE expense_tb SET appearance = 'visible' WHERE expense_ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $expense_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Expense unarchived successfully';
    } else {
        $response['message'] = 'Error unarchiving expense';
    }

    $stmt->close();
    $check_stmt->close();
    $conn->close();
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>