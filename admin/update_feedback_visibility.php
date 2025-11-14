<?php
// update_feedback_visibility.php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_id = $_POST['feedback_id'] ?? null;
    $is_visible = $_POST['is_visible'] ?? 0;
    
    if ($feedback_id) {
        // Get current count of visible feedbacks
        $count_query = "SELECT COUNT(*) as visible_count FROM feedback_tb WHERE status = 'Show'";
        $count_result = $conn->query($count_query);
        $count_row = $count_result->fetch_assoc();
        $current_visible_count = $count_row['visible_count'];
        
        // Check if trying to show when maximum (2) is already reached
        if ($is_visible && $current_visible_count >= 2) {
            // Check if this feedback is already visible (allowing toggling off)
            $current_status_query = "SELECT status FROM feedback_tb WHERE id = ?";
            $current_stmt = $conn->prepare($current_status_query);
            $current_stmt->bind_param("i", $feedback_id);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();
            $current_row = $current_result->fetch_assoc();
            
            if ($current_row['status'] !== 'Show') {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Maximum of 2 visible feedbacks reached',
                    'max_reached' => true,
                    'current_visible_count' => $current_visible_count
                ]);
                exit();
            }
        }
        
        $status = $is_visible ? 'Show' : 'Hidden';
        
        $query = "UPDATE feedback_tb SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $status, $feedback_id);
        
        if ($stmt->execute()) {
            // Get updated count
            $updated_count_result = $conn->query($count_query);
            $updated_count_row = $updated_count_result->fetch_assoc();
            $updated_visible_count = $updated_count_row['visible_count'];
            
            echo json_encode([
                'success' => true, 
                'current_visible_count' => $updated_visible_count
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Database update failed'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid feedback ID'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}
?>