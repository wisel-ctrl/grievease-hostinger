<?php
// get_editAddondetails.php
require_once '../../db_connect.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$addonId = $_GET['id'];

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$query = "
    SELECT 
        a.addOns_id,
        a.addOns_name,
        a.icon,
        b.branch_name,
        b.branch_id,
        a.price,
        a.status,
        a.creation_date,
        a.update_date
    FROM AddOnsService_tb AS a
    JOIN branch_tb AS b 
        ON a.branch_id = b.branch_id
    WHERE a.addOns_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $addonId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $addon = $result->fetch_assoc();
    
    // Extract just the icon class name (remove 'fas ' prefix if present)
    $icon = $addon['icon'];
    if (strpos($icon, 'fas ') === 0) {
        $icon = substr($icon, 4);
    }
    $addon['icon'] = $icon;
    
    echo json_encode(['success' => true, 'data' => $addon]);
} else {
    echo json_encode(['error' => 'Add-on not found']);
}

$stmt->close();
$conn->close();
?>