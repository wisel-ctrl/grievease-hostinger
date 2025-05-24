<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

// Get employee IDs from query parameter
$employeeIds = isset($_GET['employee_ids']) ? $_GET['employee_ids'] : '';

if (empty($employeeIds)) {
    echo json_encode([]);
    exit;
}

try {
    // Convert comma-separated string to array and sanitize
    $ids = array_map('intval', explode(',', $employeeIds));
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    // Prepare and execute query
    $query = "SELECT employeeID, salary FROM employee_tb WHERE employeeID IN ($placeholders)";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    // Bind parameters dynamically
    $types = str_repeat('i', count($ids));
    $stmt->bind_param($types, ...$ids);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $salaries = [];
    
    while ($row = $result->fetch_assoc()) {
        $salaries[$row['employeeID']] = floatval($row['salary']);
    }
    
    echo json_encode($salaries);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 