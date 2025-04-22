@ -1,127 +1,127 @@
<?php
// Include the database connection
require_once('../../db_connect.php');

// Initialize variables
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'id_asc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Construct the SQL query with search and sorting
$sql = "SELECT id, first_name, last_name, email, is_verified, created_at FROM users WHERE user_type = 2";

// Add search condition if search term is provided
if (!empty($search)) {
    $sql .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}

// Add sorting based on selected option
switch ($sort) {
    case 'id_asc':
        $sql .= " ORDER BY id ASC";
        break;
    case 'id_desc':
        $sql .= " ORDER BY id DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY first_name ASC, last_name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY first_name DESC, last_name DESC";
        break;
    case 'email_asc':
        $sql .= " ORDER BY email ASC";
        break;
    case 'email_desc':
        $sql .= " ORDER BY email DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY created_at DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    default:
        $sql .= " ORDER BY id ASC";
}

// Add pagination to the query
$sql .= " LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM users WHERE user_type = 2";
if (!empty($search)) {
    $countSql .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Initialize empty table content
$tableContent = '';

// Check if there are results
if ($result->num_rows > 0) {
    // Create table rows with actual data
    while($row = $result->fetch_assoc()) {
        // Determine status based on is_verified
        $status = $row['is_verified'] == 1 ? 
            '<span class="px-2 py-1 bg-green-100 text-green-600 rounded-full text-xs">Active</span>' : 
            '<span class="px-2 py-1 bg-yellow-100 text-yellow-600 rounded-full text-xs">Pending</span>';
        
        // Format employee ID
        $employeeId = "#EMP-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
        
        // Format full name
        $fullName = $row['first_name'] . ' ' . $row['last_name'];
        
        // Create table row
        $tableContent .= '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">' . $employeeId . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($fullName) . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row['email']) . '</td>
            <td class="p-4 text-sm text-sidebar-text">Employee</td>
            <td class="p-4 text-sm">' . $status . '</td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="openEditEmployeeAccountModal(' . $row['id'] . ')">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all" onclick="deleteEmployeeAccount(' . $row['id'] . ')">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>';
    }
    
    // Update pagination info
    $startCount = min($offset + 1, $totalRows);
    $endCount = min($offset + $perPage, $totalRows);
    $paginationInfo = "Showing {$startCount}-{$endCount} of {$totalRows} employee accounts";
} else {
    // If no employees found, display a message
    $tableContent = '<tr class="border-b border-sidebar-border">
        <td colspan="6" class="p-4 text-sm text-center text-gray-500">No employee accounts found</td>
    </tr>';
    
    // Set pagination info for empty results
    $paginationInfo = "Showing 0 of 0 employee accounts";
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'tableContent' => $tableContent,
    'paginationInfo' => $paginationInfo,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);

// Close the database connection
$conn->close();
?>