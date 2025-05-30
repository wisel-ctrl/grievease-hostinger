<?php
require_once '../../db_connect.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check database connection
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Get parameters from AJAX request
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$branch = isset($_GET['branch']) && is_numeric($_GET['branch']) ? (int)$_GET['branch'] : 0;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$recordsPerPage = 10;

// Validate branch
if ($branch === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid branch ID']);
    exit;
}

// Calculate offset
$offset = ($page - 1) * $recordsPerPage;

// Build base query
$query = "SELECT 
            cs.customsales_id,
            CONCAT_WS(' ', 
                u.first_name, 
                COALESCE(u.middle_name, ''), 
                u.last_name, 
                COALESCE(u.suffix, '')
            ) AS client_name,
            CONCAT_WS(' ', 
                cs.fname_deceased, 
                COALESCE(cs.mname_deceased, ''), 
                cs.lname_deceased, 
                COALESCE(cs.suffix_deceased, '')
            ) AS deceased_name,
            cs.discounted_price,
            cs.date_of_burial,
            cs.status,
            cs.balance,
            cs.customer_id,
            (SELECT COUNT(*) FROM employee_service_payments esp WHERE esp.sales_id = cs.customsales_id AND esp.sales_type = 'custom') AS staff_assigned
          FROM customsales_tb AS cs
          JOIN users AS u ON cs.customer_id = u.id
          WHERE cs.branch_id = ? AND cs.status = 'Pending'";

$params = [$branch];
$paramTypes = "i";

// Add search if term exists
if (!empty($searchTerm)) {
    $query .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR 
                     CONCAT(cs.fname_deceased, ' ', cs.lname_deceased) LIKE ? OR
                     cs.customsales_id LIKE ?)";
    $searchParam = "%$searchTerm%";
    array_push($params, $searchParam, $searchParam, $searchParam);
    $paramTypes .= "sss";
}

$query .= " LIMIT ?, ?";
array_push($params, $offset, $recordsPerPage);
$paramTypes .= "ii";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param($paramTypes, ...$params);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
    exit;
}
$result = $stmt->get_result();

// Generate HTML for table rows
$html = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#' . $row['customsales_id'] . '</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">' . htmlspecialchars($row['client_name']) . '</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">' . htmlspecialchars($row['deceased_name']) . '</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                ₱' . number_format($row['discounted_price'], 2) . '
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">' . htmlspecialchars($row['date_of_burial']) . '</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">
              <button onclick="viewCustomServiceDetails(' . $row['customsales_id'] . ')" class="text-sidebar-accent hover:text-darkgold transition-colors">
                <i class="fas fa-eye"></i> View Details
              </button>
            </td>
            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱' . number_format($row['balance'], 2) . '</td>
            <td class="px-4 py-3.5 text-sm">
              <div class="flex space-x-2">
                <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Service" onclick="openEditCustomServiceModal(\'' . $row['customsales_id'] . '\')">
                  <i class="fas fa-edit"></i>
                </button>';
                
        if ($row['staff_assigned'] == 0) {
            $html .= '<button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip assign-staff-btn" 
                      title="Assign Staff" 
                      onclick="openAssignCustomStaffModal(\'' . $row['customsales_id'] . '\')"
                      data-has-customer="' . ($row['customer_id'] ? 'true' : 'false') . '">
                    <i class="fas fa-users"></i>
                  </button>';
        }
        
        $html .= '<button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip complete-btn" 
                  title="Complete Service" 
                  onclick="openCompleteCustomModal(\'' . $row['customsales_id'] . '\')"
                  data-has-customer="' . ($row['customer_id'] ? 'true' : 'false') . '">
                <i class="fas fa-check"></i>
              </button>
            </div>
          </td>
        </tr>';
    }
} else {
    $html .= '<tr>
        <td colspan="8" class="p-6 text-sm text-center">
          <div class="flex flex-col items-center">
            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-500">No custom ongoing services found</p>
          </div>
        </td>
      </tr>';
}

// Get total records count
$countQuery = "SELECT COUNT(*) as total 
               FROM customsales_tb AS cs
               JOIN users AS u ON cs.customer_id = u.id
               WHERE cs.branch_id = ? AND cs.status = 'Pending'";

$countParams = [$branch];
$countParamTypes = "i";

if (!empty($searchTerm)) {
    $countQuery .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR 
                    CONCAT(cs.fname_deceased, ' ', cs.lname_deceased) LIKE ? OR
                    cs.customsales_id LIKE ?)";
    array_push($countParams, $searchParam, $searchParam, $searchParam);
    $countParamTypes .= "sss";
}

$countStmt = $conn->prepare($countQuery);
if (!$countStmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Count query preparation failed: ' . $conn->error]);
    exit;
}

$countStmt->bind_param($countParamTypes, ...$countParams);
if (!$countStmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Count query execution failed: ' . $countStmt->error]);
    exit;
}
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Close statements
$stmt->close();
$countStmt->close();

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