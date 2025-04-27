<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Database connection
    require_once '../../addressDB.php';
    
    // Check if connection is successful
    if (!isset($addressDB) || $addressDB->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Prepare SQL query to fetch all regions
    $sql = "SELECT region_id, region_name FROM table_region ORDER BY region_name ASC";
    
    // Execute the query
    $result = $addressDB->query($sql);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $addressDB->error);
    }
    
    // Fetch all results as an associative array
    $regions = [];
    while ($row = $result->fetch_assoc()) {
        $regions[] = $row;
    }
    
    // Return the regions as JSON
    echo json_encode($regions);
} catch (Exception $e) {
    // Return error message
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
