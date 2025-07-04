<?php
// inventory/load_inventory_table.php
include '../../db_connect.php';

// Get parameters
$branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Validate parameters
if ($branchId <= 0 || $page <= 0) {
    die("Invalid parameters");
}

// Set items per page
$itemsPerPage = 5;
$startItem = ($page - 1) * $itemsPerPage;

// Query to get total items count for this branch
$countSql = "SELECT COUNT(*) as total FROM inventory_tb WHERE branch_id = ? AND status = 1";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("i", $branchId);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalItems = $countResult->fetch_assoc()['total'];
$countStmt->close();

// Query to get paginated inventory items
$sql = "SELECT 
            i.inventory_id, 
            i.item_name, 
            c.category_name, 
            i.quantity, 
            i.price, 
            (i.quantity * i.price) AS total_value
        FROM inventory_tb i
        JOIN inventory_category c ON i.category_id = c.category_id
        WHERE i.branch_id = ? AND i.status = 1
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $branchId, $startItem, $itemsPerPage);
$stmt->execute();
$result = $stmt->get_result();

// Output table rows
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo generateInventoryRow($row);
    }
} else {
    echo '<tr>';
    echo '<td colspan="7" class="p-6 text-sm text-center">';
    echo '<div class="flex flex-col items-center">';
    echo '<i class="fas fa-box-open text-gray-300 text-4xl mb-3"></i>';
    echo '<p class="text-gray-500">No inventory items found for this branch</p>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
}

$stmt->close();
$conn->close();

function generateInventoryRow($row) {
    // Determine quantity cell class based on stock level
    $quantity = $row["quantity"];
    
    // Simplified stock level visualization with just three levels using text color
    if ($quantity <= 2) { // Critical stock
        $quantityClass = 'quantity-cell text-red-600 font-bold';
        $quantityText = $quantity . ' <span class="text-xs ml-1">(Critical)</span>';
        $stockIcon = '<i class="fas fa-exclamation-triangle mr-1"></i>';
    } elseif ($quantity <= 5) { // Low stock
        $quantityClass = 'quantity-cell text-yellow-600 font-medium';
        $quantityText = $quantity . ' <span class="text-xs ml-1">(Low)</span>';
        $stockIcon = '<i class="fas fa-arrow-down mr-1"></i>';
    } else { // Normal stock
        $quantityClass = 'quantity-cell text-green-600';
        $quantityText = $quantity;
    }
  
    $html = '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">';
    $html .= '<td class="p-4 text-sm text-sidebar-text font-medium">#INV-' . str_pad($row["inventory_id"], 3, '0', STR_PAD_LEFT) . '</td>';
    $html .= '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row["item_name"]) . '</td>';
    $html .= '<td class="p-4 text-sm text-sidebar-text">';
    $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">';
    $html .= htmlspecialchars($row["category_name"]) . '</span>';
    $html .= '</td>';
    
    // Enhanced quantity cell with visual indicators and proper padding
    $html .= '<td class="p-0 text-sm">';
    $html .= '<div class="' . $quantityClass . ' px-3 py-2 rounded-lg flex items-center justify-center">';
    $html .= $stockIcon . $quantityText;
    $html .= '</div>';
    $html .= '</td>';
    
    $html .= '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["price"] . '">₱' . number_format($row["price"], 2) . '</td>';
    $html .= '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["total_value"] . '">₱' . number_format($row["total_value"], 2) . '</td>';
    $html .= '<td class="p-4 text-sm">';
    $html .= '<div class="flex space-x-2">';
    $html .= '<button class="p-1.5 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-all tooltip" title="Edit Item" onclick="openViewItemModal(' . $row["inventory_id"] . ')">';
    $html .= '<i class="fas fa-edit text-base align-middle"></i>';
    $html .= '</button>';
    $html .= '<form method="POST" action="inventory/delete_inventory_item.php" onsubmit="return false;" style="display:inline;" class="delete-form">';
    $html .= '<input type="hidden" name="inventory_id" value="' . $row["inventory_id"] . '">';
    $html .= '<button type="submit" class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all tooltip" title="Archive Item">';
    $html .= '<i class="fas fa-archive text-base align-middle"></i>';
    $html .= '</button>';
    $html .= '</form>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    
    return $html;
  }
?>