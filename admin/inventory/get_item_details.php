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
        <label for="item_name" class="block text-sm font-medium text-gold">Item Name <span class="text-red-500">*</span></label>
        <input type="text" id="item_name" name="item_name" class="mt-1 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo htmlspecialchars($item["item_name"]); ?>" required>
        <div id="item_name_error" class="text-red-500 text-xs mt-1 hidden">Please enter a valid name (letters only, 2+ characters, no consecutive spaces)</div>
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
            <input type="number" id="quantity" name="quantity" min="0" class="mt-1 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo $item["quantity"]; ?>">
            <div id="quantity_error" class="text-red-500 text-xs mt-1 hidden">Quantity must be 0 or more</div>
        </div>

        <div>
            <label for="price" class="block text-sm font-medium text-gold">Unit Price</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-dark-gold">₱</span>
                </div>
                <input type="number" id="price" name="price" min="0" step="0.01" class="pl-7 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold" value="<?php echo $item["price"]; ?>">
            </div>
            <div id="price_error" class="text-red-500 text-xs mt-1 hidden">Price must be 0.00 or more</div>
        </div>

        <div>
            <label for="total_value" class="block text-sm font-medium text-gold">Total Value</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-dark-gold">₱</span>
                </div>
                <input type="text" id="total_value" name="total_value" class="pl-7 block w-full px-3 py-2 bg-white border border-gold rounded-md shadow-sm text-gray-700 focus:outline-none focus:ring-dark-gold focus:border-dark-gold font-medium" value="₱<?php echo number_format($item["total_value"], 2); ?>" readonly>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Item Name validation - strict version
    const itemNameInput = document.getElementById('item_name');
    const itemNameError = document.getElementById('item_name_error');
    let lastValidValue = itemNameInput.value;
    
    itemNameInput.addEventListener('input', function(e) {
        const originalValue = e.target.value;
        let newValue = originalValue;
        
        // 1. Remove all numbers and special characters (keep only letters and spaces)
        newValue = newValue.replace(/[^a-zA-Z ]/g, '');
        
        // 2. Prevent consecutive spaces
        newValue = newValue.replace(/\s{2,}/g, ' ');
        
        // 3. Capitalize first letter of each word
        newValue = newValue.toLowerCase().replace(/(?:^|\s)\S/g, function(char) {
            return char.toUpperCase();
        });
        
        // 4. Prevent space as first character
        if (newValue.startsWith(' ')) {
            newValue = newValue.substring(1);
        }
        
        // 5. Don't allow space if less than 2 characters
        if (newValue.length < 2 && newValue.includes(' ')) {
            newValue = newValue.replace(/\s/g, '');
        }
        
        // Update the field if we made changes
        if (newValue !== originalValue) {
            e.target.value = newValue;
        }
        
        // Validate the final value
        const isValid = validateItemName(newValue);
        if (!isValid) {
            itemNameError.classList.remove('hidden');
        } else {
            itemNameError.classList.add('hidden');
            lastValidValue = newValue;
        }
    });
    
    // Handle paste event - clean the pasted text
    itemNameInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        
        // Clean the pasted text using same rules as input
        let cleanedText = pastedText.replace(/[^a-zA-Z ]/g, '')
                                   .replace(/\s{2,}/g, ' ')
                                   .toLowerCase()
                                   .replace(/(?:^|\s)\S/g, char => char.toUpperCase());
        
        if (cleanedText.startsWith(' ')) cleanedText = cleanedText.substring(1);
        if (cleanedText.length < 2 && cleanedText.includes(' ')) {
            cleanedText = cleanedText.replace(/\s/g, '');
        }
        
        // Insert at cursor position
        const startPos = e.target.selectionStart;
        const endPos = e.target.selectionEnd;
        const currentValue = e.target.value;
        
        const newValue = currentValue.substring(0, startPos) + cleanedText + currentValue.substring(endPos);
        e.target.value = newValue;
        
        // Trigger validation
        const isValid = validateItemName(newValue);
        if (!isValid) {
            itemNameError.classList.remove('hidden');
        } else {
            itemNameError.classList.add('hidden');
            lastValidValue = newValue;
        }
    });
    
    // Validate on blur (when field loses focus)
    itemNameInput.addEventListener('blur', function() {
        if (!validateItemName(this.value)) {
            this.value = lastValidValue;
            itemNameError.classList.add('hidden');
        }
    });
    
    // Validation function
    function validateItemName(value) {
        // Must be at least 2 characters
        if (value.length < 2) return false;
        
        // Must contain only letters and single spaces
        if (!/^[A-Za-z]+(?: [A-Za-z]+)*$/.test(value)) return false;
        
        // No consecutive spaces (handled by input cleaning)
        // First letter of each word capitalized (handled by input cleaning)
        return true;
    }
    
    // Quantity validation
    const quantityInput = document.getElementById('quantity');
    const quantityError = document.getElementById('quantity_error');
    
    quantityInput.addEventListener('change', function() {
        if (parseFloat(this.value) < 0) {
            quantityError.classList.remove('hidden');
            this.value = 0;
        } else {
            quantityError.classList.add('hidden');
        }
        calculateTotalValue();
    });
    
    // Price validation
    const priceInput = document.getElementById('price');
    const priceError = document.getElementById('price_error');
    
    priceInput.addEventListener('change', function() {
        if (parseFloat(this.value) < 0) {
            priceError.classList.remove('hidden');
            this.value = 0.00;
        } else {
            priceError.classList.add('hidden');
        }
        calculateTotalValue();
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