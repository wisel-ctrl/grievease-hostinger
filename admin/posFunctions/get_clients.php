<?php
// get_clients.php
require_once '../../db_connect.php'; // Your database connection file

header('Content-Type: application/json');


// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

try {
    $query = "SELECT first_name, middle_name, last_name, suffix, phone_number, email FROM users WHERE user_type = 3";
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