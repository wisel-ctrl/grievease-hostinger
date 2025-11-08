function openArchivedModal() {
  // Show the modal
  document.getElementById('archivedItemsModal').classList.remove('hidden');
  // Prevent background scrolling
  document.body.style.overflow = 'hidden';
}

// Function to close the modal
function closeArchivedModal() {
  $('#archivedItemsModal').addClass('hidden');
  // Hide the modal
  document.getElementById('archivedItemsModal').classList.add('hidden');
  // Restore background scrolling
  document.body.style.overflow = 'auto';
}

// In the unarchiveItem function
function unarchiveItem(itemId, branchId) {
  Swal.fire({
      title: 'Unarchive this item?',
      text: 'You want to restore this item to active inventory?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, unarchive it!',
      cancelButtonText: 'No, cancel'
  }).then((result) => {
      if (result.isConfirmed) {
          // AJAX request to unarchive
          $.ajax({
              url: 'inventory/unarchive_item.php',
              type: 'POST',
              data: { 
                  inventory_id: itemId,
                  branch_id: branchId 
              },
              success: function(response) {
                  showArchivedItems(branchId);
                  location.reload();
              },
              error: function(xhr, status, error) {
                  Swal.fire({
                      icon: 'error',
                      title: 'Error',
                      text: 'Error unarchiving item: ' + error
                  });
              }
          });
      }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.branch-container').forEach(container => {
    const branchId = container.dataset.branchId;
    const currentPage = new URLSearchParams(window.location.search).get(`page_${branchId}`) || 1;
    updatePaginationActiveState(branchId, currentPage);
  });

  // Toggle filter dropdowns
  document.querySelectorAll('[id^="filterButton_"]').forEach(button => {
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        const branchId = this.id.split('_')[1];
        const dropdown = document.getElementById(`filterDropdown_${branchId}`);
        dropdown.classList.toggle('hidden');
    });
  });

  // Close dropdowns when clicking outside
  document.addEventListener('click', function(e) {
    if (!e.target.matches('[id^="filterButton_"]')) {
        document.querySelectorAll('[id^="filterDropdown_"]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
  });

  // Handle filter option clicks
  document.querySelectorAll('.filter-option').forEach(option => {
    option.addEventListener('click', function(e) {
        e.preventDefault();
        const sortType = this.getAttribute('data-sort');
        const branchId = this.getAttribute('data-branch');
        applyFilter(branchId, sortType);
        
        // Close the dropdown
        document.getElementById(`filterDropdown_${branchId}`).classList.add('hidden');
    });
  });

  // Search functionality
  document.querySelectorAll('[id^="searchBox_"]').forEach(searchBox => {
    searchBox.addEventListener('keyup', function() {
        const branchId = this.id.split('_')[1];
        const searchTerm = this.value.toLowerCase().trim();
        const table = document.getElementById(`inventoryTable_${branchId}`);
        const rows = table.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let shouldShow = false;

            // Search across all columns except the last (actions) column
            for (let j = 0; j < cells.length - 1; j++) {
                const cellText = cells[j].textContent.toLowerCase();
                if (cellText.includes(searchTerm)) {
                    shouldShow = true;
                    break;
                }
            }

            // Toggle row visibility
            rows[i].style.display = shouldShow ? '' : 'none';
        }
    });
  });

  // Apply quantity heatmap
  applyQuantityHeatmap();

  // Initialize file input handling
  handleFileInput();

  // Optional: Add trigger buttons for opening the add inventory modal
  const addInventoryButtons = document.querySelectorAll('.open-add-inventory-modal');
  if (addInventoryButtons) {
    addInventoryButtons.forEach(button => {
      button.addEventListener('click', openAddInventoryModal);
    });
  }

  // Real-time validation for unitPrice and sellingPrice
  const unitPriceInput = document.getElementById('unitPrice');
  const sellingPriceInput = document.getElementById('sellingPrice');
  const errorMessage = document.createElement('p');
  errorMessage.id = 'priceError';
  errorMessage.className = 'text-red-500 text-sm mt-1 hidden';
  errorMessage.textContent = 'Selling Price must be greater than Unit Price.';
  
  // Insert error message after sellingPrice input
  if (sellingPriceInput && sellingPriceInput.parentNode) {
    sellingPriceInput.parentNode.insertBefore(errorMessage, sellingPriceInput.nextSibling);
  }

  function validatePrices() {
    const unitPrice = parseFloat(unitPriceInput.value.replace(/[^0-9.]/g, '')) || 0;
    const sellingPrice = parseFloat(sellingPriceInput.value.replace(/[^0-9.]/g, '')) || 0;
    
    if (sellingPrice <= unitPrice && sellingPrice > 0) {
      errorMessage.classList.remove('hidden');
      sellingPriceInput.classList.add('border-red-500');
    } else {
      errorMessage.classList.add('hidden');
      sellingPriceInput.classList.remove('border-red-500');
    }
  }

  if (unitPriceInput) unitPriceInput.addEventListener('input', validatePrices);
  if (sellingPriceInput) sellingPriceInput.addEventListener('input', validatePrices);
});

