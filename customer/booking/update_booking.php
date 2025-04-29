<?php
require_once '../../db_connect.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    
    try {
        // Initialize variables for file paths
        $deathCertPath = '';
        $paymentPath = '';
        
        // Process file uploads if they exist
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Process death certificate if uploaded
        if (isset($_FILES['death_certificate']) && $_FILES['death_certificate']['error'] === UPLOAD_ERR_OK) {
            $deathCertExt = pathinfo($_FILES['death_certificate']['name'], PATHINFO_EXTENSION);
            $deathCertName = 'death_cert_' . time() . '.' . $deathCertExt;
            $deathCertPath = $uploadDir . $deathCertName;
            move_uploaded_file($_FILES['death_certificate']['tmp_name'], $deathCertPath);
            
            // If there was an old file, we might want to delete it (optional)
            // if (!empty($data['current_death_cert']) && file_exists($data['current_death_cert'])) {
            //     unlink($data['current_death_cert']);
            // }
        }
        
        // Process payment proof if uploaded
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $paymentExt = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $paymentName = 'payment_' . time() . '.' . $paymentExt;
            $paymentPath = $uploadDir . $paymentName;
            move_uploaded_file($_FILES['payment_proof']['tmp_name'], $paymentPath);
            
            // If there was an old file, we might want to delete it (optional)
            // if (!empty($data['current_payment_proof']) && file_exists($data['current_payment_proof'])) {
            //     unlink($data['current_payment_proof']);
            // }
        }
        
        // Build the SQL query based on whether files were uploaded
        $query = "UPDATE booking_tb SET 
                  service_id = ?, 
                  branch_id = ?, 
                  deceased_fname = ?, 
                  deceased_midname = ?, 
                  deceased_lname = ?, 
                  deceased_suffix = ?, 
                  deceased_birth = ?, 
                  deceased_dodeath = ?, 
                  deceased_dateOfBurial = ?, 
                  deceased_address = ?, 
                  with_cremate = ?,
                  status = 'Pending'";
        
        // Add file updates if files were uploaded
        $params = [];
        $types = "iissssssssi"; // initial types for the non-file fields
        
        if (!empty($deathCertPath)) {
            $query .= ", deathcert_url = ?";
            $params[] = $deathCertPath;
            $types .= "s";
        }
        
        if (!empty($paymentPath)) {
            $query .= ", payment_url = ?";
            $params[] = $paymentPath;
            $types .= "s";
        }
        
        $query .= " WHERE booking_id = ?";
        $types .= "i"; // for booking_id
        
        // Prepare the statement
        $stmt = $conn->prepare($query);
        
        // Bind parameters - first the standard fields
        $bindParams = [
            $data['service_id'],
            $data['branch_id'],
            $data['deceased_fname'],
            $data['deceased_midname'],
            $data['deceased_lname'],
            $data['deceased_suffix'],
            $data['deceased_birth'],
            $data['deceased_dodeath'],
            $data['deceased_dateOfBurial'],
            $data['deceased_address'],
            $data['with_cremate'] ?? 0
        ];
        
        // Add file paths if they exist
        if (!empty($deathCertPath)) {
            $bindParams[] = $deathCertPath;
        }
        if (!empty($paymentPath)) {
            $bindParams[] = $paymentPath;
        }
        
        // Add booking_id
        $bindParams[] = $data['booking_id'];
        
        // Bind all parameters
        $stmt->bind_param($types, ...$bindParams);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update booking: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$stmt->close();
$conn->close();
?>