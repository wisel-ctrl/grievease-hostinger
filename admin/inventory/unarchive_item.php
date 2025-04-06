<?php
include '../../db_connect.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get inventory ID from POST
$inventoryId = isset($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : 0;

// Update the item status to 1 (unarchived)
$sql = "UPDATE inventory_tb SET status = 1 WHERE inventory_id = $inventoryId";

if ($conn->query($sql) === TRUE) {
    echo "success";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>