function applyFilter(branchId, sortType) {
  const table = document.getElementById(`inventoryTable_${branchId}`);
  const rows = Array.from(table.getElementsByTagName('tr'));

  // Remove any existing sorting classes
  table.querySelectorAll('th').forEach(th => {
    th.classList.remove('sorted-asc', 'sorted-desc');
  });

  // Sort based on the selected option
  switch(sortType) {
    case 'default':
        resetTableSort(table);
        break;
    case 'price_asc':
        sortTableByColumn(table, rows, 4, 'asc', 'number');
        break;
    case 'price_desc':
        sortTableByColumn(table, rows, 4, 'desc', 'number');
        break;
    case 'quantity_asc':
        sortTableByColumn(table, rows, 3, 'asc', 'number');
        break;
    case 'quantity_desc':
        sortTableByColumn(table, rows, 3, 'desc', 'number');
        break;
    case 'name_asc':
        sortTableByColumn(table, rows, 1, 'asc', 'text');
        break;
    case 'name_desc':
        sortTableByColumn(table, rows, 1, 'desc', 'text');
        break;
  }
}

function resetTableSort(table) {
  const tbody = table;
  const rows = Array.from(tbody.rows);

  // Sort by original position (data-index attribute)
  rows.sort((a, b) => {
    const indexA = parseInt(a.getAttribute('data-index')) || 0;
    const indexB = parseInt(b.getAttribute('data-index')) || 0;
    return indexA - indexB;
  });

  // Re-append rows in original order
  rows.forEach(row => {
    tbody.appendChild(row);
  });
}

function sortTableByColumn(table, rows, columnIndex, direction, type) {
  // First, set data-index attributes to remember original positions
  rows.forEach((row, index) => {
    row.setAttribute('data-index', index);
  });

  // Sort the rows
  rows.sort((a, b) => {
    let aValue, bValue;
    
    if (type === 'number') {
        aValue = parseFloat(a.cells[columnIndex].getAttribute('data-sort-value'));
        bValue = parseFloat(b.cells[columnIndex].getAttribute('data-sort-value'));
    } else {
        aValue = a.cells[columnIndex].textContent.trim().toLowerCase();
        bValue = b.cells[columnIndex].textContent.trim().toLowerCase();
    }
    
    if (direction === 'asc') {
        return aValue > bValue ? 1 : (aValue < bValue ? -1 : 0);
    } else {
        return aValue < bValue ? 1 : (aValue > bValue ? -1 : 0);
    }
  });

  // Re-append the sorted rows
  rows.forEach(row => {
    table.appendChild(row);
  });
}

