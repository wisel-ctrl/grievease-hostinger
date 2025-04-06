
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
    
    // Initialize charts
    const servicesCtx = document.getElementById('servicesChart').getContext('2d');
    const servicesChart = new Chart(servicesCtx, {
      type: 'doughnut',
      data: {
        labels: ['Memorial Services', 'Funeral Services', 'Cremation Services', 'Visitation', 'Burial Services'],
        datasets: [{
          data: [15, 12, 8, 4, 3],
          backgroundColor: [
            '#1976d2',
            '#4caf50',
            '#ff9800',
            '#9c27b0',
            '#f44336'
          ],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 12,
              padding: 15
            }
          }
        }
      }
    });
    
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
      type: 'line',
      data: {
        labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
        datasets: [{
          label: 'Revenue',
          data: [62000, 58000, 69000, 74000, 78000, 87320],
          borderColor: '#4caf50',
          backgroundColor: 'rgba(76, 175, 80, 0.1)',
          tension: 0.3,
          fill: true
        }, {
          label: 'Expenses',
          data: [42000, 39000, 36000, 31000, 37000, 34520],
          borderColor: '#f44336',
          backgroundColor: 'rgba(244, 67, 54, 0.1)',
          tension: 0.3,
          fill: true
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
          legend: {
            position: 'top'
          }
        }
      }
    });
    
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

  // Initialize charts when the DOM content is loaded
  document.addEventListener('DOMContentLoaded', function() {
    
    
    
    // Stock Movement Chart (Bar)
    const stockCtx = document.getElementById('stockMovementChart').getContext('2d');
    const stockChart = new Chart(stockCtx, {
      type: 'bar',
      data: {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [{
          label: 'Items Added',
          data: [12, 8, 15, 10],
          backgroundColor: 'rgba(25, 118, 210, 0.7)',
          borderWidth: 0
        }, {
          label: 'Items Removed',
          data: [8, 5, 12, 7],
          backgroundColor: 'rgba(244, 67, 54, 0.7)',
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            stacked: false
          },
          y: {
            stacked: false,
            beginAtZero: true
          }
        },
        plugins: {
          legend: {
            position: 'top'
          }
        }
      }
    });
    
    // Item Performance Chart (Horizontal Bar)
    const performanceCtx = document.getElementById('itemPerformanceChart').getContext('2d');
    const performanceChart = new Chart(performanceCtx, {
      type: 'bar',
      data: {
        labels: ['Oak Casket - Premium', 'Floral Arrangement - Classic', 'Memorial Urns - Bronze', 'Printed Programs - Standard', 'Guest Book - Deluxe'],
        datasets: [{
          axis: 'y',
          label: 'Monthly Usage',
          data: [8, 22, 12, 35, 15],
          backgroundColor: [
            'rgba(25, 118, 210, 0.7)',
            'rgba(76, 175, 80, 0.7)',
            'rgba(255, 152, 0, 0.7)',
            'rgba(156, 39, 176, 0.7)',
            'rgba(244, 67, 54, 0.7)'
          ],
          borderWidth: 0
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Units Used This Month'
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });
    
    // Event listeners for period filters
    document.getElementById('valueTrendPeriod').addEventListener('change', function() {
      // Update value trend chart based on selected period
      // This would typically fetch new data from the server
      console.log('Value trend period changed to:', this.value);
    });
    
    document.getElementById('stockMovementPeriod').addEventListener('change', function() {
      // Update stock movement chart based on selected period
      // This would typically fetch new data from the server
      console.log('Stock movement period changed to:', this.value);
    });
  });

  