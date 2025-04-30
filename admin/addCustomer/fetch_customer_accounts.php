<?php
// fetch_customer_accounts.php
// Include the database connection
require_once('../../db_connect.php');

// Pagination settings
$usersPerPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Initialize search and sorting variables
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'id_asc';

// Calculate offset
$offset = ($page - 1) * $usersPerPage;

// Base SQL query
$baseSql = "FROM users WHERE user_type = 3 AND is_verified = 1";

// Add search condition if search term is provided
if (!empty($search)) {
    // Remove '#CUST-' prefix and leading zeros if present
    $cleanedSearch = preg_replace('/^#?CUST-?0*/', '', $search);
    
    $baseSql .= " AND (
        first_name LIKE '%$search%' OR 
        last_name LIKE '%$search%' OR 
        email LIKE '%$search%' OR 
        id LIKE '%$cleanedSearch%'
    )";
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
    default:
        $orderBy = " ORDER BY id ASC";
}

// Count total rows
$countQuery = "SELECT COUNT(*) as total $baseSql";
$countResult = $conn->query($countQuery);
$totalRows = $countResult->fetch_assoc()['total'];

// Calculate total pages
$totalPages = ceil($totalRows / $usersPerPage);

// Modify base SQL to include pagination
$sql = "SELECT id, first_name, last_name, email, is_verified $baseSql $orderBy LIMIT $usersPerPage OFFSET $offset";
$result = $conn->query($sql);

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
        
        // Format customer ID
        $customerId = "#CUST-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
        
        // Format full name
        $fullName = ucfirst(strtolower($row['first_name'])) . ' ' . ucfirst(strtolower($row['last_name']));
        
        // Create table row
        $tableContent .= '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">' . $customerId . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($fullName) . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row['email']) . '</td>
            <td class="p-4 text-sm text-sidebar-text">Customer</td>
            <td class="p-4 text-sm">' . $status . '</td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-all" onclick="openEditCustomerAccountModal(' . $row['id'] . ')">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all" onclick="deleteCustomerAccount(' . $row['id'] . ')">
                   <i class="fas fa-archive text-red"></i>
                </button>
              </div>
            </td>
          </tr>';
    }
    
    // Calculate start and end for current page
    $start = ($page - 1) * $usersPerPage + 1;
    $end = min($start + $usersPerPage - 1, $totalRows);
    
    // Update pagination info
    $paginationInfo = "Showing $start - $end of $totalRows customer accounts";
} else {
    // If no customers found, display a message
    $tableContent = '<tr class="border-b border-sidebar-border">
        <td colspan="6" class="p-4 text-sm text-center text-gray-500">No customer accounts found</td>
    </tr>';
    
    // Set pagination info for empty results
    $paginationInfo = "Showing 0 of 0 customer accounts";
}

$response = [
    'tableContent' => $tableContent,
    'showingFrom' => $offset + 1,
    'showingTo' => min($offset + $itemsPerPage, $totalCustomers),
    'totalCount' => $totalCustomers,
    'totalPages' => $totalPages,
    'currentPage' => $page
];
?>