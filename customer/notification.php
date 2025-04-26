<?php
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
    'declined' => 0
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

// Get requested page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 5;
$total_pages = ceil(count($bookings) / $items_per_page);
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Filter and paginate bookings
$filtered_bookings = [];
foreach ($bookings as $booking) {
    if ($current_filter === 'all' || 
        ($current_filter === 'pending' && $booking['status'] === 'Pending') ||
        ($current_filter === 'accepted' && $booking['status'] === 'Accepted') ||
        ($current_filter === 'declined' && $booking['status'] === 'Declined')) {
        $filtered_bookings[] = $booking;
    }
}

$total_filtered = count($filtered_bookings);
$total_filtered_pages = ceil($total_filtered / $items_per_page);

// Get current page items
$current_page_bookings = array_slice($filtered_bookings, ($page - 1) * $items_per_page, $items_per_page);
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
            font-family: 'Inter', sans-serif;
        }
        .notification-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        :root {
            --navbar-height: 64px;
            --section-spacing: 4rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notification-animate {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
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
        
        .hover-scale {
            transition: transform 0.3s ease;
        }
        
        .hover-scale:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
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
<div class="container mx-auto px-4 py-6 max-w-screen-xl mt-[var(--navbar-height)]">
    <!-- Page Header -->
    <div class="bg-gradient-to-b from-yellow-600/10 to-transparent rounded-lg py-4 px-3 mb-4 shadow-sm">
        <div class="max-w-2xl mx-auto text-center">
            <h1 class="text-3xl md:text-5xl font-hedvig text-navy mb-1">Notifications</h1>
            <p class="text-dark text-sm md:text-lg">Stay updated with important information about your services.</p>
            <div class="w-12 h-1 bg-yellow-600 mx-auto mt-1 rounded-full"></div>
        </div>
    </div>

    <!-- Dashboard Layout -->
    <div class="flex flex-col lg:flex-row gap-4">
        <!-- Left Sidebar: Filter Controls -->
        <div class="lg:w-1/4 mb-4 lg:mb-0">
            <!-- Mobile Filter Toggle Button -->
            <button id="mobileFilterToggle" class="lg:hidden w-full bg-white text-navy px-4 py-3 rounded-xl shadow-md mb-2 flex items-center justify-between">
                <span class="flex items-center font-medium">
                    <i class="fas fa-filter mr-2"></i> Filter Notifications
                </span>
                <i class="fas fa-chevron-down transition-transform" id="filterChevron"></i>
            </button>
            
            <div id="filterContainer" class="bg-white rounded-xl shadow-md p-4 sticky top-20 lg:block hidden">
                <h2 class="text-navy text-lg mb-3 font-hedvig">Filter Notifications</h2>
                
                <!-- Filter Buttons -->
                <div class="space-y-2">
                    <a href="?filter=all" 
                       class="w-full <?php echo $current_filter === 'all' ? 'bg-navy text-white filter-active' : 'bg-white hover:bg-navy/5 border border-input-border text-navy'; ?> px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-between transition-all duration-200">
                        <span class="flex items-center">
                            <i class="fas fa-inbox mr-2"></i>
                            <span>All Notifications</span>
                        </span>
                        <span class="<?php echo $current_filter === 'all' ? 'bg-white text-navy' : 'bg-navy/10 text-navy'; ?> w-6 h-6 rounded-full flex items-center justify-center text-xs"><?php echo $notifications_count['total']; ?></span>
                    </a>
                    
                    <a href="?filter=pending" 
                       class="w-full <?php echo $current_filter === 'pending' ? 'bg-yellow-600 text-white filter-active' : 'bg-white hover:bg-yellow-600/5 border border-input-border text-navy'; ?> px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-between transition-all duration-200">
                        <span class="flex items-center">
                            <span class="notification-dot <?php echo $current_filter === 'pending' ? 'bg-white' : 'bg-yellow-600'; ?> mr-2"></span>
                            <span>Pending</span>
                        </span>
                        <span class="<?php echo $current_filter === 'pending' ? 'bg-white text-yellow-600' : 'bg-yellow-600/10 text-yellow-600'; ?> w-6 h-6 rounded-full flex items-center justify-center text-xs"><?php echo $notifications_count['pending']; ?></span>
                    </a>
                    
                    <a href="?filter=accepted" 
                       class="w-full <?php echo $current_filter === 'accepted' ? 'bg-success text-white filter-active' : 'bg-white hover:bg-success/5 border border-input-border text-navy'; ?> px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-between transition-all duration-200">
                        <span class="flex items-center">
                            <span class="notification-dot <?php echo $current_filter === 'accepted' ? 'bg-white' : 'bg-success'; ?> mr-2"></span>
                            <span>Accepted</span>
                        </span>
                        <span class="<?php echo $current_filter === 'accepted' ? 'bg-white text-success' : 'bg-success/10 text-success'; ?> w-6 h-6 rounded-full flex items-center justify-center text-xs"><?php echo $notifications_count['accepted']; ?></span>
                    </a>
                    
                    <a href="?filter=declined" 
                       class="w-full <?php echo $current_filter === 'declined' ? 'bg-error text-white filter-active' : 'bg-white hover:bg-error/5 border border-input-border text-navy'; ?> px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-between transition-all duration-200">
                        <span class="flex items-center">
                            <span class="notification-dot <?php echo $current_filter === 'declined' ? 'bg-white' : 'bg-error'; ?> mr-2"></span>
                            <span>Declined</span>
                        </span>
                        <span class="<?php echo $current_filter === 'declined' ? 'bg-white text-error' : 'bg-error/10 text-error'; ?> w-6 h-6 rounded-full flex items-center justify-center text-xs"><?php echo $notifications_count['declined']; ?></span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Right Content: Notifications List -->
        <div class="lg:w-3/4">
            <!-- Search Bar -->
            <div class="mb-4 relative">
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search notifications..." class="w-full pl-10 pr-4 py-2 bg-white border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600/50 shadow-input transition-all">
                    <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <?php if (empty($current_page_bookings)): ?>
            <!-- No Notifications Message -->
            <div class="bg-white rounded-xl shadow-md p-6 text-center">
                <div class="rounded-full bg-gray-100 p-4 inline-flex items-center justify-center mb-3 mx-auto">
                    <i class="fas fa-bell-slash text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-2">No notifications found</h3>
                <p class="text-gray-500 text-sm max-w-lg mx-auto">
                    <?php 
                    if ($current_filter !== 'all') {
                        echo "You don't have any " . strtolower($current_filter) . " notifications. Check back later or select a different filter.";
                    } else {
                        echo "You don't have any notifications yet. Updates about your services will appear here.";
                    }
                    ?>
                </p>
                <div class="mt-4">
                    <a href="?" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-600/90 transition">
                        <i class="fas fa-sync-alt mr-1"></i> Refresh
                    </a>
                </div>
            </div>
            <?php else: ?>
            
            <!-- Notifications Container -->
            <div class="space-y-3" id="notificationsContainer">
                <?php foreach ($current_page_bookings as $booking): ?>
                    <?php
                    // Define notification style based on status
                    $status_color = '';
                    $status_bg = '';
                    $status_icon = '';
                    
                    switch ($booking['status']) {
                        case 'Pending':
                            $status_color = 'border-yellow-600';
                            $status_bg = 'bg-yellow-600/10';
                            $status_icon = 'fas fa-clock';
                            $status_text_color = 'text-yellow-600';
                            $btn_color = 'bg-yellow-600 hover:bg-yellow-700';
                            $status_badge_bg = 'bg-yellow-600/20';
                            break;
                        case 'Accepted':
                            $status_color = 'border-success';
                            $status_bg = 'bg-success/10';
                            $status_icon = 'fas fa-check-circle';
                            $status_text_color = 'text-success';
                            $btn_color = 'bg-success hover:bg-success/90';
                            $status_badge_bg = 'bg-success/20';
                            break;
                        case 'Declined':
                            $status_color = 'border-error';
                            $status_bg = 'bg-error/10';
                            $status_icon = 'fas fa-times-circle';
                            $status_text_color = 'text-error';
                            $btn_color = 'bg-error hover:bg-error/90';
                            $status_badge_bg = 'bg-error/20';
                            break;
                    }
                    ?>
                    
                    <div class="bg-white border-l-4 <?php echo $status_color; ?> rounded-xl shadow-md overflow-hidden notification-animate hover:shadow-lg transition-all duration-300">
                        <div class="flex flex-col md:flex-row">
                            
                            <!-- Notification Content -->
                            <div class="flex-1 py-4 px-4 md:py-5 md:px-7">
                                <div class="flex flex-col md:flex-row justify-between">
                                    <div>
                                        <span class="<?php echo $status_badge_bg; ?> <?php echo $status_text_color; ?> text-xs px-2 py-1 rounded-full inline-flex items-center">
                                            <i class="<?php echo $status_icon; ?> mr-1 text-xs"></i>
                                            <?php echo htmlspecialchars($booking['status']); ?>
                                        </span>
                                        <h3 class="text-navy text-base md:text-lg font-hedvig mt-1">
                                            <?php echo htmlspecialchars($booking['service_name']); ?>
                                        </h3>
                                        <p class="text-gray-600 text-sm mt-1 flex items-center">
                                            <i class="fas fa-map-marker-alt mr-1 text-gold"></i> 
                                            <?php echo htmlspecialchars($booking['branch_name']); ?>
                                        </p>
                                    </div>
                                    <div class="mt-2 md:mt-0 bg-cream rounded-lg p-2 text-xs">
                                        <p class="text-gray-700 flex items-center mb-1">
                                            <i class="far fa-calendar mr-1 text-gold"></i>
                                            <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                        </p>
                                        <p class="text-gray-700 flex items-center">
                                            <i class="far fa-clock mr-1 text-gold"></i>
                                            <?php echo date('h:i A', strtotime($booking['booking_date'])); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Booking Details -->
                                <div class="mt-2 p-2 bg-navy/5 rounded-lg text-sm">
                                    <p class="text-gray-700">
                                        <?php 
                                            // Create a description based on status
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
                                
                                <!-- Action Buttons -->
                                <div class="mt-3 flex flex-wrap gap-2 items-center justify-between">
                                    <button onclick="viewBookingDetails(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['status']; ?>')" 
                                        class="<?php echo $btn_color; ?> text-white px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center">
                                        <i class="fas fa-eye mr-1"></i> View Details
                                    </button>
                                    
                                    <!-- Timestamp -->
                                    <div class="text-xs text-gray-500 flex items-center">
                                        <i class="fas fa-history mr-1"></i> 
                                        <?php echo time_elapsed_string($booking['booking_date']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_filtered_pages > 1): ?>
            <div class="mt-6 overflow-x-auto">
                <nav class="flex items-center justify-center">
                    <div class="inline-flex shadow-md rounded-lg overflow-hidden">
                        <!-- Previous Button -->
                        <a href="?filter=<?php echo $current_filter; ?>&page=<?php echo max(1, $page - 1); ?>" 
                           class="<?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?> px-2 md:px-3 py-2 bg-white text-navy hover:bg-gray-50 transition border-r border-gray-200 flex items-center text-xs">
                            <i class="fas fa-chevron-left text-xs mr-1"></i>
                            <span class="hidden sm:inline">Prev</span>
                        </a>
                        
                        <!-- Page Numbers -->
                        <div class="flex items-center">
                            <?php 
                            $start_page = max(1, $page - 1);
                            $end_page = min($total_filtered_pages, $page + 1);
                            
                            if ($start_page > 1) {
                                echo '<a href="?filter=' . $current_filter . '&page=1" class="w-8 h-8 bg-white flex items-center justify-center text-navy hover:bg-gray-50 transition border-r border-gray-200 text-xs">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="w-8 h-8 flex items-center justify-center text-gray-500 bg-white border-r border-gray-200 text-xs">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = ($i == $page) ? 'bg-yellow-600 text-white hover:bg-yellow-600' : 'bg-white text-navy hover:bg-gray-50';
                                echo '<a href="?filter=' . $current_filter . '&page=' . $i . '" class="w-8 h-8 ' . $active_class . ' flex items-center justify-center transition border-r border-gray-200 text-xs">' . $i . '</a>';
                            }
                            
                            if ($end_page < $total_filtered_pages) {
                                if ($end_page < $total_filtered_pages - 1) {
                                    echo '<span class="w-8 h-8 flex items-center justify-center text-gray-500 bg-white border-r border-gray-200 text-xs">...</span>';
                                }
                                echo '<a href="?filter=' . $current_filter . '&page=' . $total_filtered_pages . '" class="w-8 h-8 bg-white flex items-center justify-center text-navy hover:bg-gray-50 transition border-r border-gray-200 text-xs">' . $total_filtered_pages . '</a>';
                            }
                            ?>
                        </div>
                        
                        <!-- Next Button -->
                        <a href="?filter=<?php echo $current_filter; ?>&page=<?php echo min($total_filtered_pages, $page + 1); ?>" 
                           class="<?php echo $page >= $total_filtered_pages ? 'opacity-50 cursor-not-allowed' : ''; ?> px-2 md:px-3 py-2 bg-white text-navy hover:bg-gray-50 transition flex items-center text-xs">
                            <span class="hidden sm:inline">Next</span>
                            <i class="fas fa-chevron-right text-xs ml-1"></i>
                        </a>
                    </div>
                </nav>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add this JavaScript at the end of your document -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileFilterToggle = document.getElementById('mobileFilterToggle');
    const filterContainer = document.getElementById('filterContainer');
    const filterChevron = document.getElementById('filterChevron');
    
    // Toggle filter visibility on mobile
    if (mobileFilterToggle) {
        mobileFilterToggle.addEventListener('click', function() {
            filterContainer.classList.toggle('hidden');
            filterChevron.classList.toggle('rotate-180');
        });
    }
    
    // Show filters by default on large screens
    function handleResize() {
        if (window.innerWidth >= 1024) { // lg breakpoint
            filterContainer.classList.remove('hidden');
        } else {
            filterContainer.classList.add('hidden');
        }
    }
    
    // Run on page load
    handleResize();
    
    // Listen for window resize events
    window.addEventListener('resize', handleResize);
});
</script>

    <!-- Search No Results State - Enhanced -->
    <div id="noSearchResults" class="hidden bg-white rounded-xl shadow-md p-10 text-center max-w-2xl mx-auto mt-8">
        <div class="rounded-full bg-gray-100 p-6 inline-flex items-center justify-center mb-5 mx-auto">
            <i class="fas fa-search text-gray-400 text-4xl"></i>
        </div>
        <h3 class="text-2xl font-hedvig text-navy mb-3">No matching notifications</h3>
        <p class="text-gray-500 mb-5">We couldn't find any notifications matching your search. Try adjusting your search terms or filters.</p>
        <button onclick="document.getElementById('searchInput').value = ''; document.getElementById('searchInput').dispatchEvent(new Event('input'));" 
                class="px-5 py-2 bg-navy text-white rounded-lg hover:bg-navy/90 transition flex items-center mx-auto">
            <i class="fas fa-times mr-2"></i> Clear Search
        </button>
    </div>

    <!-- Booking Details Modal - Enhanced design -->
    <div id="bookingDetailsModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Modal content -->
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="absolute top-4 right-4">
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="bg-white px-6 pt-8 pb-6">
                    <div class="sm:flex sm:items-center mb-6">
                        <div class="bg-navy/10 rounded-full p-3 mr-4">
                            <i class="fas fa-info-circle text-navy text-xl"></i>
                        </div>
                        <h3 class="text-2xl leading-6 font-hedvig text-navy">Booking Details</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="bookingDetailsContent">
                        <!-- Details will be loaded here via JavaScript -->
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="button" onclick="closeModal()" class="inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-navy font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-600 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'customService/chat_elements.html'; ?>
    
    <script>
    // Toggle Mobile Menu (Keeping original function)
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

    // Enhanced viewBookingDetails function with better loading animation
    function viewBookingDetails(bookingId, status) {
        // Show loading state with improved animation
        document.getElementById('bookingDetailsContent').innerHTML = `
            <div class="col-span-2 flex flex-col items-center justify-center py-10">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-600"></div>
                <p class="mt-3 text-gray-600">Loading details...</p>
            </div>
        `;
        
        // Show the modal with fade-in effect
        const modal = document.getElementById('bookingDetailsModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Fetch booking details via AJAX
        fetch(`notification/get_booking_details.php?booking_id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('bookingDetailsContent').innerHTML = `
                        <div class="col-span-2 text-center py-8">
                            <div class="bg-error/10 rounded-full p-4 mx-auto w-16 h-16 flex items-center justify-center mb-4">
                                <i class="fas fa-exclamation-circle text-error text-2xl"></i>
                            </div>
                            <p class="text-error">${data.error}</p>
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
                    
                    // Get status color classes
                    let statusColorClass = '';
                    let statusBgClass = '';
                    let statusIcon = '';
                    
                    switch(data.status) {
                        case 'Pending':
                            statusColorClass = 'text-yellow-600';
                            statusBgClass = 'bg-yellow-600/20';
                            statusIcon = 'fas fa-clock';
                            break;
                        case 'Accepted':
                            statusColorClass = 'text-success';
                            statusBgClass = 'bg-success/20';
                            statusIcon = 'fas fa-check-circle';
                            break;
                        case 'Declined':
                            statusColorClass = 'text-error';
                            statusBgClass = 'bg-error/20';
                            statusIcon = 'fas fa-times-circle';
                            break;
                        default:
                            statusColorClass = 'text-gray-700';
                            statusBgClass = 'bg-gray-200';
                            statusIcon = 'fas fa-info-circle';
                    }
                    
                    // Create the HTML content with enhanced styling
                    let htmlContent = `
                        <div class="col-span-2">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-user text-navy bg-navy/10 p-2 rounded-full mr-3"></i>
                                <h4 class="font-semibold text-navy text-lg">Deceased Information</h4>
                            </div>
                            <div class="bg-cream p-4 rounded-lg mb-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="mb-2"><span class="font-medium text-navy">Full Name:</span></p>
                                        <p class="bg-white px-3 py-2 rounded-md">${data.deceased_lname}, ${data.deceased_fname} ${data.deceased_midname || ''}</p>
                                    </div>
                                    <div>
                                        <p class="mb-2"><span class="font-medium text-navy">Date of Death:</span></p>
                                        <p class="bg-white px-3 py-2 rounded-md">${formatDate(data.date_of_death)}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-2">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-calendar-alt text-navy bg-navy/10 p-2 rounded-full mr-3"></i>
                                <h4 class="font-semibold text-navy text-lg">Service Details</h4>
                            </div>
                            <div class="bg-cream p-4 rounded-lg mb-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="mb-2"><span class="font-medium text-navy">Service Type:</span></p>
                                        <p class="bg-white px-3 py-2 rounded-md">${data.service_name}</p>
                                    </div>
                                    <div>
                                        <p class="mb-2"><span class="font-medium text-navy">Branch:</span></p>
                                        <p class="bg-white px-3 py-2 rounded-md">${data.branch_name}</p>
                                    </div>
                                    <div>
                                        <p class="mb-2"><span class="font-medium text-navy">Date & Time:</span></p>
                                        <p class="bg-white px-3 py-2 rounded-md">${formattedBookingDate}</p>
                                    </div>
                                    <div>
                                        <p class="mb-2"><span class="font-medium text-navy">Status:</span></p>
                                        <p class="bg-white px-3 py-2 rounded-md">
                                            <span class="${statusBgClass} ${statusColorClass} inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium">
                                                <i class="${statusIcon} mr-1"></i> ${data.status}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-2">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-comment-alt text-navy bg-navy/10 p-2 rounded-full mr-3"></i>
                                <h4 class="font-semibold text-navy text-lg">Additional Information</h4>
                            </div>
                            <div class="bg-cream p-4 rounded-lg">
                                <p class="mb-2"><span class="font-medium text-navy">Special Instructions:</span></p>
                                <p class="bg-white px-3 py-2 rounded-md min-h-[60px]">${data.special_instructions || 'No special instructions provided.'}</p>
                                
                                ${data.admin_message ? `
                                <div class="mt-4">
                                    <p class="mb-2"><span class="font-medium text-navy">Message from Staff:</span></p>
                                    <p class="bg-white px-3 py-2 rounded-md">${data.admin_message}</p>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('bookingDetailsContent').innerHTML = htmlContent;
                }
            })
            .catch(error => {
                document.getElementById('bookingDetailsContent').innerHTML = `
                    <div class="col-span-2 text-center py-8">
                        <div class="bg-error/10 rounded-full p-4 mx-auto w-16 h-16 flex items-center justify-center mb-4">
                            <i class="fas fa-exclamation-triangle text-error text-2xl"></i>
                        </div>
                        <p class="text-error">Failed to load booking details. Please try again.</p>
                    </div>
                `;
                console.error('Error fetching booking details:', error);
            });
    }

    // Close modal function
    function closeModal() {
        const modal = document.getElementById('bookingDetailsModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const notificationsContainer = document.getElementById('notificationsContainer');
        const noSearchResults = document.getElementById('noSearchResults');
        
        if (searchInput && notificationsContainer) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                let found = false;
                
                // Get all notification cards
                const notificationCards = notificationsContainer.querySelectorAll('.notification-animate');
                
                // Loop through each card and check for matches
                notificationCards.forEach(card => {
                    const cardText = card.textContent.toLowerCase();
                    
                    if (cardText.includes(searchTerm)) {
                        card.style.display = 'block';
                        found = true;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show/hide no results message
                if (!found && searchTerm !== '') {
                    noSearchResults.classList.remove('hidden');
                } else {
                    noSearchResults.classList.add('hidden');
                }
            });
        }
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('bookingDetailsModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    
    // Add keyboard support for closing modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
    </script>


    <!-- Footer -->
<footer class="bg-black text-white py-12 mt-16">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div>
                <div class="flex items-center mb-4">
                    <img src="..\Landing_Page/Landing_images/logo.png" alt="Logo" class="h-[42px] w-[38px]">
                    <span class="text-yellow-600 text-2xl ml-2">GrievEase</span>
                </div>
                <p class="text-gray-400 mb-4">Providing compassionate and comprehensive funeral services to honor your loved ones with dignity and respect.</p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-semibold mb-4 text-white">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="index.php" class="text-gray-400 hover:text-white transition">Home</a></li>
                    <li><a href="about.php" class="text-gray-400 hover:text-white transition">About Us</a></li>
                    <li><a href="lifeplan.php" class="text-gray-400 hover:text-white transition">Life Plan</a></li>
                    <li><a href="traditional_funeral.php" class="text-gray-400 hover:text-white transition">Traditional Funeral</a></li>
                    <li><a href="faqs.php" class="text-gray-400 hover:text-white transition">FAQs</a></li>
                </ul>
            </div>
            
            <!-- Services -->
            <div>
                <h3 class="text-lg font-semibold mb-4 text-white">Our Services</h3>
                <ul class="space-y-2">
                    <li><a href="traditional_funeral.php" class="text-gray-400 hover:text-white transition">Traditional Funeral</a></li>
                    <li><a href="lifeplan.php" class="text-gray-400 hover:text-white transition">Life Plan Packages</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Memorial Services</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Cremation Services</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Grief Counseling</a></li>
                </ul>
            </div>
            
            <!-- Contact -->
            <div>
                <h3 class="text-lg font-semibold mb-4 text-white">Contact Us</h3>
                <ul class="space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt mt-1 mr-2 text-yellow-600"></i>
                        <span class="text-gray-400">4671 Sugar Camp Road, Owatonna, MN 55060</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-phone-alt mt-1 mr-2 text-yellow-600"></i>
                        <span class="text-gray-400">(507) 475-6094</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-envelope mt-1 mr-2 text-yellow-600"></i>
                        <span class="text-gray-400">support@grievease.com</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-clock mt-1 mr-2 text-yellow-600"></i>
                        <span class="text-gray-400">Available 24/7</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> GrievEase. All rights reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>