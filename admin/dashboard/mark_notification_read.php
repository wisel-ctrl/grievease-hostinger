<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_type']) && isset($_POST['booking_id'])) {
    $notificationType = $_POST['notification_type'];
    $bookingId = $_POST['booking_id'];
    
    try {
        switch ($notificationType) {
            case 'funeral':
                $stmt = $conn->prepare("UPDATE booking_tb SET is_read = 1 WHERE booking_id = ?");
                break;
            case 'lifeplan':
                $stmt = $conn->prepare("UPDATE lifeplan_booking_tb SET is_read = 1 WHERE lpbooking_id = ?");
                break;
            case 'id_validation':
                $stmt = $conn->prepare("UPDATE valid_id_tb SET is_read = 1 WHERE id = ?");
                break;
            default:
                throw new Exception("Invalid notification type");
        }
        
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>