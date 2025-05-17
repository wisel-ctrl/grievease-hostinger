<?php
//this is get_item_details.php file
//trial new account
include '../../db_connect.php';
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get inventory ID from request
$inventoryId = isset($_GET['inventory_id']) ? (int)$_GET['inventory_id'] : 0;

if ($inventoryId <= 0) {
  echo '<div class="p-4 bg-red-100 text-red-700 rounded-lg">Invalid inventory ID</div>';
  exit;
}

// Get detailed information about the item
$sql = "SELECT 
  i.inventory_id,
  i.item_name,
  i.category_id,
  c.category_name,
  i.quantity,
  i.price,
  i.inventory_img,
  (i.quantity * i.price) AS total_value,
  i.updated_at,
  b.branch_name
FROM inventory_tb i
JOIN inventory_category c ON i.category_id = c.category_id
JOIN branch_tb b ON i.branch_id = b.branch_id
WHERE i.inventory_id = $inventoryId AND i.status = 1";

$result = $conn->query($sql);

// Get all categories for the dropdown
$categoryQuery = "SELECT category_id, category_name FROM inventory_category ORDER BY category_name";
$categoryResult = $conn->query($categoryQuery);
$categories = [];
if ($categoryResult && $categoryResult->num_rows > 0) {
  while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row;
  }
}

if ($result && $result->num_rows > 0) {
  // Fetch item details
  $item = $result->fetch_assoc();
  
  // Display item details in a structured format
  ?>
  <form id="editInventoryForm">
    <input type="hidden" id="inventory_id" name="inventory_id" value="<?php echo $item["inventory_id"]; ?>">
    <!-- Row 1: ID, Name, Category -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="item_id" class="block text-sm font-medium text-gold">ID</label>
            <input type="text" id="item_id" class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="#INV-<?php echo str_pad($item["inventory_id"], 3, '0', STR_PAD_LEFT); ?>" readonly>
        </div>

        <div>
            <label for="item_name" class="block text-sm font-medium text-gold">Item Name</label>
            <input type="text" id="item_name" name="item_name" class="mt-1 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo htmlspecialchars($item["item_name"]); ?>">
        </div>

        <div>
            <label for="category_id" class="block text-sm font-medium text-gold">Category</label>
            <select id="category_id" name="category_id" class="mt-1 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold">
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['category_id']; ?>" <?php echo ($category['category_id'] == $item['category_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['category_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Row 2: Quantity, Price, Total Value -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
            <label for="quantity" class="block text-sm font-medium text-gold">Quantity</label>
            <input type="number" id="quantity" name="quantity" class="mt-1 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo $item["quantity"]; ?>">
        </div>

        <div>
            <label for="price" class="block text-sm font-medium text-gold">Unit Price</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-dark-gold">₱</span>
                </div>
                <input type="text" id="price" name="price" class="pl-7 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo number_format($item["price"], 2); ?>">
            </div>
        </div>

        <div>
            <label for="total_value" class="block text-sm font-medium text-gold">Total Value</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-dark-gold">₱</span>
                </div>
                <input type="text" id="total_value" name="total_value" class="pl-7 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold font-medium" value="<?php echo number_format($item["total_value"], 2); ?>">
            </div>
        </div>
    </div>

    <!-- Row 3: Last Updated -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div class="md:col-span-3">
            <label for="last_updated" class="block text-sm font-medium text-gold">Last Updated</label>
            <input type="text" id="last_updated" class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo date('M d, Y H:i', strtotime($item["updated_at"])); ?>" readonly>
        </div>
    </div>

    <!-- Image Preview -->
    <!-- Image Preview -->
    <div class="mt-6 flex flex-wrap gap-6 items-end">
        <div class="bg-dark-gold p-5 rounded-xl w-full md:w-auto">
            <div class="flex flex-col items-center space-y-3">
                <div class="w-full h-32 bg-center bg-cover rounded-lg shadow-md border-2 border-gold" 
                    style="background-image: url('uploads/inventory/<?php echo htmlspecialchars(basename($item["inventory_img"])); ?>');">
                </div>
                <span class="text-sm text-white">Item Image</span>
            </div>
        </div>
    </div>
</form>

  <?php
} else {
  echo '<div class="p-4 bg-red-100 text-red-700 rounded-lg">Item not found</div>';
}

// Close connection
$conn->close();
?>