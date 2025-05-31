<?php
require_once '../../db_connect.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Extract parameters
$branchId = $data['branch_id'] ?? 0;
$page = $data['page'] ?? 1;
$searchTerm = $data['search'] ?? '';
$categoryFilter = $data['category'] ?? '';
$statusFilter = $data['status'] ?? '';

// Pagination settings
$recordsPerPage = 5;
$offset = ($page - 1) * $recordsPerPage;

// Build WHERE clause
$whereClause = "WHERE branch_id = ? AND appearance = 'visible'";
$params = [$branchId];
$types = 'i';

if (!empty($searchTerm)) {
    $whereClause .= " AND (expense_name LIKE ? OR category LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $types .= 'ss';
}

if (!empty($categoryFilter)) {
    $whereClause .= " AND category = ?";
    $params[] = $categoryFilter;
    $types .= 's';
}

if (!empty($statusFilter)) {
    $whereClause .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

// Count total records
$countQuery = "SELECT COUNT(*) as total FROM expense_tb $whereClause";
$stmt = $conn->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$countResult = $stmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get records for current page
$query = "SELECT * FROM expense_tb $whereClause ORDER BY date DESC LIMIT ? OFFSET ?";
$params[] = $recordsPerPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Generate HTML for table rows
$html = '';
if ($result->num_rows > 0) {
    while($expense = $result->fetch_assoc()) {
        $statusClass = $expense['status'] == 'paid' 
            ? "bg-green-100 text-green-600 border border-green-200" 
            : "bg-orange-100 text-orange-500 border border-orange-200";
        $statusIcon = $expense['status'] == 'paid' ? "fa-check-circle" : "fa-clock";
        $statusText = $expense['status'] == 'paid' ? 'Paid' : 'To be paid';
        
        $html .= '
        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#EXP-'.str_pad($expense['expense_ID'], 3, "0", STR_PAD_LEFT).'</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">'.htmlspecialchars($expense['expense_name']).'</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    '.htmlspecialchars($expense['category']).'
                </span>
            </td>
            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱'.number_format($expense['price'], 2).'</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">'.$expense['date'].'</td>
            <td class="px-4 py-3.5 text-sm">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium '.$statusClass.'">
                    <i class="fas '.$statusIcon.' mr-1"></i> '.$statusText.'
                </span>
            </td>
            <td class="px-4 py-3.5 text-sm">
                <div class="flex space-x-2">
                    <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Expense" 
                            onclick="openEditExpenseModal(\'#EXP-'.str_pad($expense['expense_ID'], 3, "0", STR_PAD_LEFT).'\', 
                                \''.addslashes($expense['expense_name']).'\', 
                                \''.addslashes($expense['category']).'\', 
                                \''.$expense['price'].'\', 
                                \''.$expense['date'].'\', 
                                \''.$expense['branch_id'].'\', 
                                \''.$expense['status'].'\', 
                                \''.addslashes($expense['notes'] ?? '').'\')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" title="Archive Expense" 
                            onclick="archiveExpense(\'#EXP-'.str_pad($expense['expense_ID'], 3, "0", STR_PAD_LEFT).'\')">
                        <i class="fas fa-archive"></i>
                    </button>
                </div>
            </td>
        </tr>';
    }
} else {
    $html = '
    <tr>
        <td colspan="7" class="px-4 py-6 text-sm text-center">
            <div class="flex flex-col items-center">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No expenses found for this branch</p>
            </div>
        </td>
    </tr>';
}

// Generate pagination info
$start = $offset + 1;
$end = min($offset + $recordsPerPage, $totalRecords);
$paginationInfo = "Showing $start - $end of $totalRecords expenses";

// Generate pagination HTML
$paginationHtml = '';
if ($totalPages > 1) {
    // First page button
    $paginationHtml .= '<a href="#" onclick="loadExpenses('.$branchId.', 1); return false;" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover '.($page == 1 ? 'opacity-50 pointer-events-none' : '').'">«</a>';
    
    // Previous page button
    $paginationHtml .= '<a href="#" onclick="loadExpenses('.$branchId.', '.max(1, $page - 1).'); return false;" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover '.($page == 1 ? 'opacity-50 pointer-events-none' : '').'">‹</a>';
    
    // Page numbers
    $start_page = max(1, $page - 1);
    $end_page = min($totalPages, $page + 1);
    
    if ($page == 1) {
        $end_page = min($totalPages, 3);
    } elseif ($page == $totalPages) {
        $start_page = max(1, $totalPages - 2);
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active_class = ($i == $page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
        $paginationHtml .= '<a href="#" onclick="loadExpenses('.$branchId.', '.$i.'); return false;" class="px-3.5 py-1.5 rounded text-sm '.$active_class.'">'.$i.'</a>';
    }
    
    // Next page button
    $paginationHtml .= '<a href="#" onclick="loadExpenses('.$branchId.', '.min($totalPages, $page + 1).'); return false;" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover '.($page == $totalPages ? 'opacity-50 pointer-events-none' : '').'">›</a>';
    
    // Last page button
    $paginationHtml .= '<a href="#" onclick="loadExpenses('.$branchId.', '.$totalPages.'); return false;" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover '.($page == $totalPages ? 'opacity-50 pointer-events-none' : '').'">»</a>';
}
// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'html' => $html,
    'pagination_info' => $paginationInfo,
    'pagination_html' => $paginationHtml
]);


$conn->close();

?>