<?php
// fetch_customer_accounts.php
// Include the database connection
require_once('../../db_connect.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has a user_id
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

// Get the current user's branch location
$current_user_id = $_SESSION['user_id'];

// First, get the branch location of the current user
$branch_query = "SELECT branch_loc FROM users WHERE id = ?";
$stmt = $conn->prepare($branch_query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$branch_result = $stmt->get_result();

if ($branch_result->num_rows === 0) {
    die(json_encode(['error' => 'User not found']));
}

$user_data = $branch_result->fetch_assoc();
$branch_loc = $user_data['branch_loc'];

// Pagination settings
$usersPerPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Initialize search and sorting variables
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'id_asc';

// Calculate offset
$offset = ($page - 1) * $usersPerPage;

// Base SQL query - now filtering by branch_loc
$baseSql = "FROM users WHERE user_type = 3 AND is_verified = 1 AND branch_loc = '$branch_loc'";

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

// Calculate start and end for current page
$start = ($page - 1) * $usersPerPage + 1;
$end = min($start + $usersPerPage - 1, $totalRows);

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
                <button class="p-1.5 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-all" 
                        onclick="openEditCustomerAccountModal(' . $row['id'] . ')">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all" 
                        onclick="archiveCustomerAccount(' . $row['id'] . ')">
                  <i class="fas fa-archive"></i>
                </button>
              </div>
            </td>
        </tr>';
    }
} else {
    // If no customers found, display a message
    $tableContent = '<tr class="border-b border-sidebar-border">
        <td colspan="6" class="p-4 text-sm text-center text-gray-500">No customer accounts found for your branch</td>
    </tr>';
    
    // Reset start and end when no results
    $start = 0;
    $end = 0;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'tableContent' => $tableContent,
    'showingFrom' => $start,
    'showingTo' => $end,
    'totalCount' => $totalRows,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>