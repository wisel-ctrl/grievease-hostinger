<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

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

require_once '../db_connect.php'; // Database connection

$items_per_page = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// Get user's ID validation status
$user_id = $_SESSION['user_id'];
$id_validation_query = "SELECT is_validated, decline_reason, upload_at, image_path FROM valid_id_tb WHERE id = ?";
$id_validation_stmt = $conn->prepare($id_validation_query);
$id_validation_stmt->bind_param("i", $user_id);
$id_validation_stmt->execute();
$id_validation_result = $id_validation_stmt->get_result();
$id_validation = $id_validation_result->fetch_assoc();
$id_validation_stmt->close();

// Map the validation status to more readable values
$id_status = 'Not Submitted'; // Default
$id_status_class = 'bg-gray-200 text-gray-800'; // Default
$id_icon = 'fas fa-question-circle';


// Get user's information from database
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, email, birthdate FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name'];
$last_name = $row['last_name'];
$email = $row['email'];
$stmt->close();

// Get user's bookings from database (notifications)
$query = "SELECT * FROM booking_tb WHERE customerID = ? ORDER BY booking_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
$notifications_count = [
    'total' => 0,
    'pending' => 0,
    'accepted' => 0,
    'declined' => 0,
    'id_pending' => 0,
    'id_accepted' => 0,
    'id_declined' => 0
];

while ($booking = $bookings_result->fetch_assoc()) {
    $bookings[] = $booking;
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

// Update ID validation counts
if ($id_validation) {
    $notifications_count['total']++;
    switch ($id_validation['is_validated']) {
        case 'no':
            $notifications_count['pending']++;
            $notifications_count['id_pending']++;
            break;
        case 'valid':
            $notifications_count['accepted']++;
            $notifications_count['id_accepted']++;
            break;
        case 'denied':
            $notifications_count['declined']++;
            $notifications_count['id_declined']++;
            break;
    }
}

$stmt->close();

// Get service names for each booking
foreach ($bookings as $key => $booking) {
    $service_query = "SELECT service_name FROM services_tb WHERE service_id = ?";
    $stmt = $conn->prepare($service_query);
    $stmt->bind_param("i", $booking['service_id']);
    $stmt->execute();
    $service_result = $stmt->get_result();
    if ($service_row = $service_result->fetch_assoc()) {
        $bookings[$key]['service_name'] = $service_row['service_name'];
    } else {
        $bookings[$key]['service_name'] = 'Unknown Service';
    }
    $stmt->close();
}

// Get branch names for each booking
foreach ($bookings as $key => $booking) {
    $branch_query = "SELECT branch_name FROM branch_tb WHERE branch_id = ?";
    $stmt = $conn->prepare($branch_query);
    $stmt->bind_param("i", $booking['branch_id']);
    $stmt->execute();
    $branch_result = $stmt->get_result();
    if ($branch_row = $branch_result->fetch_assoc()) {
        $bookings[$key]['branch_name'] = $branch_row['branch_name'];
    } else {
        $bookings[$key]['branch_name'] = 'Unknown Branch';
    }
    $stmt->close();
}

$conn->close();

// Function to calculate time elapsed
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return 'just now';
}

// Handle filtering
$current_filter = 'all';
if (isset($_GET['filter'])) {
    $current_filter = $_GET['filter'];
}

// Filter and paginate bookings and ID validation
$filtered_items = [];
foreach ($bookings as $booking) {
    if ($current_filter === 'all' || 
        ($current_filter === 'pending' && $booking['status'] === 'Pending') ||
        ($current_filter === 'accepted' && $booking['status'] === 'Accepted') ||
        ($current_filter === 'declined' && $booking['status'] === 'Declined')) {
        $filtered_items[] = [
            'type' => 'booking',
            'data' => $booking,
            'timestamp' => $booking['booking_date']
        ];
    }
}
// Add ID validation if it matches the filter
if ($id_validation) {
    $id_validation_status = $id_validation['is_validated'];
    if ($current_filter === 'all' || 
        ($current_filter === 'pending' && $id_validation_status === 'no') ||
        ($current_filter === 'accepted' && $id_validation_status === 'valid') ||
        ($current_filter === 'declined' && $id_validation_status === 'denied')) {
        $filtered_items[] = [
            'type' => 'id_validation',
            'data' => $id_validation,
            'timestamp' => $id_validation['upload_at']
        ];
    }
}
// Sort all items by timestamp
usort($filtered_items, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

$total_filtered = count($filtered_items);
$total_filtered_pages = ceil($total_filtered / $items_per_page);

// Get current page items
$current_page_items = array_slice($filtered_items, ($page - 1) * $items_per_page, $items_per_page);


?>

<script src="customer_support.js"></script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - GrievEase</title>
    <?php include 'faviconLogo.php'; ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Hedvig+Letters+Serif:ital@0;1&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../tailwind.js"></script>
    <style>
        body {
            background-color: #F9F6F0;
        }
        .notification-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
        }
        
        /* Animation for notifications */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notification-animate {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Filter button active state */
        .filter-active {
            position: relative;
            overflow: hidden;
        }
        
        .filter-active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #CA8A04;
        }
    </style>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
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
                
                <a href="faqs.php" class="text-white hover:text-gray-300 transition relative group">
                    FAQs
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
            </div>
            
            <!-- User Menu -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="notification.php" class="relative text-white hover:text-yellow-600 transition-colors">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifications_count['pending'] > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                        <?php echo $notifications_count['pending']; ?>
                    </span>
                    <?php endif; ?>
                </a>
                
            
                
                <div class="relative group">
                    <button class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full bg-yellow-600 flex items-center justify-center text-sm">
                        <?php 
                            $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); 
                            echo htmlspecialchars($initials);
                        ?>
                    </div>
                    <span class="hidden md:inline text-sm">
                        <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
                    </span>

                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-card overflow-hidden invisible group-hover:visible transition-all duration-300 opacity-0 group-hover:opacity-100">
                        <div class="p-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-navy"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></p>
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
                <i class="fas fa-bell text-xl"></i>
                <?php if ($notifications_count['pending'] > 0): ?>
                <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                    <?php echo $notifications_count['pending']; ?>
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
    
    <!-- Main Content Container -->
