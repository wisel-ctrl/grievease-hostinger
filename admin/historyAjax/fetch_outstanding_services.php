<?php
require_once '../../db_connect.php';

$recordsPerPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $recordsPerPage;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id_asc';

$whereClause = "WHERE s.status = 'Completed' AND s.payment_status = 'With Balance'";
if ($search) {
    $whereClause .= " AND (s.fname LIKE '%$search%' OR s.lname LIKE '%$search%' OR s.fname_deceased LIKE '%$search%' OR s.lname_deceased LIKE '%$search%' OR sv.service_name LIKE '%$search%')";
}

$orderBy = '';
switch ($sort) {
    case 'id_asc': $orderBy = 's.sales_id ASC'; break;
    case 'id_desc': $orderBy = 's.sales_id DESC'; break;
    case 'client_asc': $orderBy = 's.lname ASC, s.fname ASC'; break;
    case 'client_desc': $orderBy = 's.lname DESC, s.fname ASC'; break;
    case 'date_asc': $orderBy = 's.date_of_burial ASC'; break;
    case 'date_desc': $orderBy = 's.date_of_burial DESC'; break;
    default: $orderBy = 's.sales_id ASC';
}

$countQuery = "SELECT COUNT(*) as total FROM sales_tb s JOIN services_tb sv ON s.service_id = sv.service_id $whereClause";
$countResult = $conn->query($countQuery);
$totalOutstanding = $countResult->fetch_assoc()['total'];

$outstandingQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
    s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
    sv.service_name, s.date_of_burial, s.balance, s.status, s.payment_status
    FROM sales_tb s
    JOIN services_tb sv ON s.service_id = sv.service_id
    $whereClause
    ORDER BY $orderBy
    LIMIT $offset, $recordsPerPage";

$outstandingResult = $conn->query($outstandingQuery);

$data = [
    'total' => $totalOutstanding,
    'records' => [],
    'pagination' => [
        'currentPage' => $page,
        'totalPages' => ceil($totalOutstanding / $recordsPerPage),
        'recordsPerPage' => $recordsPerPage
    ]
];

while ($row = $outstandingResult->fetch_assoc()) {
    $data['records'][] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>