<?php

session_start();

require_once '../db_connect.php'; // Database connection

// Get user's first name from database
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name , last_name , email , birthdate FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name']; // We're confident user_id exists
$last_name = $row['last_name'];
$email = $row['email'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to login page
  header("Location: ../Landing_Page/login.php");
  exit();
}

// Check for admin user type (user_type = 1)
if ($_SESSION['user_type'] != 1) {
  // Redirect to appropriate page based on user type
  switch ($_SESSION['user_type']) {
      case 2:
          header("Location: ../employee/index.php");
          break;
      case 3:
          header("Location: ../customer/index.php");
          break;
      default:
          // Invalid user_type
          session_destroy();
          header("Location: ../Landing_Page/login.php");
  }
  exit();
}

// Optional: Check for session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
  // Session has expired
  session_unset();
  session_destroy();
  header("Location: ../Landing_Page/login.php?timeout=1");
  exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Prevent caching for authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - GrievEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.4.8/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.4.8/sweetalert2.min.css">
</head>

</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>

<!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Bookings</h1>
    </div>
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-cog"></i>
      </button>
    </div>
  </div>

  <!-- Pending Bookings List -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
    <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
      <h2 class="text-xl font-semibold text-sidebar-text">Pending Requests</h2>
      <div class="relative">
        <input type="text" placeholder="Search bookings..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
      </div>
    </div>
    <div class="overflow-x-auto scrollbar-thin">
      <table class="w-full">
        <thead>
          <tr class="bg-sidebar-hover">
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
              <div class="flex items-center">
                Booking ID <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
              <div class="flex items-center">
                Customer <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
              <div class="flex items-center">
                Service <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
              <div class="flex items-center">
                Date Requested <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
              <div class="flex items-center">
                Status <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Query to get booking data with joins
          $query = "SELECT b.booking_id, b.booking_date, b.status, 
                          CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', COALESCE(u.suffix, '')) AS customer_name,
                          s.service_name
                    FROM booking_tb b
                    JOIN users u ON b.customerID = u.id
                    JOIN services_tb s ON b.service_id = s.service_id
                    WHERE b.status = 'Pending'
                    ORDER BY b.booking_date DESC";
          
          $stmt = $conn->prepare($query);
          $stmt->execute();
          $result = $stmt->get_result();
          
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              // Format booking ID
              $booking_id = "#BK-" . date('Y', strtotime($row['booking_date'])) . "-" . str_pad($row['booking_id'], 3, '0', STR_PAD_LEFT);
              
              // Format date
              $formatted_date = date('M j, Y', strtotime($row['booking_date']));
              
              // Status badge class
              $status_class = "bg-yellow-100 text-yellow-800";
              if ($row['status'] == 'Approved') {
                $status_class = "bg-green-100 text-green-800";
              } elseif ($row['status'] == 'Rejected') {
                $status_class = "bg-red-100 text-red-800";
              }
              
              echo '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">';
              echo '<td class="p-4 text-sm text-sidebar-text font-medium">' . htmlspecialchars($booking_id) . '</td>';
              echo '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row['customer_name']) . '</td>';
              echo '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row['service_name']) . '</td>';
              echo '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($formatted_date) . '</td>';
              echo '<td class="p-4 text-sm">';
              echo '<span class="px-2 py-1 ' . $status_class . ' rounded-full text-xs">' . htmlspecialchars($row['status']) . '</span>';
              echo '</td>';
              echo '<td class="p-4 text-sm">';
              echo '<button onclick="openBookingDetails(' . $row['booking_id'] . ')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View Details</button>';
              echo '</td>';
              echo '</tr>';
            }
          } else {
            echo '<tr><td colspan="6" class="p-4 text-center text-gray-500">No pending bookings found</td></tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
      <div class="text-sm text-gray-500">Showing 3 of 10 bookings</div>
      <div class="flex space-x-1">
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&laquo;</button>
        <button class="px-3 py-1 bg-sidebar-accent text-white rounded text-sm">1</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">3</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
      </div>
    </div>
  </div>
</div>

<!-- Improved Booking Details Modal -->
<div id="bookingDetailsModal" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden overflow-y-auto flex items-center justify-center p-4 w-full h-full">
  <div class="bg-white rounded-xl shadow-2xl modal-content transform transition-all duration-300 ease-out max-w-4xl w-full">
      <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 id="modal-package-title" class="text-xl font-bold text-white">Booking Details</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
      
      <!-- Modal Body (Scrollable Content) -->
      <div class="modal-body">
          <!-- Booking ID and Status Banner -->
          <div class="flex justify-between items-center mb-6 bg-gray-50 p-4 rounded-lg">
              <div class="flex items-center">
                  <div class="bg-navy rounded-full p-2 mr-3">
                      <i class="fas fa-hashtag text-sidebar-accent"></i>
                  </div>
                  <div>
                      <p class="text-sm text-gray-500">Booking ID</p>
                      <p class="font-semibold text-gray-800" id="bookingId">#BK-2025-001</p>
                  </div>
              </div>
              <div>
                  <p class="text-sm text-gray-500 mb-1">Status</p>
                  <div id="bookingStatus">
                      <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-yellow-100 text-sidebar-accent flex items-center">
                          <i class="fas fa-clock mr-1.5"></i>
                          Pending
                      </span>
                  </div>
              </div>
          </div>
          
          <!-- Content Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Left Column -->
              <div>
                
                  <!-- Customer Information -->
                  <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm mb-6">
                    <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user text-sidebar-accent mr-2"></i>
                        Customer Information
                    </h4>
                    <div class="space-y-3">
                        <div class="flex">
                            <div class="w-1/3 text-sm text-gray-500">Name</div>
                            <div class="w-2/3 font-medium text-gray-800" id="customerName">John Doe</div>
                        </div>
                        <div class="flex">
                            <div class="w-1/3 text-sm text-gray-500">Contact</div>
                            <div class="w-2/3 font-medium text-gray-800" id="contactNumber">(555) 123-4567</div>
                        </div>
                        <div class="flex">
                            <div class="w-1/3 text-sm text-gray-500">Email</div>
                            <div class="w-2/3 font-medium text-gray-800" id="emailAddress">john.doe@example.com</div>
                        </div>
                        <div class="flex">
                            <div class="w-1/3 text-sm text-gray-500">Address</div>
                            <div class="w-2/3 font-medium text-gray-800" id="address">123 Main St, Anytown, CA 12345</div>
                        </div>
                    </div>
                </div>

                  <!-- Service Details -->
                  <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm">
                      <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                          <i class="fas fa-hands-helping text-sidebar-accent mr-2"></i>
                          Service Details
                      </h4>
                      <div class="space-y-3">
                          <div class="flex">
                              <div class="w-1/3 text-sm text-gray-500">Service Type</div>
                              <div class="w-2/3 font-medium text-gray-800" id="serviceType">Funeral Service Package A</div>
                          </div>
                          <div class="flex">
                              <div class="w-1/3 text-sm text-gray-500">Date Requested</div>
                              <div class="w-2/3 font-medium text-gray-800" id="dateRequested">Mar 15, 2025</div>
                          </div>
                          <div class="flex">
                              <div class="w-1/3 text-sm text-gray-500">Service Date</div>
                              <div class="w-2/3 font-medium text-gray-800" id="serviceDate">Mar 20, 2025</div>
                          </div>
                          <div class="flex">
                              <div class="w-1/3 text-sm text-gray-500">Amount Paid</div>
                              <div class="w-2/3 font-medium text-gray-800" id="amountPaid">$3,500.00</div>
                          </div>
                      </div>
                  </div>  
              </div>
              
              <!-- Right Column -->
              <div>
                  <!-- Documents -->
                  <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm">
                      <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                          <i class="fas fa-file-alt text-sidebar-accent mr-2"></i>
                          Documents
                      </h4>
                      
                      <!-- Death Certificate -->
                      <div id="deathCertificateSection" class="mb-5">
                          <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                              <i class="fas fa-certificate text-gray-500 mr-2"></i>
                              Death Certificate
                          </h5>
                          <div class="border border-gray-200 rounded-lg overflow-hidden">
                              <div id="deathCertificateAvailable" class="text-center">
                                  <div class="relative bg-gray-100 p-1">
                                      <img id="deathCertificateImage"  alt="Death Certificate" class="mx-auto rounded-md max-h-48 object-contain" />
                                      <div class="absolute top-2 right-2">
                                          <button class="bg-white rounded-full p-1 shadow-md hover:bg-gray-100 transition-colors duration-200" title="View Full Size">
                                              <i class="fas fa-search-plus text-blue-600"></i>
                                          </button>
                                      </div>
                                  </div>
                                  <div class="bg-gray-50 p-2 text-xs text-gray-500 flex items-center justify-center">
                                      <i class="fas fa-clock mr-1"></i>
                                      Uploaded on Mar 15, 2025
                                  </div>
                              </div>
                              <div id="deathCertificateNotAvailable" class="hidden">
                                  <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
                                      <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
                                      <p class="text-gray-500 text-center">No death certificate has been uploaded yet.</p>
                                  </div>
                              </div>
                          </div>
                      </div>
                      
                      <!-- Payment Proof -->
                      <div>
                          <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                              <i class="fas fa-receipt text-gray-500 mr-2"></i>
                              Payment Proof
                          </h5>
                          <div class="border border-gray-200 rounded-lg overflow-hidden">
                              <div class="relative bg-gray-100 p-1">
                                  <img id="paymentProofImage"  alt="Payment Proof" class="mx-auto rounded-md max-h-48 object-contain" />
                                  <div class="absolute top-2 right-2">
                                      <button class="bg-white rounded-full p-1 shadow-md hover:bg-gray-100 transition-colors duration-200" title="View Full Size">
                                          <i class="fas fa-search-plus text-blue-600"></i>
                                      </button>
                                  </div>
                              </div>
                              <div class="bg-gray-50 p-2 text-xs text-gray-500 flex items-center justify-center">
                                  <i class="fas fa-credit-card mr-1"></i>
                                  Payment received on Mar 15, 2025
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      
      <!-- Action Buttons -->
      <div class="p-6 border-t border-gray-200 modal-footer bg-gray-50">
          <div class="flex flex-col-reverse sm:flex-row justify-end gap-4">
              <button onclick="confirmDecline()" class="bg-white border border-sidebar-accent text-sidebar-accent hover:bg-navy font-medium rounded-lg px-5 py-2.5 transition-colors duration-200 flex items-center justify-center">
                  <!-- <i class="fas fa-times-circle mr-2"></i> -->
                  Decline Booking
              </button>
              <button onclick="confirmAccept()" class="bg-sidebar-accent text-white hover:bg-darkgold font-medium rounded-lg px-5 py-2.5 transition-colors duration-200 flex items-center justify-center">
                  <!-- <i class="fas fa-check-circle mr-2"></i> -->
                  Accept Booking
              </button>
          </div>
      </div>
  </div>
</div>


    <script src="script.js"></script>
    <script src="tailwind.js"></script>

    <script>
      function openBookingDetails(bookingId) {
      // First, fetch booking details via AJAX
      fetch('bookingpage/get_booking_details.php?id=' + bookingId)
        .then(response => response.json())
        .then(data => {
          // Populate modal with the fetched data
          document.getElementById('bookingId').textContent = '#BK-' + 
            new Date(data.booking_date).getFullYear() + '-' + 
            String(data.booking_id).padStart(3, '0');
          document.getElementById('customerName').textContent = data.customer_name;
          document.getElementById('serviceType').textContent = data.service_name;
          document.getElementById('emailAddress').textContent = data.email;
          document.getElementById('dateRequested').textContent = 
            new Date(data.booking_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
          
          // Update other fields as needed...
          
          // Then show the modal
          const modal = document.getElementById("bookingDetailsModal");
          modal.classList.remove("hidden");
          modal.classList.add("flex");
          document.body.classList.add("overflow-hidden");
        })
        .catch(error => {
          console.error('Error:', error);
          // Show error message
        });
    }

function closeModal() {
    const modal = document.getElementById("bookingDetailsModal");
    if (modal) {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    }
}

// Close modal when clicking outside content
window.addEventListener("click", function (event) {
    const modal = document.getElementById("bookingDetailsModal");
    if (modal && event.target === modal) {
        closeModal();
    }
});

// Close modal on 'Escape' key press
window.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeModal();
    }
});

    </script>
</body>
</html>