<div class="container mx-auto px-4 py-6 sm:py-8 max-w-screen-xl mt-[var(--navbar-height)]">
    <!-- Page Header with subtle background -->
    <div class="bg-dark rounded-xl py-6 px-4 sm:py-8 sm:px-6 mb-6 sm:mb-10">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-hedvig text-cream/70 mb-2 sm:mb-3">Notifications</h1>
            <p class="text-cream/70 text-base sm:text-lg max-w-2xl mx-auto">Stay updated with important information about your services and arrangements.</p>
            <div class="w-16 h-1 bg-yellow-600 mx-auto mt-3 sm:mt-4"></div>
        </div>
    </div>

    <!-- Dashboard Layout -->
    <div class="flex flex-col lg:flex-row gap-4">
        <!-- Left Sidebar: Filter Controls - Dropdown on mobile, Regular on desktop -->
        <div class="lg:w-1/4 mb-4 lg:mb-0">
            <!-- Mobile Dropdown Filter -->
<div class="lg:hidden bg-white rounded-lg shadow-sm p-4">
    <h2 class="text-navy text-lg mb-3">Filter by Status</h2>
    
    <!-- Dropdown Trigger Button styled like the All Notifications button -->
    <div class="dropdown-container relative">
        <button id="mobileFilterButton" 
                class="w-full <?php echo $current_filter === 'all' ? 'bg-navy text-white filter-active' : 'bg-white hover:bg-navy/10 border border-input-border text-navy'; ?> px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between group transition-all duration-200"
                onclick="toggleFilterDropdown()">
            <span class="flex items-center">
                <i class="fas fa-inbox mr-2"></i>
                <span>
                    <?php 
                    $filter_name = ucfirst($current_filter);
                    if($current_filter === 'all') { echo "All Notifications"; }
                    else if($current_filter === 'pending') { echo "Pending"; }
                    else if($current_filter === 'accepted') { echo "Accepted"; }
                    else if($current_filter === 'declined') { echo "Declined"; }
                    ?>
                </span>
            </span>
            <div class="flex items-center">
                <span class="<?php 
                    if($current_filter === 'all') {
                        echo 'bg-white text-navy';
                    } else if($current_filter === 'pending') {
                        echo 'bg-white text-yellow-600';
                    } else if($current_filter === 'accepted') {
                        echo 'bg-white text-success';
                    } else if($current_filter === 'declined') {
                        echo 'bg-white text-error';
                    } else {
                        echo 'bg-navy/20 text-navy';
                    }
                ?> w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs mr-2">
                    <?php 
                    if($current_filter === 'all') { echo $notifications_count['total']; }
                    else if($current_filter === 'pending') { echo $notifications_count['pending']; }
                    else if($current_filter === 'accepted') { echo $notifications_count['accepted']; }
                    else if($current_filter === 'declined') { echo $notifications_count['declined']; }
                    ?>
                </span>
                <i class="fas fa-chevron-down transition-transform" id="dropdownChevron"></i>
            </div>
        </button>
        
        <!-- Dropdown Content -->
        <div id="mobileFilterDropdown" class="absolute left-0 right-0 mt-1 bg-white rounded-md shadow-md z-10 overflow-hidden transition-all duration-200 max-h-0 opacity-0 invisible">
            <div class="p-2 space-y-1.5">
                <!-- Only show options that aren't currently selected -->
                <?php if($current_filter !== 'all'): ?>
                <a href="?filter=all" 
                   class="w-full bg-white hover:bg-navy/10 border border-input-border text-navy px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between group transition-all duration-200">
                    <span class="flex items-center">
                        <i class="fas fa-inbox mr-2"></i>
                        <span>All Notifications</span>
                    </span>
                    <span class="bg-navy/20 text-navy w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['total']; ?></span>
                </a>
                <?php endif; ?>
                
                <?php if($current_filter !== 'pending'): ?>
                <a href="?filter=pending" 
                   class="w-full bg-white hover:bg-yellow-600/10 border border-input-border text-navy px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between group transition-all duration-200">
                    <span class="flex items-center">
                        <span class="notification-dot bg-yellow-600 mr-2"></span>
                        <span>Pending</span>
                    </span>
                    <span class="bg-yellow-600/20 text-yellow-600 w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['pending']; ?></span>
                </a>
                <?php endif; ?>
                
                <?php if($current_filter !== 'accepted'): ?>
                <a href="?filter=accepted" 
                   class="w-full bg-white hover:bg-success/10 border border-input-border text-navy px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between group transition-all duration-200">
                    <span class="flex items-center">
                        <span class="notification-dot bg-success mr-2"></span>
                        <span>Accepted</span>
                    </span>
                    <span class="bg-success/20 text-success w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['accepted']; ?></span>
                </a>
                <?php endif; ?>
                
                <?php if($current_filter !== 'declined'): ?>
                <a href="?filter=declined" 
                   class="w-full bg-white hover:bg-error/10 border border-input-border text-navy px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between group transition-all duration-200">
                    <span class="flex items-center">
                        <span class="notification-dot bg-error mr-2"></span>
                        <span>Declined</span>
                    </span>
                    <span class="bg-error/20 text-error w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['declined']; ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFilterDropdown() {
    const dropdown = document.getElementById('mobileFilterDropdown');
    const chevron = document.getElementById('dropdownChevron');
    
    if (dropdown.classList.contains('max-h-0')) {
        // Open dropdown
        dropdown.classList.remove('max-h-0', 'opacity-0', 'invisible');
        dropdown.classList.add('max-h-80', 'opacity-100', 'visible');
        chevron.classList.add('rotate-180');
    } else {
        // Close dropdown
        dropdown.classList.remove('max-h-80', 'opacity-100', 'visible');
        dropdown.classList.add('max-h-0', 'opacity-0', 'invisible');
        chevron.classList.remove('rotate-180');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('mobileFilterDropdown');
    const button = document.getElementById('mobileFilterButton');
    
    if (!button.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('max-h-80', 'opacity-100', 'visible');
        dropdown.classList.add('max-h-0', 'opacity-0', 'invisible');
        document.getElementById('dropdownChevron').classList.remove('rotate-180');
    }
});
</script>
            
            <!-- Desktop Filter Buttons -->
            <div class="hidden lg:block bg-white rounded-lg shadow-sm p-4 sticky top-20">
                <h2 class="text-navy text-lg mb-3">Filter by Status</h2>
                
                <!-- Filter Buttons -->
                <div class="space-y-1.5">
                    <a href="?filter=all" 
                       class="w-full <?php echo $current_filter === 'all' ? 'bg-navy text-white filter-active' : 'bg-white hover:bg-navy/10 border border-input-border text-navy'; ?> px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between group transition-all duration-200">
                        <span class="flex items-center">
                            <i class="fas fa-inbox mr-2"></i>
                            <span>All Notifications</span>
                        </span>
                        <span class="<?php echo $current_filter === 'all' ? 'bg-white text-navy' : 'bg-navy/20 text-navy'; ?> w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['total']; ?></span>
                    </a>
                    
                    <a href="?filter=pending" 
                       class="w-full <?php echo $current_filter === 'pending' ? 'bg-yellow-600 text-white filter-active' : 'bg-white hover:bg-yellow-600/10 border border-input-border text-navy'; ?> px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between group transition-all duration-200">
                        <span class="flex items-center">
                            <span class="notification-dot <?php echo $current_filter === 'pending' ? 'bg-white' : 'bg-yellow-600'; ?> mr-2"></span>
                            <span>Pending</span>
                        </span>
                        <span class="<?php echo $current_filter === 'pending' ? 'bg-white text-yellow-600' : 'bg-yellow-600/20 text-yellow-600'; ?> w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['pending']; ?></span>
                    </a>
                    
                    <a href="?filter=accepted" 
                       class="w-full <?php echo $current_filter === 'accepted' ? 'bg-success text-white filter-active' : 'bg-white hover:bg-success/10 border border-input-border text-navy'; ?> px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between group transition-all duration-200">
                        <span class="flex items-center">
                            <span class="notification-dot <?php echo $current_filter === 'accepted' ? 'bg-white' : 'bg-success'; ?> mr-2"></span>
                            <span>Accepted</span>
                        </span>
                        <span class="<?php echo $current_filter === 'accepted' ? 'bg-white text-success' : 'bg-success/20 text-success'; ?> w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['accepted']; ?></span>
                    </a>
                    
                    <a href="?filter=declined" 
                       class="w-full <?php echo $current_filter === 'declined' ? 'bg-error text-white filter-active' : 'bg-white hover:bg-error/10 border border-input-border text-navy'; ?> px-3 py-2 rounded-md text-xs font-medium flex items-center justify-between group transition-all duration-200">
                        <span class="flex items-center">
                            <span class="notification-dot <?php echo $current_filter === 'declined' ? 'bg-white' : 'bg-error'; ?> mr-2"></span>
                            <span>Declined</span>
                        </span>
                        <span class="<?php echo $current_filter === 'declined' ? 'bg-white text-error' : 'bg-error/20 text-error'; ?> w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['declined']; ?></span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Right Content: Notifications List -->
        <div class="lg:w-3/4">
            <!-- Search Bar -->
            <div class="mb-4 relative">
                <input type="text" id="searchInput" placeholder="Search notifications..." class="w-full pl-8 pr-3 py-2 bg-white border border-input-border rounded-md focus:outline-none focus:ring-1 focus:ring-yellow-600/50 text-sm sm:text-base">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
            </div>
            
            <?php if (empty($current_page_items)): ?>
            <!-- No Notifications Message -->
            <div class="bg-white rounded-md shadow-md p-6 text-center">
                <div class="rounded-full bg-gray-100 p-3 inline-flex items-center justify-center mb-3">
                    <i class="fas fa-bell-slash text-gray-400 text-xl"></i>
                </div>
                <h3 class="text-lg font-hedvig text-navy mb-1">No notifications found</h3>
                <p class="text-gray-500 text-sm">
                    <?php 
                    if ($current_filter !== 'all') {
                        echo "You don't have any " . strtolower($current_filter) . " notifications.";
                    } else {
                        echo "You don't have any notifications yet.";
                    }
                    ?>
                </p>
            </div>
            <?php else: ?>
            
            <!-- Notifications Container -->
            <div class="space-y-3" id="notificationsContainer">
                <?php foreach ($current_page_items as $item): ?>
                    <?php if ($item['type'] === 'booking'): ?>
                        <?php 
                            $booking = $item['data'];
                            $border_color = '';
                            $status_bg = '';
                            $status_icon = '';
                            $status_text_color = '';
                            
                            switch ($booking['status']) {
                                case 'Pending':
                                    $border_color = 'border-yellow-600';
                                    $status_bg = 'bg-yellow-600/20';
                                    $status_icon = 'fas fa-clock';
                                    $status_text_color = 'text-yellow-600';
                                    break;
                                case 'Accepted':
                                    $border_color = 'border-success';
                                    $status_bg = 'bg-success/20';
                                    $status_icon = 'fas fa-check-circle';
                                    $status_text_color = 'text-success';
                                    break;
                                case 'Declined':
                                    $border_color = 'border-error';
                                    $status_bg = 'bg-error/20';
                                    $status_icon = 'fas fa-times-circle';
                                    $status_text_color = 'text-error';
                                    break;
                            }
                        ?>
                        
                        <!-- Booking Notification -->
