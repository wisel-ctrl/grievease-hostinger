<?php
//packages.php
session_start();

require_once '../db_connect.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for correct user type based on which directory we're in
$current_directory = basename(dirname($_SERVER['PHP_SELF']));
$allowed_user_type = null;

switch ($current_directory) {
    case 'admin':
        $allowed_user_type = 1;
        break;
    case 'employee':
        $allowed_user_type = 2;
        break;
    case 'customer':
        $allowed_user_type = 3;
        break;
}

// If user is not the correct type for this page, redirect to appropriate page
if ($_SESSION['user_type'] != $allowed_user_type) {
    switch ($_SESSION['user_type']) {
        case 1:
            header("Location: ../admin/index.php");
            break;
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

// Get user's first name from database
$user_id = $_SESSION['user_id'];
$query = "SELECT u.first_name, u.last_name, u.email, u.birthdate, u.branch_loc, b.branch_id 
          FROM users u
          LEFT JOIN branch_tb b ON u.branch_loc = b.branch_id
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name'];
$last_name = $row['last_name'];
$email = $row['email'];
$branch_id = $row['branch_id']; // This will be used for the hidden branch_id input


function getImageUrl($image_path) {
    if (empty($image_path)) {
        return 'assets/images/placeholder.jpg';
    }
    
    if (!preg_match('/^(http|\/)/i', $image_path)) {
        return '../../admin/servicesManagement/' . $image_path;
    }
    
    return $image_path;
}

// Fetch packages from database
$packages = [];
$branch_id = $row['branch_loc'];
$show_branch_modal = ($branch_id === null || $branch_id === 'unknown' || $branch_id === '');

error_log("User branch_loc: " . $branch_id . ", show_branch_modal: " . ($show_branch_modal ? 'true' : 'false'));

if ($branch_id && $branch_id !== 'unknown' && $branch_id !== '') {
    $query = "SELECT s.service_id, s.service_name, s.description, s.selling_price, s.image_url, 
                     i.item_name AS casket_name, s.flower_design, s.inclusions
              FROM services_tb s
              LEFT JOIN inventory_tb i ON s.casket_id = i.inventory_id
              WHERE s.status = 'active' AND s.branch_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Parse inclusions (assuming it's stored as a JSON string or comma-separated)
            $inclusions = []; // Define $inclusions here, inside the loop
            if (!empty($row['inclusions'])) {
                // Try to decode as JSON first
                $decoded = json_decode($row['inclusions'], true);
                $inclusions = is_array($decoded) ? $decoded : explode(',', $row['inclusions']);
            }

            // Add casket name first (if it exists)
            if (!empty($row['casket_name'])) {
                array_unshift($inclusions, $row['casket_name'] . " casket");
            }

            // Add flower design second (if it exists)
            if (!empty($row['flower_design'])) {
                array_splice($inclusions, 1, 0, $row['flower_design']);
            }
            
            $packages[] = [
                'id' => $row['service_id'],
                'name' => $row['service_name'],
                'description' => $row['description'],
                'price' => $row['selling_price'],
                'image' => getImageUrl($row['image_url']), // Use the helper function for image URLs
                'features' => $inclusions, // Now $inclusions is defined for each package
                'service' => 'traditional' // Assuming all are traditional for now
            ];
        }
        $result->free();
    }
}

// Add some debug information
echo "<!-- DEBUG: Image URLs in database -->\n";
echo "<!-- ";
foreach ($packages as $pkg) {
    echo "Package: " . $pkg['name'] . ", Image: " . $pkg['image'] . "\n";
}
echo " -->\n";

// Function to determine icon based on package price
function getIconForPackage($price) {
    if ($price > 500000) return 'star';
    else if ($price > 200000) return 'crown';
    else if ($price > 100000) return 'heart';
    else if ($price > 50000) return 'dove';
    else return 'leaf'; // default icon
}


// Get notification count for the current user
$notifications_count = [
                    'total' => 0,
                    'pending' => 0,
                    'accepted' => 0,
                    'declined' => 0,
                    'id_pending' => 0,
                    'id_accepted' => 0,
                    'id_declined' => 0
                ];
                
                // Get user's life plan bookings from database (notifications)
                $lifeplan_query = "SELECT * FROM lifeplan_booking_tb WHERE customer_id = ? ORDER BY initial_date DESC";
                $lifeplan_stmt = $conn->prepare($lifeplan_query);
                $lifeplan_stmt->bind_param("i", $user_id);
                $lifeplan_stmt->execute();
                $lifeplan_result = $lifeplan_stmt->get_result();
                $lifeplan_bookings = [];
                
                while ($lifeplan_booking = $lifeplan_result->fetch_assoc()) {
                    $lifeplan_bookings[] = $lifeplan_booking;
                    
                    switch ($lifeplan_booking['booking_status']) {
                        case 'pending':
                            $notifications_count['total']++;
                            $notifications_count['pending']++;
                            break;
                        case 'accepted':
                            $notifications_count['total']++;
                            $notifications_count['accepted']++;
                            break;
                        case 'decline':
                            $notifications_count['total']++;
                            $notifications_count['declined']++;
                            break;
                    }
                }

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT status FROM booking_tb WHERE customerID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($booking = $result->fetch_assoc()) {
        $notifications_count['total']++;
        
        switch ($booking['status']) {
            case 'Pending':
                $notifications_count['pending']++;
                break;
            case 'Accepted':
                $notifications_count['accepted']++;
                break;
            case 'Declined':
                $notifications_count['declined']++;
                break;
        }
    }
    $stmt->close();
        // Get ID validation status
                $query = "SELECT is_validated FROM valid_id_tb WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($id_validation = $result->fetch_assoc()) {
                    if ($id_validation['is_validated'] == 'no') {
                        $notifications_count['id_validation']++;
                        $notifications_count['total']++;
                    }
                }
                $stmt->close();
}

// Get user's validation status
$validationQuery = "SELECT is_validated FROM valid_id_tb WHERE id = ?";
$validationStmt = $conn->prepare($validationQuery);
$validationStmt->bind_param("i", $user_id);
$validationStmt->execute();
$validationResult = $validationStmt->get_result();
$validationStatus = 'no'; // Default to 'no' if no record exists

if ($validationResult->num_rows > 0) {
    $validationRow = $validationResult->fetch_assoc();
    $validationStatus = $validationRow['is_validated'];
}
$validationStmt->close();


                $profile_query = "SELECT profile_picture FROM users WHERE id = ?";
                $profile_stmt = $conn->prepare($profile_query);
                $profile_stmt->bind_param("i", $user_id);
                $profile_stmt->execute();
                $profile_result = $profile_stmt->get_result();
                $profile_data = $profile_result->fetch_assoc();
                
                $profile_picture = $profile_data['profile_picture'];
                
$query_gcash = "SELECT qr_number, qr_image FROM gcash_qr_tb WHERE is_available = 1";
$result_gcash = $conn->query($query_gcash);
$gcash_qrs = [];
if ($result_gcash) {
    while ($row_gcash = $result_gcash->fetch_assoc()) {
        $gcash_qrs[] = [
            'qr_number' => $row_gcash['qr_number'],
            'qr_image' => '../' . $row_gcash['qr_image']
        ];
    }
    $result_gcash->free();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Packages</title>
    <?php include 'faviconLogo.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Add to your existing styles */
#enlargedQrView {
    transition: opacity 0.3s ease;
}

#enlargedQrView img {
    transition: transform 0.3s ease;
}

#enlargedQrView:hover img {
    transform: scale(1.02);
}
        /* Add this to your existing CSS */
input[name*="FirstName"],
input[name*="MiddleName"],
input[name*="LastName"] {
    text-transform: capitalize;
}

        :root {
            --navbar-height: 64px;
            --section-spacing: 4rem;
        }
        
        .selectPackageBtn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: #9ca3af !important;
        }

        .selectPackageBtn.disabled:hover {
            background-color: #9ca3af !important;
        }

        .package-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .package-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Sorting active indicator */
        .sorting-active {
            border-color: #d97706 !important;
            background-color: #fef3c7 !important;
        }
        
        /* Smooth transitions for package cards */
        .package-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }
        /* Add this to your existing CSS */
@media (min-width: 768px) {
    #lifeplanModal .form-section {
        display: block !important;
    }
}

@media (max-width: 767px) {
    #lifeplanModal .form-section:not(.force-show) {
        display: none !important;
    }
}
/* Additional styles for scrollbar */
.modal-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: #d4a933 #f5f5f5;
}

.modal-scroll-container::-webkit-scrollbar {
    width: 8px;
}

.modal-scroll-container::-webkit-scrollbar-track {
    background: #f5f5f5;
}

.modal-scroll-container::-webkit-scrollbar-thumb {
    background-color: #d4a933;
    border-radius: 6px;
}


.landscape-img {
    object-fit: contain;
    object-position: center;
    transform: rotate(0deg); /* Ensure landscape orientation */
}

.gcash-qr-option {
    display: flex;
    justify-content: center;
    align-items: center;
}

@media (max-width: 640px) {
    .gcash-qr-option > div {
        width: 100% !important;
        height: auto !important;
        aspect-ratio: 3/2; /* Maintain landscape aspect ratio */
    }
}
    </style>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
    <!-- Add this right after the opening <body> tag -->
<?php if ($show_branch_modal): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show branch modal immediately if branch is unknown
    const branchModal = document.getElementById('branch-modal');
    branchModal.classList.remove('hidden');
    
    // Fetch branches from server
    fetchBranches();
    
    // Close modal when clicking outside
    branchModal.addEventListener('click', function(e) {
        if (e.target === branchModal) {
            // Don't allow closing by clicking outside - user must select a branch
            Swal.fire({
                title: 'Branch Selection Required',
                text: 'You must select a branch to continue using our services.',
                icon: 'warning',
                confirmButtonColor: '#d97706'
            });
        }
    });
});

function fetchBranches() {
    const branchOptions = document.getElementById('branch-options');
    branchOptions.innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i> Loading branches...
        </div>
    `;
    
    fetch('customService/get_branches.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.branches && data.branches.length > 0) {
                populateBranchOptions(data.branches);
            } else {
                showBranchError(data.message || 'No branches available');
            }
        })
        .catch(error => {
            console.error('Error fetching branches:', error);
            showBranchError('Failed to load branches. Please try again.');
        });
}

function populateBranchOptions(branches) {
    const branchOptions = document.getElementById('branch-options');
    branchOptions.innerHTML = '';
    
    branches.forEach(branch => {
        const option = document.createElement('button');
        option.className = 'w-full py-3 px-4 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors text-left mb-2 last:mb-0';
        option.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-map-marker-alt text-yellow-600 mr-3"></i>
                <div>
                    <h4 class="font-medium">${capitalizeWords(branch.name)}</h4>
                </div>
            </div>
        `;
        
        // Modified click handler to show confirmation dialog
        option.addEventListener('click', () => confirmBranchSelection(branch.id, branch.name));
        branchOptions.appendChild(option);
    });
}

// New function to show confirmation dialog
function confirmBranchSelection(branchId, branchName) {
    Swal.fire({
        title: 'Confirm Branch Selection',
        html: `You are about to select <strong>${capitalizeWords(branchName)}</strong> as your branch.<br><br>This action is <strong>irreversible</strong>. Are you sure?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, select this branch',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            selectBranch(branchId);
        }
    });
}

function selectBranch(branchId) {
    const branchOptions = document.getElementById('branch-options');
    branchOptions.innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i> Updating your branch...
        </div>
    `;
    
    fetch('customService/save_branch.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            branch: branchId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Success - reload the page to show packages from selected branch
            window.location.reload();
        } else {
            showBranchError(data.message || 'Failed to update branch');
        }
    })
    .catch(error => {
        console.error('Error updating branch:', error);
        showBranchError('Network error. Please check your connection.');
    });
}

function showBranchError(message) {
    document.getElementById('branch-options').innerHTML = `
        <div class="text-center py-4 text-red-500">
            <i class="fas fa-exclamation-circle"></i> ${message}
            <button onclick="fetchBranches()" class="mt-2 text-yellow-600 hover:text-yellow-700">
                <i class="fas fa-sync-alt mr-1"></i> Try Again
            </button>
        </div>
    `;
}

function capitalizeWords(str) {
    return str.replace(/\b\w/g, function(char) {
        return char.toUpperCase();
    });
}
</script>
<?php endif; ?>    
    
    <!-- Fixed Navigation Bar -->
    <nav class="bg-black text-white shadow-md w-full fixed top-0 left-0 z-50 px-4 sm:px-6 lg:px-8" style="height: var(--navbar-height);">
        <div class="flex justify-between items-center h-16">
            <!-- Left side: Logo and Text with Link -->
            <a href="index.php" class="flex items-center space-x-2">
                <img src="..\Landing_Page/Landing_images/logo.png" alt="Logo" class="h-[42px] w-[38px]">
                <span class="text-yellow-600 text-3xl">GrievEase</span>
            </a>
            
            <!-- Center: Navigation Links (Hidden on small screens) -->
            <div class="hidden md:flex space-x-6">
                <a href="index.php" class="text-white hover:text-gray-300 transition relative group">
                    Home
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="about.php" class="text-white hover:text-gray-300 transition relative group">
                    About
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="lifeplan.php" class="text-white hover:text-gray-300 transition relative group">
                    Life Plan
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>

                <a href="traditional_funeral.php" class="text-white hover:text-gray-300 transition relative group">
                    Traditional Funeral
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>

                <a href="packages.php" class="text-white hover:text-gray-300 transition relative group">
                    Packages
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                
                <a href="faqs.php" class="text-white hover:text-gray-300 transition relative group">
                    FAQs
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
            </div>
            
            <!-- User Menu -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="notification.php" class="relative text-white hover:text-yellow-600 transition-colors">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifications_count['pending'] > 0 || $notifications_count['id_validation'] > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                        <?php echo $notifications_count['pending'] + $notifications_count['id_validation']; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <div class="relative group">
                    <button class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full bg-yellow-600 flex items-center justify-center text-sm overflow-hidden">
                        <?php if ($profile_picture && file_exists('../profile_picture/' . $profile_picture)): ?>
                            <img src="../profile_picture/<?php echo htmlspecialchars($profile_picture); ?>" 
                                 alt="Profile Picture" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php 
                                $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); 
                                echo htmlspecialchars($initials);
                            ?>
                        <?php endif; ?>
                    </div>
                    <span class="hidden md:inline text-sm">
                    <?php echo htmlspecialchars(ucwords($first_name . ' ' . $last_name)); ?>
                    </span>

                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-card overflow-hidden invisible group-hover:visible transition-all duration-300 opacity-0 group-hover:opacity-100">
                        <div class="p-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-navy"><?php echo htmlspecialchars(ucwords($first_name . ' ' . $last_name)); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($email); ?></p>
                        </div>
                        <div class="py-1">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                            <a href="../logout.php" class="block px-4 py-2 text-sm text-error hover:bg-gray-100">Sign Out</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- mobile menu header -->
            <div class="md:hidden flex justify-between items-center px-4 py-3 border-b border-gray-700">
        <div class="flex items-center space-x-4">
                <a href="notification.php" class="relative text-white hover:text-yellow-600 transition-colors">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifications_count['pending'] > 0 || $notifications_count['id_validation'] > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                        <?php echo $notifications_count['pending'] + $notifications_count['id_validation']; ?>
                    </span>
                    <?php endif; ?>
                </a>
            <button onclick="toggleMenu()" class="focus:outline-none text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
        </div>
    </div>
        
        <!-- Mobile Menu -->
<div id="mobile-menu" class="hidden md:hidden fixed left-0 right-0 top-[--navbar-height] bg-black/90 backdrop-blur-md p-4 z-40 max-h-[calc(100vh-var(--navbar-height))] overflow-y-auto">
    <div class="space-y-2">
        <a href="index.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
            <div class="flex justify-between items-center">
                <span>Home</span>
                <i class="fas fa-home text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </div>
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="about.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
            <div class="flex justify-between items-center">
                <span>About</span>
                <i class="fas fa-info-circle text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </div>
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="lifeplan.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
            <div class="flex justify-between items-center">
                <span>Life Plan</span>
                <i class="fas fa-calendar-alt text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </div>
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="traditional_funeral.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
            <div class="flex justify-between items-center">
                <span>Traditional Funeral</span>
                <i class="fas fa-briefcase text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </div>
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="packages.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
            <div class="flex justify-between items-center">
                <span>Packages</span>
                <i class="fa-solid fa-cube text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </div>
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="faqs.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
            <div class="flex justify-between items-center">
                <span>FAQs</span>
                <i class="fas fa-question-circle text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </div>
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
    </div>

    <div class="mt-6 border-t border-gray-700 pt-4">
        <div class="space-y-2">
            <a href="profile.php" class="flex items-center justify-between text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300">
                <span>My Profile</span>
                <i class="fas fa-user text-yellow-600"></i>
            </a>
            <a href="../logout.php" class="flex items-center justify-between text-red-400 py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300">
                <span>Sign Out</span>
                <i class="fas fa-sign-out-alt text-red-400"></i>
            </a>
        </div>
    </div>
</div>
    </nav>

    <!-- Packages Section -->
<div class="container mx-auto px-4 py-16 mt-[var(--navbar-height)]">
    <div class="text-center mb-12">
        <h2 class="text-5xl font-hedvig text-navy mb-4">Our Packages</h2>
        <p class="text-dark text-lg max-w-3xl mx-auto">Compassionate and personalized funeral services to honor your loved one's memory with dignity.</p>
        <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
    </div>

    <!-- Search and Filter Section -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-12 space-y-4 md:space-y-0 gap-4">
        <!-- Search Input -->
        <div class="relative w-full md:w-2/5">
            <input 
                type="text" 
                id="searchInput" 
                placeholder="Search packages..." 
                class="w-full px-4 py-2 border rounded-lg pl-10 focus:outline-none focus:ring-2 focus:ring-yellow-600"
            >
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Price Sort -->
        <select 
            id="priceSort" 
            class="w-full md:w-2/5 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 transition-all duration-200"
        >
            <option value="">Default Order</option>
            <option value="asc">Price: Low to High</option>
            <option value="desc">Price: High to Low</option>
        </select>

        <!-- Reset Filters Button -->
        <button id="resetFilters" class="w-full md:w-1/5 px-6 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg flex items-center justify-center space-x-2 transition duration-300">
    <i class="fas fa-sync mr-2"></i>
    <span>Reset</span>
