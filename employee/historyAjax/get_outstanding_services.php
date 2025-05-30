<?php
require_once '../../db_connect.php';

// Get parameters from AJAX request
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$branch = isset($_GET['branch']) ? $_GET['branch'] : '';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$recordsPerPage = 10;

// Calculate offset
$offset = ($page - 1) * $recordsPerPage;

// Build base query
$query = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
          s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
          sv.service_name, s.date_of_burial, s.balance, s.status, s.payment_status
          FROM sales_tb s
          JOIN services_tb sv ON s.service_id = sv.service_id
          WHERE s.status = 'Completed' AND s.payment_status = 'With Balance' AND s.branch_id = ?";

$params = [$branch];
$paramTypes = "s";

// Add search if term exists
if (!empty($searchTerm)) {
    $query .= " AND (CONCAT(s.fname, ' ', s.lname) LIKE ? OR CONCAT(s.fname_deceased, ' ', s.lname_deceased) LIKE ? OR sv.service_name LIKE ?)";
    $searchParam = "%$searchTerm%";
    array_push($params, $searchParam, $searchParam, $searchParam);
    $paramTypes .= "sss";
}

$query .= " LIMIT ?, ?";
array_push($params, $offset, $recordsPerPage);
$paramTypes .= "ii";

// Prepare and execute query
$stmt = $conn->prepare($query);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Generate HTML for table rows
$html = '';
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $clientName = htmlspecialchars($row['fname'] . ' ' . 
                    ($row['mname'] ? $row['mname'] . ' ' : '') . 
                    $row['lname'] . 
                    ($row['suffix'] ? ' ' . $row['suffix'] : ''));
                    
        $deceasedName = htmlspecialchars($row['fname_deceased'] . ' ' . 
                        ($row['mname_deceased'] ? $row['mname_deceased'] . ' ' : '') . 
                        $row['lname_deceased'] . 
                        ($row['suffix_deceased'] ? ' ' . $row['suffix_deceased'] : ''));
        
        $html .= '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-4 text-sm text-sidebar-text font-medium">#' . $row['sales_id'] . '</td>
            <td class="px-4 py-4 text-sm text-sidebar-text">' . $clientName . '</td>
            <td class="px-4 py-4 text-sm text-sidebar-text">' . $deceasedName . '</td>
            <td class="px-4 py-4 text-sm text-sidebar-text">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    ' . htmlspecialchars($row['service_name']) . '
                </span>
            </td>
            <td class="px-4 py-4 text-sm text-sidebar-text">' . htmlspecialchars($row['date_of_burial']) . '</td>
            <td class="px-4 py-4 text-sm">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-500 border border-yellow-200">
                    <i class="fas fa-exclamation-circle mr-1"></i> ' . htmlspecialchars($row['payment_status']) . '
                </span>
            </td>
            <td class="px-4 py-4 text-sm font-medium text-sidebar-text">₱' . number_format($row['balance'], 2) . '</td>
            <td class="px-4 py-4 text-sm">
                <div class="flex space-x-2">
                    <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewServiceDetails(\'' . $row['sales_id'] . '\')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="p-2 bg-orange-100 text-orange-600 rounded-lg hover:bg-orange-200 transition-all tooltip" title="Record Payment" onclick="openRecordPaymentModal(\'' . $row['sales_id'] . '\',\'' . $clientName . '\',\'' . $row['balance'] . '\')">
                        <i class="fas fa-money-bill-wave"></i>
                    </button>
                </div>
            </td>
        </tr>';
    }
} else {
    $html .= '<tr>
        <td colspan="8" class="px-4 py-6 text-sm text-center">
            <div class="flex flex-col items-center">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No past services with outstanding balance found</p>
            </div>
        </td>
    </tr>';
}

// Get total records count
$countQuery = "SELECT COUNT(*) as total FROM sales_tb s 
               JOIN services_tb sv ON s.service_id = sv.service_id 
               WHERE s.status = 'Completed' AND s.payment_status = 'With Balance' AND s.branch_id = ?";

$countParams = [$branch];
$countParamTypes = "s";

if (!empty($searchTerm)) {
    $countQuery .= " AND (CONCAT(s.fname, ' ', s.lname) LIKE ? OR CONCAT(s.fname_deceased, ' ', s.lname_deceased) LIKE ? OR sv.service_name LIKE ?)";
    array_push($countParams, $searchParam, $searchParam, $searchParam);
    $countParamTypes .= "sss";
}

$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param($countParamTypes, ...$countParams);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Prepare response
$response = [
    'html' => $html,
    'totalRecords' => $totalRecords,
    'totalPages' => $totalPages,
    'currentPage' => $page
];

header('Content-Type: application/json');
echo json_encode($response);
?>