document.querySelectorAll('.delete-form').forEach(form => {
  form.addEventListener('submit', function(event) {
      event.preventDefault();
      const formElement = this;

      Swal.fire({
          title: 'Archive this item?',
          text: "The item will be moved to archives and can be restored later.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, archive it!',
          cancelButtonText: 'No, cancel',
          customClass: {
              confirmButton: 'swal2-confirm',
              cancelButton: 'swal2-cancel'
          }
      }).then((result) => {
          if (result.isConfirmed) {
              formElement.submit();
          }
      });
  });
});

function sortTable(branchId, n) {
  let table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("inventoryTable_" + branchId);
  switching = true;
  dir = "asc";

  while (switching) {
    switching = false;
    rows = table.rows;

    for (i = 0; i < (rows.length - 1); i++) {
        shouldSwitch = false;
        x = rows[i].getElementsByTagName("TD")[n];
        y = rows[i + 1].getElementsByTagName("TD")[n];
        
        if (dir == "asc") {
            if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                shouldSwitch = true;
                break;
            }
        } else if (dir == "desc") {
            if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                shouldSwitch = true;
                break;
            }
        }
    }

    if (shouldSwitch) {
        rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
        switching = true;
        switchcount++;
    } else {
        if (switchcount == 0 && dir == "asc") {
            dir = "desc";
            switching = true;
        }
    }
  }
}

function showArchivedItems(branchId) {
  document.getElementById('archivedItemsModal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';

  $.ajax({
    url: 'inventory/get_archived_items.php',
    type: 'POST',
    data: { branch_id: branchId },
    success: function(response) {
        let branchName = $('#branchTitle_' + branchId).text().trim();
        $('#archivedItemsTitle').text('Archived Items - ' + branchName);
        $('#archivedItemsContent').html(response);
        
        $('#archivedItemsContent').off('click', '.unarchive-btn').on('click', '.unarchive-btn', function() {
            const itemId = $(this).data('item-id');
            unarchiveItem(itemId, branchId);
        });
        
        $('#archivedItemsSearch').trigger('input');
    },
    error: function(xhr, status, error) {
        alert('Error fetching archived items: ' + error);
    }
  });
}

function confirmDelete() {
  return confirm("Are you sure you want to delete this item?");
}

function openViewItemModal(inventoryId) {
  document.getElementById('viewItemModal').classList.remove('hidden');

  fetch(`inventory/get_item_details.php?inventory_id=${inventoryId}`)
    .then(response => response.text())
    .then(data => {
      document.getElementById('itemDetailsContent').innerHTML = data;
    })
    .catch(error => {
      document.getElementById('itemDetailsContent').innerHTML = `<div class="p-4 bg-red-100 text-red-700 rounded-lg">Error: ${error.message}</div>`;
    });
}

function closeViewItemModal() {
  document.getElementById('viewItemModal').classList.add('hidden');
  document.getElementById('itemDetailsContent').innerHTML = `<div class="flex justify-center">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-sidebar-accent"></div>
  </div>`;
}

function saveItemChanges() {
  const form = document.getElementById('editInventoryForm');
  const formData = new FormData(form);

  for (let pair of formData.entries()) {
    console.log(pair[0] + ': ' + pair[1]);
  }

  Swal.fire({
    title: 'Confirm Update',
    text: 'Are you sure you want to update this inventory item?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, update it!',
    cancelButtonText: 'No, cancel',
    customClass: {
      container: '!font-sans',
    }
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('inventory/update_inventory_item.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          return response.text().then(text => {
            throw new Error('Network response was not ok: ' + text);
          });
        }
        return response.text();
      })
      .then(data => {
        if (data === 'success') {
          Swal.fire({
            position: 'top-end',
            title: 'Success!',
            html: `
              <div class="flex items-start">
                <div class="mr-4 mt-1 flex-shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <div class="text-gray-600">Your inventory item has been updated successfully!</div>
                </div>
              </div>
            `,
            timer: 2500,
            timerProgressBar: true,
            width: 500,
            padding: '1.5em',
            showConfirmButton: false,
            backdrop: false,
            customClass: {
              popup: '!rounded-xl !bg-gray-50 !shadow-lg !font-sans !p-4',
              progressbar: '!h-1 !bg-green-500'
            },
            willClose: () => {
              closeEditInventoryModal();
              location.reload();
            }
          });
        } else {
          throw new Error('Unexpected response: ' + data);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          title: 'Error!',
          text: 'Error updating item: ' + error.message,
          icon: 'error',
          customClass: {
            container: '!font-sans',
            popup: '!rounded-xl'
          }
        });
      });
    }
  });

  return false;
}