</button>
    </div>

        <!-- Packages Grid -->
        <div id="packages-container" class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if (count($packages) > 0): ?>
                <?php foreach ($packages as $pkg): ?>
                    <?php 
                    // Determine icon for this package
                    $icon = getIconForPackage($pkg['price']);
                    ?>
                    <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden"
                         data-price="<?= $pkg['price'] ?>"
                         data-service="<?= $pkg['service'] ?>"
                         data-name="<?= htmlspecialchars($pkg['name']) ?>"
                         data-image="<?= htmlspecialchars($pkg['image']) ?>">
                        <div class="flex flex-col h-full">
                            <!-- Image section -->
                            <div class="h-48 bg-cover bg-center relative" style="background-image: url('<?= htmlspecialchars($pkg['image']) ?>')">
                                <div class="absolute inset-0 bg-black/40 group-hover:bg-black/30 transition-all duration-300"></div>
                                <div class="absolute top-4 right-4 w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white">
                                    <i class="fas fa-<?= $icon ?> text-xl"></i>
                                </div>
                            </div>
                            
                            <!-- Content section with consistent sizing -->
                            <div class="p-6 flex flex-col flex-grow">
                                <!-- Title -->
                                <h3 class="text-2xl font-hedvig text-navy mb-3"><?= htmlspecialchars($pkg['name']) ?></h3>
                                
                                <!-- Description -->
                                <p class="text-dark mb-4 line-clamp-3 h-[72px] overflow-hidden"><?= htmlspecialchars($pkg['description']) ?></p>
                                
                                <!-- Price -->
                                <div class="text-3xl font-hedvig text-yellow-600 mb-4 h-12 flex items-center">
                                    ₱<?= number_format($pkg['price']) ?>
                                </div>
                                
                                <!-- Features list -->
                                <div class="border-t border-gray-200 pt-4 mt-2 flex-grow overflow-y-auto">
                                    <ul class="space-y-2">
                                        <?php foreach ($pkg['features'] as $feature): ?>
                                            <li class="flex items-center text-sm text-gray-700">
                                                <i class="fas fa-check-circle mr-2 text-yellow-600"></i>
                                                <span><?= htmlspecialchars($feature) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <!-- Button -->
                                <button class="selectPackageBtn mt-6 w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                                    Select Package
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                    // Store validation status in a JavaScript variable
                const validationStatus = '<?php echo $validationStatus; ?>';

                // Function to handle package selection based on validation status
                function handlePackageSelection() {
                    switch(validationStatus) {
                        case 'no':
                            Swal.fire({
                                title: 'Profile Incomplete',
                                html: 'You need to <strong>complete your profile</strong> and <strong>upload a valid ID</strong> before you can select a package.',
                                icon: 'warning',
                                confirmButtonColor: '#d97706',
                                confirmButtonText: 'Go to Profile',
                                allowOutsideClick: false,  // Prevent closing when clicking outside
                                allowEscapeKey: false      // Prevent closing with Escape key
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'profile.php';
                                }
                            });
                            return false;
                        case 'denied':
                            Swal.fire({
                                title: 'ID Verification Failed',
                                html: 'Your uploaded ID was <strong>not approved</strong>. Please re-upload a valid government-issued ID to proceed with package selection.',
                                icon: 'error',
                                confirmButtonColor: '#d97706',
                                confirmButtonText: 'Upload ID',
                                allowOutsideClick: false,  // Prevent closing when clicking outside
                                allowEscapeKey: false      // Prevent closing with Escape key
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'profile.php#id-upload';
                                }
                            });
                            return false;
                        case 'valid':
                            return true; // Allow package selection
                        default:
                            return true; // Default allow (shouldn't happen)
                    }
                }

                // Modify the package selection event listener
                document.addEventListener('click', function(event) {
                    if (event.target.classList.contains('selectPackageBtn')) {
                        event.preventDefault(); // Prevent any default behavior
                        
                        // First check validation status
                        if (!handlePackageSelection()) {
                            return; // Stop here if validation fails - modal will not be shown
                        }
                        
                        // Only proceed with package selection if validation passed
                        const packageCard = event.target.closest('.package-card');
                        if (!packageCard) return;
                        
                        const packageName = packageCard.dataset.name;
                        const packagePrice = packageCard.dataset.price;
                        const packageImage = packageCard.dataset.image || '';
                        const serviceType = packageCard.dataset.service;

                        sessionStorage.setItem('selectedPackageName', packageName);
                        sessionStorage.setItem('selectedPackagePrice', packagePrice);
                        sessionStorage.setItem('selectedPackageImage', packageImage);
                        sessionStorage.setItem('selectedServiceType', serviceType);
                        
                        const features = Array.from(packageCard.querySelectorAll('ul li')).map(li => li.textContent.trim());
                        sessionStorage.setItem('selectedPackageFeatures', JSON.stringify(features));
                        
                        const traditionalBtn = document.getElementById('traditionalServiceBtn');
                        traditionalBtn.innerHTML = `
                            <i class="fas fa-dove text-3xl text-yellow-600 mb-2"></i>
                            <span class="font-hedvig text-lg">Traditional</span>
                            <span class="text-sm text-gray-600 mt-2 text-center">For immediate funeral needs</span>
                        `;
                        
                        // Only show the modal if validation passed
                        document.getElementById('serviceTypeModal').classList.remove('hidden');
                    }
                });
            });
        </script>

        <!-- No Results Message (initially hidden) -->
        <div id="no-results" class="<?= (count($packages) > 0) ? 'hidden' : '' ?> text-center py-12">
            <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-2xl font-hedvig text-navy mb-2">No Packages Found</h3>
            <p class="text-gray-600 max-w-md mx-auto">We couldn't find any packages matching your criteria. Try adjusting your filters or search terms.</p>
            <button id="reset-filters-no-results" class="mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
    Reset All Filters
</button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-black font-playfair text-white py-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <h3 class="text-yellow-600 text-2xl mb-4">GrievEase</h3>
                    <p class="text-gray-300 mb-4">Providing dignified funeral services with compassion and respect since 1980.</p>
                    <div class="flex space-x-4"></div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-300 hover:text-white transition">Home</a></li>
                        <li><a href="about.php" class="text-gray-300 hover:text-white transition">About</a></li>
                        <li><a href="lifeplan.php" class="text-gray-300 hover:text-white transition">Life Plan</a></li>
                        <li><a href="traditional_funeral.php" class="text-gray-300 hover:text-white transition">Traditional Funeral</a></li>
                        <li><a href="faqs.php" class="text-gray-300 hover:text-white transition">FAQs</a></li>
                    </ul>
                </div>
                
                <!-- Services -->
                <div>
                    <h3 class="text-lg mb-4">Our Services</h3>
                    <ul class="space-y-2">
                        <li><a href="traditional_funeral.php" class="text-gray-300 hover:text-white transition">Traditional Funeral</a></li>
                        <li><a href="lifeplan.php" class="text-gray-300 hover:text-white transition">Life Plan</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-2 text-yellow-600"></i>
                            <span>#6 J.P Rizal St. Brgy. Sta Clara Sur, (Pob) Pila, Laguna</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt mr-2 text-yellow-600"></i>
                            <span>(0956) 814-3000 <br> (0961) 345-4283</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2 text-yellow-600"></i>
                            <span>GrievEase@gmail.com</span>
                        </li>
                        <li class="flex items-center">
                            <a href="https://web.facebook.com/vjayrelovafuneralservices" class="hover:text-white transition">
                                <i class="fab fa-facebook-f mr-2 text-yellow-600"></i>
                                <span>VJay Relova Funeral Services</span>
                            </a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock mr-2 text-yellow-600"></i>
                            <span>Available 24/7</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p class="text-yellow-600">&copy; 2025 Vjay Relova Funeral Services. All rights reserved.</p>
                <div class="mt-2">
                    <a href=".\privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                    <a href="..\termsofservice.php" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <?php include 'customService/chat_elements.html'; ?>

    <!-- Initial Service Type Selection Modal (Hidden by Default) -->
<div id="serviceTypeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6">
            <h2 class="text-2xl font-hedvig text-navy mb-6 text-center">Select Service Type</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <button id="traditionalServiceBtn" class="bg-cream hover:bg-yellow-100 border-2 border-yellow-600 text-navy px-6 py-8 rounded-lg shadow-md transition-all duration-300 flex flex-col items-center">
                    <i class="fas fa-dove text-3xl text-yellow-600 mb-2"></i>
                    <span class="font-hedvig text-lg">Traditional</span>
                    <span class="text-sm text-gray-600 mt-2 text-center">For immediate funeral needs</span>
                </button>
                
                <button id="lifeplanServiceBtn" class="bg-cream hover:bg-yellow-100 border-2 border-yellow-600 text-navy px-6 py-8 rounded-lg shadow-md transition-all duration-300 flex flex-col items-center">
                    <i class="fas fa-seedling text-3xl text-yellow-600 mb-2"></i>
                    <span class="font-hedvig text-lg">Lifeplan</span>
                    <span class="text-sm text-gray-600 mt-2 text-center">Lifeplan funeral planning</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Traditional Funeral Modal (Hidden by Default) -->
<div id="traditionalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] md:max-h-[80vh]">
        <!-- Scroll container for both columns -->
        <div class="modal-scroll-container grid grid-cols-1 md:grid-cols-2 overflow-y-auto max-h-[90vh] md:max-h-[80vh]">
            <!-- Left Side: Package Details -->
            <div class="bg-cream p-4 md:p-8 details-section">
                <!-- Header and Close Button for Mobile -->
                <div class="flex justify-between items-center mb-4 md:hidden">
                    <h2 class="text-xl font-hedvig text-navy">Package Details</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Package Image -->
                <div class="mb-4 md:mb-6">
                    <img id="traditionalPackageImage" src="" alt="" class="w-full h-48 md:h-64 object-cover rounded-lg mb-4">
                </div>

                <!-- Package Header -->
                <div class="flex justify-between items-center mb-4 md:mb-6">
                    <h2 id="traditionalPackageName" class="text-2xl md:text-3xl font-hedvig text-navy"></h2>
                    <div id="traditionalPackagePrice" class="text-2xl md:text-3xl font-hedvig text-yellow-600"></div>
                </div>

                <!-- Package Description -->
                <p id="traditionalPackageDesc" class="text-dark mb-4 md:mb-6 text-sm md:text-base"></p>

                <!-- Main Package Details -->
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-lg md:text-xl font-hedvig text-navy mb-3 md:mb-4">Package Includes:</h3>
                    <ul id="traditionalPackageFeatures" class="space-y-1 md:space-y-2">
                        <!-- Features will be inserted here by JavaScript -->
                    </ul>
                </div>

                <!-- Mobile-only summary and navigation button -->
                <div class="mt-6 border-t border-gray-200 pt-4 md:hidden">
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="traditionalTotalPriceMobile" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between font-bold mt-2 pt-2 border-t border-gray-300">
                            <span class="text-navy">Amount Due Now (30%)</span>
                            <span id="traditionalAmountDueMobile" class="text-yellow-600">₱0</span>
                        </div>
                    </div>
                    <button id="continueToFormBtn" class="mt-4 w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                        Continue to Booking
                    </button>
                </div>
            </div>

            <!-- Right Side: Booking Form -->
            <div class="bg-white p-4 md:p-8 border-t md:border-t-0 md:border-l border-gray-200 overflow-y-auto form-section hidden md:block">
                <!-- Header and back button for mobile -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl md:text-2xl font-hedvig text-navy">Book Your Traditional Service</h2>
                    <div class="flex items-center">
                        <button id="backToDetailsBtn" class="mr-2 text-gray-500 hover:text-navy md:hidden flex items-center">
                            <i class="fas fa-arrow-left text-lg mr-1"></i>
                            <span class="text-sm">Back</span>
                        </button>
                        <button class="closeModalBtn text-gray-500 hover:text-navy">
                            <i class="fas fa-times text-xl md:text-2xl"></i>
                        </button>
                    </div>
                </div>

                <form id="traditionalBookingForm" class="space-y-4">
                    <input type="hidden" id="traditionalSelectedPackagePrice" name="packagePrice">
                    <input type="hidden" id="traditionalServiceId" name="service_id">
                    <input type="hidden" id="traditionalBranchId" name="branch_id">
                    <input type="hidden" name="customerID" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Deceased Information</h3>
                        
                        <!-- First Name & Middle Name (Side by side) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="traditionalDeceasedFirstName" class="block text-sm font-medium text-navy mb-1">First Name <span class="text-red-500">*</label>
                                <input type="text" id="traditionalDeceasedFirstName" name="deceasedFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="traditionalDeceasedMiddleName" class="block text-sm font-medium text-navy mb-1">Middle Name</label>
                                <input type="text" id="traditionalDeceasedMiddleName" name="deceasedMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                        </div>
                        
                        <!-- Last Name & Suffix (Side by side) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-3/4 px-2 mb-3 sm:mb-0">
                                <label for="traditionalDeceasedLastName" class="block text-sm font-medium text-navy mb-1">Last Name <span class="text-red-500">*</label>
                                <input type="text" id="traditionalDeceasedLastName" name="deceasedLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                            <div class="w-full sm:w-1/4 px-2">
                                <label for="traditionalDeceasedSuffix" class="block text-sm font-medium text-navy mb-1">Suffix</label>
                                <select id="traditionalDeceasedSuffix" name="deceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Date fields (Three in a row on larger screens, stacked on mobile) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/3 px-2 mb-3 sm:mb-0">
                                <label for="traditionalDateOfBirth" class="block text-sm font-medium text-navy mb-1">Date of Birth</label>
                                <input type="date" id="traditionalDateOfBirth" name="dateOfBirth" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div class="w-full sm:w-1/3 px-2 mb-3 sm:mb-0">
                                <label for="traditionalDateOfDeath" class="block text-sm font-medium text-navy mb-1">Date of Death <span class="text-red-500">*</label>
                                <input type="date" id="traditionalDateOfDeath" name="dateOfDeath" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div class="w-full sm:w-1/3 px-2">
                                <label for="traditionalDateOfBurial" class="block text-sm font-medium text-navy mb-1">Date of Burial</label>
                                <input type="date" id="traditionalDateOfBurial" name="dateOfBurial" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        
                        <!-- Death Certificate Upload with Preview -->
                        <div class="mb-4">
                            <label for="traditionalDeathCertificate" class="block text-sm font-medium text-navy mb-1">Death Certificate</label>
                            <div class="border border-input-border bg-white rounded-lg p-3 focus-within:ring-2 focus-within:ring-yellow-600">
                                <!-- Upload Button and File Name -->
                                <div class="flex items-center mb-2">
                                    <label for="traditionalDeathCertificate" class="flex-1 cursor-pointer">
                                        <div class="flex items-center justify-center py-2 px-3 bg-gray-50 rounded hover:bg-gray-100 transition">
                                            <i class="fas fa-upload mr-2 text-gray-500"></i>
                                            <span class="text-sm text-gray-600">Upload Certificate</span>
                                        </div>
                                    </label>
                                    <span class="text-xs ml-2 text-gray-500" id="traditionalDeathCertFileName">No file chosen</span>
                                </div>
                                
                                <!-- Preview Container -->
                                <div id="deathCertPreviewContainer" class="hidden mt-2 rounded-lg overflow-hidden border border-gray-200">
                                    <!-- Image Preview -->
                                    <div id="deathCertImagePreview" class="hidden">
                                        <img id="deathCertImage" src="" alt="Death Certificate Preview" class="w-full h-auto max-h-48 object-contain">
                                    </div>
                                    
                                    
                                </div>
                                
                                <!-- Remove Button -->
                                <button type="button" id="removeDeathCert" class="text-xs text-red-600 hover:text-red-800 mt-2 hidden">
                                    <i class="fas fa-trash-alt mr-1"></i> Remove file
                                </button>
                                
                                <input type="file" id="traditionalDeathCertificate" name="deathCertificate" accept=".jpg,.jpeg,.png" class="hidden">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG</p>
                        </div>
                        
                        <!-- Address (Improved UI with dropdowns in specified layout) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="traditionalDeceasedRegion" class="block text-sm font-medium text-navy mb-1">Region <span class="text-red-500">*</span></label>
                                <select id="traditionalDeceasedRegion" name="deceasedRegion" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" required>
                                    <option value="">Select Region</option>
                                    <!-- Regions will be populated by JavaScript -->
                                </select>
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="traditionalDeceasedProvince" class="block text-sm font-medium text-navy mb-1">Province <span class="text-red-500">*</span></label>
                                <select id="traditionalDeceasedProvince" name="deceasedProvince" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" disabled required>
                                    <option value="">Select Province</option>
                                    <!-- Provinces will be populated by JavaScript based on selected region -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="traditionalDeceasedCity" class="block text-sm font-medium text-navy mb-1">City/Municipality <span class="text-red-500">*</span></label>
                                <select id="traditionalDeceasedCity" name="deceasedCity" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" disabled required>
                                    <option value="">Select City/Municipality</option>
                                    <!-- Cities will be populated by JavaScript based on selected province -->
                                </select>
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="traditionalDeceasedBarangay" class="block text-sm font-medium text-navy mb-1">Barangay <span class="text-red-500">*</span></label>
                                <select id="traditionalDeceasedBarangay" name="deceasedBarangay" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" disabled required>
                                    <option value="">Select Barangay</option>
                                    <!-- Barangays will be populated by JavaScript based on selected city -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label for="traditionalDeceasedAddress" class="block text-sm font-medium text-navy mb-2">Street/Block/House Number <span class="text-red-500">*</span></label>
                            <input type="text" id="traditionalDeceasedAddress" name="deceasedStreet" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="e.g. 123 Main Street">
                        </div>
                        
                        <input type="hidden" id="deceasedAddress" name="deceasedAddress">
                        
                        <div class="flex items-center mt-3 md:mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <input type="checkbox" id="traditionalWithCremate" name="with_cremate" value="yes" class="h-5 w-5 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                            <div class="ml-3">
                                <label for="traditionalWithCremate" class="block text-sm font-medium text-navy">
                                    Include Cremation Service
                                </label>
                                <p class="text-xs text-gray-500 mt-1">Cremation and urn for 40,000 pesos</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Payment</h3>

                        <!-- QR Code Button and Modal -->
                        <div class="mb-4">
                            <button type="button" id="showQrCodeBtn" class="w-full bg-navy hover:bg-navy-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-all duration-200">
                                <i class="fas fa-qrcode mr-2"></i>
                                <span>View GCash QR Code</span>
                            </button>
                        </div>
                        
                        <!-- QR Code Modal -->
                        <!-- QR Code Modal (for traditional) -->
                        <div id="qrCodeModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                            <div class="bg-white rounded-lg p-4 sm:p-6 max-w-[90vw] sm:max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg sm:text-xl font-hedvig text-navy">Scan to Pay</h3>
                                    <button id="closeQrModal" class="text-gray-500 hover:text-navy">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <div class="flex flex-col items-center justify-center">
                                    <?php if (!empty($gcash_qrs)): ?>
                                        <div id="gcashQrContainer" class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                                            <?php foreach ($gcash_qrs as $qr): ?>
                                                <div class="gcash-qr-option cursor-pointer p-2 border border-gray-200 rounded-lg hover:border-yellow-600 transition-colors flex justify-center items-center"
                                                     data-qr-number="<?= htmlspecialchars($qr['qr_number']) ?>">
                                                    <div class="w-48 h-32 sm:w-64 sm:h-40">
                                                        <img src="<?= htmlspecialchars($qr['qr_image']) ?>" 
                                                             alt="GCash QR Code <?= htmlspecialchars($qr['qr_number']) ?>" 
                                                             class="w-full h-full object-contain landscape-img"
                                                             onclick="enlargeQrCode(this)">
                                                        <p class="text-center text-xs sm:text-sm font-medium text-gray-600 mt-2"><?= htmlspecialchars($qr['qr_number']) ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" id="selectedGcashQr" name="gcashQrNumber" value="">
                                    <?php else: ?>
                                        <p class="text-center text-sm text-gray-500">No GCash QR codes available</p>
                                    <?php endif; ?>
                                    <p class="text-center text-sm text-gray-600 mt-4 mb-2">Scan a QR code with your GCash app to make payment</p>
                                    <p class="text-center font-bold text-yellow-600" id="qrCodeAmount">Amount: ₱0</p>
                                </div>
                            </div>
                        </div>

