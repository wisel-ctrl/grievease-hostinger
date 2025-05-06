<?php
require_once '../../db_connect.php';

// Handle archive-related actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action == 'get_archived_expenses') {
        $branchId = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
        
        // Fetch all expenses with appearance = 'hidden' for the specific branch
        $query = "SELECT expense_ID, expense_name, category, date, price 
                  FROM expense_tb 
                  WHERE appearance = 'hidden'";
        
        if ($branchId > 0) {
            $query .= " AND branch_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $branchId);
        } else {
            $stmt = $conn->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $expenses = array();
        while ($row = $result->fetch_assoc()) {
            $expenses[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($expenses);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'unarchive_expense') {
    // Unarchive an expense
    $expenseId = $_POST['expense_id'];
    
    $stmt = $conn->prepare("UPDATE expense_tb SET appearance = 'visible' WHERE expense_ID = ?");
    $stmt->bind_param("i", $expenseId);
    
    $response = array();
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Expense unarchived successfully';
    } else {
        $response['success'] = false;
        $response['message'] = 'Error unarchiving expense: ' . $stmt->error;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$conn->close();
?>