<?php
header('Content-Type: application/json');

// Database connection
require_once "../../db_connect.php";

// Check connection
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($data['name']) || !isset($data['price']) || !isset($data['branch_id']) || !isset($data['icon'])) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Sanitize and validate input
$addonName = $conn->real_escape_string(trim($data['name']));
$price = floatval($data['price']);
$branchId = intval($data['branch_id']);
$icon = $conn->real_escape_string(trim($data['icon']));

// Validate price
if ($price <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Price must be greater than 0']);
    exit;
}

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO AddOnsService_tb (addOns_name, icon, branch_id, price) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssid", $addonName, $icon, $branchId, $price);

// Execute the statement
if ($stmt->execute()) {
    $response = [
        'status' => 'success',
        'message' => 'Add-on saved successfully!',
        'html' => '
            <script>
                Swal.fire({
                    title: "Success!",
                    text: "Add-on saved successfully!",
                    icon: "success",
                    confirmButtonText: "OK"
                }).then(() => {
                    // Close modal and refresh or redirect as needed
                    if (typeof closeModal === "function") {
                        closeModal();
                    }
                    // Optionally refresh the page or update the add-ons list
                    window.location.reload();
                });
            </script>
        '
    ];
} else {
    $response = [
        'status' => 'error',
        'message' => 'Error saving add-on: ' . $stmt->error,
        'html' => '
            <script>
                Swal.fire({
                    title: "Error!",
                    text: "Error saving add-on: ' . addslashes($stmt->error) . '",
                    icon: "error",
                    confirmButtonText: "OK"
                });
            </script>
        '
    ];
}

// Close connections
$stmt->close();
$conn->close();

echo json_encode($response);
?>