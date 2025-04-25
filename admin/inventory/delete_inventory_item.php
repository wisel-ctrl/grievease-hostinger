<?php
include '../../db_connect.php'; // Ensure this connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['inventory_id'])) {
    $inventory_id = $_POST['inventory_id'];

    // Update status to 0
    $query = "UPDATE inventory_tb SET status = 0 WHERE inventory_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $inventory_id);

    if ($stmt->execute()) {
        // Redirect back to inventory page
        header("Location: ../inventory_management.php");
        exit();
    } else {
        echo "Error updating status.";
    }

    $stmt->close();
    $conn->close();
}
?>
