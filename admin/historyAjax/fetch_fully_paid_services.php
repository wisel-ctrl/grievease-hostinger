<?php
require_once '../../db_connect.php';

$recordsPerPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $recordsPerPage;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id_asc';
$branch = isset($_GET['branch']) ? $_GET['branch'] : 'all';

$whereClause = "WHERE s.status = 'Completed' AND s.payment_status = 'Fully Paid' AND s.balance = 0";
if ($search) {
    $whereClause .= " AND (s.fname LIKE '%$search%' OR s.lname LIKE '%$search%' OR s.fname_deceased LIKE '%$search%' OR s.lname_deceased LIKE '%$search%' OR sv.service_name LIKE '%$search%')";
}

if ($branch !== 'all') {
    $branchId = intval($branch);
    $whereClause .= " AND s.branch_id = $branchId";
}

$orderBy = '';
switch ($sort) {
    case 'id_asc': $orderBy = 's.sales_id ASC'; break;
    case 'id_desc': $orderBy = 's.sales_id DESC'; break;
    case 'client_asc': $orderBy = 's.lname ASC, s.fname ASC'; break;
    case 'client_desc': $orderBy = 's.lname DESC, s.fname DESC'; break;
    case 'deceased_asc': $orderBy = 's.lname_deceased ASC, s.fname_deceased ASC'; break;
    case 'deceased_desc': $orderBy = 's.lname_deceased DESC, s.fname_deceased DESC'; break;
    case 'date_asc': $orderBy = 's.date_of_burial ASC'; break;
    case 'date_desc': $orderBy = 's.date_of_burial DESC'; break;
    default: $orderBy = 's.sales_id ASC';
}

$countQuery = "SELECT COUNT(*) as total FROM sales_tb s JOIN services_tb sv ON s.service_id = sv.service_id $whereClause";
$countResult = $conn->query($countQuery);
$totalServices = $countResult->fetch_assoc()['total'];

$fullyPaidQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
    s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
    sv.service_name, CASE 
    WHEN s.date_of_burial IS NULL OR s.date_of_burial = '0000-00-00' THEN 'Unknown'
    ELSE DATE_FORMAT(s.date_of_burial, '%M %d, %Y')
    END AS date_of_burial , s.balance, s.status, s.payment_status
    FROM sales_tb s
    JOIN services_tb sv ON s.service_id = sv.service_id
    $whereClause
    ORDER BY $orderBy
    LIMIT $offset, $recordsPerPage";

$fullyPaidResult = $conn->query($fullyPaidQuery);

$data = [
    'total' => $totalServices,
    'records' => [],
    'pagination' => [
        'currentPage' => $page,
        'totalPages' => ceil($totalServices / $recordsPerPage),
        'recordsPerPage' => $recordsPerPage
    ]
];

while ($row = $fullyPaidResult->fetch_assoc()) {
    $data['records'][] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>