<!-- Enlarged QR Code View (hidden by default) -->
<div id="enlargedQrView" class="fixed inset-0 bg-black bg-opacity-90 z-[60] flex items-center justify-center hidden">
    <div class="relative max-w-4xl w-full p-4">
        <button onclick="closeEnlargedQr()" class="absolute top-4 right-4 text-white text-2xl z-10">
            <i class="fas fa-times"></i>
        </button>
        <img id="enlargedQrImage" src="" class="w-full max-h-[90vh] object-contain" alt="Enlarged QR Code">
    </div>
</div>
                        
                        <!-- GCash Upload with Preview -->
                        <div class="mb-4">
                            <label for="traditionalGcashReceipt" class="block text-sm font-medium text-navy mb-1">Payment Proof <span class="text-red-500">*</label>
                            <div class="border border-input-border bg-white rounded-lg p-3 focus-within:ring-2 focus-within:ring-yellow-600">
                                <!-- Upload Button and File Name -->
                                <div class="flex items-center mb-2">
                                    <label for="traditionalGcashReceipt" class="flex-1 cursor-pointer">
                                        <div class="flex items-center justify-center py-2 px-3 bg-gray-50 rounded hover:bg-gray-100 transition">
                                            <i class="fas fa-receipt mr-2 text-blue-500"></i>
                                            <span class="text-sm text-gray-600">Upload Receipt</span>
                                        </div>
                                    </label>
                                    <span class="text-xs ml-2 text-gray-500" id="traditionalGcashFileName">No file chosen</span>
                                </div>
                                
                                <!-- Preview Container -->
                                <div id="gcashPreviewContainer" class="hidden mt-2 rounded-lg overflow-hidden border border-gray-200">
                                    <!-- Image Preview -->
                                    <div id="gcashImagePreview" class="hidden">
                                        <img id="gcashImage" src="" alt="GCash Receipt Preview" class="w-full h-auto max-h-48 object-contain">
                                    </div>
                                    
                                </div>
                                
                                <!-- Remove Button -->
                                <button type="button" id="removeGcash" class="text-xs text-red-600 hover:text-red-800 mt-2 hidden">
                                    <i class="fas fa-trash-alt mr-1"></i> Remove file
                                </button>
                                
                                <!-- Traditional GCash Receipt Input -->
<input type="file" id="traditionalGcashReceipt" name="gcashReceipt" accept=".jpg,.jpeg,.png" class="hidden">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG</p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="traditionalReferenceNumber" class="block text-sm font-medium text-navy mb-1">Reference Number <span class="text-red-500">*</label>
                            <input type="text" id="traditionalReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="e.g. 1234567890">
                        </div>
                    </div>

                    <div class="bg-cream p-3 md:p-4 rounded-lg">
                        <div class="flex justify-between text-xs md:text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="traditionalTotalPrice" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between text-xs md:text-sm mb-2">
                            <span class="text-navy">Required Downpayment (30%)</span>
                            <span id="traditionalDownpayment" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between font-bold mt-2 pt-2 border-t border-gray-300">
                            <span class="text-navy">Amount Due Now</span>
                            <span id="traditionalAmountDue" class="text-yellow-600">₱0</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                        Confirm Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

    // Function to enlarge QR code
function enlargeQrCode(imgElement) {
    const enlargedView = document.getElementById('enlargedQrView');
    const enlargedImg = document.getElementById('enlargedQrImage');
    
    enlargedImg.src = imgElement.src;
    enlargedView.classList.remove('hidden');
    
    // Close when clicking outside the image
    enlargedView.addEventListener('click', function(e) {
        if (e.target === this) {
            closeEnlargedQr();
        }
    });
}

// Function to close enlarged view
function closeEnlargedQr() {
    document.getElementById('enlargedQrView').classList.add('hidden');
}

// Close with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('enlargedQrView').classList.contains('hidden')) {
        closeEnlargedQr();
    }
});
// QR Code functionality for both modals
document.addEventListener('DOMContentLoaded', function() {
    // Traditional modal QR code
    const showQrCodeBtn = document.getElementById('showQrCodeBtn');
    const qrCodeModal = document.getElementById('qrCodeModal');
    const closeQrModal = document.getElementById('closeQrModal');
    const qrCodeAmount = document.getElementById('qrCodeAmount');
    
    if (showQrCodeBtn && qrCodeModal) {
        showQrCodeBtn.addEventListener('click', function() {
            const amountDue = document.getElementById('traditionalAmountDue').textContent;
            qrCodeAmount.textContent = 'Amount: ' + amountDue;
            qrCodeModal.classList.remove('hidden');
        });
        
        closeQrModal.addEventListener('click', function() {
            qrCodeModal.classList.add('hidden');
        });
        
        qrCodeModal.addEventListener('click', function(e) {
            if (e.target === qrCodeModal) {
                qrCodeModal.classList.add('hidden');
            }
        });
    }

    // Lifeplan modal QR code
    const lifeplanShowQrCodeBtn = document.getElementById('lifeplanShowQrCodeBtn');
    const lifeplanQrCodeModal = document.getElementById('lifeplanQrCodeModal');
    const lifeplanCloseQrModal = document.getElementById('lifeplanCloseQrModal');
    const lifeplanQrCodeAmount = document.getElementById('lifeplanQrCodeAmount');
    
    if (lifeplanShowQrCodeBtn && lifeplanQrCodeModal) {
        lifeplanShowQrCodeBtn.addEventListener('click', function() {
            const monthlyPayment = document.getElementById('lifeplanMonthlyPayment').textContent;
            lifeplanQrCodeAmount.textContent = 'Amount: ' + monthlyPayment;
            lifeplanQrCodeModal.classList.remove('hidden');
        });
        
        lifeplanCloseQrModal.addEventListener('click', function() {
            lifeplanQrCodeModal.classList.add('hidden');
        });
        
        lifeplanQrCodeModal.addEventListener('click', function(e) {
            if (e.target === lifeplanQrCodeModal) {
                lifeplanQrCodeModal.classList.add('hidden');
            }
        });
    }
});

// Lifeplan Holder Street Address Validation
function validateLifeplanHolderStreet(input) {
    // Remove any leading spaces
    let value = input.value.replace(/^\s+/, '');
    
    // Remove multiple consecutive spaces
    value = value.replace(/\s{2,}/g, ' ');
    
    // Capitalize first letter of the string if it exists
    if (value.length > 0) {
        value = value.charAt(0).toUpperCase() + value.slice(1);
    }
    
    // Update the input value
    input.value = value;
}

// Lifeplan Reference Number Validation (numbers only, no letters or spaces)
function validateLifeplanReferenceNumber(input) {
    // Remove any non-digit characters
    let value = input.value.replace(/[^0-9]/g, '');
    
    // Update the input value
    input.value = value;
}

document.addEventListener('DOMContentLoaded', function() {
    // Lifeplan Holder Street field
    const lifeplanHolderStreetInput = document.getElementById('lifeplanHolderStreet');
    if (lifeplanHolderStreetInput) {
        lifeplanHolderStreetInput.addEventListener('input', function() {
            validateLifeplanHolderStreet(this);
        });
        
        lifeplanHolderStreetInput.addEventListener('blur', function() {
            validateLifeplanHolderStreet(this);
        });
        
        // Prevent pasting text that starts with space
        lifeplanHolderStreetInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            let cleanedText = pastedText.replace(/^\s+/, ''); // Remove leading spaces
            cleanedText = cleanedText.replace(/\s{2,}/g, ' '); // Remove multiple spaces
            document.execCommand('insertText', false, cleanedText);
        });
    }

    // Lifeplan Reference Number field
    const lifeplanReferenceNumberInput = document.getElementById('lifeplanReferenceNumber');
    if (lifeplanReferenceNumberInput) {
        lifeplanReferenceNumberInput.addEventListener('input', function() {
            validateLifeplanReferenceNumber(this);
        });
        
        lifeplanReferenceNumberInput.addEventListener('blur', function() {
            validateLifeplanReferenceNumber(this);
        });
        
        lifeplanReferenceNumberInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedText = pastedText.replace(/[^0-9]/g, ''); // Remove non-digit characters
            document.execCommand('insertText', false, cleanedText);
        });
    }
});
</script>

<script>
    // Enhanced address dropdown functions with AJAX for traditional form
function updateTraditionalProvinces() {
    const regionId = document.getElementById('traditionalDeceasedRegion').value;
    const provinceDropdown = document.getElementById('traditionalDeceasedProvince');
    
    if (!regionId) {
        provinceDropdown.disabled = true;
        document.getElementById('traditionalDeceasedCity').disabled = true;
        document.getElementById('traditionalDeceasedBarangay').disabled = true;
        return;
    }
    
    // Fetch provinces via AJAX
    fetch('address/get_provinces.php?region_id=' + regionId)
        .then(response => response.json())
        .then(data => {
            provinceDropdown.innerHTML = '<option value="">Select Province</option>';
            data.forEach(province => {
                provinceDropdown.innerHTML += `<option value="${province.province_id}">${province.province_name}</option>`;
            });
            provinceDropdown.disabled = false;
            
            // Reset dependent dropdowns
            document.getElementById('traditionalDeceasedCity').innerHTML = '<option value="">Select City/Municipality</option>';
            document.getElementById('traditionalDeceasedCity').disabled = true;
            document.getElementById('traditionalDeceasedBarangay').innerHTML = '<option value="">Select Barangay</option>';
            document.getElementById('traditionalDeceasedBarangay').disabled = true;
        })
        .catch(error => {
            console.error('Error fetching provinces:', error);
        });
}

function updateTraditionalCities() {
    const provinceId = document.getElementById('traditionalDeceasedProvince').value;
    const cityDropdown = document.getElementById('traditionalDeceasedCity');
    
    if (!provinceId) {
        cityDropdown.disabled = true;
        document.getElementById('traditionalDeceasedBarangay').disabled = true;
        return;
    }
    
    // Fetch cities via AJAX
    fetch('address/get_cities.php?province_id=' + provinceId)
        .then(response => response.json())
        .then(data => {
            cityDropdown.innerHTML = '<option value="">Select City/Municipality</option>';
            data.forEach(city => {
                cityDropdown.innerHTML += `<option value="${city.municipality_id}">${city.municipality_name}</option>`;
            });
            cityDropdown.disabled = false;
            
            // Reset dependent dropdown
            document.getElementById('traditionalDeceasedBarangay').innerHTML = '<option value="">Select Barangay</option>';
            document.getElementById('traditionalDeceasedBarangay').disabled = true;
        })
        .catch(error => {
            console.error('Error fetching cities:', error);
        });
}

function updateTraditionalBarangays() {
    const cityId = document.getElementById('traditionalDeceasedCity').value;
    const barangayDropdown = document.getElementById('traditionalDeceasedBarangay');
    
    if (!cityId) {
        barangayDropdown.disabled = true;
        return;
    }
    
    // Fetch barangays via AJAX
    fetch('address/get_barangays.php?city_id=' + cityId)
        .then(response => response.json())
        .then(data => {
            barangayDropdown.innerHTML = '<option value="">Select Barangay</option>';
            data.forEach(barangay => {
                barangayDropdown.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
            });
            barangayDropdown.disabled = false;
        })
        .catch(error => {
            console.error('Error fetching barangays:', error);
        });
}

// Initialize the dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    
    // Load regions via AJAX
    fetch('address/get_regions.php')
        .then(response => {
            console.log('Regions response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Regions data:', data);
            const regionDropdown = document.getElementById('traditionalDeceasedRegion');
            
            // Check if dropdown exists
            if (regionDropdown) {
                regionDropdown.innerHTML = '<option value="">Select Region</option>';
                data.forEach(region => {
                    regionDropdown.innerHTML += `<option value="${region.region_id}">${region.region_name}</option>`;
                });
            } else {
                console.error('traditionalDeceasedRegion dropdown not found in the DOM');
            }
        })
        .catch(error => {
            console.error('Error loading regions:', error);
        });
    
    // Set up event listeners
    const regionElement = document.getElementById('traditionalDeceasedRegion');
    const provinceElement = document.getElementById('traditionalDeceasedProvince');
    const cityElement = document.getElementById('traditionalDeceasedCity');
    
    document.getElementById('traditionalDeceasedRegion').addEventListener('change', function() {
        updateTraditionalProvinces();
        combineAddress();
    });
    
    document.getElementById('traditionalDeceasedProvince').addEventListener('change', function() {
        updateTraditionalCities();
        combineAddress();
    });
    
    document.getElementById('traditionalDeceasedCity').addEventListener('change', function() {
        updateTraditionalBarangays();
        combineAddress();
    });
    
    document.getElementById('traditionalDeceasedBarangay').addEventListener('change', combineAddress);
    document.getElementById('traditionalDeceasedAddress').addEventListener('input', combineAddress);
});

    function combineAddress() {
        // Get the selected option text from each dropdown
        const regionSelect = document.getElementById('traditionalDeceasedRegion');
        const regionText = regionSelect.options[regionSelect.selectedIndex]?.text || '';
        
        const provinceSelect = document.getElementById('traditionalDeceasedProvince');
        const provinceText = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
        
        const citySelect = document.getElementById('traditionalDeceasedCity');
        const cityText = citySelect.options[citySelect.selectedIndex]?.text || '';
        
        const barangaySelect = document.getElementById('traditionalDeceasedBarangay');
        const barangayText = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
        
        // Get the street address from the input field
        const streetText = document.getElementById('traditionalDeceasedAddress').value || '';
        
        // Filter out any empty values and default selection texts
        const addressParts = [
            regionText !== 'Select Region' ? regionText : '',
            provinceText !== 'Select Province' ? provinceText : '',
            cityText !== 'Select City/Municipality' ? cityText : '',
            barangayText !== 'Select Barangay' ? barangayText : '',
            streetText
        ].filter(part => part !== '');
        
        // Combine all parts with comma separation
        const combinedAddress = addressParts.join(', ');
        
        // Set the value to the hidden input
        document.getElementById('deceasedAddress').value = combinedAddress;
        
        return combinedAddress;
    }


    function combineLifeplanAddress() {
        const region = document.getElementById('lifeplanHolderRegion');
        const province = document.getElementById('lifeplanHolderProvince');
        const city = document.getElementById('lifeplanHolderCity');
        const barangay = document.getElementById('lifeplanHolderBarangay');
        const street = document.getElementById('lifeplanHolderStreet');
        
        // Create an address object
        const address = {
            region: region.options[region.selectedIndex]?.text || '',
            province: province.options[province.selectedIndex]?.text || '',
            city: city.options[city.selectedIndex]?.text || '',
            barangay: barangay.options[barangay.selectedIndex]?.text || '',
            street: street.value || ''
        };
        
        // Convert to JSON string and store in the hidden input
        document.getElementById('holderAddress').value = JSON.stringify(address);
    }
    function updateLifeplanProvinces() {
        const regionId = document.getElementById('lifeplanHolderRegion').value;
        const provinceDropdown = document.getElementById('lifeplanHolderProvince');
        console.log('updateLifeplanProvinces called');
        console.log('Selected region ID:', regionId)
        
        if (!regionId) {
            provinceDropdown.disabled = true;
            document.getElementById('lifeplanHolderCity').disabled = true;
            document.getElementById('lifeplanHolderBarangay').disabled = true;
            return;
        }
        
        // Fetch provinces via AJAX
        fetch('address/get_provinces.php?region_id=' + regionId)
            .then(response => response.json())
            .then(data => {
                provinceDropdown.innerHTML = '<option value="">Select Province</option>';
                data.forEach(province => {
                    provinceDropdown.innerHTML += `<option value="${province.province_id}">${province.province_name}</option>`;
                });
                provinceDropdown.disabled = false;
                
                // Reset dependent dropdowns
                document.getElementById('lifeplanHolderCity').innerHTML = '<option value="">Select City/Municipality</option>';
                document.getElementById('lifeplanHolderCity').disabled = true;
                document.getElementById('lifeplanHolderBarangay').innerHTML = '<option value="">Select Barangay</option>';
                document.getElementById('lifeplanHolderBarangay').disabled = true;
            })
            .catch(error => {
                console.error('Error fetching provinces:', error);
            });
    }

    function updateLifeplanCities() {
        const provinceId = document.getElementById('lifeplanHolderProvince').value;
        const cityDropdown = document.getElementById('lifeplanHolderCity');
        
        if (!provinceId) {
            cityDropdown.disabled = true;
            document.getElementById('lifeplanHolderBarangay').disabled = true;
            return;
        }
        
        // Fetch cities via AJAX
        fetch('address/get_cities.php?province_id=' + provinceId)
            .then(response => response.json())
            .then(data => {
                cityDropdown.innerHTML = '<option value="">Select City/Municipality</option>';
                data.forEach(city => {
                    cityDropdown.innerHTML += `<option value="${city.municipality_id}">${city.municipality_name}</option>`;
                });
                cityDropdown.disabled = false;
                
                // Reset dependent dropdown
                document.getElementById('lifeplanHolderBarangay').innerHTML = '<option value="">Select Barangay</option>';
                document.getElementById('lifeplanHolderBarangay').disabled = true;
            })
            .catch(error => {
                console.error('Error fetching cities:', error);
            });
    }

    function updateLifeplanBarangays() {
        const cityId = document.getElementById('lifeplanHolderCity').value;
        const barangayDropdown = document.getElementById('lifeplanHolderBarangay');
        
        if (!cityId) {
            barangayDropdown.disabled = true;
            return;
        }
        
        // Fetch barangays via AJAX
        fetch('address/get_barangays.php?city_id=' + cityId)
            .then(response => response.json())
            .then(data => {
                barangayDropdown.innerHTML = '<option value="">Select Barangay</option>';
                data.forEach(barangay => {
                    barangayDropdown.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
                });
                barangayDropdown.disabled = false;
            })
            .catch(error => {
                console.error('Error fetching barangays:', error);
            });
    }


    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded Lifeplan');
        
        // Load regions via AJAX
        fetch('address/get_regions.php')
            .then(response => {
                console.log('Regions response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Regions data lp:', data);
                const regionDropdown = document.getElementById('lifeplanHolderRegion');
                
                // Check if dropdown exists
                if (regionDropdown) {
                    regionDropdown.innerHTML = '<option value="">Select Region</option>';
                    data.forEach(region => {
                        regionDropdown.innerHTML += `<option value="${region.region_id}">${region.region_name}</option>`;
                    });
                } else {
                    console.error('lifeplanHolderRegion dropdown not found in the DOM');
                }
            })
            .catch(error => {
                console.error('Error loading regions:', error);
            });
        
        // Set up event listeners
        const regionElement = document.getElementById('lifeplanHolderRegion');
        const provinceElement = document.getElementById('lifeplanHolderProvince');
        const cityElement = document.getElementById('lifeplanHolderCity');

        
        if (regionElement) {
            regionElement.addEventListener('change', (event) => {
                updateLifeplanProvinces(event);
                combineLifeplanAddress(event);
            });
        } else {
            console.error('lifeplanHolderRegion element not found for event listener');
        }

        if (provinceElement) {
            provinceElement.addEventListener('change', (event) => {
                updateLifeplanCities(event);
                combineLifeplanAddress(event);
            });
        } else {
            console.error('lifeplanHolderProvince element not found for event listener');
        }

        if (cityElement) {
            cityElement.addEventListener('change', (event) => {
                updateLifeplanBarangays(event);
                combineLifeplanAddress(event);
            });
        } else {
            console.error('lifeplanHolderCity element not found for event listener');
        }
    });

