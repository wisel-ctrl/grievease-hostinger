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
        // First, get the service_type of the feedback we're trying to update
        $feedback_query = "SELECT service_type FROM feedback_tb WHERE id = ?";
        $feedback_stmt = $conn->prepare($feedback_query);
        $feedback_stmt->bind_param("i", $feedback_id);
        $feedback_stmt->execute();
        $feedback_result = $feedback_stmt->get_result();
        
        if ($feedback_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Feedback not found']);
            exit();
        }
        
        $feedback_row = $feedback_result->fetch_assoc();
        $service_type = $feedback_row['service_type'];
        
        // Determine service group
        $service_group = '';
        if ($service_type === 'life-plan') {
            $service_group = 'life-plan';
        } else {
            // Both traditional-funeral and custom-package belong to the same group
            $service_group = 'traditional-group';
        }
        
        // Get current count of visible feedbacks for this service group
        if ($service_group === 'life-plan') {
            $count_query = "SELECT COUNT(*) as visible_count FROM feedback_tb WHERE status = 'Show' AND service_type = 'life-plan'";
        } else {
            $count_query = "SELECT COUNT(*) as visible_count FROM feedback_tb WHERE status = 'Show' AND service_type IN ('traditional-funeral', 'custom-package')";
        }
        
        $count_result = $conn->query($count_query);
        $count_row = $count_result->fetch_assoc();
        $current_visible_count = $count_row['visible_count'];
        
        // Check if trying to show when maximum (2) is already reached for this service group
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
                    'message' => 'Maximum of 2 visible feedbacks reached for ' . ($service_group === 'life-plan' ? 'Life Plan' : 'Traditional/Custom') . ' services',
                    'max_reached' => true,
                    'current_visible_count' => $current_visible_count,
                    'service_group' => $service_group
                ]);
                exit();
            }
        }
        
        $status = $is_visible ? 'Show' : 'Hidden';
        
        $query = "UPDATE feedback_tb SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $status, $feedback_id);
        
        if ($stmt->execute()) {
            // Get updated counts for both service groups
            $life_plan_count_query = "SELECT COUNT(*) as visible_count FROM feedback_tb WHERE status = 'Show' AND service_type = 'life-plan'";
            $traditional_count_query = "SELECT COUNT(*) as visible_count FROM feedback_tb WHERE status = 'Show' AND service_type IN ('traditional-funeral', 'custom-package')";
            
            $life_plan_result = $conn->query($life_plan_count_query);
            $traditional_result = $conn->query($traditional_count_query);
            
            $life_plan_count = $life_plan_result->fetch_assoc()['visible_count'];
            $traditional_count = $traditional_result->fetch_assoc()['visible_count'];
            
            echo json_encode([
                'success' => true, 
                'life_plan_visible_count' => $life_plan_count,
                'traditional_visible_count' => $traditional_count,
                'total_visible_count' => $life_plan_count + $traditional_count
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