$(document).ready(function() {
  $('#archivedItemsSearch').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    
    $('#archivedItemsContent tr').each(function() {
      const itemText = $(this).text().toLowerCase();
      $(this).toggle(itemText.includes(searchTerm));
    });
  });

  $('#archivedItemsModal').on('hidden.bs.modal', function() {
    $('#archivedItemsSearch').val('');
  });
});

/* for add items in inventory */
function openAddInventoryModal(branchId) {
  document.getElementById('addInventoryModal').classList.remove('hidden');
  document.body.classList.add('overflow-hidden');

  const radioButtons = document.querySelectorAll('input[name="branch"]');
  radioButtons.forEach(radio => {
    if (radio.value === branchId.toString()) {
      radio.checked = true;
      const customRadio = radio.nextElementSibling;
      if (customRadio) {
        customRadio.classList.add('peer-checked:bg-gold', 'peer-checked:border-darkgold');
      }
    } else {
      radio.checked = false;
    }
  });
}

function closeAddInventoryModal() {
  document.getElementById('addInventoryModal').classList.add('hidden');
  document.body.classList.remove('overflow-hidden');
  document.getElementById('addInventoryForm').reset();
}

function handleFileInput() {
  const fileInput = document.getElementById('itemImage');
  fileInput.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
      console.log('File selected:', this.files[0].name);
    }
  });
}