// Traditional Funeral Deceased Street Address Validation
function validateTraditionalDeceasedStreet(input) {
    // Remove any leading spaces
    let value = input.value.replace(/^\s+/, '');
    
    // Remove multiple consecutive spaces
    value = value.replace(/\s{2,}/g, ' ');
    
    // Capitalize first letter of the string if it exists
    if (value.length > 0) {
        value = value.charAt(0).toUpperCase() + value.slice(1);
    }
    
    // Update both the visible input and hidden field
    input.value = value;
    document.getElementById('deceasedAddress').value = value;
}

document.addEventListener('DOMContentLoaded', function() {
    // Traditional Funeral Deceased Street field
    const traditionalDeceasedStreetInput = document.getElementById('traditionalDeceasedStreet');
    if (traditionalDeceasedStreetInput) {
        traditionalDeceasedStreetInput.addEventListener('input', function() {
            validateTraditionalDeceasedStreet(this);
        });
        
        traditionalDeceasedStreetInput.addEventListener('blur', function() {
            validateTraditionalDeceasedStreet(this);
        });
        
        // Prevent pasting text that starts with space
        traditionalDeceasedStreetInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            let cleanedText = pastedText.replace(/^\s+/, ''); // Remove leading spaces
            cleanedText = cleanedText.replace(/\s{2,}/g, ' '); // Remove multiple spaces
            document.execCommand('insertText', false, cleanedText);
            // Also update the hidden field
            document.getElementById('deceasedAddress').value = cleanedText;
        });
        
        // Initialize hidden field with current value
        document.getElementById('deceasedAddress').value = traditionalDeceasedStreetInput.value;
    }
});

// Traditional Reference Number Validation (numbers only, no letters or spaces)
// Traditional Reference Number Validation (numbers only, no letters, symbols or spaces)
function validateTraditionalReferenceNumber(input) {
    // Remove any non-digit characters
    let value = input.value.replace(/[^0-9]/g, '');
    
    // Update the input value
    input.value = value;
}

document.addEventListener('DOMContentLoaded', function() {
    // Traditional Reference Number field
    const referenceNumberInput = document.getElementById('traditionalReferenceNumber');
    if (referenceNumberInput) {
        referenceNumberInput.addEventListener('input', function() {
            validateTraditionalReferenceNumber(this);
        });
        
        referenceNumberInput.addEventListener('blur', function() {
            validateTraditionalReferenceNumber(this);
        });
        
        referenceNumberInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedText = pastedText.replace(/[^0-9]/g, ''); // Remove non-digit characters
            document.execCommand('insertText', false, cleanedText);
        });
    }
});

// Add this to your form submission handler
document.querySelector('form').addEventListener('submit', function(e) {
    // Create or update hidden input with combined address
    let addressInput = document.getElementById('deceasedAddress');
    if (!addressInput) {
        addressInput = document.createElement('input');
        addressInput.type = 'hidden';
        addressInput.name = 'deceasedAddress';
        addressInput.id = 'deceasedAddress';
        this.appendChild(addressInput);
    }
    addressInput.value = combineAddress();
});

</script>

<!-- Add this script at the end -->
<script>

    // Disable buttons if not validated
if (validationStatus !== 'valid') {
    document.querySelectorAll('.selectPackageBtn').forEach(btn => {
        btn.classList.add('disabled');
    });
}
// Death Certificate Upload Preview
// Traditional Death Certificate Upload Handler
document.getElementById('traditionalDeathCertificate')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) {
        hideDeathCertPreview();
        return;
    }

    // Check if the file is an image
    if (!file.type.match('image.*')) {
        Swal.fire({
            title: 'Invalid File Type',
            text: 'Please upload an image file (JPG, JPEG, PNG).',
            icon: 'error',
            confirmButtonColor: '#d97706'
        });
        this.value = ''; // Clear the file input
        return;
    }

    // Update file name display
    const fileName = file.name;
    document.getElementById('traditionalDeathCertFileName').textContent = fileName.length > 20 ? 
        fileName.substring(0, 17) + '...' : fileName;

    // Show preview container
    const previewContainer = document.getElementById('deathCertPreviewContainer');
    previewContainer?.classList.remove('hidden');

    // Show remove button
    document.getElementById('removeDeathCert')?.classList.remove('hidden');

    // Image Preview
    document.getElementById('deathCertImagePreview')?.classList.remove('hidden');

    const reader = new FileReader();
    reader.onload = function(e) {
        const imgPreview = document.getElementById('deathCertImage');
        if (imgPreview) {
            imgPreview.src = e.target.result;
        }
    };
    reader.readAsDataURL(file);
});

// Traditional GCash Receipt Upload Handler (similar changes)
document.getElementById('traditionalGcashReceipt')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) {
        hideGcashPreview();
        return;
    }

    // Check if the file is an image
    if (!file.type.match('image.*')) {
        Swal.fire({
            title: 'Invalid File Type',
            text: 'Please upload an image file (JPG, JPEG, PNG).',
            icon: 'error',
            confirmButtonColor: '#d97706'
        });
        this.value = ''; // Clear the file input
        return;
    }
    
    // Update file name display
    const fileName = file.name;
    document.getElementById('traditionalGcashFileName').textContent = fileName.length > 20 ? 
        fileName.substring(0, 17) + '...' : fileName;
    
    // Show preview container
    const previewContainer = document.getElementById('gcashPreviewContainer');
    previewContainer?.classList.remove('hidden');
    
    // Show remove button
    document.getElementById('removeGcash')?.classList.remove('hidden');
    
    // Check file type
    if (file.type === 'application/pdf') {
        // PDF Preview
        document.getElementById('gcashPdfPreview')?.classList.remove('hidden');
        document.getElementById('gcashImagePreview')?.classList.add('hidden');
        
        // Setup PDF viewer button
        document.getElementById('viewGcashPdf').onclick = function() {
            const fileURL = URL.createObjectURL(file);
            window.open(fileURL, '_blank');
        };
    } else {
        // Image Preview
        document.getElementById('gcashImagePreview')?.classList.remove('hidden');
        document.getElementById('gcashPdfPreview')?.classList.add('hidden');
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgPreview = document.getElementById('gcashImage');
            if (imgPreview) {
                imgPreview.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
});

// Remove buttons functionality
document.getElementById('removeDeathCert').addEventListener('click', function() {
    document.getElementById('traditionalDeathCertificate').value = '';
    document.getElementById('traditionalDeathCertFileName').textContent = 'No file chosen';
    hideDeathCertPreview();
});

document.getElementById('removeGcash').addEventListener('click', function() {
    document.getElementById('traditionalGcashReceipt').value = '';
    document.getElementById('traditionalGcashFileName').textContent = 'No file chosen';
    hideGcashPreview();
});

function hideDeathCertPreview() {
    document.getElementById('deathCertPreviewContainer')?.classList.add('hidden');
    document.getElementById('deathCertImagePreview')?.classList.add('hidden');
    document.getElementById('removeDeathCert')?.classList.add('hidden');
    document.getElementById('traditionalDeathCertificate').value = '';
    document.getElementById('traditionalDeathCertFileName').textContent = 'No file chosen';
}

function hideGcashPreview() {
    document.getElementById('gcashPreviewContainer')?.classList.add('hidden');
    document.getElementById('gcashImagePreview')?.classList.add('hidden');
    document.getElementById('removeGcash')?.classList.add('hidden');
    document.getElementById('traditionalGcashReceipt').value = '';
    document.getElementById('traditionalGcashFileName').textContent = 'No file chosen';
}


function removeDeathCert() {
    hideDeathCertPreview();
}

function removeGcash() {
    hideGcashPreview();
}



</script>

