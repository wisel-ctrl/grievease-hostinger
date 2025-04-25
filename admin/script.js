
// Modified chart initialization code to include branch comparison
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.analytics-carousel');
    const dots = document.querySelectorAll('.carousel-dot');
    let currentSlide = 0;

    // Function to change slide
    function goToSlide(index) {
      currentSlide = index;
      carousel.style.transform = `translateX(-${currentSlide * 100}%)`;
      
      // Update active dot
      dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === currentSlide);
      });
    }
    
    // Add click event to dots
    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        goToSlide(index);
      });
    });
    
    // Auto slide change
    setInterval(() => {
      currentSlide = (currentSlide + 1) % dots.length;
      goToSlide(currentSlide);
    }, 10000);
    
    
    // Get the current month and previous months for labels
    
    
    // Branch comparison charts
    const branchRevenueCtx = document.getElementById('branchRevenueChart').getContext('2d');
    const branchRevenueChart = new Chart(branchRevenueCtx, {
      type: 'bar',
      data: {
        labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
        datasets: [{
          label: 'Downtown Branch',
          data: [62000, 58000, 69000, 74000, 78000, 87320],
          backgroundColor: 'rgba(25, 118, 210, 0.7)',
          borderWidth: 0
        }, {
          label: 'Westside Branch',
          data: [54000, 62000, 57000, 68000, 72000, 79500],
          backgroundColor: 'rgba(156, 39, 176, 0.7)',
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return '$' + value.toLocaleString();
              }
            }
          }
        },
        plugins: {
          title: {
            display: true,
            text: 'Revenue by Branch'
          },
          legend: {
            position: 'top'
          }
        }
      }
    });
    
    const branchServicesCtx = document.getElementById('branchServicesChart').getContext('2d');
    const branchServicesChart = new Chart(branchServicesCtx, {
      type: 'radar',
      data: {
        labels: ['Memorial', 'Funeral', 'Cremation', 'Visitation', 'Burial'],
        datasets: [{
          label: 'Downtown Branch',
          data: [15, 12, 8, 4, 3],
          borderColor: 'rgba(25, 118, 210, 0.7)',
          backgroundColor: 'rgba(25, 118, 210, 0.2)',
          pointBackgroundColor: 'rgba(25, 118, 210, 1)',
          borderWidth: 2
        }, {
          label: 'Westside Branch',
          data: [12, 14, 10, 6, 5],
          borderColor: 'rgba(156, 39, 176, 0.7)',
          backgroundColor: 'rgba(156, 39, 176, 0.2)',
          pointBackgroundColor: 'rgba(156, 39, 176, 1)',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          r: {
            angleLines: {
              display: true
            },
            suggestedMin: 0
          }
        },
        plugins: {
          title: {
            display: true,
            text: 'Services by Type & Branch'
          },
          legend: {
            position: 'top'
          }
        }
      }
    });
  });

  
  

  
  // Sort table
  function sortTable(columnIndex) {
    const table = document.querySelector('.table-sortable');
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const isAscending = table.querySelector('th').getAttribute('data-sort') === 'asc';
  
    rows.sort((a, b) => {
      const aValue = a.children[columnIndex].textContent;
      const bValue = b.children[columnIndex].textContent;
      return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });
  
    table.querySelector('th').setAttribute('data-sort', isAscending ? 'desc' : 'asc');
    table.querySelector('tbody').innerHTML = '';
    rows.forEach(row => table.querySelector('tbody').appendChild(row));
  }
  
  // Branch filter functionality
  document.addEventListener('DOMContentLoaded', function() {
    const branchFilter = document.getElementById('branchFilter');
    if (branchFilter) {
      branchFilter.addEventListener('change', function() {
        // You can implement branch filtering logic here
        const selectedBranch = this.value;
        console.log('Filtering by branch:', selectedBranch);
        // Add logic to filter dashboard data by branch
      });
    }
  });

  

  



  // Function to delete an account
  function deleteAccount() {
    if (confirm('Are you sure you want to delete this account?')) {
      // Delete account logic here
    }
  }


  

  // Function to add a new inventory item
  function addInventoryItem() {
    const form = document.getElementById('inventoryForm');
    if (form.checkValidity()) {
      // Add inventory item logic here
      closeAddInventoryModal();
    } else {
      form.reportValidity();
    }
  }

  // Function to save changes to an inventory item
  function saveInventoryChanges() {
    const form = document.getElementById('editInventoryForm');
    if (form.checkValidity()) {
      // Save changes logic here
      closeEditInventoryModal();
    } else {
      form.reportValidity();
    }
  }

  // Function to delete an inventory item
  function deleteInventoryItem() {
    if (confirm('Are you sure you want to delete this inventory item?')) {
      // Delete inventory item logic here
    }
  }

  
  