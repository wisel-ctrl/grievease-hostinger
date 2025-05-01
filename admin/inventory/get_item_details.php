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
            <input type="text" id="item_name" name="item_name" 
                   class="mt-1 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" 
                   value="<?php echo htmlspecialchars($item["item_name"]); ?>"
                   oninput="validateItemName(this)"
                   pattern="^(?! )[A-Za-z0-9][A-Za-z0-9 ]*(?<! )$"
                   title="Item name cannot start/end with space or have consecutive spaces">
            <div id="itemNameError" class="text-red-500 text-xs mt-1 hidden">Item name cannot start/end with space or have consecutive spaces</div>
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
            <input type="number" id="quantity" name="quantity" 
                   class="mt-1 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" 
                   value="<?php echo $item["quantity"]; ?>"
                   min="0"
                   oninput="validateQuantity(this)">
        </div>

        <div>
            <label for="price" class="block text-sm font-medium text-gold">Unit Price</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-dark-gold">₱</span>
                </div>
                <input type="text" id="price" name="price" 
                       class="pl-7 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" 
                       value="<?php echo number_format($item["price"], 2); ?>"
                       oninput="validatePrice(this)"
                       pattern="^\d+(\.\d{1,2})?$"
                       title="Please enter a valid price (e.g. 10.99)">
            </div>
        </div>

        <div>
            <label for="total_value" class="block text-sm font-medium text-gold">Total Value</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-dark-gold">₱</span>
                </div>
                <input type="text" id="total_value" name="total_value" 
                       class="pl-7 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold font-medium" 
                       value="<?php echo number_format($item["total_value"], 2); ?>"
                       readonly>
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

    <!-- Expanded Image Preview -->
    <div class="mt-6">
        <div class="bg-dark-gold p-5 rounded-xl">
            <div class="flex flex-col items-center space-y-3">
                <div class="w-full h-64 bg-center bg-cover rounded-lg shadow-md border-2 border-gold" 
                    style="background-image: url('uploads/inventory/<?php echo htmlspecialchars(basename($item["inventory_img"])); ?>');"
                    id="itemImagePreview">
                </div>
                <span class="text-sm text-white">Item Image - Click to View Full Size</span>
            </div>
        </div>
    </div>
</form>

<script>
// Item Name Validation
function validateItemName(input) {
    const errorElement = document.getElementById('itemNameError');
    let value = input.value;
    
    // Check if first character is space
    if (value.length > 0 && value.charAt(0) === ' ') {
        errorElement.classList.remove('hidden');
        input.value = value.trim();
        return;
    }
    
    // Check for consecutive spaces
    if (value.includes('  ')) {
        errorElement.classList.remove('hidden');
        input.value = value.replace(/\s+/g, ' ');
        return;
    }
    
    // Check if last character is space
    if (value.length > 0 && value.charAt(value.length - 1) === ' ') {
        errorElement.classList.remove('hidden');
        input.value = value.trim();
        return;
    }
    
    errorElement.classList.add('hidden');
    
    // Auto-capitalize first letter
    if (value.length === 1) {
        input.value = value.charAt(0).toUpperCase() + value.slice(1);
    }
}

// Quantity Validation
function validateQuantity(input) {
    if (input.value < 0) {
        input.value = 0;
    }
    calculateTotalValue();
}

// Price Validation
function validatePrice(input) {
    // Remove any non-digit and non-dot characters
    input.value = input.value.replace(/[^\d.]/g, '');
    
    // Ensure only one dot is present
    const dotCount = (input.value.match(/\./g) || []).length;
    if (dotCount > 1) {
        input.value = input.value.substring(0, input.value.lastIndexOf('.'));
    }
    
    // Ensure max 2 decimal places
    if (input.value.includes('.')) {
        const parts = input.value.split('.');
        if (parts[1].length > 2) {
            input.value = parts[0] + '.' + parts[1].substring(0, 2);
        }
    }
    
    calculateTotalValue();
}

// Calculate Total Value
function calculateTotalValue() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const price = parseFloat(document.getElementById('price').value.replace(/[^\d.]/g, '')) || 0;
    const totalValue = quantity * price;
    
    document.getElementById('total_value').value = totalValue.toFixed(2);
}

// Image Preview Click Handler
document.getElementById('itemImagePreview').addEventListener('click', function() {
    const imgUrl = this.style.backgroundImage.replace(/url\(['"]?(.*?)['"]?\)/, '$1');
    Swal.fire({
        imageUrl: imgUrl,
        imageAlt: 'Item Image',
        showConfirmButton: false,
        background: 'transparent',
        backdrop: `
            rgba(0,0,0,0.8)
            url("${imgUrl}")
            center top
            no-repeat
        `,
        width: '80%',
        padding: 0,
        showCloseButton: true,
        closeButtonHtml: '<i class="fas fa-times text-white text-2xl"></i>',
        customClass: {
            container: '!bg-transparent',
            popup: '!bg-transparent !shadow-none',
            closeButton: '!text-white hover:!text-gold !top-4 !right-4'
        }
    });
});

// Initialize validation on form load
document.addEventListener('DOMContentLoaded', function() {
    validateItemName(document.getElementById('item_name'));
    validatePrice(document.getElementById('price'));
});
</script>
  <?php
} else {
  echo '<div class="p-4 bg-red-100 text-red-700 rounded-lg">Item not found</div>';
}

// Close connection
$conn->close();
?
[file content end]