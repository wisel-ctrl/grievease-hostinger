<?php
// update_history_sales.php
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

if ($_SESSION['user_type'] != 2) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

// Database connection
require_once '../../db_connect.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// Prepare the response array
$response = array('success' => false, 'message' => '');

try {
    // Validate required fields
    if (empty($data['sales_id'])) {
        throw new Exception('Service ID is required');
    }

    // Prepare the SQL query
    $query = "UPDATE sales_tb SET
                customerID = ?,
                fname = ?,
                mname = ?,
                lname = ?,
                suffix = ?,
                phone = ?,
                email = ?,
                fname_deceased = ?,
                mname_deceased = ?,
                lname_deceased = ?,
                suffix_deceased = ?,
                date_of_birth = ?,
                date_of_death = ?,
                date_of_burial = ?,
                deceased_address = ?,
                service_id = ?,
                discounted_price = ?
              WHERE sales_id = ?";

    $stmt = $conn->prepare($query);

    // Handle null customerID (when no customer is selected)
    $customerID = !empty($data['customer_id']) ? $data['customer_id'] : NULL;

    // Get the deceased address directly from the data
    $deceasedAddress = $data['deceased_address'];

    // Bind parameters
    $stmt->bind_param("issssssssssssssisi",
        $customerID,
        $data['firstName'],
        $data['middleName'],
        $data['lastName'],
        $data['nameSuffix'],
        $data['phone'],
        $data['email'],
        $data['deceasedFirstName'],
        $data['deceasedMiddleName'],
        $data['deceasedLastName'],
        $data['deceasedSuffix'],
        $data['birthDate'],
        $data['deathDate'],
        $data['burialDate'],
        $deceasedAddress,
        $data['service_id'],
        $data['service_price'],
        $data['sales_id']
    );

    // Execute the query
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Service updated successfully';
    } else {
        throw new Exception('Failed to update service: ' . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Close database connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>