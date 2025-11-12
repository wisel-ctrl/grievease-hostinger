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
  i.selling_price, 
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
            <label for="item_name" class="block text-sm font-medium text-gold">Item Name <span class="text-red-500">*</span></label>
            <input type="text" id="item_name" name="item_name" class="mt-1 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo htmlspecialchars($item["item_name"]); ?>" required minlength="2" maxlength="30" pattern="[A-Za-z ]+" title="Only letters and single spaces allowed">
            <div id="item_name_error" class="text-red-500 text-xs mt-1 hidden">Please enter a valid item name (letters only, minimum 2 characters)</div>
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

    <!-- Row 2: Quantity, Price, Selling Price, Total Value -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4"> 
        <div>
            <label for="quantity" class="block text-sm font-medium text-gold">Quantity</label>
            <input type="number" id="quantity" name="quantity" min="0" max="999999" class="mt-1 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo $item["quantity"]; ?>">
            <div id="quantity_error" class="text-red-500 text-xs mt-1 hidden">Quantity must be 0 or more</div>
        </div>
    
        <div>
            <label for="price" class="block text-sm font-medium text-gold">Unit Price</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-dark-gold">₱</span>
                </div>
                <input type="number" id="price" name="price" min="0" max="999999999" step="0.01" class="pl-7 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo $item["price"]; ?>">
            </div>
            <div id="price_error" class="text-red-500 text-xs mt-1 hidden">Price must be 0.00 or more</div>
        </div>
    
        <div>
            <label for="selling_price" class="block text-sm font-medium text-gold">Selling Price</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-dark-gold">₱</span>
                </div>
                <input type="number" id="selling_price" name="selling_price" min="0" max="999999999" step="0.01" class="pl-7 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo $item["selling_price"]; ?>">
            </div>
            <div id="selling_price_error" class="text-red-500 text-xs mt-1 hidden">Selling Price must be 0.00 or more</div>
        </div>
    
        <div>
            <label for="total_value" class="block text-sm font-medium text-gold">Total Value</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-dark-gold">₱</span>
                </div>
                <input type="text" id="total_value" name="total_value" class="pl-7 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold font-medium" value="₱<?php echo number_format($item["total_value"], 2); ?>">
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
    <div class="mt-6">
        <div class="bg-dark-gold p-5 rounded-xl w-full">
            <div class="flex flex-col items-center space-y-3">
                <div class="w-full h-48 sm:h-56 bg-center bg-cover rounded-lg shadow-md border-2 border-gold" 
                    style="background-image: url('uploads/inventory/<?php echo htmlspecialchars(basename($item["inventory_img"])); ?>');">
                </div>
                <span class="text-sm text-white">Item Image</span>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Item Name validation
    const itemNameInput = document.getElementById('item_name');
    const itemNameError = document.getElementById('item_name_error');
    
    // Selling Price validation
    const sellingPriceInput = document.getElementById('selling_price');
    const sellingPriceError = document.getElementById('selling_price_error');
    
    // Price input
    const priceInput = document.getElementById('price');
    
    // Quantity validation
    const quantityInput = document.getElementById('quantity');
    const quantityError = document.getElementById('quantity_error');
    
    // Price error
    const priceError = document.getElementById('price_error');

    // Real-time selling price validation
    function validateSellingPrice() {
        const sellingPrice = parseFloat(sellingPriceInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        
        if (sellingPrice <= price) {
            sellingPriceInput.classList.add('border-red-500', 'bg-red-50');
            // Show error message
            if (!document.getElementById('sellingPriceValidationError')) {
                const errorDiv = document.createElement('div');
                errorDiv.id = 'sellingPriceValidationError';
                errorDiv.className = 'text-red-500 text-xs mt-1';
                errorDiv.textContent = `Selling Price must be greater than Unit Price (₱${price.toFixed(2)})`;
                sellingPriceInput.parentNode.appendChild(errorDiv);
            }
        } else {
            sellingPriceInput.classList.remove('border-red-500', 'bg-red-50');
            const errorDiv = document.getElementById('sellingPriceValidationError');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
    }
    
    // Real-time validation
    sellingPriceInput.addEventListener('input', validateSellingPrice);
    priceInput.addEventListener('input', validateSellingPrice);
    
    // Initial validation
    validateSellingPrice();
    
    sellingPriceInput.addEventListener('change', function() {
        if (parseFloat(this.value) < 0) {
            sellingPriceError.classList.remove('hidden');
            this.value = 0.00;
        } else {
            sellingPriceError.classList.add('hidden');
        }
        validateSellingPrice();
    });
    
    itemNameInput.addEventListener('input', function(e) {
        // Clean input: remove numbers and symbols, allow only letters and single spaces
        let value = e.target.value.replace(/[^a-zA-Z ]/g, '');
        
        // Capitalize first letter of each word
        value = value.toLowerCase().replace(/(?:^|\s)\S/g, function(char) {
            return char.toUpperCase();
        });
        
        // Prevent multiple consecutive spaces
        value = value.replace(/\s{2,}/g, ' ');
        
        // Don't allow space as first character or if less than 2 characters
        if (value.length < 2 && value.includes(' ')) {
            value = value.replace(/\s/g, '');
        }
        
        e.target.value = value;
        
        // Validate
        if (value.length < 2) {
            itemNameError.classList.remove('hidden');
        } else {
            itemNameError.classList.add('hidden');
        }
    });
    
    // Handle paste event for item name
    itemNameInput.addEventListener('paste', function(e) {
        e.preventDefault();
        let pastedText = (e.clipboardData || window.clipboardData).getData('text');
        
        // Clean pasted text
        pastedText = pastedText.replace(/[^a-zA-Z ]/g, '');
        pastedText = pastedText.toLowerCase().replace(/(?:^|\s)\S/g, function(char) {
            return char.toUpperCase();
        });
        pastedText = pastedText.replace(/\s{2,}/g, ' ');
        
        // Insert cleaned text at cursor position
        const startPos = e.target.selectionStart;
        const endPos = e.target.selectionEnd;
        const currentValue = e.target.value;
        
        e.target.value = currentValue.substring(0, startPos) + pastedText + currentValue.substring(endPos);
        
        // Trigger input event for validation
        e.target.dispatchEvent(new Event('input'));
    });
    
    quantityInput.addEventListener('change', function() {
        if (parseFloat(this.value) < 0) {
            quantityError.classList.remove('hidden');
            this.value = 0;
        } else {
            quantityError.classList.add('hidden');
        }
        calculateTotalValue();
    });
    
    priceInput.addEventListener('change', function() {
        if (parseFloat(this.value) < 0) {
            priceError.classList.remove('hidden');
            this.value = 0.00;
        } else {
            priceError.classList.add('hidden');
        }
        calculateTotalValue();
        validateSellingPrice(); // Re-validate selling price when price changes
    });
    
    // Calculate total value when quantity or price changes
    quantityInput.addEventListener('input', calculateTotalValue);
    priceInput.addEventListener('input', calculateTotalValue);
    
    function calculateTotalValue() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const totalValue = quantity * price;
        
        // Format the total value with currency symbol and 2 decimal places
        document.getElementById('total_value').value = '₱' + totalValue.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Initialize total value on page load
    calculateTotalValue();
});
</script>

  <?php
} else {
  echo '<div class="p-4 bg-red-100 text-red-700 rounded-lg">Item not found</div>';
}

// Close connection
$conn->close();
?>