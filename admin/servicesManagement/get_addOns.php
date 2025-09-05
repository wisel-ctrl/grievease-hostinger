<?php
header('Content-Type: application/json');

require_once "../../db_connect.php";

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get parameters from request
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Pagination parameters
$recordsPerPage = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $recordsPerPage;

// Build WHERE clause
$whereClause = "WHERE a.status IN ('active', 'inactive')";
if (!empty($search)) {
    $whereClause .= " AND (a.addOns_name LIKE '%$search%' OR a.description LIKE '%$search%')";
}
if (!empty($status)) {
    $statusValue = strtolower($status) === 'active' ? 'active' : 'inactive';
    $whereClause .= " AND a.status = '$statusValue'";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM AddOnsService_tb AS a $whereClause";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Query to fetch add-ons data with pagination
$sql = "SELECT 
            a.addOns_id,
            a.addOns_name,
            a.description,
            a.icon,
            b.branch_name,
            a.price,
            a.status,
            a.creation_date,
            a.update_date
        FROM AddOnsService_tb AS a
        JOIN branch_tb AS b 
            ON a.branch_id = b.branch_id
        $whereClause
        ORDER BY a.addOns_id
        LIMIT $offset, $recordsPerPage";

$result = $conn->query($sql);

if (!$result) {
    die(json_encode(['error' => 'Query failed: ' . $conn->error]));
}

$addOns = [];
while ($row = $result->fetch_assoc()) {
    $addOns[] = $row;
}

$conn->close();

echo json_encode([
    'data' => $addOns,
    'totalRecords' => $totalRecords,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>