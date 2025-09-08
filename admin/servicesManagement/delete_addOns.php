<?php
header('Content-Type: application/json');

// Database connection
require_once '../../db_connect.php'; // Assuming you have a separate file for DB connection

// Get the add-on ID from POST request
$addOnId = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($addOnId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid add-on ID'
    ]);
    exit;
}

// Prepare the update query
$query = "UPDATE AddOnsService_tb SET status = 'archived' WHERE addOns_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare statement: ' . $conn->error
    ]);
    exit;
}

// Bind parameters and execute
$stmt->bind_param('i', $addOnId);
$result = $stmt->execute();

if ($result) {
    // Check if any row was actually updated
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Add-on successfully archived'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No add-on found with the specified ID'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error deactivating add-on: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>