<!-- Lifeplan Modal (Hidden by Default) -->
<div id="lifeplanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] md:max-h-[80vh]">
        <!-- Scroll container for both columns -->
        <div class="modal-scroll-container grid grid-cols-1 md:grid-cols-2 overflow-y-auto max-h-[90vh] md:max-h-[80vh]">
            <!-- Left Side: Package Details -->
            <div class="bg-cream p-4 md:p-8 details-section">
                <!-- Header and Close Button for Mobile -->
                <div class="flex justify-between items-center mb-4 md:hidden">
                    <h2 class="text-xl font-hedvig text-navy">Package Details</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Package Image -->
                <div class="mb-4 md:mb-6">
                    <img id="lifeplanPackageImage" src="" alt="" class="w-full h-48 md:h-64 object-cover rounded-lg mb-4">
                </div>

                <!-- Package Header -->
                <div class="flex justify-between items-center mb-4 md:mb-6">
                    <h2 id="lifeplanPackageName" class="text-2xl md:text-3xl font-hedvig text-navy"></h2>
                    <div id="lifeplanPackagePrice" class="text-2xl md:text-3xl font-hedvig text-yellow-600"></div>
                </div>

                <!-- Package Description -->
                <p id="lifeplanPackageDesc" class="text-dark mb-4 md:mb-6 text-sm md:text-base"></p>

                <!-- Main Package Details -->
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-lg md:text-xl font-hedvig text-navy mb-3 md:mb-4">Package Includes:</h3>
                    <ul id="lifeplanPackageFeatures" class="space-y-1 md:space-y-2">
                        <!-- Features will be inserted here by JavaScript -->
                    </ul>
                </div>

                <!-- Add this simple continue button at the bottom of the details section -->
    <div class="mt-6 border-t border-gray-200 pt-4 md:hidden">
        <button id="continueToLifeplanFormBtn" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
            Continue to Booking
        </button>
    </div>

                
            </div>

            <!-- Right Side: Booking Form -->
            <div class="bg-white p-4 md:p-8 border-t md:border-t-0 md:border-l border-gray-200 overflow-y-auto form-section hidden md:block">
                <!-- Header and back button for mobile -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl md:text-2xl font-hedvig text-navy">Book Your Lifeplan</h2>
                    <div class="flex items-center">
                        <button id="backToLifeplanDetailsBtn" class="mr-2 text-gray-500 hover:text-navy md:hidden flex items-center">
                            <i class="fas fa-arrow-left text-lg mr-1"></i>
                            <span class="text-sm">Back</span>
                        </button>
                        <button class="closeModalBtn text-gray-500 hover:text-navy">
                            <i class="fas fa-times text-xl md:text-2xl"></i>
                        </button>
                    </div>
                </div>

                <form id="lifeplanBookingForm" class="space-y-4">
                    <input type="hidden" id="lifeplanSelectedPackageName" name="packageName">
                    <input type="hidden" id="lifeplanSelectedPackagePrice" name="packagePrice">
                    <input type="hidden" id="lifeplanServiceId" name="service_id">
                    <input type="hidden" id="lifeplanBranchId" name="branch_id">
                    <input type="hidden" name="customerID" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                    <input type="hidden" id="holderAddress" name="holderAddress">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Beneficiary Information</h3>
                        
                        <!-- First Name & Middle Name (Side by side) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="lifeplanHolderFirstName" class="block text-sm font-medium text-navy mb-1">First Name <span class="text-red-500">*</label>
                                <input type="text" id="lifeplanHolderFirstName" name="holderFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="lifeplanHolderMiddleName" class="block text-sm font-medium text-navy mb-1">Middle Name</label>
                                <input type="text" id="lifeplanHolderMiddleName" name="holderMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                        </div>
                        
                        <!-- Last Name & Suffix (Side by side) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-3/4 px-2 mb-3 sm:mb-0">
                                <label for="lifeplanHolderLastName" class="block text-sm font-medium text-navy mb-1">Last Name <span class="text-red-500">*</label>
                                <input type="text" id="lifeplanHolderLastName" name="holderLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                            <div class="w-full sm:w-1/4 px-2">
                                <label for="lifeplanHolderSuffix" class="block text-sm font-medium text-navy mb-1">Suffix</label>
                                <select id="lifeplanHolderSuffix" name="holderSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="lifeplanDateOfBirth" class="block text-sm font-medium text-navy mb-1">Date of Birth <span class="text-red-500">*</label>
                                <input type="date" id="lifeplanDateOfBirth" name="dateOfBirth" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="lifeplanContactNumber" class="block text-sm font-medium text-navy mb-1">Contact Number <span class="text-red-500">*</label>
                                <input type="tel" id="lifeplanContactNumber" name="contactNumber" required 
       pattern="09[0-9]{9}" 
       title="Please enter a valid Philippine mobile number starting with 09 (11 digits total)"
       class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="relationshipWithBeneficiary" class="block text-sm font-medium text-navy mb-1">
                                Relationship with the Beneficiary <span class="text-red-500">*
                            </label>
                            <input type="text" id="relationshipWithBeneficiary" name="relationshipWithBeneficiary" required
                                title="Please enter the relationship with the beneficiary"
                                class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                        </div>
                        
                        <!-- Address (Improved UI with dropdowns in specified layout) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="lifeplanHolderRegion" class="block text-sm font-medium text-navy mb-1">Region <span class="text-red-500">*</span></label>
                                <select id="lifeplanHolderRegion" name="holderRegion" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" required>
                                    <option value="">Select Region</option>
                                    <!-- Regions will be populated by JavaScript -->
                                </select>
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="lifeplanHolderProvince" class="block text-sm font-medium text-navy mb-1">Province <span class="text-red-500">*</span></label>
                                <select id="lifeplanHolderProvince" name="holderProvince" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" disabled required>
                                    <option value="">Select Province</option>
                                    <!-- Provinces will be populated by JavaScript based on selected region -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="lifeplanHolderCity" class="block text-sm font-medium text-navy mb-1">City/Municipality <span class="text-red-500">*</span></label>
                                <select id="lifeplanHolderCity" name="holderCity" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" disabled required>
                                    <option value="">Select City/Municipality</option>
                                    <!-- Cities will be populated by JavaScript based on selected province -->
                                </select>
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="lifeplanHolderBarangay" class="block text-sm font-medium text-navy mb-1">Barangay <span class="text-red-500">*</span></label>
                                <select id="lifeplanHolderBarangay" name="holderBarangay" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" disabled required>
                                    <option value="">Select Barangay</option>
                                    <!-- Barangays will be populated by JavaScript based on selected city -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label for="lifeplanHolderStreet" class="block text-sm font-medium text-navy mb-2">Street/Block/House Number <span class="text-red-500">*</span></label>
                            <input type="text" id="lifeplanHolderStreet" name="holderStreet" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="e.g. 123 Main Street">
                        </div>
                    </div>

                    <!-- Co-Maker Information Section -->
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Co-Maker Information</h3>
                        
                        <!-- First Name & Middle Name (Side by side) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="comakerFirstName" class="block text-sm font-medium text-navy mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" id="comakerFirstName" name="comakerFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="comakerMiddleName" class="block text-sm font-medium text-navy mb-1">Middle Name</label>
                                <input type="text" id="comakerMiddleName" name="comakerMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                        </div>
                        
                        <!-- Last Name & Suffix (Side by side) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-3/4 px-2 mb-3 sm:mb-0">
                                <label for="comakerLastName" class="block text-sm font-medium text-navy mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" id="comakerLastName" name="comakerLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                            <div class="w-full sm:w-1/4 px-2">
                                <label for="comakerSuffix" class="block text-sm font-medium text-navy mb-1">Suffix</label>
                                <select id="comakerSuffix" name="comakerSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comakerOccupation" class="block text-sm font-medium text-navy mb-1">Occupation <span class="text-red-500">*</span></label>
                            <input type="text" id="comakerOccupation" name="comakerOccupation" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="e.g. Teacher, Engineer, etc.">
                        </div>
                        
                        <!-- Co-Maker Address (Same structure as beneficiary address) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="comakerRegion" class="block text-sm font-medium text-navy mb-1">Region <span class="text-red-500">*</span></label>
                                <select id="comakerRegion" name="comakerRegion" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" required>
                                    <option value="">Select Region</option>
                                    <!-- Regions will be populated by JavaScript -->
                                </select>
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="comakerProvince" class="block text-sm font-medium text-navy mb-1">Province <span class="text-red-500">*</span></label>
                                <select id="comakerProvince" name="comakerProvince" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" disabled required>
                                    <option value="">Select Province</option>
                                    <!-- Provinces will be populated by JavaScript based on selected region -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="comakerCity" class="block text-sm font-medium text-navy mb-1">City/Municipality <span class="text-red-500">*</span></label>
                                <select id="comakerCity" name="comakerCity" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" disabled required>
                                    <option value="">Select City/Municipality</option>
                                    <!-- Cities will be populated by JavaScript based on selected province -->
                                </select>
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="comakerBarangay" class="block text-sm font-medium text-navy mb-1">Barangay <span class="text-red-500">*</span></label>
                                <select id="comakerBarangay" name="comakerBarangay" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" disabled required>
                                    <option value="">Select Barangay</option>
                                    <!-- Barangays will be populated by JavaScript based on selected city -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label for="comakerStreet" class="block text-sm font-medium text-navy mb-2">Street/Block/House Number <span class="text-red-500">*</span></label>
                            <input type="text" id="comakerStreet" name="comakerStreet" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="e.g. 123 Main Street">
                        </div>
                        
                        <!-- Co-Maker ID Information -->
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-navy mb-3">ID Information</h4>
                            
                            <div class="mb-3">
                                <label for="comakerIdType" class="block text-sm font-medium text-navy mb-1">ID Type <span class="text-red-500">*</span></label>
                                <select id="comakerIdType" name="comakerIdType" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">Select ID Type</option>
                                    <option value="Passport">Passport</option>
                                    <option value="Driver's License">Driver's License</option>
                                    <option value="SSS ID">SSS ID</option>
                                    <option value="GSIS ID">GSIS ID</option>
                                    <option value="PhilHealth ID">PhilHealth ID</option>
                                    <option value="TIN ID">TIN ID</option>
                                    <option value="Postal ID">Postal ID</option>
                                    <option value="Voter's ID">Voter's ID</option>
                                    <option value="PRC ID">PRC ID</option>
                                    <option value="UMID">Unified Multi-Purpose ID (UMID)</option>
                                    <option value="Senior Citizen ID">Senior Citizen ID</option>
                                    <option value="Company ID">Company ID</option>
                                    <option value="School ID">School ID</option>
                                    <option value="Other">Other Government-Issued ID</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="comakerIdNumber" class="block text-sm font-medium text-navy mb-1">ID Number <span class="text-red-500">*</span></label>
                                <input type="text" id="comakerIdNumber" name="comakerIdNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="Enter ID Number">
                            </div>
                            
                            <div class="mb-3">
                                <label for="comakerIdImage" class="block text-sm font-medium text-navy mb-1">Upload ID Image <span class="text-red-500">*</span></label>
                                <div class="border border-input-border bg-white rounded-lg p-3 focus-within:ring-2 focus-within:ring-yellow-600">
                                    <!-- Upload Button and File Name -->
                                    <div class="flex items-center mb-2">
                                        <label for="comakerIdImage" class="flex-1 cursor-pointer">
                                            <div class="flex items-center justify-center py-2 px-3 bg-gray-50 rounded hover:bg-gray-100 transition">
                                                <i class="fas fa-id-card mr-2 text-blue-500"></i>
                                                <span class="text-sm text-gray-600">Upload ID Image</span>
                                            </div>
                                        </label>
                                        <span class="text-xs ml-2 text-gray-500" id="comakerIdFileName">No file chosen</span>
                                    </div>
                                    
                                    <!-- Preview Container -->
                                    <div id="comakerIdPreviewContainer" class="hidden mt-2 rounded-lg overflow-hidden border border-gray-200">
                                        <!-- Image Preview -->
                                        <div id="comakerIdImagePreview" class="hidden">
                                            <img id="comakerIdImagePreviews" src="" alt="ID Preview" class="w-full h-auto max-h-48 object-contain">
                                        </div>
                                    </div>
                                    
                                    <!-- Remove Button -->
                                    <button type="button" id="removeComakerId" class="text-xs text-red-600 hover:text-red-800 mt-2 hidden">
                                        <i class="fas fa-trash-alt mr-1"></i> Remove file
                                    </button>
                                    
                                    <input type="file" id="comakerIdImage" name="comakerIdImage" accept=".jpg,.jpeg,.png" class="hidden" required>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Additional Services</h3>
                        
                        <div class="mb-3">
                            <div class="flex items-center">
                                <input type="checkbox" id="cremationOption" name="cremationOption" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                <label for="cremationOption" class="ml-2 block text-sm text-navy">
                                    Include Cremation Services (+₱40,000)
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 ml-6">Select this option to include cremation services and an urn in your Lifeplan package. This adds ₱40,000 to the total price.</p>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Payment Plan</h3>

                        <div class="mb-3 md:mb-4">
                            <label class="block text-sm font-medium text-navy mb-1">Payment Term:</label>
                            <!-- Displayed read-only input -->
                            <input type="text" value="5 Years (60 Monthly Payments)" readonly
                                class="w-full px-3 py-2 border border-input-border rounded-lg bg-gray-100 text-gray-700 cursor-not-allowed focus:outline-none">

                            
                            <input type="hidden" id="lifeplanPaymentTerm" name="paymentTerm" value="5">
                        </div>


                        <!-- QR Code Button and Modal -->
                        <div class="mb-4">
                            <button type="button" id="lifeplanShowQrCodeBtn" class="w-full bg-navy hover:bg-navy-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-all duration-200">
                                <i class="fas fa-qrcode mr-2"></i>
                                <span>View GCash QR Code</span>
                            </button>
                        </div>
                        
                        <!-- QR Code Modal -->
                        <div id="lifeplanQrCodeModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                            <div class="bg-white rounded-lg p-4 sm:p-6 max-w-[90vw] sm:max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg sm:text-xl font-hedvig text-navy">Scan to Pay</h3>
                                    <button id="lifeplanCloseQrModal" class="text-gray-500 hover:text-navy">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <div class="flex flex-col items-center justify-center">
                                    <?php if (!empty($gcash_qrs)): ?>
                                        <div id="lifeplanGcashQrContainer" class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                                            <?php foreach ($gcash_qrs as $qr): ?>
                                                <div class="gcash-qr-option cursor-pointer p-2 border border-gray-200 rounded-lg hover:border-yellow-600 transition-colors flex justify-center items-center"
                                                     data-qr-number="<?= htmlspecialchars($qr['qr_number']) ?>">
                                                    <div class="w-48 h-32 sm:w-64 sm:h-40">
                                                        <img src="<?= htmlspecialchars($qr['qr_image']) ?>" 
                                                             alt="GCash QR Code <?= htmlspecialchars($qr['qr_number']) ?>" 
                                                             class="w-full h-full object-contain landscape-img"
                                                             onclick="enlargeQrCode(this)">
                                                        <p class="text-center text-xs sm:text-sm font-medium text-gray-600 mt-2"><?= htmlspecialchars($qr['qr_number']) ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" id="lifeplanSelectedGcashQr" name="gcashQrNumber" value="">
                                    <?php else: ?>
                                        <p class="text-center text-sm text-gray-500">No GCash QR codes available</p>
                                    <?php endif; ?>
                                    <p class="text-center text-sm text-gray-600 mt-4 mb-2">Scan a QR code with your GCash app to make payment</p>
                                    <p class="text-center font-bold text-yellow-600" id="lifeplanQrCodeAmount">Amount: ₱0</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GCash Upload with Preview (Improved UI) -->
                        <div class="mb-4">
                            <label for="lifeplanGcashReceipt" class="block text-sm font-medium text-navy mb-1">First Payment Receipt <span class="text-red-500">*</label>
                            <div class="border border-input-border bg-white rounded-lg p-3 focus-within:ring-2 focus-within:ring-yellow-600">
                                <!-- Upload Button and File Name -->
                                <div class="flex items-center mb-2">
                                    <label for="lifeplanGcashReceipt" class="flex-1 cursor-pointer">
                                        <div class="flex items-center justify-center py-2 px-3 bg-gray-50 rounded hover:bg-gray-100 transition">
                                            <i class="fas fa-receipt mr-2 text-blue-500"></i>
                                            <span class="text-sm text-gray-600">Upload Receipt</span>
                                        </div>
                                    </label>
                                    <span class="text-xs ml-2 text-gray-500" id="lifeplanGcashFileName">No file chosen</span>
                                </div>
                                
                                <!-- Preview Container -->
                                <div id="lifeplanGcashPreviewContainer" class="hidden mt-2 rounded-lg overflow-hidden border border-gray-200">
                                    <!-- Image Preview -->
                                    <div id="lifeplanGcashImagePreview" class="hidden">
                                        <img id="lifeplanGcashImage" src="" alt="GCash Receipt Preview" class="w-full h-auto max-h-48 object-contain">
                                    </div>
                                    
                                </div>
                                
                                <!-- Remove Button -->
                                <button type="button" id="removeLifeplanGcash" class="text-xs text-red-600 hover:text-red-800 mt-2 hidden">
                                    <i class="fas fa-trash-alt mr-1"></i> Remove file
                                </button>
                                
                                <input type="file" id="lifeplanGcashReceipt" name="gcashReceipt" accept=".jpg,.jpeg,.png" class="hidden">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG</p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="lifeplanReferenceNumber" class="block text-sm font-medium text-navy mb-1">Reference Number <span class="text-red-500">*</label>
                            <input type="text" id="lifeplanReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="e.g. 1234567890">
                        </div>
                    </div>

                    <div class="bg-cream p-3 md:p-4 rounded-lg">
                        <div class="flex justify-between text-xs md:text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="lifeplanTotalPrice" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between text-xs md:text-sm mb-2">
                            <span class="text-navy">Payment Term</span>
                            <span id="lifeplanPaymentTermDisplay" class="text-yellow-600">5 Years (60 Monthly Payments)</span>
                        </div>
                        <div class="flex justify-between font-bold mt-2 pt-2 border-t border-gray-300">
                            <span class="text-navy">Monthly Payment</span>
                            <span id="lifeplanMonthlyPayment" class="text-yellow-600">₱0</span>
                        </div>
                    </div>

                    <!-- Privacy Policy and Terms Consent -->
                    <div class="mt-4 mb-4 border border-gray-200 rounded-lg p-4 bg-gray-50 terms-checkbox-container">
                        <div class="flex items-start">
                            <input type="checkbox" id="termsCheckbox" name="terms_accepted" required 
                                class="h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1">
                            <label for="termsCheckbox" class="ml-3 text-sm">
                                <span class="block text-navy mb-1">I have read and agree to the <a href="#" class="text-yellow-600 hover:underline" id="viewPrivacyPolicy">Privacy Policy</a>, <a href="#" class="text-yellow-600 hover:underline" id="viewTermsOfService">Terms of Service</a>, and <a href="lifeplancontract.php" target="_blank" rel="noopener noreferrer" class="text-yellow-600 hover:underline">LifePlan Contract</a>. <a href="lifeplancontract_pdf.php" target="_blank" rel="noopener noreferrer" class="text-yellow-600 hover:underline">(Download PDF Version)</a> <span class="text-red-500">*</span></span>
                                <span class="block text-gray-500 text-xs">By checking this box, you acknowledge that you have read and understood all terms and conditions, and consent to our data collection practices as described in our Privacy Policy.</span>
                            </label>
                        </div>
                    </div>

                    <!-- Privacy Policy Modal -->
                    <div id="privacyPolicyModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                        <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-hedvig text-navy">Privacy Policy</h3>
                                <button id="closePrivacyModal" class="text-gray-500 hover:text-navy">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            <div class="text-sm text-gray-700 space-y-4">
                                <p>At GrievEase, we understand that privacy is of utmost importance, especially during times of grief and loss. This Privacy Policy outlines how we collect, use, protect, and share information gathered through our website and services. We are committed to ensuring the privacy and security of all personal information entrusted to us.</p>
                                <p>Last Updated: March 22, 2025</p>

                                <h4 class="font-medium text-navy">Information We Collect</h4>
                                <p>We collect only what is necessary to provide our services with dignity and respect.</p>
                                <h5 class="font-medium">Personal Information</h5>
                                <p>We may collect the following personal information when you use our website, contact us, or arrange for our services:</p>
                                <ul class="list-disc ml-5 space-y-1">
                                    <li>Full name and contact information (email, phone number, address)</li>
                                    <li>Information about the deceased required for documentation</li>
                                    <li>Payment information for service arrangements</li>
                                </ul>

                                <h4 class="font-medium text-navy">How We Use Your Information</h4>
                                <p>We use the information we collect for the following purposes:</p>
                                <ul class="list-disc ml-5 space-y-1">
                                    <li><strong>Providing Services:</strong> To arrange and conduct funeral services according to your wishes and requirements.</li>
                                    <li><strong>Communication:</strong> To respond to your inquiries, provide information, and offer support throughout the process.</li>
                                    <li><strong>Legal Requirements:</strong> To complete necessary documentation and comply with legal obligations related to funeral services.</li>
                                </ul>

                                <h4 class="font-medium text-navy">Information Sharing</h4>
                                <p>We treat your information with the same respect and dignity as we treat your loved ones.</p>
                                <p>GrievEase is committed to maintaining your privacy. We do not sell, rent, or trade your personal information to third parties for marketing purposes. We may share information in the following limited circumstances:</p>
                                <ul class="list-disc ml-5 space-y-1">
                                    <li><strong>Service Partners:</strong> With trusted partners who assist us in providing funeral services when necessary to fulfill your service requests.</li>
                                    <li><strong>Legal Requirements:</strong> When required by law, such as to comply with a subpoena, court order, or similar legal procedure.</li>
                                    <li><strong>Protection:</strong> When we believe in good faith that disclosure is necessary to protect our rights, protect your safety or the safety of others, or investigate fraud.</li>
                                </ul>

                                <h4 class="font-medium text-navy">Security Measures</h4>
                                <p>We implement comprehensive security measures to protect your personal information:</p>
                                <ul class="list-disc ml-5 space-y-1">
                                    <li>Encryption of sensitive data during transmission and storage</li>
                                    <li>Regular security assessments and updates</li>
                                    <li>Limited access to personal information on a need-to-know basis</li>
                                    <li>Secure disposal of information when no longer needed</li>
                                </ul>

                                <h4 class="font-medium text-navy">Your Rights</h4>
                                <p>You have the right to:</p>
                                <ul class="list-disc ml-5 space-y-1">
                                    <li>Access and review your personal information</li>
                                    <li>Request corrections to inaccurate information</li>
                                    <li>Request deletion of your information (subject to legal requirements)</li>
                                    <li>Opt-out of certain communications</li>
                                </ul>

                                <h4 class="font-medium text-navy">Contact Us</h4>
                                <p>If you have any questions about this Privacy Policy or our data practices, please contact us at:</p>
                                <p>Email: privacy@grievease.com<br>
                                Phone: +63 912 345 6789<br>
                                Address: 123 Funeral Services Ave, Metro Manila, Philippines</p>
                            </div>
                        </div>
                    </div>

                    <!-- Terms of Service Modal -->
                    <div id="termsOfServiceModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                        <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-hedvig text-navy">Terms of Service</h3>
                                <button id="closeTermsModal" class="text-gray-500 hover:text-navy">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            <div class="text-sm text-gray-700 space-y-4">
                                <p>Welcome to GrievEase. These Terms of Service govern your use of our website and services. By using our services, you agree to these terms.</p>
                                <p>Last Updated: March 22, 2025</p>

                                <h4 class="font-medium text-navy">Service Description</h4>
                                <p>GrievEase provides funeral and memorial services, including traditional funeral arrangements, cremation services, and life plan packages.</p>

                                <h4 class="font-medium text-navy">Booking and Payment</h4>
                                <ul class="list-disc ml-5 space-y-1">
                                    <li>All bookings are subject to availability and confirmation</li>
                                    <li>Payment terms vary by service type and will be clearly communicated</li>
                                    <li>Life plan packages require monthly payments over 5 years</li>
                                    <li>Traditional services require 30% downpayment with balance due before service</li>
                                </ul>

                                <h4 class="font-medium text-navy">Cancellation Policy</h4>
                                <p>Cancellations must be made in writing and are subject to the following terms:</p>
                                <ul class="list-disc ml-5 space-y-1">
                                    <li>Life plan packages: 30-day notice required for cancellation</li>
                                    <li>Traditional services: 48-hour notice required for full refund</li>
                                    <li>Administrative fees may apply</li>
                                </ul>

                                <h4 class="font-medium text-navy">Limitation of Liability</h4>
                                <p>GrievEase strives to provide the highest quality services, but we cannot guarantee that our services will be uninterrupted or error-free. Our liability is limited to the amount paid for the specific service.</p>

                                <h4 class="font-medium text-navy">Governing Law</h4>
                                <p>These terms are governed by the laws of the Philippines. Any disputes will be resolved in the courts of Metro Manila.</p>

                                <h4 class="font-medium text-navy">Contact Information</h4>
                                <p>For questions about these terms, please contact us at:</p>
                                <p>Email: legal@grievease.com<br>
                                Phone: +63 912 345 6789</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                        Confirm Lifeplan Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add this script at the end -->
<script>
let originalPackages = [];

