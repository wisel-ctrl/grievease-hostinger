<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once "../../db_connect.php";

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Handle GET request to fetch archived add-ons
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
}

// Handle POST request to unarchive an add-on
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['id'])) {
        $id = $data['id'];
        
        $stmt = $conn->prepare("UPDATE AddOnsService_tb SET status = 'active' WHERE addOns_id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => "Add-on unarchived successfully"]);
        } else {
            echo json_encode(["error" => "Error unarchiving add-on: " . $stmt->error]);
        }
        
        $stmt->close();
    } else {
        echo json_encode(["error" => "No ID provided"]);
    }
}

$conn->close();
?>