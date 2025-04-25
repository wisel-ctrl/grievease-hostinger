<?php
// fetch_archived_services.php
session_start();

// Include database connection
include '../../db_connect.php';

header('Content-Type: application/json');

try {
    // Only fetch the needed fields
    $sql = "SELECT service_id, service_name FROM services_tb WHERE status = 'Inactive'";
    
    // Execute the query
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $archivedServices = [];
    while ($row = $result->fetch_assoc()) {
        $archivedServices[] = $row;
    }
    
    echo json_encode($archivedServices);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>