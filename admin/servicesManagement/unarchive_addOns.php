<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once "../../db_connect.php";

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
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
} else {
    echo json_encode(["error" => "Invalid request method"]);
}

$conn->close();
?>