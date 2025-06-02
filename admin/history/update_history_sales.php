<?php
header('Content-Type: application/json');

// Include database connection
require_once '../../db_connect.php';

// Get the JSON data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (empty($data['sales_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sales ID is required']);
    exit;
}

try {
    // Prepare the SQL update statement
    $sql = "UPDATE sales_tb SET
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
            branch_id = ?,
            service_id = ?,
            discounted_price = ?
            WHERE sales_id = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("issssssssssssssiidi",
        $data['customer_id'],
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
        $data['deceasedAddress'],
        $data['branch'],
        $data['service_id'],
        $data['service_price'],
        $data['sales_id']
    );

    // Execute the query
    $executed = $stmt->execute();
    
    if (!$executed) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Record updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No records were updated. Please check if the data was different.']);
    }
    
    $stmt->close();
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>