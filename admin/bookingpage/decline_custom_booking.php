<?php
session_start();
require_once '../../db_connect.php';

date_default_timezone_set('Asia/Manila');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

if ($_SESSION['user_type'] != 1) { // 1 = admin
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Get the POST data
$bookingId = isset($_POST['bookingId']) ? intval($_POST['bookingId']) : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

// Validate inputs
if ($bookingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit();
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Reason for decline is required']);
    exit();
}

// Prepare the update query
$query = "UPDATE booking_tb 
          SET status = 'Declined', 
              reason_for_decline = ?, 
              accepter_decliner = ?, 
              decline_date = ? 
          WHERE booking_id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$adminId = $_SESSION['user_id'];
$declineDate = date('Y-m-d H:i:s');
$stmt->bind_param("sisi", $reason, $adminId, $declineDate,$bookingId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Booking declined successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No booking was updated - ID may not exist']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error declining booking: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>