document.addEventListener('DOMContentLoaded', function() {
    // Collect original packages from the DOM
    const packageCards = document.querySelectorAll('.package-card');
    originalPackages = Array.from(packageCards).map(card => ({
        name: card.dataset.name,
        price: parseFloat(card.dataset.price),
        service: card.dataset.service,
        image: card.dataset.image,
        icon: card.querySelector('.fa-')?.className.match(/fa-(.+?)( |$)/)[1] || 'box',
        description: card.querySelector('p').textContent,
        features: Array.from(card.querySelectorAll('ul li')).map(li => li.textContent.trim())
    }));

    // Initialize sorting and filtering
    initializeSortingAndFiltering();

    // Rest of your existing DOMContentLoaded code...
});

document.addEventListener('DOMContentLoaded', function() {
    // Set max date for lifeplan date of birth to today
    const today = new Date();
    const todayFormatted = today.toISOString().split('T')[0];
    document.getElementById('lifeplanDateOfBirth').max = todayFormatted;
    
    // Contact number validation - only numbers
    // Contact number validation - Philippine mobile number starting with 09
    const contactNumberInput = document.getElementById('lifeplanContactNumber');
        if (contactNumberInput) {
    contactNumberInput.addEventListener('input', function() {
        // Remove any non-digit characters
        this.value = this.value.replace(/\D/g, '');
        
        // Ensure it starts with 09 and has exactly 11 digits
        if (this.value.length > 0 && !this.value.startsWith('09')) {
            this.value = '09' + this.value.slice(2);
        }
        
        // Limit to 11 characters
        if (this.value.length > 11) {
            this.value = this.value.slice(0, 11);
        }
    });
    
    contactNumberInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const cleanedText = pastedText.replace(/\D/g, ''); // Remove non-digits
        
        // Ensure it starts with 09
        let finalText = cleanedText;
        if (cleanedText.length > 0 && !cleanedText.startsWith('09')) {
            finalText = '09' + cleanedText.slice(2);
        }
        
        // Limit to 11 characters
        document.execCommand('insertText', false, finalText.slice(0, 11));
    });
    
    // Add blur validation
    contactNumberInput.addEventListener('blur', function() {
        if (this.value.length !== 11 || !this.value.startsWith('09')) {
            this.setCustomValidity('Please enter a valid 11-digit Philippine mobile number starting with 09');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Set pattern attribute for HTML5 validation
    contactNumberInput.pattern = "09[0-9]{9}";
    contactNumberInput.title = "Please enter a valid Philippine mobile number starting with 09 (11 digits total)";
    }
    
    
    
    // Add pattern validation for contact number (optional)
    if (contactNumberInput) {
        contactNumberInput.pattern = '\\d*'; // Only digits allowed
        contactNumberInput.title = 'Please enter numbers only (no spaces or symbols)';
    }
});
// Lifeplan GCash Receipt Upload Preview
// Lifeplan GCash Receipt Upload Handler (similar changes)
document.getElementById('lifeplanGcashReceipt')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) {
        hideLifeplanGcashPreview();
        return;
    }

    // Check if the file is an image
    if (!file.type.match('image.*')) {
        Swal.fire({
            title: 'Invalid File Type',
            text: 'Please upload an image file (JPG, JPEG, PNG).',
            icon: 'error',
            confirmButtonColor: '#d97706'
        });
        this.value = ''; // Clear the file input
        return;
    }
    
    // Update file name display
    const fileName = file.name;
    document.getElementById('lifeplanGcashFileName').textContent = fileName.length > 20 ? 
        fileName.substring(0, 17) + '...' : fileName;
    
    // Show preview container
    const previewContainer = document.getElementById('lifeplanGcashPreviewContainer');
    previewContainer?.classList.remove('hidden');
    
    // Show remove button
    document.getElementById('removeLifeplanGcash')?.classList.remove('hidden');
    
    // Check file type
    if (file.type === 'application/pdf') {
        // PDF Preview
        document.getElementById('lifeplanGcashPdfPreview')?.classList.remove('hidden');
        document.getElementById('lifeplanGcashImagePreview')?.classList.add('hidden');
        
        // Setup PDF viewer button
        document.getElementById('viewLifeplanGcashPdf').onclick = function() {
            const fileURL = URL.createObjectURL(file);
            window.open(fileURL, '_blank');
        };
    } else {
        // Image Preview
        document.getElementById('lifeplanGcashImagePreview')?.classList.remove('hidden');
        document.getElementById('lifeplanGcashPdfPreview')?.classList.add('hidden');
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgPreview = document.getElementById('lifeplanGcashImage');
            if (imgPreview) {
                imgPreview.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
});



// Remove button functionality for Lifeplan
document.getElementById('removeLifeplanGcash').addEventListener('click', function() {
    document.getElementById('lifeplanGcashReceipt').value = '';
    document.getElementById('lifeplanGcashFileName').textContent = 'No file chosen';
    hideLifeplanGcashPreview();
});


// Mobile view navigation
document.getElementById('continueToLifeplanFormBtn').addEventListener('click', function() {
    // Hide details section and show form section on mobile
    document.querySelector('#lifeplanModal .details-section').classList.add('hidden');
    document.querySelector('#lifeplanModal .form-section').classList.remove('hidden');
});

document.getElementById('backToLifeplanDetailsBtn').addEventListener('click', function() {
    // Hide form section and show details section on mobile
    document.querySelector('#lifeplanModal .form-section').classList.add('hidden');
    document.querySelector('#lifeplanModal .details-section').classList.remove('hidden');
});
</script>

