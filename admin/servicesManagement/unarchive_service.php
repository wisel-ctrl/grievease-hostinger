<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : null;
    $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : null;

    if ($service_id && $branch_id) {
        // Update service status to Active
        $sql = "UPDATE services_tb SET status = 'Active' WHERE service_id = ? AND branch_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $service_id, $branch_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Service unarchived successfully';
            } else {
                $response['message'] = 'No service found with the given ID and branch';
            }
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }

        $stmt->close();
    } else {
        $response['message'] = 'Invalid service ID or branch ID';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
$conn->close();
?>