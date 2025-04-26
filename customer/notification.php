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
    <div class="container mx-auto px-4 py-8 max-w-screen-xl mt-[var(--navbar-height)]">
        <!-- Page Header with subtle background -->
        <div class="bg-gradient-to-b from-yellow-600/10 to-transparent rounded-xl py-8 px-6 mb-10">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-hedvig text-navy mb-3">Notifications</h1>
                <p class="text-dark text-lg max-w-2xl mx-auto">Stay updated with important information about your services and arrangements.</p>
                <div class="w-16 h-1 bg-yellow-600 mx-auto mt-4"></div>
            </div>
        </div>

        <!-- Dashboard Layout -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Sidebar: Filter Controls -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded-xl shadow-md p-5 sticky top-20">
                    <h2 class="text-navy text-xl mb-4">Filter by Status</h2>
                    
                    <!-- Filter Buttons -->
                    <div class="space-y-2">
                        <a href="?filter=all" 
                           class="w-full <?php echo $current_filter === 'all' ? 'bg-navy text-white filter-active' : 'bg-white hover:bg-navy/10 border border-input-border text-navy'; ?> px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between group transition-all duration-200">
                            <span class="flex items-center">
                                <i class="fas fa-inbox mr-3"></i>
                                <span>All Notifications</span>
                            </span>
                            <span class="<?php echo $current_filter === 'all' ? 'bg-white text-navy' : 'bg-navy/20 text-navy'; ?> w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['total']; ?></span>
                        </a>
                        
                        <a href="?filter=pending" 
                           class="w-full <?php echo $current_filter === 'pending' ? 'bg-yellow-600 text-white filter-active' : 'bg-white hover:bg-yellow-600/10 border border-input-border text-navy'; ?> px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between group transition-all duration-200">
                            <span class="flex items-center">
                                <span class="notification-dot <?php echo $current_filter === 'pending' ? 'bg-white' : 'bg-yellow-600'; ?> mr-3"></span>
                                <span>Pending</span>
                            </span>
                            <span class="<?php echo $current_filter === 'pending' ? 'bg-white text-yellow-600' : 'bg-yellow-600/20 text-yellow-600'; ?> w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['pending']; ?></span>
                        </a>
                        
                        <a href="?filter=accepted" 
                           class="w-full <?php echo $current_filter === 'accepted' ? 'bg-success text-white filter-active' : 'bg-white hover:bg-success/10 border border-input-border text-navy'; ?> px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between group transition-all duration-200">
                            <span class="flex items-center">
                                <span class="notification-dot <?php echo $current_filter === 'accepted' ? 'bg-white' : 'bg-success'; ?> mr-3"></span>
                                <span>Accepted</span>
                            </span>
                            <span class="<?php echo $current_filter === 'accepted' ? 'bg-white text-success' : 'bg-success/20 text-success'; ?> w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['accepted']; ?></span>
                        </a>
                        
                        <a href="?filter=declined" 
                           class="w-full <?php echo $current_filter === 'declined' ? 'bg-error text-white filter-active' : 'bg-white hover:bg-error/10 border border-input-border text-navy'; ?> px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between group transition-all duration-200">
                            <span class="flex items-center">
                                <span class="notification-dot <?php echo $current_filter === 'declined' ? 'bg-white' : 'bg-error'; ?> mr-3"></span>
                                <span>Declined</span>
                            </span>
                            <span class="<?php echo $current_filter === 'declined' ? 'bg-white text-error' : 'bg-error/20 text-error'; ?> w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"><?php echo $notifications_count['declined']; ?></span>
                        </a>
                    </div>
                    
                
                </div>
            </div>
            
            <!-- Right Content: Notifications List -->
            <div class="lg:w-3/4">
                <!-- Search Bar -->
                <div class="mb-6 relative">
                    <input type="text" id="searchInput" placeholder="Search notifications..." class="w-full pl-10 pr-4 py-3 bg-white border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600/50">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                
                <?php if (empty($current_page_bookings)): ?>
                <!-- No Notifications Message -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="rounded-full bg-gray-100 p-4 inline-flex items-center justify-center mb-4">
                        <i class="fas fa-bell-slash text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-hedvig text-navy mb-2">No notifications found</h3>
                    <p class="text-gray-500">
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
                <div class="space-y-4" id="notificationsContainer">
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
                                break;
                            case 'Accepted':
                                $status_color = 'border-success';
                                $status_bg = 'bg-success/10';
                                $status_icon = 'fas fa-check-circle';
                                $status_text_color = 'text-success';
                                $btn_color = 'bg-success hover:bg-success/90';
                                break;
                                case 'Declined':
                                    $status_color = 'border-error';
                                    $status_bg = 'bg-error/10';
                                    $status_icon = 'fas fa-times-circle';
                                    $status_text_color = 'text-error';
                                    $btn_color = 'bg-error hover:bg-error/90';
                                    break;
                                }
                                ?>
                                
                                <div class="bg-white border-l-4 <?php echo $status_color; ?> rounded-lg shadow-md overflow-hidden notification-animate">
                                    <div class="flex flex-col md:flex-row">
                                        
                                        <!-- Notification Content -->
                                        <div class="flex-1 px-7 py-5">
                                            <div class="flex flex-col md:flex-row justify-between">
                                                <div>
                                                    <span class="<?php echo $status_text_color; ?> font-medium text-sm">
                                                        <?php echo htmlspecialchars($booking['status']); ?>
                                                    </span>
                                                    <h3 class="text-navy text-lg font-medium">
                                                        <?php echo htmlspecialchars($booking['service_name']); ?>
                                                    </h3>
                                                    <p class="text-gray-600 text-sm mt-1">
                                                        <i class="fas fa-map-marker-alt mr-1"></i> 
                                                        <?php echo htmlspecialchars($booking['branch_name']); ?>
                                                    </p>
                                                </div>
                                                <div class="mt-2 md:mt-0">
                                                    <p class="text-sm text-gray-500">
                                                        <i class="far fa-calendar mr-1"></i>
                                                        <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                                    </p>
                                                    <p class="text-sm text-gray-500">
                                                        <i class="far fa-clock mr-1"></i>
                                                        <?php echo date('h:i A', strtotime($booking['booking_date'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <!-- Booking Details -->
                                            <div class="mt-3">
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
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                <button onclick="viewBookingDetails(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['status']; ?>')" 
                                                    class="<?php echo $btn_color; ?> text-white px-4 py-2 rounded-lg text-sm hover:shadow-md transition">
                                                    <i class="fas fa-eye mr-1"></i> View Details
                                                </button>
                                            </div>
                                            
                                            <!-- Timestamp -->
                                            <div class="mt-3 text-xs text-gray-500">
                                                <i class="fas fa-history mr-1"></i> 
                                                <?php echo time_elapsed_string($booking['booking_date']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_filtered_pages > 1): ?>
                                <div class="mt-8 flex justify-center">
                                    <nav class="flex items-center space-x-1">
                                        <!-- Previous Button -->
                                        <a href="?filter=<?php echo $current_filter; ?>&page=<?php echo max(1, $page - 1); ?>" 
                                           class="<?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?> px-3 py-2 bg-white rounded-md border border-gray-300 text-navy hover:bg-gray-50 transition">
                                            <i class="fas fa-chevron-left text-sm"></i>
                                        </a>
                                        
                                        <!-- Page Numbers -->
                                        <?php 
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_filtered_pages, $page + 2);
                                        
                                        if ($start_page > 1) {
                                            echo '<a href="?filter=' . $current_filter . '&page=1" class="px-3 py-2 bg-white rounded-md border border-gray-300 text-navy hover:bg-gray-50 transition">1</a>';
                                            if ($start_page > 2) {
                                                echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                            }
                                        }
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            $active_class = ($i == $page) ? 'bg-yellow-600 text-white border-yellow-600' : 'bg-white text-navy border-gray-300 hover:bg-gray-50';
                                            echo '<a href="?filter=' . $current_filter . '&page=' . $i . '" class="px-3 py-2 rounded-md border ' . $active_class . ' transition">' . $i . '</a>';
                                        }
                                        
                                        if ($end_page < $total_filtered_pages) {
                                            if ($end_page < $total_filtered_pages - 1) {
                                                echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                            }
                                            echo '<a href="?filter=' . $current_filter . '&page=' . $total_filtered_pages . '" class="px-3 py-2 bg-white rounded-md border border-gray-300 text-navy hover:bg-gray-50 transition">' . $total_filtered_pages . '</a>';
                                        }
                                        ?>
                                        
                                        <!-- Next Button -->
                                        <a href="?filter=<?php echo $current_filter; ?>&page=<?php echo min($total_filtered_pages, $page + 1); ?>" 
                                           class="<?php echo $page >= $total_filtered_pages ? 'opacity-50 cursor-not-allowed' : ''; ?> px-3 py-2 bg-white rounded-md border border-gray-300 text-navy hover:bg-gray-50 transition">
                                            <i class="fas fa-chevron-right text-sm"></i>
                                        </a>
                                    </nav>
                                </div>
                                <?php endif; ?>
                                
                                <?php endif; ?>
                                </div>
                                </div>
                                </div>

                            

                                <!-- Search No Results State -->
                                <div id="noSearchResults" class="hidden bg-white rounded-lg shadow-lg p-8 text-center">
                                    <div class="rounded-full bg-gray-100 p-4 inline-flex items-center justify-center mb-4">
                                        <i class="fas fa-search text-gray-400 text-3xl"></i>
                                    </div>
                                    <h3 class="text-xl font-hedvig text-navy mb-2">No notifications found</h3>
                                    <p class="text-gray-500">Try adjusting your search criteria or check back later.</p>
                                </div>

                                <!-- Booking Details Modal -->
                                <div id="bookingDetailsModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
                                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                        <!-- Background overlay -->
                                        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                                            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                                        </div>
                                        
                                        <!-- Modal content -->
                                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Booking Details</h3>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="bookingDetailsContent">
                                                    <!-- Details will be loaded here via JavaScript -->
                                                </div>
                                            </div>
                                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                    Close
                                                </button>
                                            </div>
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
    document.getElementById('bookingDetailsContent').innerHTML = '<div class="col-span-2 flex justify-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i></div>';
    
    // Show the modal
    document.getElementById('bookingDetailsModal').classList.remove('hidden');
    
    // Fetch booking details via AJAX
    fetch(`notification/get_booking_details.php?booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('bookingDetailsContent').innerHTML = `
                    <div class="col-span-2 text-center py-8 text-red-500">
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
                
                // Create the HTML content
                let htmlContent = `
                    <div class="col-span-2">
                        <h4 class="font-semibold text-gray-700 mb-2">Deceased Information</h4>
                        <div class="bg-gray-50 p-3 rounded-lg mb-4">
                            <p><span class="font-medium">Full Name:</span> ${data.deceased_lname}, ${data.deceased_fname} ${data.deceased_midname || ''} ${data.deceased_suffix || ''}</p>
                            <p><span class="font-medium">Address:</span> ${data.deceased_address}</p>
                            <p><span class="font-medium">Birth Date:</span> ${formatDate(data.deceased_birth)}</p>
                            <p><span class="font-medium">Date of Death:</span> ${formatDate(data.deceased_dodeath)}</p>
                            <p><span class="font-medium">Date of Burial:</span> ${formatDate(data.deceased_dateOfBurial)}</p>
                            <p><span class="font-medium">With Cremation:</span> ${data.with_cremate === 'yes' ? 'Yes' : 'No'}</p>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Service Details</h4>
                        <div class="bg-gray-50 p-3 rounded-lg mb-4">
                            <p><span class="font-medium">Service:</span> ${data.service_name}</p>
                            <p><span class="font-medium">Branch:</span> ${data.branch_name}</p>
                            <p><span class="font-medium">Initial Price:</span> ₱${parseFloat(data.initial_price).toFixed(2)}</p>
                            <p><span class="font-medium">Amount Paid:</span> ₱${parseFloat(data.amount_paid || 0).toFixed(2)}</p>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Booking Information</h4>
                        <div class="bg-gray-50 p-3 rounded-lg mb-4">
                            <p><span class="font-medium">Status:</span> <span class="px-2 py-1 rounded text-xs ${getStatusColorClass(data.status)}">${data.status}</span></p>
                            <p><span class="font-medium">Booking Date:</span> ${formattedBookingDate}</p>
                            ${data.accepted_date ? `<p><span class="font-medium">${data.status} Date:</span> ${new Date(data.accepted_date).toLocaleString()}</p>` : ''}
                            <p><span class="font-medium">Reference Code:</span> ${data.reference_code || 'N/A'}</p>
                        </div>
                    </div>
                    
                    ${(data.deathcert_url || data.payment_url) ? `
                    <div class="col-span-2">
                        <h4 class="font-semibold text-gray-700 mb-2">Attachments</h4>
                        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-3 rounded-lg">
                            ${data.deathcert_url ? `
                                <div class="col-span-1">
                                    <p class="font-medium mb-2">Death Certificate:</p>
                                    <img src="booking/${data.deathcert_url}" 
                                        alt="Death Certificate" 
                                        class="w-full h-auto rounded border border-gray-200"
                                        onload="this.nextElementSibling.textContent = this.naturalWidth + '×' + this.naturalHeight + 'px'">
                                    <p class="text-sm text-gray-500 mt-1 text-center"></p>
                                </div>
                            ` : `
                                <div class="col-span-1">
                                    <p class="font-medium mb-2">Death Certificate:</p>
                                    <div class="w-full h-40 bg-gray-100 rounded border border-gray-200 flex items-center justify-center">
                                        <p class="text-gray-400">N/A</p>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1 text-center">0×0 px</p>
                                </div>
                            `}
                            
                            ${data.payment_url ? `
                                <div class="col-span-1">
                                    <p class="font-medium mb-2">Payment Receipt:</p>
                                    <img src="booking/${data.payment_url}" 
                                        alt="Payment Receipt" 
                                        class="w-full h-auto rounded border border-gray-200"
                                        onload="this.nextElementSibling.textContent = this.naturalWidth + '×' + this.naturalHeight + 'px'">
                                    <p class="text-sm text-gray-500 mt-1 text-center"></p>
                                </div>
                            ` : `
                                <div class="col-span-1">
                                    <p class="font-medium mb-2">Payment Receipt:</p>
                                    <div class="w-full h-40 bg-gray-100 rounded border border-gray-200 flex items-center justify-center">
                                        <p class="text-gray-400">N/A</p>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1 text-center">0×0 px</p>
                                </div>
                            `}
                        </div>
                    </div>
                ` : ''}
                `;
                
                document.getElementById('bookingDetailsContent').innerHTML = htmlContent;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('bookingDetailsContent').innerHTML = `
                <div class="col-span-2 text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                    <p>Failed to load booking details. Please try again.</p>
                </div>
            `;
        });
}

function closeModal() {
    document.getElementById('bookingDetailsModal').classList.add('hidden');
}

function getStatusColorClass(status) {
    switch(status) {
        case 'Pending': return 'bg-yellow-100 text-yellow-800';
        case 'Accepted': return 'bg-green-100 text-green-800';
        case 'Declined': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
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