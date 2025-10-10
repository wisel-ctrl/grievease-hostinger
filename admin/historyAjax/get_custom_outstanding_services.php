<?php
session_start();
require_once '../../db_connect.php';

$recordsPerPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $recordsPerPage;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id_asc';
$branch = isset($_GET['branch']) ? $_GET['branch'] : 'all';

$whereClause = "WHERE cs.status = 'Completed' AND cs.payment_status = 'With Balance'";
if ($search) {
  $search = $conn->real_escape_string($search);
  $whereClause .= " AND (CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) LIKE '%$search%' 
                   OR CONCAT(cs.fname_deceased, ' ', COALESCE(cs.mname_deceased, ''), ' ', cs.lname_deceased) LIKE '%$search%')";
}

if ($branch !== 'all') {
    $branchId = intval($branch);
    $whereClause .= " AND cs.branch_id = $branchId";
}


$orderBy = 'ORDER BY cs.customsales_id ASC';
switch ($sort) {
  case 'id_asc':
    $orderBy = 'ORDER BY cs.customsales_id ASC';
    break;
  case 'id_desc':
    $orderBy = 'ORDER BY cs.customsales_id DESC';
    break;
  case 'client_asc':
    $orderBy = 'ORDER BY CONCAT(u.first_name, u.last_name) ASC';
    break;
  case 'client_desc':
    $orderBy = 'ORDER BY CONCAT(u.first_name, u.last_name) DESC';
    break;
  case 'date_asc':
    $orderBy = 'ORDER BY cs.date_of_burial ASC';
    break;
  case 'date_desc':
    $orderBy = 'ORDER BY cs.date_of_burial DESC';
    break;
}

$query = "SELECT cs.customsales_id, u.first_name AS fname, u.middle_name AS mname, u.last_name AS lname, u.suffix,
                 cs.fname_deceased, cs.mname_deceased, cs.lname_deceased, cs.suffix_deceased,
                 CASE 
                 WHEN cs.date_of_burial IS NULL OR cs.date_of_burial = '0000-00-00' THEN 'Unknown'
                 ELSE DATE_FORMAT(cs.date_of_burial, '%M %d, %Y')
                 END AS date_of_burial, cs.balance, cs.status, cs.with_cremate
          FROM customsales_tb cs
          LEFT JOIN users u ON cs.customer_id = u.id
          $whereClause
          $orderBy
          LIMIT $offset, $recordsPerPage";
$result = $conn->query($query);

$services = [];
while ($row = $result->fetch_assoc()) {
  $services[] = $row;
}

$countQuery = "SELECT COUNT(*) as total FROM customsales_tb cs LEFT JOIN users u ON cs.customer_id = u.id $whereClause";
$countResult = $conn->query($countQuery);
$total = $countResult->fetch_assoc()['total'];
$totalPages = ceil($total / $recordsPerPage);

header('Content-Type: application/json');
echo json_encode([
  'services' => $services,
  'total' => $total,
  'total_pages' => $totalPages,
  'start' => $offset + 1,
  'end' => min($offset + $recordsPerPage, $total)
]);
?>