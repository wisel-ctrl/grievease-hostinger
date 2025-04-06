<?php
include '../../db_connect.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get branch ID from POST
$branchId = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;

// Get branch name
$branchQuery = "SELECT branch_name FROM branch_tb WHERE branch_id = $branchId";
$branchResult = $conn->query($branchQuery);
$branchName = "";
if ($branchResult->num_rows > 0) {
    $branchRow = $branchResult->fetch_assoc();
    $branchName = $branchRow["branch_name"];
}

// Get archived items
$sql = "SELECT 
        inventory_id, 
        item_name
        FROM inventory_tb
        WHERE branch_id = $branchId AND status = 0";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<div class='w-full overflow-x-auto rounded-lg border border-gray-200'>";
    echo "<table class='w-full'>";
    echo "<thead>";
    echo "<tr class='bg-gray-100'>";
    echo "<th class='p-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-1/6'>ID</th>"; // Equal width
    echo "<th class='p-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-4/6'>Item Name</th>"; // Wider for content
    echo "<th class='p-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-1/6'>Actions</th>"; // Equal width
    echo "</tr>";
    echo "</thead>";
    echo "<tbody class='divide-y divide-gray-200'>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr class='hover:bg-gray-50'>";
        echo "<td class='p-3 text-sm font-medium text-gray-900 text-center align-middle'>#INV-" . str_pad($row["inventory_id"], 3, '0', STR_PAD_LEFT) . "</td>";
// In your get_archived_items.php, modify the item name line:
    echo "<td class='p-3 text-sm text-gray-700 text-center align-middle'><span class='item-name'>" . htmlspecialchars($row["item_name"]) . "</span></td>";        echo "<td class='p-3 text-sm text-center align-middle'>";
        echo "<button class='px-3 py-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all text-xs font-medium whitespace-nowrap' onclick='unarchiveItem(" . $row["inventory_id"] . ", " . $branchId . ")'>";
        echo "<i class='fas fa-undo-alt mr-1'></i> Unarchive";
        echo "</button>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
} else {
    echo "<div class='p-4 bg-gray-100 text-gray-700 rounded-lg text-center'>No archived items found for this branch.</div>";
}

$conn->close();
?>