<script src="../tailwind.js"></script>
<script src="customer_support.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const packagesFromDB = <?php echo json_encode($packages); ?>;
    
    // Mobile form toggle functionality
    const continueBtn = document.getElementById('continueToFormBtn');
    const backBtn = document.getElementById('backToDetailsBtn');
    const detailsSection = document.querySelector('.details-section');
    const formSection = document.querySelector('.form-section');
    const continueToLifeplanFormBtn = document.getElementById('continueToLifeplanFormBtn');
    const backToLifeplanDetailsBtn = document.getElementById('backToLifeplanDetailsBtn');
    const lifeplanDetailsSection = document.querySelector('#lifeplanModal .details-section');
    const lifeplanFormSection = document.querySelector('#lifeplanModal .form-section');
    
    // Update your existing continue/back button handlers
    if (continueBtn && backBtn && detailsSection && formSection) {
    continueBtn.addEventListener('click', function() {
        detailsSection.classList.add('hidden');
        formSection.classList.remove('hidden');
        // Force show on mobile when navigating to form
        formSection.classList.add('force-show');
    });
    
    backBtn.addEventListener('click', function() {
        formSection.classList.add('hidden');
        formSection.classList.remove('force-show');
        detailsSection.classList.remove('hidden');
    });
    }

    // Update your existing Lifeplan button handlers
    if (continueToLifeplanFormBtn && backToLifeplanDetailsBtn && lifeplanDetailsSection && lifeplanFormSection) {
        continueToLifeplanFormBtn.addEventListener('click', function() {
            lifeplanDetailsSection.classList.add('hidden');
            lifeplanFormSection.classList.remove('hidden');
            // Force show on mobile when navigating to form
            lifeplanFormSection.classList.add('force-show');
        });
        
        backToLifeplanDetailsBtn.addEventListener('click', function() {
            lifeplanFormSection.classList.add('hidden');
            lifeplanFormSection.classList.remove('force-show');
            lifeplanDetailsSection.classList.remove('hidden');
        });
    }
    
    // Update your close modal handler
    document.querySelectorAll('.closeModalBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        const traditionalModal = document.getElementById('traditionalModal');
        const lifeplanModal = document.getElementById('lifeplanModal');
        
        if (!traditionalModal.classList.contains('hidden')) {
            traditionalModal.classList.add('hidden');
            // Reset to show details when modal is reopened
            document.querySelector('#traditionalModal .details-section').classList.remove('hidden');
            document.querySelector('#traditionalModal .form-section').classList.add('hidden');
            document.querySelector('#traditionalModal .form-section').classList.remove('force-show');
        }
        
        if (!lifeplanModal.classList.contains('hidden')) {
            lifeplanModal.classList.add('hidden');
            // Reset to show details when modal is reopened
            document.querySelector('#lifeplanModal .details-section').classList.remove('hidden');
            document.querySelector('#lifeplanModal .form-section').classList.add('hidden');
            document.querySelector('#lifeplanModal .form-section').classList.remove('force-show');
        }
    });
});

    // Package selection functionality
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('selectPackageBtn')) {
            const packageCard = event.target.closest('.package-card');
            if (!packageCard) return;
            
            const packageName = packageCard.dataset.name;
            const packagePrice = packageCard.dataset.price;
            const packageImage = packageCard.dataset.image || '';
            const serviceType = packageCard.dataset.service;

            sessionStorage.setItem('selectedPackageName', packageName);
            sessionStorage.setItem('selectedPackagePrice', packagePrice);
            sessionStorage.setItem('selectedPackageImage', packageImage);
            sessionStorage.setItem('selectedServiceType', serviceType);
            
            const features = Array.from(packageCard.querySelectorAll('ul li')).map(li => li.textContent.trim());
            sessionStorage.setItem('selectedPackageFeatures', JSON.stringify(features));
            
            const traditionalBtn = document.getElementById('traditionalServiceBtn');
            traditionalBtn.innerHTML = `
                <i class="fas fa-dove text-3xl text-yellow-600 mb-2"></i>
                <span class="font-hedvig text-lg">Traditional</span>
                <span class="text-sm text-gray-600 mt-2 text-center">For immediate funeral needs</span>
            `;
            
            document.getElementById('serviceTypeModal').classList.remove('hidden');
        }
    });

    // Traditional Service button click event
    document.getElementById('traditionalServiceBtn').addEventListener('click', function() {
        // Hide service type modal
        document.getElementById('serviceTypeModal').classList.add('hidden');
        
        // Open traditional modal
        openTraditionalModal();
    });

    document.getElementById('traditionalWithCremate').addEventListener('change', function() {
        // Get the current package price (assuming it's stored in a variable or element)
        // This will depend on how your package price is calculated
        const packagePriceElement = document.getElementById('traditionalTotalPrice');
        let packagePrice = parseInt(packagePriceElement.textContent.replace('₱', '').replace(/,/g, '') || 0);
        
        // If checkbox is checked, add 40000, otherwise subtract 40000
        if (this.checked) {
            packagePrice += 40000;
        } else {
            packagePrice -= 40000;
            // Ensure price doesn't go negative
            if (packagePrice < 0) packagePrice = 0;
        }
        
        // Update the package total
        packagePriceElement.textContent = `₱${packagePrice.toLocaleString()}`;
        
        // Calculate and update downpayment (30%)
        const downpayment = Math.floor(packagePrice * 0.3);
        document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        document.getElementById('traditionalSelectedPackagePrice').value = parseFloat(packagePrice) || 0;
        
        // Update amount due (same as downpayment)
        document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;
    });

    // Traditional Booking Form submission
    document.getElementById('traditionalBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const formElement = this;

        Swal.fire({
            title: 'Confirm Booking',
            text: 'Are you sure you want to proceed with this booking?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, book now',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading indicator
                Swal.fire({
                    title: 'Processing Booking',
                    html: 'Please wait while we process your booking...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch('booking/booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    
                    if (data.success) {
                        // Close modal and reset form
                        document.getElementById('traditionalModal').classList.add('hidden');
                        formElement.reset();
                        
                        // Reset to show details section for next time
                        detailsSection.classList.remove('hidden');
                        formSection.classList.add('hidden');
                        
                        // Show notification
                        showNotification('New Booking', 'Your booking has been confirmed. Click to view details.', 'notification.php');
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'An error occurred. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#d97706'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#d97706'
                    });
                });
            }
        });
    });

    // Function to show notifications
    function showNotification(title, message, redirectUrl) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-50 w-80 bg-white rounded-lg shadow-lg overflow-hidden border-l-4 border-yellow-600 transform transition-all duration-300 hover:scale-105 cursor-pointer';
        notification.innerHTML = `
            <div class="p-4" onclick="window.location.href='${redirectUrl}'">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-bell text-yellow-600 text-xl mt-1"></i>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900">${title}</p>
                        <p class="mt-1 text-sm text-gray-500">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none" onclick="event.stopPropagation(); this.parentNode.parentNode.parentNode.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('opacity-0');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
        
        notification.querySelector('button').addEventListener('click', function(e) {
            e.stopPropagation();
            notification.remove();
        });
    }

    // Traditional addon checkbox event handling
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateTraditionalTotal();
        });
    });

    // Function to update traditional total price when addons are selected
    function updateTraditionalTotal() {
        const basePrice = parseInt(sessionStorage.getItem('selectedPackagePrice') || '0');
        let addonTotal = 0;
        
        document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
            addonTotal += parseInt(checkbox.value);
        });
        
        const totalPrice = basePrice + addonTotal;
        const downpayment = Math.ceil(totalPrice * 0.3);
        
        document.getElementById('traditionalTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;
        
        // Update mobile view totals as well
        document.getElementById('traditionalTotalPriceMobile').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('traditionalAmountDueMobile').textContent = `₱${downpayment.toLocaleString()}`;
        
        document.getElementById('traditionalSelectedPackagePrice').value = totalPrice;
    }

    function openTraditionalModal() {
    const packageName = sessionStorage.getItem('selectedPackageName');
    const packagePrice = sessionStorage.getItem('selectedPackagePrice');
    const packageImage = sessionStorage.getItem('selectedPackageImage');
    const packageFeatures = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
    
    const selectedPackage = packagesFromDB.find(pkg => pkg.name === packageName);
    
    document.querySelector('#traditionalModal .font-hedvig.text-2xl.text-navy').textContent = 'Book Your Package';
    
    document.getElementById('traditionalPackageName').textContent = packageName;
    document.getElementById('traditionalPackagePrice').textContent = `₱${parseInt(packagePrice).toLocaleString()}`;
    
    if (packageImage) {
        document.getElementById('traditionalPackageImage').src = packageImage;
        document.getElementById('traditionalPackageImage').alt = packageName;
    }
    
    const totalPrice = parseInt(packagePrice);
    const downpayment = Math.ceil(totalPrice * 0.3);
    
    document.getElementById('traditionalTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
    document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
    document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;

    // Update mobile view totals
    document.getElementById('traditionalTotalPriceMobile').textContent = `₱${totalPrice.toLocaleString()}`;
    document.getElementById('traditionalAmountDueMobile').textContent = `₱${downpayment.toLocaleString()}`;

    const featuresList = document.getElementById('traditionalPackageFeatures');
    featuresList.innerHTML = '';
    packageFeatures.forEach(feature => {
        featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
    });
    
    document.getElementById('traditionalSelectedPackagePrice').value = totalPrice;
    document.getElementById('traditionalServiceId').value = selectedPackage.id;
    document.getElementById('traditionalBranchId').value = <?php echo $branch_id; ?>;
    
    // Reset file upload previews
    hideDeathCertPreview();
    hideGcashPreview();
    
    // Reset form fields
    document.getElementById('traditionalBookingForm').reset();
    
    // Re-attach event listeners for file uploads
    const deathCertInput = document.getElementById('traditionalDeathCertificate');
    const gcashInput = document.getElementById('traditionalGcashReceipt');
    
    if (deathCertInput) {
        deathCertInput.removeEventListener('change', handleDeathCertUpload);
        deathCertInput.addEventListener('change', handleDeathCertUpload);
    }
    
    if (gcashInput) {
        gcashInput.removeEventListener('change', handleGcashUpload);
        gcashInput.addEventListener('change', handleGcashUpload);
    }
    
    // Re-attach remove button listeners
    const removeDeathCertBtn = document.getElementById('removeDeathCert');
    const removeGcashBtn = document.getElementById('removeGcash');
    
    if (removeDeathCertBtn) {
        removeDeathCertBtn.removeEventListener('click', removeDeathCert);
        removeDeathCertBtn.addEventListener('click', removeDeathCert);
    }
    
    if (removeGcashBtn) {
        removeGcashBtn.removeEventListener('click', removeGcash);
        removeGcashBtn.addEventListener('click', removeGcash);
    }
    
    // Reset form section visibility
    const detailsSection = document.querySelector('#traditionalModal .details-section');
    const formSection = document.querySelector('#traditionalModal .form-section');
    
    detailsSection.classList.remove('hidden');
    formSection.classList.add('hidden');
    formSection.classList.remove('force-show');
    
    // Show the modal
    document.getElementById('traditionalModal').classList.remove('hidden');
    
    // Initialize address fields
    initializeAddressFields();
}

// Helper functions for file uploads
function handleDeathCertUpload() {
    const file = this.files[0];
    if (!file) {
        hideDeathCertPreview();
        return;
    }
    
    // Update file name display
    const fileName = file.name;
    document.getElementById('traditionalDeathCertFileName').textContent = fileName.length > 20 ? 
        fileName.substring(0, 17) + '...' : fileName;
    
    // Show preview container
    const previewContainer = document.getElementById('deathCertPreviewContainer');
    if (previewContainer) previewContainer.classList.remove('hidden');
    
    // Show remove button
    const removeBtn = document.getElementById('removeDeathCert');
    if (removeBtn) removeBtn.classList.remove('hidden');
    
    // Check file type
    if (file.type === 'application/pdf') {
        // PDF Preview
        const pdfPreview = document.getElementById('deathCertPdfPreview');
        const imgPreview = document.getElementById('deathCertImagePreview');
        if (pdfPreview) pdfPreview.classList.remove('hidden');
        if (imgPreview) imgPreview.classList.add('hidden');
        
        // Setup PDF viewer button
        const viewPdfBtn = document.getElementById('viewDeathCertPdf');
        if (viewPdfBtn) {
            viewPdfBtn.onclick = function() {
                const fileURL = URL.createObjectURL(file);
                window.open(fileURL, '_blank');
            };
        }
    } else {
        // Image Preview
        const imgPreview = document.getElementById('deathCertImagePreview');
        const pdfPreview = document.getElementById('deathCertPdfPreview');
        if (imgPreview) imgPreview.classList.remove('hidden');
        if (pdfPreview) pdfPreview.classList.add('hidden');
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgElement = document.getElementById('deathCertImage');
            if (imgElement) {
                imgElement.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
}

function handleGcashUpload() {
    const file = this.files[0];
    if (!file) {
        hideGcashPreview();
        return;
    }
    
    // Update file name display
    const fileName = file.name;
    document.getElementById('traditionalGcashFileName').textContent = fileName.length > 20 ? 
        fileName.substring(0, 17) + '...' : fileName;
    
    // Show preview container
    const previewContainer = document.getElementById('gcashPreviewContainer');
    if (previewContainer) previewContainer.classList.remove('hidden');
    
    // Show remove button
    const removeBtn = document.getElementById('removeGcash');
    if (removeBtn) removeBtn.classList.remove('hidden');
    
    // Check file type
    if (file.type === 'application/pdf') {
        // PDF Preview
        const pdfPreview = document.getElementById('gcashPdfPreview');
        const imgPreview = document.getElementById('gcashImagePreview');
        if (pdfPreview) pdfPreview.classList.remove('hidden');
        if (imgPreview) imgPreview.classList.add('hidden');
        
        // Setup PDF viewer button
        const viewPdfBtn = document.getElementById('viewGcashPdf');
        if (viewPdfBtn) {
            viewPdfBtn.onclick = function() {
                const fileURL = URL.createObjectURL(file);
                window.open(fileURL, '_blank');
            };
        }
    } else {
        // Image Preview
        const imgPreview = document.getElementById('gcashImagePreview');
        const pdfPreview = document.getElementById('gcashPdfPreview');
        if (imgPreview) imgPreview.classList.remove('hidden');
        if (pdfPreview) pdfPreview.classList.add('hidden');
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgElement = document.getElementById('gcashImage');
            if (imgElement) {
                imgElement.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
}

function initializeAddressFields() {
    // Reset address fields
    document.getElementById('traditionalDeceasedRegion').value = '';
    document.getElementById('traditionalDeceasedProvince').value = '';
    document.getElementById('traditionalDeceasedCity').value = '';
    document.getElementById('traditionalDeceasedBarangay').value = '';
    document.getElementById('traditionalDeceasedStreet').value = '';
    
    // Re-enable event listeners for address fields
    document.getElementById('traditionalDeceasedRegion').addEventListener('change', updateTraditionalProvinces);
    document.getElementById('traditionalDeceasedProvince').addEventListener('change', updateTraditionalCities);
    document.getElementById('traditionalDeceasedCity').addEventListener('change', updateTraditionalBarangays);
}
    
    // Reset addons when modal is opened
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Lifeplan Service button click event
    // Update the lifeplanServiceBtn click event to initialize the payment calculation
document.getElementById('lifeplanServiceBtn').addEventListener('click', function() {
    //DITO MAGCOCODE PAG MAY DATA RELATED KINEMERUTS SA LIFEPLAN!!!!!
    document.getElementById('serviceTypeModal').classList.add('hidden');
    
    const packageName = sessionStorage.getItem('selectedPackageName');
    const packagePrice = sessionStorage.getItem('selectedPackagePrice');
    const packageImage = sessionStorage.getItem('selectedPackageImage');
    const packageFeatures = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
    
    document.getElementById('lifeplanPackageName').textContent = packageName;
    document.getElementById('lifeplanPackagePrice').textContent = `₱${parseInt(packagePrice).toLocaleString()}`;
    
    if (packageImage) {
        document.getElementById('lifeplanPackageImage').src = packageImage;
        document.getElementById('lifeplanPackageImage').alt = packageName;
    }
    
    const totalPrice = parseInt(packagePrice);

    const selectedPackage = packagesFromDB.find(pkg => pkg.name === packageName);

        if (selectedPackage) {
            console.log('Selected Package:', selectedPackage);
        } else {
            console.warn(`No package found with the name: "${packageName}"`);
        }
        if (selectedPackage) {
            document.getElementById('lifeplanServiceId').value = selectedPackage.id;
        }
        document.getElementById('lifeplanBranchId').value = <?php echo $branch_id; ?>;
    
    document.getElementById('lifeplanTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
    document.getElementById('lifeplanSelectedPackageName').value = packageName;
    document.getElementById('lifeplanSelectedPackagePrice').value = packagePrice;
    
    const featuresList = document.getElementById('lifeplanPackageFeatures');
    featuresList.innerHTML = '';
    packageFeatures.forEach(feature => {
        featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
    });
    
    // Initialize payment calculation
    updateLifeplanPayment();
    
    document.getElementById('lifeplanModal').classList.remove('hidden');
});


    document.querySelectorAll('.lifeplan-addon').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Close modals when close button is clicked
    document.querySelectorAll('.closeModalBtn').forEach(button => {
        button.addEventListener('click', function() {
            closeAllModals();
        });
    });

    function openLifeplanModal() { //walang silbe to di nagana to kaya wag kayo dito maglagay ng code mga animal
        const packageName = sessionStorage.getItem('selectedPackageName');
        const packagePrice = sessionStorage.getItem('selectedPackagePrice');
        const packageImage = sessionStorage.getItem('selectedPackageImage');
        const packageFeatures = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
        
        // Find the selected package from the database packages
        const selectedPackage = packagesFromDB.find(pkg => pkg.name === packageName);

        if (selectedPackage) {
            console.log('Selected Package:', selectedPackage);
        } else {
            console.warn(`No package found with the name: "${packageName}"`);
        }
        
        document.getElementById('lifeplanPackageName').textContent = packageName;
        document.getElementById('lifeplanPackagePrice').textContent = `₱${parseInt(packagePrice).toLocaleString()}`;
        
        if (packageImage) {
            document.getElementById('lifeplanPackageImage').src = packageImage;
            document.getElementById('lifeplanPackageImage').alt = packageName;
        }
        
        const totalPrice = parseInt(packagePrice);
        
        document.getElementById('lifeplanTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('lifeplanSelectedPackageName').value = packageName;
        document.getElementById('lifeplanSelectedPackagePrice').value = packagePrice;
        
        // Set the service_id and branch_id hidden inputs
        
        
        const featuresList = document.getElementById('lifeplanPackageFeatures');
        featuresList.innerHTML = '';
        packageFeatures.forEach(feature => {
            featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
        });
        
        // Reset file upload preview
        hideLifeplanGcashPreview();
        handleComakerIdUpload();
        
        // Reset form fields
        document.getElementById('lifeplanBookingForm').reset();
        
        // Re-attach event listeners for file uploads
        const gcashInput = document.getElementById('lifeplanGcashReceipt');
        
        if (gcashInput) {
            gcashInput.removeEventListener('change', handleLifeplanGcashUpload);
            gcashInput.addEventListener('change', handleLifeplanGcashUpload);
        }
        
        // Re-attach remove button listener
        const removeGcashBtn = document.getElementById('removeLifeplanGcash');
        
        if (removeGcashBtn) {
            removeGcashBtn.removeEventListener('click', removeLifeplanGcash);
            removeGcashBtn.addEventListener('click', removeLifeplanGcash);
        }
        
        // Initialize payment calculation
        updateLifeplanPayment();
        
        // Reset form section visibility
        const detailsSection = document.querySelector('#lifeplanModal .details-section');
        const formSection = document.querySelector('#lifeplanModal .form-section');
        
        detailsSection.classList.remove('hidden');
        formSection.classList.add('hidden');
        formSection.classList.remove('force-show');
        
        initializeLifeplanAddressFields();
        
        // Show the modal
        document.getElementById('lifeplanModal').classList.remove('hidden');
    }

    // Handle comaker ID image upload
function handleComakerIdUpload() {
    const file = this.files[0];
    if (!file) {
        hideComakerIdPreview();
        return;
    }

    // Check if the file is an image
    if (!file.type.match('image.*')) {
        Swal.fire({
            title: 'Invalid File Type',
            text: 'Please upload an image file (JPG, JPEG, PNG).',
            icon: 'error',
            confirmButtonColor: '#d97706'
        });
        this.value = ''; // Clear the file input
        hideComakerIdPreview();
        return;
    }
    
    // Update file name display
    const fileName = file.name;
    document.getElementById('comakerIdFileName').textContent = fileName.length > 20 ? 
        fileName.substring(0, 17) + '...' : fileName;
    
    // Show preview container
    const previewContainer = document.getElementById('comakerIdPreviewContainer');
    if (previewContainer) previewContainer.classList.remove('hidden');
    
    // Show remove button
    const removeBtn = document.getElementById('removeComakerId');
    if (removeBtn) removeBtn.classList.remove('hidden');
    
    // Show image preview
    const imgPreview = document.getElementById('comakerIdImagePreview');
    if (imgPreview) imgPreview.classList.remove('hidden');
    
    // Create and display image preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const imgElement = document.getElementById('comakerIdImagePreviews');
        if (imgElement) {
            imgElement.src = e.target.result;
        }
    };
    reader.readAsDataURL(file);
}

// Hide comaker ID preview
function hideComakerIdPreview() {
    const previewContainer = document.getElementById('comakerIdPreviewContainer');
    const imgPreview = document.getElementById('comakerIdImagePreview');
    const removeBtn = document.getElementById('removeComakerId');
    const fileInput = document.getElementById('comakerIdImage');
    const fileNameDisplay = document.getElementById('comakerIdFileName');
    
    if (previewContainer) previewContainer.classList.add('hidden');
    if (imgPreview) imgPreview.classList.add('hidden');
    if (removeBtn) removeBtn.classList.add('hidden');
    if (fileInput) fileInput.value = '';
    if (fileNameDisplay) fileNameDisplay.textContent = 'No file chosen';
}

// Remove comaker ID image
function removeComakerId() {
    hideComakerIdPreview();
}

// Initialize comaker ID upload functionality
function initComakerIdUpload() {
    const comakerIdInput = document.getElementById('comakerIdImage');
    const removeComakerIdBtn = document.getElementById('removeComakerId');
    
    if (comakerIdInput) {
        comakerIdInput.removeEventListener('change', handleComakerIdUpload);
        comakerIdInput.addEventListener('change', handleComakerIdUpload);
    }
    
    if (removeComakerIdBtn) {
        removeComakerIdBtn.removeEventListener('click', removeComakerId);
        removeComakerIdBtn.addEventListener('click', removeComakerId);
    }
}

// Call this function when the page loads to initialize the event listeners
document.addEventListener('DOMContentLoaded', function() {
    initComakerIdUpload();
});

// Helper functions for lifeplan file uploads
function handleLifeplanGcashUpload() {
    const file = this.files[0];
    if (!file) {
        hideLifeplanGcashPreview();
        return;
    }
    
    // Update file name display
    const fileName = file.name;
    document.getElementById('lifeplanGcashFileName').textContent = fileName.length > 20 ? 
        fileName.substring(0, 17) + '...' : fileName;
    
    // Show preview container
    const previewContainer = document.getElementById('lifeplanGcashPreviewContainer');
    if (previewContainer) previewContainer.classList.remove('hidden');
    
    // Show remove button
    const removeBtn = document.getElementById('removeLifeplanGcash');
    if (removeBtn) removeBtn.classList.remove('hidden');
    
    // Check file type
    if (file.type === 'application/pdf') {
        // PDF Preview
        const pdfPreview = document.getElementById('lifeplanGcashPdfPreview');
        const imgPreview = document.getElementById('lifeplanGcashImagePreview');
        if (pdfPreview) pdfPreview.classList.remove('hidden');
        if (imgPreview) imgPreview.classList.add('hidden');
        
        // Setup PDF viewer button
        const viewPdfBtn = document.getElementById('viewLifeplanGcashPdf');
        if (viewPdfBtn) {
            viewPdfBtn.onclick = function() {
                const fileURL = URL.createObjectURL(file);
                window.open(fileURL, '_blank');
            };
        }
    } else {
        // Image Preview
        const imgPreview = document.getElementById('lifeplanGcashImagePreview');
        const pdfPreview = document.getElementById('lifeplanGcashPdfPreview');
        if (imgPreview) imgPreview.classList.remove('hidden');
        if (pdfPreview) pdfPreview.classList.add('hidden');
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgElement = document.getElementById('lifeplanGcashImage');
            if (imgElement) {
                imgElement.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
}

function hideLifeplanGcashPreview() {
    // Hide preview containers
    document.getElementById('lifeplanGcashPreviewContainer')?.classList.add('hidden');
    document.getElementById('lifeplanGcashImagePreview')?.classList.add('hidden');
    document.getElementById('lifeplanGcashPdfPreview')?.classList.add('hidden');
    document.getElementById('removeLifeplanGcash')?.classList.add('hidden');
    
    // Reset file input
    const fileInput = document.getElementById('lifeplanGcashReceipt');
    if (fileInput) fileInput.value = '';

    const imagePreview = document.getElementById('lifeplanGcashImage');
    if (imagePreview) {
        imagePreview.src = ''; // This clears the uploaded image data
    }
    
    // Reset file name display
    const fileNameDisplay = document.getElementById('lifeplanGcashFileName');
    if (fileNameDisplay) fileNameDisplay.textContent = 'No file chosen';
}

function removeLifeplanGcash() {
    hideLifeplanGcashPreview();
}

function updateLifeplanPayment() {
    const months = parseInt(document.getElementById('lifeplanPaymentTerm').value);
    const totalPrice = parseInt(sessionStorage.getItem('selectedPackagePrice') || '0');
    const monthlyPayment = Math.ceil(totalPrice / months);
    
    let termText = '';
    if (months === 60) termText = '5 Years (60 Monthly Payments)';
    else if (months === 36) termText = '3 Years (36 Monthly Payments)';
    else if (months === 24) termText = '2 Years (24 Monthly Payments)';
    else if (months === 12) termText = '1 Year (12 Monthly Payments)';
    
    document.getElementById('lifeplanPaymentTermDisplay').textContent = termText;
    document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
}

function initializeLifeplanAddressFields() { // WALA DIN PALA TONG SILBI 
    // Remove existing event listeners to prevent duplicates
    const regionElement = document.getElementById('lifeplanHolderRegion');
    const provinceElement = document.getElementById('lifeplanHolderProvince');
    const cityElement = document.getElementById('lifeplanHolderCity');
    
    if (regionElement) {
        regionElement.removeEventListener('change', updateLifeplanProvinces);
        regionElement.addEventListener('change', updateLifeplanProvinces);
    }
    
    if (provinceElement) {
        provinceElement.removeEventListener('change', updateLifeplanCities);
        provinceElement.addEventListener('change', updateLifeplanCities);
    }
    
    if (cityElement) {
        cityElement.removeEventListener('change', updateLifeplanBarangays);
        cityElement.addEventListener('change', updateLifeplanBarangays);
    }
    
    // Reset address fields
    if (regionElement) regionElement.value = '';
    if (provinceElement) provinceElement.value = '';
    if (cityElement) cityElement.value = '';
    document.getElementById('lifeplanHolderBarangay').value = '';
    document.getElementById('lifeplanHolderStreet').value = '';
    
    // Disable dependent dropdowns initially
    if (provinceElement) provinceElement.disabled = true;
    if (cityElement) cityElement.disabled = true;
    document.getElementById('lifeplanHolderBarangay').disabled = true;
}

// Lifeplan address dropdown functions



    // Function to close all modals
    // Update your closeAllModals function
function closeAllModals() {
    document.getElementById('traditionalModal').classList.add('hidden');
    document.getElementById('lifeplanModal').classList.add('hidden');
    document.getElementById('serviceTypeModal').classList.add('hidden');
    
    // Reset traditional modal to show details section
    document.querySelector('#traditionalModal .details-section').classList.remove('hidden');
    document.querySelector('#traditionalModal .form-section').classList.add('hidden');
    document.querySelector('#traditionalModal .form-section').classList.remove('force-show');
    
    // Reset lifeplan modal to show details section
    document.querySelector('#lifeplanModal .details-section').classList.remove('hidden');
    document.querySelector('#lifeplanModal .form-section').classList.add('hidden');
    document.querySelector('#lifeplanModal .form-section').classList.remove('force-show');
}


    // Close modals when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });

    // Close modals when clicking outside of modal content
    document.querySelectorAll('#serviceTypeModal, #traditionalModal, #lifeplanModal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeAllModals();
            }
        });
    });

    document.getElementById('lifeplanPaymentTerm').addEventListener('change', function() {
    updateLifeplanPayment();
});

// Function to update lifeplan payment details
    function updateLifeplanPayment() {
        const years = parseInt(document.getElementById('lifeplanPaymentTerm').value);
        const months = years * 12;
        const totalPrice = parseInt(sessionStorage.getItem('selectedPackagePrice') || '0');
        const monthlyPayment = Math.ceil(totalPrice / months);
        
        let termText = '';
        if (months === 60) termText = '5 Years (60 Monthly Payments)';
        else if (months === 36) termText = '3 Years (36 Monthly Payments)';
        else if (months === 24) termText = '2 Years (24 Monthly Payments)';
        else if (months === 12) termText = '1 Year (12 Monthly Payments)';
        
        document.getElementById('lifeplanPaymentTermDisplay').textContent = termText;
        document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
    }

    

    // Lifeplan Form submission
    document.getElementById('lifeplanBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        combineLifeplanAddress();

        const formData = new FormData(this);
        const formElement = this;

        Swal.fire({
            title: 'Confirm Lifeplan Booking',
            text: 'Are you sure you want to proceed with this lifeplan?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading indicator
                Swal.fire({
                    title: 'Processing Lifeplan',
                    html: 'Please wait while we process your lifeplan...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send data to backend using fetch
                fetch('booking/lifeplan_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json()) // Expecting JSON response
                .then(data => {
                    Swal.close();

                    if (data.success) {
                        // Success handling
                        document.getElementById('lifeplanModal').classList.add('hidden');
                        formElement.reset();

                        const detailsSection = document.querySelector('#lifeplanModal .details-section');
                        const formSection = document.querySelector('#lifeplanModal .form-section');

                        detailsSection.classList.remove('hidden');
                        formSection.classList.add('hidden');

                        showNotification('Lifeplan Created', 'Your lifeplan has been successfully created.', '#');
                    } else {
                        // Show error from backend
                        Swal.fire('Error', data.message || 'Something went wrong.', 'error');
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Fetch error:', error);
                    Swal.fire('Error', 'An error occurred while processing your request.', 'error');
                });
            }
        });
    });



    // Process packages from database
    const processedPackages = packagesFromDB.map(pkg => {
        let icon = 'leaf';
        if (pkg.price > 500000) icon = 'star';
        else if (pkg.price > 200000) icon = 'crown';
        else if (pkg.price > 100000) icon = 'heart';
        else if (pkg.price > 50000) icon = 'dove';
        
        return {
            price: parseFloat(pkg.price),
            service: pkg.service,
            name: pkg.name,
            description: pkg.description,
            image: pkg.image,
            icon: icon,
            features: pkg.features
        };
    });

    // Initial render
    renderPackages(processedPackages);

   
});

// Function to render packages
function renderPackages(filteredPackages) {
    const container = document.getElementById('packages-container');
    const noResults = document.getElementById('no-results');
    
    if (!container || !noResults) {
        console.error('Required DOM elements not found!');
        return;
    }

    container.innerHTML = '';

    if (filteredPackages.length === 0) {
        noResults.classList.remove('hidden');
        return;
    } else {
        noResults.classList.add('hidden');
    }

    filteredPackages.forEach(pkg => {
        const packageCard = document.createElement('div');
        packageCard.className = 'package-card bg-white rounded-[20px] shadow-lg overflow-hidden';
        packageCard.setAttribute('data-price', pkg.price);
        packageCard.setAttribute('data-service', pkg.service);
        packageCard.setAttribute('data-name', pkg.name);
        packageCard.setAttribute('data-image', pkg.image);
        
        packageCard.innerHTML = `
            <div class="flex flex-col h-full">
                <div class="h-48 bg-cover bg-center relative" style="background-image: url('${pkg.image}')">
                    <div class="absolute inset-0 bg-black/40 group-hover:bg-black/30 transition-all duration-300"></div>
                    <div class="absolute top-4 right-4 w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white">
                        <i class="fas fa-${pkg.icon} text-xl"></i>
                    </div>
                </div>
                
                <div class="p-6 flex flex-col flex-grow">
                    <h3 class="text-2xl font-hedvig text-navy mb-3">${pkg.name}</h3>
                    
                    <p class="text-dark mb-4 line-clamp-3 h-[72px] overflow-hidden">${pkg.description}</p>
                    
                    <div class="text-3xl font-hedvig text-yellow-600 mb-4 h-12 flex items-center">₱${pkg.price.toLocaleString()}</div>
                    
                    <div class="border-t border-gray-200 pt-4 mt-2 flex-grow overflow-y-auto">
                        <ul class="space-y-2">
                            ${pkg.features.map(feature => `
                                <li class="flex items-center text-sm text-gray-700">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600"></i>
                                    <span>${feature}</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                    
                    <button class="selectPackageBtn mt-6 w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                        Select Package
                    </button>
                </div>
            </div>
        `;
        container.appendChild(packageCard);
    });
}

