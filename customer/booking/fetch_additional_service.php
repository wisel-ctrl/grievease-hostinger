<?php
session_start();
require_once '../../db_connect.php'; // Adjust path as needed

header('Content-Type: application/json');

// Check if branch_id is provided
if (!isset($_GET['branch_id']) || empty($_GET['branch_id'])) {
    echo json_encode([]);
    exit;
}

$branch_id = $_GET['branch_id'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare and execute the query
$sql = "SELECT addOns_id, addOns_name, description, icon, price 
        FROM `AddOnsService_tb` 
        WHERE branch_id = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

echo json_encode($services);

$stmt->close();
$conn->close();
?>