<div class="bg-white border-l-4 <?php echo $border_color; ?> rounded-xl shadow-md overflow-hidden notification-animate hover:shadow-lg transition-all duration-300">
    <div class="flex flex-col">
        <div class="flex-1 py-4 px-4 sm:py-5 sm:px-7">
            <!-- Top Row - Now using grid columns -->
            <div class="grid grid-cols-2 gap-2 mb-1">  <!-- Changed to grid layout -->
                <!-- Left Column - Status -->
                <!-- Remove the div and keep only the span -->
<span class="<?php echo $status_bg; ?> <?php echo $status_text_color; ?> text-xs px-2 py-1 rounded-full inline-flex items-center">
    <i class="<?php echo $status_icon; ?> mr-1 text-xs"></i>
    <?php echo htmlspecialchars($booking['status']); ?>
</span>
                
                <!-- Right Column - Date/Time and Branch -->
                <div class="flex flex-col items-end">
                    <!-- Date/Time -->
                    <div class="bg-cream rounded-lg p-1 text-xs flex items-center space-x-1">
                        <p class="text-gray-700 flex items-center">
                            <i class="far fa-calendar mr-1 text-gold text-xs"></i>
                            <?php echo date('M d', strtotime($booking['booking_date'])); ?>
                        </p>
                        <p class="text-gray-700 flex items-center">
                            <i class="far fa-clock mr-1 text-gold text-xs"></i>
                            <?php echo date('h:i A', strtotime($booking['booking_date'])); ?>
                        </p>
                    </div>
                    
                    <!-- Branch Name -->
                    <p class="text-gray-600 text-xs mt-1 flex items-center">
                        <i class="fas fa-map-marker-alt mr-1 text-gold text-xs"></i> 
                        <?php echo htmlspecialchars($booking['branch_name']); ?>
                    </p>
                </div>
            </div>

            <!-- Service Name -->
            <h3 class="text-navy text-base sm:text-lg font-hedvig">
                <?php echo htmlspecialchars($booking['service_name']); ?>
            </h3>
            
            <?php if ($booking['status'] === 'Declined' && !empty($booking['admin_message'])): ?>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">
                    <i class="fas fa-comment-alt mr-1 text-gold text-xs"></i> 
                    Reason: <?php echo htmlspecialchars($booking['admin_message']); ?>
                </p>
            <?php endif; ?>

            <!-- Booking Details -->
            <div class="mt-2">
                <p class="text-gray-700 text-xs sm:text-sm">
                    <?php 
                        switch($booking['status']) {
                            case 'Pending':
                                echo "Your booking request is being reviewed by our staff. We will update you soon.";
                                break;
                            case 'Accepted':
                                echo "Your booking has been confirmed. Please arrive 15 minutes before your scheduled time.";
                                break;
                            case 'Declined':
                                echo "We apologize, but we were unable to accommodate your booking request.";
                                if (!empty($booking['admin_message'])) {
                                    echo " Reason: " . htmlspecialchars($booking['admin_message']);
                                }
                                break;
                        }
                    ?>
                </p>
            </div>
            
            <div class="mt-3 flex flex-wrap gap-2 items-center justify-between">
                <button onclick="viewBookingDetails(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['status']; ?>')" 
                    class="<?php 
                        if($booking['status'] === 'Pending') echo 'bg-yellow-600 hover:bg-yellow-700';
                        elseif($booking['status'] === 'Accepted') echo 'bg-green-600 hover:bg-green-700';
                        elseif($booking['status'] === 'Declined') echo 'bg-red-600 hover:bg-red-700';
                        else echo 'bg-gray-600 hover:bg-gray-700';
                    ?> text-white px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center">
                    <i class="fas fa-eye mr-1 text-xs"></i> View Details
                </button>
                
                <!-- Timestamp -->
                <div class="text-xs text-gray-500 flex items-center">
                    <i class="fas fa-history mr-1 text-xs"></i> 
                    <?php echo time_elapsed_string($booking['booking_date']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
                    <?php elseif ($item['type'] === 'id_validation'): ?>
                        <!-- ID VALIDATION NOTIFICATION -->
                        <?php 
                            $id_validation = $item['data'];
                            $id_status = 'Not Submitted';
                            $id_status_class = 'bg-gray-200 text-gray-800';
                            $id_icon = 'fas fa-question-circle';
                            $border_color = 'border-gray-400';

                            if ($id_validation) {
                                switch ($id_validation['is_validated']) {
                                    case 'no':
                                        $id_status = 'Pending';
                                        $id_status_class = 'bg-yellow-600/20 text-yellow-600';
                                        $id_icon = 'fas fa-clock';
                                        $border_color = 'border-yellow-600';
                                        break;
                                    case 'valid':
                                        $id_status = 'Accepted';
                                        $id_status_class = 'bg-success/20 text-success';
                                        $id_icon = 'fas fa-check-circle';
                                        $border_color = 'border-success';
                                        break;
                                    case 'denied':
                                        $id_status = 'Declined';
                                        $id_status_class = 'bg-error/20 text-error';
                                        $id_icon = 'fas fa-times-circle';
                                        $border_color = 'border-error';
                                        break;
                                }
                            }
                        ?>
                        
                        <!-- ID Validation Notification -->
                        <div class="bg-white border-l-4 <?php echo $border_color; ?> rounded-xl shadow-md overflow-hidden notification-animate hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col">
                                <div class="flex-1 py-4 px-4 sm:py-5 sm:px-7">
                                    <div class="flex flex-col sm:flex-row justify-between">
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="<?php echo $id_status_class; ?> text-xs px-2 py-1 rounded-full inline-flex items-center">
                                                    <i class="<?php echo $id_icon; ?> mr-1 text-xs"></i>
                                                    ID: <?php echo $id_status; ?>
                                                </span>
                                                <!-- Mobile Timestamp -->
                                                <div class="sm:hidden bg-cream rounded-lg p-1.5 text-xs">
                                                    <p class="text-gray-700 flex items-center">
                                                        <i class="far fa-clock mr-1 text-gold text-xs"></i>
                                                        <?php echo date('M d', strtotime($id_validation['upload_at'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <h3 class="text-navy text-base sm:text-lg font-hedvig mt-1">
                                                ID Verification
                                            </h3>
                                            <p class="text-gray-600 text-xs sm:text-sm mt-1 flex items-center">
                                                <i class="fas fa-id-card mr-1 text-gold text-xs"></i> 
                                                <?php 
                                                if ($id_validation['is_validated'] === 'denied' && !empty($id_validation['decline_reason'])) {
                                                    echo "Reason: " . htmlspecialchars($id_validation['decline_reason']);
                                                } else {
                                                    echo "Uploaded on " . date('M d, Y', strtotime($id_validation['upload_at']));
                                                }
                                                ?>
                                            </p>
                                        </div>
                                        <div class="hidden sm:block mt-2 sm:mt-0 bg-cream rounded-lg p-2 text-xs">
                                            <p class="text-gray-700 flex items-center">
                                                <i class="far fa-clock mr-1 text-gold text-xs"></i>
                                                <?php echo time_elapsed_string($id_validation['upload_at']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 flex flex-wrap gap-2 items-center justify-between">
                                        <?php if (!empty($id_validation['image_path'])): ?>
                                            <button onclick="viewIdImage('<?php echo htmlspecialchars($id_validation['image_path']); ?>')" 
    class="<?php 
        if($id_validation['is_validated'] === 'no') echo 'bg-yellow-600 hover:bg-yellow-700';
        elseif($id_validation['is_validated'] === 'valid') echo 'bg-green-600 hover:bg-green-700';
        elseif($id_validation['is_validated'] === 'denied') echo 'bg-red-600 hover:bg-red-700';
        else echo 'bg-gray-600 hover:bg-gray-700';
    ?> text-white px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center">
    <i class="fas fa-image mr-1 text-xs"></i> View ID
</button>
                                        <?php else: ?>
                                            <span class="text-gray-500 text-xs">No ID image available</span>
                                        <?php endif; ?>
                                        
                                        <!-- Timestamp for Desktop -->
                                        <div class="text-xs text-gray-500 flex items-center">
                                            <i class="fas fa-history mr-1 text-xs"></i> 
                                            <?php echo time_elapsed_string($id_validation['upload_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination - made more touch friendly -->
            <?php if ($total_filtered_pages > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="flex items-center space-x-2">
                    <!-- Previous Button - Larger touch target -->
                    <a href="?filter=<?php echo $current_filter; ?>&page=<?php echo max(1, $page - 1); ?>" 
                       class="<?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?> px-3 py-2 bg-white rounded-md border border-gray-300 text-navy hover:bg-gray-50 transition text-xs">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    
                    <!-- Page Numbers - Better touch targets -->
                    <?php 
                    // For small screens - show fewer page links
                    $window_size = isset($_COOKIE['viewportWidth']) && intval($_COOKIE['viewportWidth']) < 640 ? 1 : 2;
                    
                    $start_page = max(1, $page - $window_size);
                    $end_page = min($total_filtered_pages, $page + $window_size);
                    
                    if ($start_page > 1) {
                        echo '<a href="?filter=' . $current_filter . '&page=1" class="px-3 py-2 bg-white rounded-md border border-gray-300 text-navy hover:bg-gray-50 transition text-xs">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="px-2 py-2 text-gray-500 text-xs">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active_class = ($i == $page) ? 'bg-yellow-600 text-white border-yellow-600' : 'bg-white text-navy border-gray-300 hover:bg-gray-50';
                        echo '<a href="?filter=' . $current_filter . '&page=' . $i . '" class="px-3 py-2 rounded-md border ' . $active_class . ' transition text-xs">' . $i . '</a>';
                    }
                    
                    if ($end_page < $total_filtered_pages) {
                        if ($end_page < $total_filtered_pages - 1) {
                            echo '<span class="px-2 py-2 text-gray-500 text-xs">...</span>';
                        }
                        echo '<a href="?filter=' . $current_filter . '&page=' . $total_filtered_pages . '" class="px-3 py-2 bg-white rounded-md border border-gray-300 text-navy hover:bg-gray-50 transition text-xs">' . $total_filtered_pages . '</a>';
                    }
                    ?>
                    
                    <!-- Next Button - Larger touch target -->
                    <a href="?filter=<?php echo $current_filter; ?>&page=<?php echo min($total_filtered_pages, $page + 1); ?>" 
                       class="<?php echo $page >= $total_filtered_pages ? 'opacity-50 cursor-not-allowed' : ''; ?> px-3 py-2 bg-white rounded-md border border-gray-300 text-navy hover:bg-gray-50 transition text-xs">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </nav>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>

    <!-- Search No Results State -->
    <div id="noSearchResults" class="hidden bg-white rounded-md shadow-md p-6 text-center">
        <div class="rounded-full bg-gray-100 p-3 inline-flex items-center justify-center mb-3">
            <i class="fas fa-search text-gray-400 text-xl"></i>
        </div>
        <h3 class="text-lg font-hedvig text-navy mb-1">No notifications found</h3>
        <p class="text-gray-500 text-sm">Try adjusting your search criteria or check back later.</p>
    </div>
    
    <!-- Add viewport detection for pagination logic -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Store viewport width for pagination adjustment
        document.cookie = "viewportWidth=" + window.innerWidth;
        
        // Handle window resize
        window.addEventListener('resize', function() {
            document.cookie = "viewportWidth=" + window.innerWidth;
        });
    });
    </script>
</div>

        <!-- ID Image Modal -->
<div id="idImageModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <!-- Blurred Backdrop (added this div) -->
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
    
    <div class="flex items-center justify-center min-h-screen">
        <div class="rounded-lg max-w-2xl w-full mx-4 relative overflow-hidden z-10"> <!-- Added z-10 -->
            <!-- Dynamic Header -->
            <div id="idModalHeader" class="px-4 py-3 bg-yellow-600">
                <h3 class="text-lg font-semibold text-white flex items-center justify-between">
                    <span>
                        <i class="fas fa-id-card mr-2"></i>
                        ID Verification
                    </span>
                    <button onclick="closeIdImageModal()" class="text-white hover:text-gray-200 ml-4">
                        <i class="fas fa-times"></i>
                    </button>
                </h3>
            </div>
            
            <!-- Content -->
            <div class="bg-white p-4">
                <div class="flex justify-center">
                    <img id="modalIdImage" src="" alt="Uploaded ID" class="max-w-full max-h-[70vh] rounded border border-gray-200">
                </div>
                <div class="mt-4 text-center">
                    <button onclick="closeIdImageModal()" class="bg-navy text-white px-4 py-2 rounded-md text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
        
        <script>
        function viewIdImage(imagePath) {
    // Get the ID validation status from PHP
    const idStatus = '<?php echo $id_validation ? $id_validation["is_validated"] : "no"; ?>';
    
    // Set modal header color based on status
    const modalHeader = document.getElementById('idModalHeader');
    
    // First remove all possible status color classes
    modalHeader.classList.remove(
        'bg-yellow-600', 
        'bg-green-600', 
        'bg-red-600', 
        'bg-gray-600'
    );
    
    // Then add the appropriate class based on status
    switch(idStatus) {
        case 'no': // Pending
            modalHeader.classList.add('bg-yellow-600');
            break;
        case 'valid': // Accepted
            modalHeader.classList.add('bg-green-600');
            break;
        case 'denied': // Declined
            modalHeader.classList.add('bg-red-600');
            break;
        default: // Not submitted/unknown
            modalHeader.classList.add('bg-gray-600');
    }

    // Set the image source and show modal
    document.getElementById('modalIdImage').src = imagePath;
    document.getElementById('idImageModal').classList.remove('hidden');
}

// Helper function to get status text
function getStatusText(status) {
    switch(status) {
        case 'no':
            return 'Pending';
        case 'valid':
            return 'Accepted';
        case 'denied':
            return 'Declined';
        default:
            return 'Not Submitted';
    }
}
 function closeIdImageModal() {
            document.getElementById('idImageModal').classList.add('hidden');
        }
        </script>
        
        <!-- Booking Details Modal -->
<div id="bookingDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-yellow-600 border-gray-200">
      <h3 id="modal-package-title" class="text-lg sm:text-xl font-bold text-white flex items-center">
        Booking Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <!-- Content will be populated via JavaScript -->
      <div id="bookingDetailsContent">
        <!-- Details will be loaded here via JavaScript -->
      </div>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeModal()">
        Close
      </button>
    </div>
  </div>
</div>

    <?php include 'customService/chat_elements.html'; ?>
    
    <script>
    // Toggle Mobile Menu
    function toggleMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.remove('hidden');
            mobileMenu.classList.add('flex', 'flex-col');
        } else {
            mobileMenu.classList.add('hidden');
            mobileMenu.classList.remove('flex', 'flex-col');
        }
    }

    function viewBookingDetails(bookingId, status) {
    // Show loading state
    document.getElementById('bookingDetailsContent').innerHTML = '<div class="flex justify-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-sidebar-accent"></i></div>';
    
    // Show the modal
    document.getElementById('bookingDetailsModal').classList.remove('hidden');
    
    // Set modal header color based on status
    const modalHeader = document.querySelector('#bookingDetailsModal .bg-yellow-600');
    modalHeader.classList.remove('bg-yellow-600');
    
    switch(status.toLowerCase()) {
        case 'pending':
            modalHeader.classList.add('bg-yellow-600');
            break;
        case 'accepted':
            modalHeader.classList.add('bg-green-600');
            break;
        case 'declined':
            modalHeader.classList.add('bg-red-600');
            break;
        default:
            modalHeader.classList.add('bg-gray-600');
    }
    
    // Fetch booking details via AJAX
    fetch(`notification/get_booking_details.php?booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('bookingDetailsContent').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                        <p>${data.error}</p>
                    </div>
                `;
            } else {
                // Format the booking date
                const bookingDate = new Date(data.booking_date);
                const formattedBookingDate = bookingDate.toLocaleString();
                
                // Format dates (handle null values)
                const formatDate = (dateString) => {
                    if (!dateString) return 'Not set';
                    const date = new Date(dateString);
                    return date.toLocaleDateString();
                };
                
                // Determine icon color based on status
                let iconBgColor = 'bg-yellow-600';
                switch(data.status.toLowerCase()) {
                    case 'accepted':
                        iconBgColor = 'bg-green-600';
                        break;
                    case 'declined':
                        iconBgColor = 'bg-red-600';
                        break;
                    case 'pending':
                    default:
                        iconBgColor = 'bg-yellow-600';
                }
                
                // Create the HTML content
                let htmlContent = `
                    <!-- Booking ID and Status Banner -->
                    <div class="flex justify-between items-center mb-6 bg-gray-50 p-3 sm:p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="${iconBgColor} rounded-full p-2 mr-3">
                                <i class="fas fa-hashtag text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Booking ID</p>
                                <p class="font-semibold text-gray-800">${data.reference_code || 'N/A'}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Status</p>
                            <div>
                                <span class="px-3 py-1.5 text-sm font-medium rounded-full ${getStatusColorClass(data.status)} flex items-center">
                                    ${data.status}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Left Column -->
                        <div class="space-y-3 sm:space-y-4">
                            <!-- Deceased Information -->
                            <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
                                <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                                    Deceased Information
                                </h4>
                                <div class="space-y-2 sm:space-y-3">
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Full Name</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${data.deceased_lname}, ${data.deceased_fname} ${data.deceased_midname || ''} ${data.deceased_suffix || ''}</div>
                                    </div>
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Address</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${data.deceased_address}</div>
                                    </div>
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Birth Date</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${formatDate(data.deceased_birth)}</div>
                                    </div>
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Date of Death</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${formatDate(data.deceased_dodeath)}</div>
                                    </div>
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Date of Burial</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${formatDate(data.deceased_dateOfBurial)}</div>
                                    </div>
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">With Cremation</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${data.with_cremate === 'yes' ? 'Yes' : 'No'}</div>
                                    </div>
                                </div>
                            </div>
                        
                            <!-- Service Details -->
                            <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
                                <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                                    Service Details
                                </h4>
                                <div class="space-y-2 sm:space-y-3">
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Service</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${data.service_name}</div>
                                    </div>
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Branch</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${data.branch_name}</div>
                                    </div>
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Initial Price</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">₱${parseFloat(data.initial_price).toFixed(2)}</div>
                                    </div>
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Amount Paid</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">₱${parseFloat(data.amount_paid || 0).toFixed(2)}</div>
                                    </div>
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">Booking Date</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${formattedBookingDate}</div>
                                    </div>
                                    ${data.accepted_date ? `
                                    <div class="flex flex-wrap">
                                        <div class="w-1/3 text-sm text-gray-500">${data.status} Date</div>
                                        <div class="w-2/3 font-medium text-gray-800 break-words">${new Date(data.accepted_date).toLocaleString()}</div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-3 sm:space-y-4">
                            <!-- Documents -->
                            <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
                                <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                                    Documents
                                </h4>
                                
                                <!-- Death Certificate -->
                                <div class="mb-4 sm:mb-5">
                                    <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                                        Death Certificate
                                    </h5>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        ${data.deathcert_url ? `
                                            <div class="relative bg-gray-100 p-1">
                                                <img src="booking/${data.deathcert_url}" alt="Death Certificate" class="mx-auto rounded-md max-h-48 object-contain" />
                                                <div class="absolute top-2 right-2">
                                                    <button class="bg-white rounded-full p-1 shadow-md hover:bg-gray-100 transition-colors duration-200" title="View Full Size" 
                                                    onclick="window.open('booking/${data.deathcert_url}', '_blank')">
                                                        <i class="fas fa-search-plus text-blue-600"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        ` : `
                                            <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
                                                <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
                                                <p class="text-gray-500 text-center">No death certificate has been uploaded yet.</p>
                                            </div>
                                        `}
                                    </div>
                                </div>
                                
                                <!-- Payment Proof -->
                                <div>
                                    <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                                        Payment Proof
                                    </h5>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        ${data.payment_url ? `
                                            <div class="relative bg-gray-100 p-1">
                                                <img src="booking/${data.payment_url}" alt="Payment Proof" class="mx-auto rounded-md max-h-48 object-contain" />
                                                <div class="absolute top-2 right-2">
                                                    <button class="bg-white rounded-full p-1 shadow-md hover:bg-gray-100 transition-colors duration-200" title="View Full Size"
                                                    onclick="window.open('booking/${data.payment_url}', '_blank')">
                                                        <i class="fas fa-search-plus text-blue-600"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        ` : `
                                            <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
                                                <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
                                                <p class="text-gray-500 text-center">No payment proof has been uploaded yet.</p>
                                            </div>
                                        `}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('bookingDetailsContent').innerHTML = htmlContent;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('bookingDetailsContent').innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                    <p>Failed to load booking details. Please try again.</p>
                </div>
            `;
        });
}

function closeModal() {
    document.getElementById('bookingDetailsModal').classList.add('hidden');
}

// Helper function for status colors
function getStatusColorClass(status) {
    switch(status.toLowerCase()) {
        case 'pending':
            return 'bg-yellow-100 text-sidebar-accent';
        case 'accepted':
        case 'approved':
            return 'bg-green-100 text-green-700';
        case 'completed':
            return 'bg-blue-100 text-blue-700';
        case 'declined':
        case 'cancelled':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

function closeBookingDetailsModal() {
    document.getElementById('bookingDetailsModal').classList.add('hidden');
    document.getElementById('bookingDetailsModal').classList.remove('flex');
}

function getStatusClass(status) {
    switch (status) {
        case 'Pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'Accepted':
            return 'bg-green-100 text-green-800';
        case 'Declined':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const notificationsContainer = document.getElementById('notificationsContainer');
    const noSearchResults = document.getElementById('noSearchResults');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const notifications = notificationsContainer.querySelectorAll('.notification-animate');
        let foundResults = false;
        
        notifications.forEach(notification => {
            const notificationText = notification.textContent.toLowerCase();
            if (notificationText.includes(searchTerm)) {
                notification.style.display = 'block';
                foundResults = true;
            } else {
                notification.style.display = 'none';
            }
        });
        
        if (!foundResults && searchTerm) {
            noSearchResults.style.display = 'block';
        } else {
            noSearchResults.style.display = 'none';
        }
    });
</script>


    <!-- Footer -->
    <footer class="bg-black font-playfair text-white py-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <h3 class="text-yellow-600 text-2xl mb-4">GrievEase</h3>
                    <p class="text-gray-300 mb-4">Providing dignified funeral services with compassion and respect since 1980.</p>
                    <div class="flex space-x-4">
                        
                    </div>
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
                        <li class=" flex items-center">
                        <a href="https://web.facebook.com/vjayrelovafuneralservices" class=" hover:text-white transition">
                            <i class="fab fa-facebook-f mr-2 text-yellow-600"></i>
                            <span> VJay Relova Funeral Services</span>
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
                    <a href="privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                    <a href="#" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>