// Initialize sorting and filtering functionality
function initializeSortingAndFiltering() {
    // Event Listeners
    document.getElementById('searchInput').addEventListener('input', filterAndSortPackages);
    document.getElementById('priceSort').addEventListener('change', function() {
        // Add a small delay to show the change is being processed
        setTimeout(() => {
            filterAndSortPackages();
        }, 100);
    });
    document.getElementById('resetFilters').addEventListener('click', resetFilters);
    document.getElementById('reset-filters-no-results')?.addEventListener('click', resetFilters);
}

// Enhanced filter and sort function
function filterAndSortPackages() {
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    // Don't proceed if the input is invalid
    if (searchInput.classList.contains('invalid')) {
        return;
    }
    
    const priceSort = document.getElementById('priceSort');
    const priceSortValue = priceSort.value;
    const packagesContainer = document.getElementById('packages-container');
    const noResults = document.getElementById('no-results');
    
    // Add/remove visual feedback for sorting
    if (priceSortValue) {
        priceSort.classList.add('sorting-active');
    } else {
        priceSort.classList.remove('sorting-active');
    }
    
    // Get all package cards
    const allCards = Array.from(document.querySelectorAll('.package-card'));
    let visibleCards = [];
    
    // Filter packages based on search term
    allCards.forEach(card => {
        const packageName = card.dataset.name.toLowerCase();
        const packageDescription = card.querySelector('p').textContent.toLowerCase();
        const featureTexts = Array.from(card.querySelectorAll('ul li')).map(li => li.textContent.toLowerCase());
        
        const matchesSearch = searchTerm === '' || 
                            packageName.includes(searchTerm) || 
                            packageDescription.includes(searchTerm) ||
                            featureTexts.some(text => text.includes(searchTerm));
        
        if (matchesSearch) {
            card.classList.remove('hidden');
            visibleCards.push(card);
        } else {
            card.classList.add('hidden');
        }
    });
    
    // Show/hide no results message
    if (visibleCards.length === 0) {
        noResults.classList.remove('hidden');
        return;
    } else {
        noResults.classList.add('hidden');
    }
    
    // Sort visible cards if sorting is selected
    if (priceSortValue) {
        visibleCards.sort((a, b) => {
            const priceA = parseFloat(a.dataset.price);
            const priceB = parseFloat(b.dataset.price);
            return priceSortValue === 'asc' ? priceA - priceB : priceB - priceA;
        });
        
        // Re-append sorted cards to maintain order
        visibleCards.forEach(card => {
            packagesContainer.appendChild(card);
        });
    } else {
        // If no sorting selected, restore original order
        restoreOriginalOrder();
    }
}

// Function to restore original package order
function restoreOriginalOrder() {
    const packagesContainer = document.getElementById('packages-container');
    const visibleCards = Array.from(packagesContainer.querySelectorAll('.package-card:not(.hidden)'));
    
    // Create a map of original positions
    const originalPositions = new Map();
    originalPackages.forEach((pkg, index) => {
        originalPositions.set(pkg.name, index);
    });
    
    // Sort visible cards by their original position
    visibleCards.sort((a, b) => {
        const posA = originalPositions.get(a.dataset.name) || 0;
        const posB = originalPositions.get(b.dataset.name) || 0;
        return posA - posB;
    });
    
    // Re-append in original order
    visibleCards.forEach(card => {
        packagesContainer.appendChild(card);
    });
}

// Enhanced reset function
function resetFilters() {
    // Reset search input
    const searchInput = document.getElementById('searchInput');
    searchInput.value = '';
    searchInput.classList.remove('invalid');
    
    // Reset price sort dropdown to default and remove visual feedback
    const priceSort = document.getElementById('priceSort');
    priceSort.value = '';
    priceSort.classList.remove('sorting-active');
    
    // Show all packages
    document.querySelectorAll('.package-card').forEach(card => {
        card.classList.remove('hidden');
    });
    
    // Restore original order
    restoreOriginalOrder();
    
    // Hide the "no results" message
    document.getElementById('no-results').classList.add('hidden');
}

// Date validation for traditional booking form
document.addEventListener('DOMContentLoaded', function() {
    // Get today's date in YYYY-MM-DD format
    const today = new Date();
    const todayFormatted = today.toISOString().split('T')[0];
    
    // Set max date for date of birth and date of death to today
    document.getElementById('traditionalDateOfBirth').max = todayFormatted;
    document.getElementById('traditionalDateOfDeath').max = todayFormatted;
    
    // Set min date for date of burial to tomorrow
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowFormatted = tomorrow.toISOString().split('T')[0];
    document.getElementById('traditionalDateOfBurial').min = tomorrowFormatted;
    
    // Validate date of birth is before date of death
    document.getElementById('traditionalDateOfBirth').addEventListener('change', function() {
        const dob = this.value;
        const dod = document.getElementById('traditionalDateOfDeath').value;
        
        if (dob && dod && dob > dod) {
            alert('Date of birth must be before date of death');
            this.value = '';
        }
    });
    
    // Validate date of death is after date of birth and before date of burial
    document.getElementById('traditionalDateOfDeath').addEventListener('change', function() {
        const dod = this.value;
        const dob = document.getElementById('traditionalDateOfBirth').value;
        const dobInput = document.getElementById('traditionalDateOfBirth');
        
        if (dob && dod < dob) {
            alert('Date of death must be after date of birth');
            this.value = '';
            return;
        }
        
        const burialDate = document.getElementById('traditionalDateOfBurial').value;
        if (burialDate && dod > burialDate) {
            alert('Date of death must be before date of burial');
            this.value = '';
        }
    });
    
    // Validate date of burial is after date of death
    document.getElementById('traditionalDateOfBurial').addEventListener('change', function() {
        const burialDate = this.value;
        const dod = document.getElementById('traditionalDateOfDeath').value;
        
        if (dod && burialDate < dod) {
            alert('Date of burial must be after date of death');
            this.value = '';
        }
    });
});

// Add this to your existing JavaScript
// Update your existing resize handler to include lifeplan modal
window.addEventListener('resize', function() {
    const traditionalModal = document.getElementById('traditionalModal');
    const lifeplanModal = document.getElementById('lifeplanModal');
    
    if (!traditionalModal.classList.contains('hidden')) {
        handleModalResize('traditionalModal');
    }
    
    if (!lifeplanModal.classList.contains('hidden')) {
        handleModalResize('lifeplanModal');
    }
});
function handleModalResize(modalId) {
    const modalPrefix = modalId === 'traditionalModal' ? '' : 'lifeplan';
    const detailsSection = document.querySelector(`#${modalId} .details-section`);
    const formSection = document.querySelector(`#${modalId} .form-section`);
    const isMobileView = window.innerWidth < 768;
    
    if (isMobileView) {
        // If switching to mobile view, ensure form is hidden if we were on details
        if (!detailsSection.classList.contains('hidden')) {
            formSection.classList.add('hidden');
        }
    } else {
        // If switching to desktop view, show both sections
        detailsSection.classList.remove('hidden');
        formSection.classList.remove('hidden');
    }
}
</script>

<!-- Loading Animation Overlay -->
<div id="page-loader" class="fixed inset-0 bg-black bg-opacity-80 z-[999] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-500">
    <div class="text-center">
        <!-- Animated Candle -->
        <div class="relative w-full h-48 mb-6">
            <!-- Candle -->
            <div class="absolute left-1/2 transform -translate-x-1/2 bottom-0 w-16">
                <!-- Wick with Flame (updated positioning) -->
                <div class="relative w-1 h-5 bg-gray-700 mx-auto rounded-t-lg">
                    <!-- Outer Flame (updated positioning) -->
                    <div class="absolute left-1/2 top-[-24px] transform -translate-x-1/2 w-6 h-12 bg-yellow-600/80 rounded-full blur-sm animate-flame"></div>
                    
                    <!-- Inner Flame (updated positioning) -->
                    <div class="absolute left-1/2 top-[-20px] transform -translate-x-1/2 w-3 h-10 bg-white/90 rounded-full blur-[2px] animate-flame"></div>
                </div>
                
                <!-- Candle Body -->
                <div class="w-12 h-24 bg-gradient-to-b from-cream to-white mx-auto rounded-t-lg"></div>
                
                <!-- Candle Base -->
                <div class="w-16 h-3 bg-gradient-to-b from-cream to-yellow-600/20 mx-auto rounded-b-lg"></div>
            </div>
            
            <!-- Reflection/Glow -->
            <div class="absolute left-1/2 transform -translate-x-1/2 bottom-4 w-48 h-10 bg-yellow-600/30 rounded-full blur-xl"></div>
        </div>
        
        <!-- Loading Text -->
        <h3 class="text-white text-xl font-hedvig">Loading...</h3>
        
        <!-- Pulsing Dots -->
        <div class="flex justify-center mt-3 space-x-2">
            <div class="w-2 h-2 bg-yellow-600 rounded-full animate-pulse" style="animation-delay: 0s"></div>
            <div class="w-2 h-2 bg-yellow-600 rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
            <div class="w-2 h-2 bg-yellow-600 rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
        </div>
    </div>
</div>

<!-- Add this JavaScript code -->
<script>
    // Function to show the loading animation
    function showLoader() {
        const loader = document.getElementById('page-loader');
        loader.classList.remove('opacity-0', 'pointer-events-none');
        document.body.style.overflow = 'hidden'; // Prevent scrolling while loading
    }
    
    // Function to hide the loading animation
    function hideLoader() {
        const loader = document.getElementById('page-loader');
        loader.classList.add('opacity-0', 'pointer-events-none');
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    // Add event listeners to all internal links
    document.addEventListener('DOMContentLoaded', function() {
        // Get all internal links that are not anchors
        const internalLinks = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="http"]):not([href^="mailto"])');
        
        internalLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Check if it's a normal click (not control/command + click to open in new tab)
                if (!e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    showLoader();
                    
                    // Store the href to navigate to
                    const href = this.getAttribute('href');
                    
                    // Delay navigation slightly to show the loader
                    setTimeout(() => {
                        window.location.href = href;
                    }, 800);
                }
            });
        });
        
        // Hide loader when back button is pressed
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                hideLoader();
            }
        });
    });
    
    // Hide loader when the page is fully loaded
    window.addEventListener('load', hideLoader);
    function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}

// Add this to your existing JavaScript code
document.addEventListener('DOMContentLoaded', function() {
    // Function to capitalize first letter of each word
    function capitalizeName(name) {
        return name.toLowerCase().replace(/\b\w/g, function(char) {
            return char.toUpperCase();
        });
    }

    // Function to validate name input
    // Update the validateNameInput function
function validateNameInput(input) {
    // Remove any numbers or symbols
    let value = input.value.replace(/[^a-zA-Z\s'-]/g, '');
    
    // Remove leading spaces
    value = value.replace(/^\s+/, '');
    
    // Capitalize first letter of each word
    value = capitalizeName(value);
    
    // Prevent multiple spaces
    value = value.replace(/\s{2,}/g, ' ');
    
    // Update the input value
    input.value = value;
}

    // Add event listeners to all name fields
    const nameFields = [
        'traditionalDeceasedFirstName',
        'traditionalDeceasedMiddleName',
        'traditionalDeceasedLastName',
        'lifeplanHolderFirstName',
        'lifeplanHolderMiddleName',
        'lifeplanHolderLastName'
    ];

    nameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            // Validate on input
            field.addEventListener('input', function() {
                validateNameInput(this);
            });

            // Validate on blur (when field loses focus)
            field.addEventListener('blur', function() {
                validateNameInput(this);
            });

            // Prevent paste of invalid content
            field.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = pastedText.replace(/[^a-zA-Z\s'-]/g, '');
                document.execCommand('insertText', false, cleanedText);
            });
        }
    });

    // Additional validation for first name and last name (required fields)
    const requiredNameFields = [
        'traditionalDeceasedFirstName',
        'traditionalDeceasedLastName',
        'lifeplanHolderFirstName',
        'lifeplanHolderLastName'
    ];

    requiredNameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', function() {
                if (this.value.trim().length < 2) {
                    this.setCustomValidity('Please enter at least 2 characters');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    });
});

// Add this function to validate the search input
function validateSearchInput(input) {
    // Remove any non-letter characters (except spaces when allowed)
    let value = input.value.replace(/[^a-zA-Z\s]/g, '');
    
    // Don't allow leading spaces
    value = value.replace(/^\s+/, '');
    
    // Don't allow multiple consecutive spaces
    value = value.replace(/\s{2,}/g, ' ');
    
    // Don't allow space until at least 2 characters are entered
    if (value.length < 2) {
        value = value.replace(/\s/g, '');
    }
    
    // Update the input value
    input.value = value;
    
    // Add/remove invalid class based on input validity
    if (value.length > 0 && !/^[a-zA-Z]{2,}(?: [a-zA-Z]+)*$/.test(value)) {
        input.classList.add('invalid');
    } else {
        input.classList.remove('invalid');
    }
}

// Update the event listener for the search input
document.getElementById('searchInput').addEventListener('input', function(e) {
    // First validate the input
    validateSearchInput(this);
    
    // Then filter and sort packages with a small delay for better performance
    clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => {
        filterAndSortPackages();
    }, 300);
});

// Prevent paste of invalid content
document.getElementById('searchInput').addEventListener('paste', function(e) {
    e.preventDefault();
    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
    let cleanedText = pastedText.replace(/[^a-zA-Z\s]/g, '');
    
    // Apply the same validation rules
    cleanedText = cleanedText.replace(/^\s+/, '');
    cleanedText = cleanedText.replace(/\s{2,}/g, ' ');
    if (cleanedText.length < 2) {
        cleanedText = cleanedText.replace(/\s/g, '');
    }
    
    document.execCommand('insertText', false, cleanedText);
});

document.addEventListener('DOMContentLoaded', function() {
    // Handle GCash QR selection for traditional modal
    const gcashQrOptions = document.querySelectorAll('#gcashQrContainer .gcash-qr-option');
    const selectedGcashQrInput = document.getElementById('selectedGcashQr');
    
    gcashQrOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            gcashQrOptions.forEach(opt => opt.classList.remove('border-yellow-600', 'bg-yellow-50'));
            // Add selected class to clicked option
            this.classList.add('border-yellow-600', 'bg-yellow-50');
            // Update hidden input with selected QR number
            selectedGcashQrInput.value = this.dataset.qrNumber;
            // Trigger enlarge QR code
            enlargeQrCode(this.querySelector('img'));
        });
    });

    // Handle GCash QR selection for lifeplan modal
    const lifeplanGcashQrOptions = document.querySelectorAll('#lifeplanGcashQrContainer .gcash-qr-option');
    const lifeplanSelectedGcashQrInput = document.getElementById('lifeplanSelectedGcashQr');
    
    lifeplanGcashQrOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            lifeplanGcashQrOptions.forEach(opt => opt.classList.remove('border-yellow-600', 'bg-yellow-50'));
            // Add selected class to clicked option
            this.classList.add('border-yellow-600', 'bg-yellow-50');
            // Update hidden input with selected QR number
            lifeplanSelectedGcashQrInput.value = this.dataset.qrNumber;
            // Trigger enlarge QR code
            enlargeQrCode(this.querySelector('img'));
        });
    });

    // Prevent clicking outside to close modals
    const qrModals = ['qrCodeModal', 'lifeplanQrCodeModal'];
    qrModals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                event.stopPropagation(); // Prevent closing when clicking outside
            }
        });
    });

    // Ensure Escape key closes modals
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.getElementById('qrCodeModal').classList.add('hidden');
            document.getElementById('lifeplanQrCodeModal').classList.add('hidden');
        }
    });

    // Close modals with X button
    document.getElementById('closeQrModal').addEventListener('click', function() {
        document.getElementById('qrCodeModal').classList.add('hidden');
    });

    document.getElementById('lifeplanCloseQrModal').addEventListener('click', function() {
        document.getElementById('lifeplanQrCodeModal').classList.add('hidden');
    });
});
</script>

</body>
</html>