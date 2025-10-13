<?php
header('Content-Type: application/json');
require_once '../../db_connect.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['sales_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sales ID is required']);
    exit;
}

try {
    // Step 1: Get amount_paid from the database
    $getAmountSql = "SELECT amount_paid FROM sales_tb WHERE sales_id = ?";
    $getStmt = $conn->prepare($getAmountSql);
    if (!$getStmt) {
        throw new Exception("Prepare failed (get amount): " . $conn->error);
    }

    $getStmt->bind_param("i", $data['sales_id']);
    $getStmt->execute();
    $getResult = $getStmt->get_result();

    if ($getResult->num_rows === 0) {
        throw new Exception("No record found for the given sales_id");
    }

    $row = $getResult->fetch_assoc();
    $amount_paid = (float)$row['amount_paid'];
    $getStmt->close();

    // Step 2: Compute balance
    $service_price = (float)$data['service_price'];
    $balance = $service_price - $amount_paid;

    // Step 3: Update sales record with balance included
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
                discounted_price = ?,
                balance = ?
            WHERE sales_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed (update): " . $conn->error);
    }

    $stmt->bind_param("issssssssssssssiiddi",
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
        $balance,
        $data['sales_id']
    );

    $executed = $stmt->execute();

    if (!$executed) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Record updated successfully',
            'amount_paid' => $amount_paid,
            'balance' => $balance
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No records were updated. Please check if the data was different.',
            'amount_paid' => $amount_paid,
            'balance' => $balance
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