// Consolidated form submission handler
document.getElementById('addInventoryForm').addEventListener('submit', function(e) {
  e.preventDefault();

  // Get cleaned numeric values
  const unitPriceInput = document.getElementById('unitPrice').value.replace(/[^0-9.]/g, '');
  const sellingPriceInput = document.getElementById('sellingPrice').value.replace(/[^0-9.]/g, '');
  
  const unitPrice = parseFloat(unitPriceInput) || 0;
  const sellingPrice = parseFloat(sellingPriceInput) || 0;

  // Validate selling price > unit price
  if (sellingPrice <= unitPrice && unitPrice > 0) {
    Swal.fire({
      title: 'Invalid Price',
      text: 'Selling Price must be greater than Unit Price.',
      icon: 'error',
      confirmButtonColor: '#3085d6',
      confirmButtonText: 'OK'
    });
    return; // Prevent submission
  }

  // Create FormData object to handle file upload
  const formData = new FormData(this);

  // Get form values
  const itemName = document.getElementById('itemName').value;
  const category = document.getElementById('category').value;
  const branch = document.querySelector('input[name="branch"]:checked').value;
  const quantity = document.getElementById('quantity').value;
  const unitPriceVal = document.getElementById('unitPrice').value;
  const sellingPriceVal = document.getElementById('sellingPrice').value;

  formData.append('category_id', category);
  formData.append('branch_id', branch);
  formData.append('price', unitPrice);
  formData.append('sellingPrice', sellingPrice);

  Swal.fire({
    title: 'Confirm Item Addition',
    html: `
      <p>Are you sure you want to add the following item?</p>
      <div class="mt-4 text-left">
        <p><strong>Item Name:</strong> ${itemName}</p>
        <p><strong>Quantity:</strong> ${quantity}</p>
        <p><strong>Unit Price:</strong> ₱${unitPriceVal}</p>
        <p><strong>Selling Price:</strong> ₱${sellingPriceVal}</p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#D4AF37',
    cancelButtonColor: '#6B7280',
    confirmButtonText: 'Yes, add item',
    cancelButtonText: 'Cancel',
    allowOutsideClick: false,
    customClass: {
      confirmButton: 'swal2-confirm bg-sidebar-accent hover:bg-darkgold',
      cancelButton: 'swal2-cancel bg-white border border-sidebar-accent text-gray-800 hover:bg-navy',
      popup: 'swal2-popup rounded-xl',
      header: 'swal2-header bg-gradient-to-r from-sidebar-accent to-white',
      title: 'swal2-title text-white'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      const submitButton = document.querySelector('button[type="submit"]');
      const originalText = submitButton.innerHTML;
      submitButton.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';
      submitButton.disabled = true;

      fetch('inventory/add_inventory.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;

        if (data.success) {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Inventory item added successfully',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
          });

          if (typeof loadInventory === 'function') {
            loadInventory();
          } else {
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          }

          closeAddInventoryModal();
        } else {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: data.message || 'Failed to add inventory item',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
        showNotification('Error', 'An unexpected error occurred', 'error');
      });
    }
  });
});

function showNotification(title, message, type) {
  alert(`${title}: ${message}`);
}

function closeEditInventoryModal() {
  document.getElementById('editInventoryModal').classList.add('hidden');
}

function applyQuantityHeatmap() {
  document.querySelectorAll('td[data-sort-value]').forEach(cell => {
    const quantity = parseFloat(cell.getAttribute('data-sort-value'));
    if (isNaN(quantity)) return;
    
    cell.className = 'p-4 text-sm quantity-cell';
    
    if (quantity <= 2) {
      cell.classList.add('quantity-critical');
      cell.textContent = quantity + ' (Critical)';
    } else if (quantity <= 5) {
      cell.classList.add('quantity-critical');
      cell.textContent = quantity + ' (Low)';
    } else if (quantity <= 10) {
      cell.classList.add('quantity-warning');
    } else if (quantity <= 20) {
      cell.classList.add('quantity-normal');
    } else {
      cell.classList.add('quantity-high');
    }
  });
}

const stockMovementChart = new Chart(document.getElementById('stockMovementChart'), {
  type: 'bar',
  data: {
    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
    datasets: [{
      label: 'Stock Movement',
      data: [20, 15, 25, 30],
      backgroundColor: '#E76F51',
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
      }
    }
  }
});

const itemPerformanceChart = new Chart(document.getElementById('itemPerformanceChart'), {
  type: 'bar',
  data: {
    labels: ['Oak Casket', 'Mahogany Casket', 'Rose Urn', 'Lily Bouquet'],
    datasets: [{
      label: 'Item Performance',
      data: [85, 70, 60, 50],
      backgroundColor: '#4caf50',
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
      }
    }
  }
});

function closeEditInventoryModal() {
document.getElementById('editInventoryModal').classList.add('hidden');
}

// Form Submission Handling
document.getElementById('addInventoryForm').addEventListener('submit', function (e) {
e.preventDefault();
// Add logic to handle form submission
closeAddInventoryModal();
});

document.getElementById('editInventoryForm').addEventListener('submit', function (e) {
e.preventDefault();
// Add logic to handle form submission
closeEditInventoryModal();
});


function applyQuantityHeatmap() {
  document.querySelectorAll('td[data-sort-value]').forEach(cell => {
      const quantity = parseFloat(cell.getAttribute('data-sort-value'));
      if (isNaN(quantity)) return;
      
      // Reset classes
      cell.className = 'p-4 text-sm quantity-cell';
      
      if (quantity <= 2) {
          cell.classList.add('quantity-critical');
          cell.textContent = quantity + ' (Critical)';
      } else if (quantity <= 5) {
          cell.classList.add('quantity-critical');
          cell.textContent = quantity + ' (Low)';
      } else if (quantity <= 10) {
          cell.classList.add('quantity-warning');
      } else if (quantity <= 20) {
          cell.classList.add('quantity-normal');
      } else {
          cell.classList.add('quantity-high');
      }
  });
}

// Call this function after loading table data
document.addEventListener('DOMContentLoaded', applyQuantityHeatmap);
