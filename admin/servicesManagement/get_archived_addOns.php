<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once "../../db_connect.php";

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Handle GET or POST request to fetch archived add-ons
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get search and filter parameters
    $search = '';
    $branch = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $search = isset($input['search']) ? $input['search'] : '';
        $branch = isset($input['branch']) ? $input['branch'] : '';
    }
    
    // Build the SQL query with filters
    $sql = "SELECT 
                a.addOns_id,
                a.addOns_name,
                a.icon,
                b.branch_name,
                a.price,
                a.status,
                a.creation_date,
                a.update_date
            FROM AddOnsService_tb AS a
            JOIN branch_tb AS b 
                ON a.branch_id = b.branch_id
            WHERE a.status = 'archived'";
    
    // Add search filter if provided
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (a.addOns_name LIKE '%$search%' OR a.addOns_id LIKE '%$search%')";
    }
    
    // Add branch filter if provided and not "All Branches"
    if (!empty($branch) && $branch !== 'All Branches') {
        $branch = $conn->real_escape_string($branch);
        $sql .= " AND b.branch_name = '$branch'";
    }
    
    $result = $conn->query($sql);
    
    if ($result) {
        $addons = [];
        while ($row = $result->fetch_assoc()) {
            $addons[] = $row;
        }
        echo json_encode($addons);
    } else {
        echo json_encode(["error" => "Error: " . $conn->error]);
    }
    exit;
}

$conn->close();
?>