<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

try {
    // Add client_id to the SELECT statement
    $query = "SELECT id as client_id, first_name, middle_name, last_name, suffix, phone_number, email FROM users WHERE user_type = 3";
    $result = $conn->query($query);
    
    if ($result === false) {
        throw new Exception($conn->error);
    }
    
    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    
    echo json_encode($clients);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>