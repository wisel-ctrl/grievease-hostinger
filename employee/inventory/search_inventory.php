<?php
// search_inventory.php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get branch ID from session
$user_id = $_SESSION['user_id'];
$query = "SELECT branch_loc FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$branch_id = $employee['branch_loc'];

// Get search term
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchTerm = $conn->real_escape_string($searchTerm);

// Pagination setup
$itemsPerPage = 5;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Base query
$query = "SELECT i.inventory_id, i.item_name, ic.category_name as category, 
          i.quantity, i.price, i.total_value, i.status
          FROM inventory_tb i
          JOIN inventory_category ic ON i.category_id = ic.category_id
          WHERE i.branch_id = ? AND i.status = 1";

// Add search conditions if search term exists
if (!empty($searchTerm)) {
    $query .= " AND (i.item_name LIKE '%$searchTerm%' OR 
                    ic.category_name LIKE '%$searchTerm%' OR 
                    i.inventory_id LIKE '%$searchTerm%' OR
                    i.quantity LIKE '%$searchTerm%' OR
                    i.price LIKE '%$searchTerm%' OR
                    i.total_value LIKE '%$searchTerm%')";
}

// Count total items for pagination
$count_query = "SELECT COUNT(*) as total FROM inventory_tb i
                JOIN inventory_category ic ON i.category_id = ic.category_id
                WHERE i.branch_id = ? AND i.status = 1";
if (!empty($searchTerm)) {
    $count_query .= " AND (i.item_name LIKE '%$searchTerm%' OR 
                          ic.category_name LIKE '%$searchTerm%' OR 
                          i.inventory_id LIKE '%$searchTerm%')";
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $branch_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$totalItems = $count_result->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Add pagination to main query
$query .= " LIMIT ? OFFSET ?";

// Execute main query
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $branch_id, $itemsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Generate table rows
$rows = '';
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rows .= generateInventoryRow($row);
    }
} else {
    $rows .= '<tr>';
    $rows .= '<td colspan="7" class="p-6 text-sm text-center">';
    $rows .= '<div class="flex flex-col items-center">';
    $rows .= '<i class="fas fa-box-open text-gray-300 text-4xl mb-3"></i>';
    $rows .= '<p class="text-gray-500">No inventory items found matching your search</p>';
    $rows .= '</div>';
    $rows .= '</td>';
    $rows .= '</tr>';
}

// Generate pagination info
$paginationInfo = 'Showing ' . min(($currentPage - 1) * $itemsPerPage + 1, $totalItems) . ' - ' . 
                 min($currentPage * $itemsPerPage, $totalItems) . ' of ' . $totalItems . ' items';

// Generate pagination links
$paginationLinks = '';
if ($currentPage > 1) {
    $paginationLinks .= '<a href="#" onclick="searchInventory('.$branch_id.', \''.addslashes($searchTerm).'\', '.($currentPage - 1).')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&laquo;</a>';
} else {
    $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">&laquo;</button>';
}

for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i == $currentPage) ? 'bg-sidebar-accent text-white' : '';
    $paginationLinks .= '<a href="#" onclick="searchInventory('.$branch_id.', \''.addslashes($searchTerm).'\', '.$i.')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover '.$activeClass.'">'.$i.'</a>';
}

if ($currentPage < $totalPages) {
    $paginationLinks .= '<a href="#" onclick="searchInventory('.$branch_id.', \''.addslashes($searchTerm).'\', '.($currentPage + 1).')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</a>';
} else {
    $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">&raquo;</button>';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'rows' => $rows,
    'paginationInfo' => $paginationInfo,
    'paginationLinks' => $paginationLinks
]);

// Reuse your existing generateInventoryRow function
function generateInventoryRow($row) {
    $html = '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text font-medium whitespace-nowrap">#INV-'.$row['inventory_id'].'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text font-medium whitespace-nowrap">'.htmlspecialchars($row['item_name']).'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text whitespace-nowrap">'.htmlspecialchars($row['category']).'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text whitespace-nowrap">'.$row['quantity'].'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text whitespace-nowrap">₱'.number_format($row['price'], 2).'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text whitespace-nowrap">₱'.number_format($row['total_value'], 2).'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm whitespace-nowrap">';
    $html .= '<div class="flex items-center gap-2">';
    $html .= '<button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all edit-btn" data-id="'.$row['inventory_id'].'">';
    $html .= '<i class="fas fa-edit"></i>';
    $html .= '</button>';
    $html .= '<button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all archive-btn" data-id="'.$row['inventory_id'].'" data-name="'.htmlspecialchars($row['item_name']).'">';
    $html .= '<i class="fas fa-archive"></i>';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    return $html;
}
?>