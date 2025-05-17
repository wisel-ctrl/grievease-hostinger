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
  <form id="editInventoryForm" class="space-y-3 sm:space-y-4">
  <!-- Item ID (readonly, no validation needed) -->
  <div>
    <label for="editItemId" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
      Item ID
    </label>
    <input type="text" id="editItemId" name="editItemId" value="<?php echo $item_id; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" readonly>
  </div>

  <!-- Item Name -->
  <div>
    <label for="editItemName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
      Item Name <span class="text-red-500">*</span>
    </label>
    <div class="relative">
      <input type="text" id="editItemName" name="editItemName" value="<?php echo $item_name; ?>" required 
             class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
             placeholder="Item Name"
             minlength="2"
             oninput="validateNameInput(this)"
             onpaste="handleNamePaste(event)">
      <div id="editItemNameError" class="text-red-500 text-xs mt-1 hidden">Please enter a valid name (letters only, min 2 chars)</div>
    </div>
  </div>

  <!-- Category Dropdown -->
  <div>
    <label for="category" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
      Category <span class="text-red-500">*</span>
    </label>
    <div class="relative">
      <select id="category" name="category" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        <option value="" disabled>Select a Category</option>
        <?php
        // Fetch categories again
        include '../db_connect.php';
        $sql = "SELECT category_id, category_name FROM inventory_category";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $selected = ($row['category_id'] == $category_id) ? 'selected' : '';
                echo '<option value="' . $row['category_id'] . '" ' . $selected . '>' . htmlspecialchars($row['category_name']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>No Categories Available</option>';
        }
        ?>
      </select>
      <div id="categoryError" class="text-red-500 text-xs mt-1 hidden">Please select a category</div>
    </div>
  </div>

  <!-- Quantity -->
  <div>
    <label for="editQuantity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
      Quantity <span class="text-red-500">*</span>
    </label>
    <div class="relative">
      <input type="number" id="editQuantity" name="editQuantity" value="<?php echo $quantity; ?>" min="0" required 
             class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
             placeholder="Quantity"
             oninput="validateQuantity(this)">
      <div id="editQuantityError" class="text-red-500 text-xs mt-1 hidden">Please enter a valid quantity (min 0)</div>
    </div>
  </div>

  <!-- Unit Price -->
  <div>
    <label for="editUnitPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
      Unit Price <span class="text-red-500">*</span>
    </label>
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <span class="text-gray-500">₱</span>
      </div>
      <input type="number" id="editUnitPrice" name="editUnitPrice" value="<?php echo $unit_price; ?>" step="0.01" min="0" required 
             class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
             placeholder="0.00"
             oninput="validateUnitPrice(this)">
      <div id="editUnitPriceError" class="text-red-500 text-xs mt-1 hidden">Please enter a valid price (min 0.00)</div>
    </div>
  </div>

  <!-- Current Image Preview -->
  <div class="bg-navy p-3 sm:p-4 rounded-lg">
    <div class="flex flex-col items-center space-y-2 sm:space-y-3">
      <div class="w-full h-32 bg-center bg-cover rounded-lg shadow-md" style="background-image: url('<?php echo $inventory_img; ?>');"></div>
      <span class="text-xs sm:text-sm text-gray-600">Current Image</span>
    </div>
  </div>

  <!-- File Upload -->
  <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
    <label for="editItemImage" class="block text-xs font-medium text-gray-700 mb-2 sm:mb-3 flex items-center">Upload New Image</label>
    <div class="relative">
      <input type="file" id="editItemImage" name="editItemImage" accept="image/*" 
             class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20"
             onchange="validateImage(this)">
      <div class="w-full px-3 py-2 border border-dashed border-gray-300 rounded-lg bg-gray-50 text-gray-500 text-xs sm:text-sm flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="17 8 12 3 7 8"></polyline>
          <line x1="12" y1="3" x2="12" y2="15"></line>
        </svg>
        Choose file or drag here
      </div>
      <div id="editItemImageError" class="text-red-500 text-xs mt-1 hidden">Please select a valid image file</div>
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