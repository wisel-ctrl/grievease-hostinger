<?php
// Include database connection
include '../db_connect.php';

// Check if branch_id is set
if(isset($_POST['branch_id'])) {
    $branch_id = $_POST['branch_id'];
    
    // Prepare response array
    $response = [
        'caskets' => [],
        'urns' => []
    ];
    
    // Fetch caskets for this branch (category_id = 1)
    $sql_caskets = "SELECT inventory_id, item_name FROM inventory_tb 
                     WHERE category_id = 1 AND status = 1 AND branch_id = ?";
    $stmt_caskets = $conn->prepare($sql_caskets);
    $stmt_caskets->bind_param("i", $branch_id);
    $stmt_caskets->execute();
    $result_caskets = $stmt_caskets->get_result();
    
    if ($result_caskets->num_rows > 0) {
        while ($row = $result_caskets->fetch_assoc()) {
            $response['caskets'][] = $row;
        }
    }
    
    // Fetch urns for this branch (category_id = 3)
    $sql_urns = "SELECT inventory_id, item_name FROM inventory_tb 
                  WHERE category_id = 3 AND status = 1 AND branch_id = ?";
    $stmt_urns = $conn->prepare($sql_urns);
    $stmt_urns->bind_param("i", $branch_id);
    $stmt_urns->execute();
    $result_urns = $stmt_urns->get_result();
    
    if ($result_urns->num_rows > 0) {
        while ($row = $result_urns->fetch_assoc()) {
            $response['urns'][] = $row;
        }
    }
    
    // Return response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    
    // Close statements
    $stmt_caskets->close();
    $stmt_urns->close();
} else {
    // If no branch_id provided, return empty response
    header('Content-Type: application/json');
    echo json_encode(['caskets' => [], 'urns' => []]);
}

// Close connection
$conn->close();
?>