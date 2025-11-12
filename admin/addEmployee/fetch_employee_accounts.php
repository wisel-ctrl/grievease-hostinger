<?php
// Fetch employee accounts
require_once('../../db_connect.php');

// Initialize variables
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'id_desc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Base SQL query - matching the customer script structure
$baseSql = "FROM users WHERE user_type = 2 AND is_verified = 1";

// Add search condition if search term is provided
if (!empty($search)) {
    $baseSql .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}

// Add sorting based on selected option
switch ($sort) {
    case 'id_asc':
        $orderBy = " ORDER BY id ASC";
        break;
    case 'id_desc':
        $orderBy = " ORDER BY id DESC";
        break;
    case 'name_asc':
        $orderBy = " ORDER BY first_name ASC, last_name ASC";
        break;
    case 'name_desc':
        $orderBy = " ORDER BY first_name DESC, last_name DESC";
        break;
    case 'email_asc':
        $orderBy = " ORDER BY email ASC";
        break;
    case 'email_desc':
        $orderBy = " ORDER BY email DESC";
        break;
    case 'newest':
        $orderBy = " ORDER BY created_at DESC";
        break;
    case 'oldest':
        $orderBy = " ORDER BY created_at ASC";
        break;
    default:
        $orderBy = " ORDER BY id DESC";
}

// Count total rows
$countQuery = "SELECT COUNT(*) as total $baseSql";
$countResult = $conn->query($countQuery);
$totalRows = $countResult->fetch_assoc()['total'];

// Calculate total pages
$totalPages = ceil($totalRows / $perPage);

// Modify base SQL to include pagination
$sql = "SELECT id, first_name, last_name, email, is_verified, created_at $baseSql $orderBy LIMIT $perPage OFFSET $offset";
$result = $conn->query($sql);

// Initialize empty table content
$tableContent = '';

// Check if there are results
if ($result->num_rows > 0) {
    // Create table rows with actual data
    while($row = $result->fetch_assoc()) {
        // Format employee ID
        $employeeId = "#EMP-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
        
        // Format full name
        $fullName = $row['first_name'] . ' ' . $row['last_name'];
        
        // Create table row - REMOVED ROLE AND STATUS COLUMNS
        $tableContent .= '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">' . $employeeId . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($fullName) . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row['email']) . '</td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="openEditEmployeeAccountModal(' . $row['id'] . ')">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all" onclick="deleteEmployeeAccount(' . $row['id'] . ')">
                  <i class="fas fa-archive text-red"></i>
                </button>
              </div>
            </td>
          </tr>';
    }
    
    // Calculate start and end for current page
    $start = ($page - 1) * $perPage + 1;
    $end = min($start + $perPage - 1, $totalRows);
    
    // Update pagination info
    $paginationInfo = "Showing $start - $end of $totalRows employee accounts";
} else {
    // If no employees found, display a message - UPDATED COLSPAN FROM 6 TO 4
    $tableContent = '<tr class="border-b border-sidebar-border">
        <td colspan="4" class="p-4 text-sm text-center text-gray-500">No employee accounts found</td>
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
    'currentPage' => $page,
    'totalCount' => $totalRows // Add this line
]);

// Close the database connection
$conn->close();
?>