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

                // Get notification count for the current user
                $notifications_count = [
                    'total' => 0,
                    'pending' => 0,
                    'accepted' => 0,
                    'declined' => 0,
                    'id_validation' => 0
                ];

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

                // After getting user info but before closing connection
                    if (!isset($_SESSION['branch_loc'])) {
                        // Fetch user's branch location from database
                        $query = "SELECT branch_loc FROM users WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($row = $result->fetch_assoc()) {
                            $_SESSION['branch_loc'] = $row['branch_loc'];
                        }
                        $stmt->close();
                    }

                }

                
                
?>

<script src="customer_support.js"></script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Traditional Funeral</title>
    <?php include 'faviconLogo.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../tailwind.js"></script>
    <style>
        .modal {
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }
        :root {
            --navbar-height: 64px; /* Adjust this value as needed */
        }
        .modal.active {
            opacity: 1;
            pointer-events: auto;
        }
        .notification {
            animation: slideIn 0.5s forwards, slideOut 0.5s 4.5s forwards;
        }
        @keyframes slideIn {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }
        @keyframes slideOut {
            from { transform: translateY(0); }
            to { transform: translateY(-100%); }
        }

        .candlelight {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px 20px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 90;
            font-size: 24px;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
            transition: all 0.3s ease;
        }

        .candlelight:hover {
            transform: scale(1.1);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.8);
        }
        html {
        scroll-behavior: smooth;
    }
    </style>
    <script>
        function toggleMenu() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }

        // Enhanced carousel functionality with infinite loop
        document.addEventListener('DOMContentLoaded', function() {
            const partnerContainer = document.querySelector('#partner-carousel');
            const partners = document.querySelectorAll('.partner-logo');
            const totalPartners = partners.length;
            const visiblePartners = window.innerWidth < 768 ? 2 : 4;
            let position = 0;
            
            // Clone partners for infinite loop
            partners.forEach(partner => {
                const clone = partner.cloneNode(true);
                partnerContainer.appendChild(clone);
            });
            
            function moveCarousel() {
                position++;
                
                // Reset position smoothly for infinite loop
                if (position >= totalPartners) {
                    // Quick reset after transition completes
                    setTimeout(() => {
                        partnerContainer.style.transition = 'none';
                        position = 0;
                        partnerContainer.style.transform = `translateX(-${position * (100 / visiblePartners)}%)`;
                        // Re-enable transition after reset
                        setTimeout(() => {
                            partnerContainer.style.transition = 'transform 500ms ease-in-out';
                        }, 50);
                    }, 500);
                }
                
                partnerContainer.style.transform = `translateX(-${position * (100 / visiblePartners)}%)`;
            }
            
            // Center the carousel
            partnerContainer.style.display = 'flex';
            partnerContainer.style.justifyContent = 'center';
            
            setInterval(moveCarousel, 3000);
        });
    </script>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
    <!-- Notification Toast (Hidden by default) -->
    <div id="notification" class="fixed top-0 right-0 m-4 p-4 bg-black text-white rounded shadow-lg z-50 hidden notification">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="notification-message">Notification message here</span>
        </div>
    </div>

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
                    <div class="w-8 h-8 rounded-full bg-yellow-600 flex items-center justify-center text-sm">
                        <?php 
                            $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); 
                            echo htmlspecialchars($initials);
                        ?>
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

<!-- Traditional Funeral Services Main Content -->
<div class="bg-cream py-20">
    <div class="container mx-auto px-6 max-w-6xl">
        <!-- Hero Section -->
        <div class="relative rounded-2xl overflow-hidden mb-16 shadow-xl">
            <div class="h-64 bg-cover bg-center" style="background-image: url('../Landing_Page/Landing_images/sampleImageLANG.jpg')">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 flex flex-col justify-center items-center text-white p-8">
                    <h1 class="text-5xl md:text-6xl font-hedvig text-center mb-6">Traditional Funeral</h1>
                    <p class="text-xl max-w-3xl text-center">Honoring your loved one with dignity, respect, and time-honored traditions</p>
                </div>
            </div>
        </div>

        <!-- Introduction Section -->
        <div class="max-w-4xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-hedvig text-navy mb-6">A Dignified Farewell</h2>
            <p class="text-lg text-dark">Our traditional funeral services honor your loved one with dignity and respect while providing support for family and friends. Each service can be customized to reflect the unique life being celebrated.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-8"></div>
        </div>

        <!-- Service Features -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-20">
            <!-- Feature 1 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                <div class="w-16 h-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-users text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-4">Viewing & Visitation</h3>
                <p class="text-dark text-sm">A private or public gathering that allows family and friends to pay their respects and offer condolences in a supportive environment.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                <div class="w-16 h-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-church text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-4">Funeral Ceremony</h3>
                <p class="text-dark text-sm">A formal service held at our chapel, a place of worship, or another meaningful location to honor and celebrate your loved one's life.</p>
            </div>
            
            <!-- Feature 3 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                <div class="w-16 h-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-car text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-4">Procession & Committal</h3>
                <p class="text-dark text-sm">A dignified journey to the final resting place, followed by a brief but meaningful graveside service for final farewells.</p>
            </div>
        </div>

        <!-- Traditional Funeral Services Section -->
<section id="traditional" class="scroll-mt-24 mb-16 px-4 sm:px-0">
    <!-- Section Header - Centered -->
    <div class="flex justify-center mb-6 sm:mb-8">
        <div class="flex items-center">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mr-3 sm:mr-4">
                <i class="fas fa-dove text-lg sm:text-xl"></i>
            </div>
            <h2 class="font-hedvig text-3xl sm:text-4xl text-navy">Traditional Funeral</h2>
        </div>
    </div>
    
    <!-- Paragraph - Centered -->
    <div class="flex justify-center mb-6">
        <p class="text-dark max-w-4xl text-center text-sm sm:text-lg">Our traditional funeral services honor your loved one with dignity and respect while providing support for family and friends. Each service can be customized to reflect the unique life being celebrated.</p>
    </div>
    
    <?php 
    $query = "SELECT 
        s.service_id, 
        s.service_name, 
        s.description, 
        s.casket_id, 
        i.item_name, 
        s.flower_design, 
        s.inclusions, 
        s.selling_price, 
        REPLACE(s.image_url, 'uploads/services/', '../admin/servicesManagement/uploads/services/') as image_url
    FROM services_tb s
    JOIN inventory_tb i ON s.casket_id = i.inventory_id
    WHERE s.branch_id = 2 AND s.status = 'active'
    ORDER BY s.selling_price DESC
    LIMIT 2";

    $result = $conn->query($query);

    if ($result->num_rows >= 2) {
    $package1 = $result->fetch_assoc();
    $package2 = $result->fetch_assoc();

    ?>
    <!-- Packages Carousel -->
    <div class="max-w-6xl mx-auto relative">
        <!-- Carousel Container -->
        <div class="overflow-hidden relative">
            <div id="carousel-container" class="flex transition-transform duration-500 ease-in-out">
                <!-- Package 1: Legacy Tribute -->

                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" 
                    data-price="<?php echo htmlspecialchars($package1['selling_price']); ?>" 
                    data-service="traditional" 
                    data-name="<?php echo htmlspecialchars($package1['service_name']); ?>" 
                    data-image="<?php echo htmlspecialchars($package1['image_url']); ?>"
                    data-service-id="<?php echo htmlspecialchars($package1['service_id']); ?>">
                    <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                        <h4 class="text-white font-hedvig text-lg sm:text-xl"><?php echo htmlspecialchars($package1['service_name']); ?></h4>
                        <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 flex flex-col flex-grow">
                        <div class="mb-3 sm:mb-4 flex justify-center">
                            <img src="<?php echo htmlspecialchars($package1['image_url']); ?>" alt="<?php echo htmlspecialchars($package1['service_name']); ?>" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                        </div>
                        <div class="text-center mb-4 sm:mb-6">
                            <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱<?php echo number_format($package1['selling_price'], 0, '.', ','); ?></span>
                        </div>
                        <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                            <!-- Display Casket Name -->
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark"><strong>Casket:</strong> <?php echo htmlspecialchars($package1['item_name']); ?></span>
                            </li>
                            
                            <!-- Display Flower Design -->
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark"><strong>Flower Design:</strong> <?php echo htmlspecialchars($package1['flower_design']); ?></span>
                            </li>
                            
                            <!-- Display Inclusions -->
                            <?php 
                            $inclusions = json_decode($package1['inclusions'], true) ?: explode(',', $package1['inclusions']);
                            foreach ($inclusions as $inclusion): 
                                if (!empty(trim($inclusion))):
                            ?>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark"><?php echo htmlspecialchars(trim($inclusion)); ?></span>
                            </li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                        <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                            Select Package
                        </button>
                    </div>
                </div>
            </div>
                
                <!-- Package 2: Eternal Remembrance -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" 
                    data-price="<?php echo htmlspecialchars($package2['selling_price']); ?>" 
                    data-service="traditional" 
                    data-name="<?php echo htmlspecialchars($package2['service_name']); ?>" 
                    data-image="<?php echo htmlspecialchars($package2['image_url']); ?>"
                    data-service-id="<?php echo htmlspecialchars($package2['service_id']); ?>">
    
                    <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                        <h4 class="text-white font-hedvig text-lg sm:text-xl"><?php echo htmlspecialchars($package2['service_name']); ?></h4>
                        <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 flex flex-col flex-grow">
                        <div class="mb-3 sm:mb-4 flex justify-center">
                            <img src="<?php echo htmlspecialchars($package2['image_url']); ?>" alt="<?php echo htmlspecialchars($package2['service_name']); ?>" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                        </div>
                        <div class="text-center mb-4 sm:mb-6">
                            <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱<?php echo number_format($package2['selling_price'], 0, '.', ','); ?></span>
                        </div>
                        <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                            <!-- Display Casket Name -->
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark"><strong>Casket:</strong> <?php echo htmlspecialchars($package2['item_name']); ?></span>
                            </li>
                            
                            <!-- Display Flower Design -->
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark"><strong>Flower Design:</strong> <?php echo htmlspecialchars($package2['flower_design']); ?></span>
                            </li>
                            
                            <!-- Display Inclusions -->
                            <?php 
                            $inclusions = json_decode($package2['inclusions'], true) ?: explode(',', $package2['inclusions']);
                            foreach ($inclusions as $inclusion): 
                                if (!empty(trim($inclusion))):
                            ?>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark"><?php echo htmlspecialchars(trim($inclusion)); ?></span>
                            </li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                        <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                            Select Package
                        </button>
                    </div>
                </div>
            </div>

            <?php
            } else {
                // Handle case where there aren't enough packages
                echo "Not enough packages found in the database.";
            }
            $conn->close();
            ?>
                
                <!-- Package 3: Custom Memorial -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                    <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="custom" data-service="traditional" data-name="Custom Memorial" data-image="image/custom.jpg">
                        <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-lg sm:text-xl">Custom Memorial</h4>
                            <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6 flex flex-col flex-grow">
                            <div class="mb-3 sm:mb-4 flex justify-center">
                                <img src="../Landing_Page/Landing_images/logo.png" alt="Custom Memorial" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-4 sm:mb-6">
                                <span class="text-2xl sm:text-3xl font-hedvig text-navy">Starting at ₱150,000</span>
                                <p class="text-xs sm:text-sm text-gray-600 mt-1">Final price depends on selections</p>
                            </div>
                            <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Choose your casket</strong> from our selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Select flower arrangements</strong> (1-3 sets)</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Customize viewing period</strong> to your needs</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Add catering options</strong> if desired</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Core funeral services included</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Personalized assistance throughout</span>
                                </li>
                            </ul>
                            <button class="customtraditionalpckg block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                                Customize Package
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- View All Packages Blur Card -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                    <a href="packages.php" class="block h-full">
                        <div class="bg-white/30 backdrop-blur-md rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full border-2 border-dashed border-navy/40">
                            <div class="flex items-center justify-center h-full p-4 sm:p-6">
                                <div class="text-center">
                                    <div class="mb-4 sm:mb-6 flex justify-center">
                                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-navy/10 flex items-center justify-center">
                                            <i class="fas fa-ellipsis-h text-2xl sm:text-3xl text-navy/60"></i>
                                        </div>
                                    </div>
                                    <h3 class="text-xl sm:text-2xl font-hedvig text-navy mb-3 sm:mb-4">View All Packages</h3>
                                    <p class="text-dark/70 mb-4 sm:mb-6 text-sm sm:text-base">Explore our complete range of funeral service options</p>
                                    <div class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-sm sm:text-base">
                                        View All
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Navigation Arrows - Modified for smaller screens -->
        <button id="prev-btn" class="absolute left-0 top-1/2 transform -translate-y-1/2 -ml-2 sm:-ml-4 bg-yellow-600 w-8 h-8 sm:w-10 sm:h-10 rounded-full shadow-lg flex items-center justify-center border border-gray-200 z-20 hover:bg-yellow-700 focus:outline-none">
            <i class="fas fa-chevron-left text-white text-xs sm:text-base"></i>
        </button>
        <button id="next-btn" class="absolute right-0 top-1/2 transform -translate-y-1/2 -mr-2 sm:-mr-4 bg-yellow-600 w-8 h-8 sm:w-10 sm:h-10 rounded-full shadow-lg flex items-center justify-center border border-gray-200 z-20 hover:bg-yellow-700 focus:outline-none">
            <i class="fas fa-chevron-right text-white text-xs sm:text-base"></i>
        </button>

        <!-- Dots Indicator -->
        <div class="flex justify-center mt-4 sm:mt-6">
            <div id="carousel-dots" class="flex space-x-2">
                <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-100" data-index="0"></button>
                <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="1"></button>
                <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="2"></button>
                <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="3"></button>
            </div>
        </div>
    </div>
</section>
</div>
</div>

<!-- Carousel JavaScript - Updated for better responsiveness -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const carouselContainer = document.getElementById('carousel-container');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const dotsContainer = document.getElementById('carousel-dots');
    
    let currentIndex = 0;
    const itemCount = 4; // Total items (3 packages + view all)
    let itemsPerView = window.innerWidth >= 768 ? 3 : 1; // 3 on desktop, 1 on mobile
    
    // Function to calculate dots needed
    function calculateDots() {
        // On mobile (1 item view), we need 4 dots (one per item)
        if (window.innerWidth < 768) {
            return itemCount;
        }
        // On desktop (3 items view), we need 2 dots (4 items - 3 per view + 1)
        return itemCount - itemsPerView + 1;
    }
    
    // Function to create/update dots
    function updateDots() {
        const dotCount = calculateDots();
        dotsContainer.innerHTML = '';
        
        for (let i = 0; i < dotCount; i++) {
            const dot = document.createElement('button');
            dot.className = `w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy ${i === currentIndex ? 'opacity-100' : 'opacity-50'}`;
            dot.dataset.index = i;
            dotsContainer.appendChild(dot);
            
            dot.addEventListener('click', () => {
                currentIndex = i;
                updateCarousel();
            });
        }
    }
    
    // Function to get max index based on view
    function getMaxIndex() {
        // On mobile, can scroll through all items
        if (window.innerWidth < 768) {
            return itemCount - 1;
        }
        // On desktop, limited by items per view
        return itemCount - itemsPerView;
    }
    
    // Main update function
    function updateCarousel() {
        const translateValue = currentIndex * -(100 / itemsPerView);
        carouselContainer.style.transform = `translateX(${translateValue}%)`;
        
        // Update dots
        const dots = document.querySelectorAll('#carousel-dots button');
        dots.forEach((dot, index) => {
            dot.classList.toggle('opacity-100', index === currentIndex);
            dot.classList.toggle('opacity-50', index !== currentIndex);
        });
        
        // Update navigation buttons
        prevBtn.style.display = currentIndex === 0 ? 'none' : 'flex';
        nextBtn.style.display = currentIndex >= getMaxIndex() ? 'none' : 'flex';
    }
    
    // Navigation handlers
    nextBtn.addEventListener('click', function() {
        if (currentIndex < getMaxIndex()) {
            currentIndex++;
            updateCarousel();
        }
    });
    
    prevBtn.addEventListener('click', function() {
        if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const newItemsPerView = window.innerWidth >= 768 ? 3 : 1;
        
        if (newItemsPerView !== itemsPerView) {
            itemsPerView = newItemsPerView;
            updateDots();
            
            // Adjust currentIndex if needed
            const newMaxIndex = getMaxIndex();
            if (currentIndex > newMaxIndex) {
                currentIndex = newMaxIndex;
            }
            
            updateCarousel();
        }
    });
    
    // Initialize
    updateDots();
    updateCarousel();
    
    // Touch/swipe handling (existing code)
    let touchStartX = 0;
    let touchEndX = 0;
    
    carouselContainer.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, false);
    
    carouselContainer.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, false);
    
    function handleSwipe() {
        const swipeThreshold = 50;
        if (touchEndX < touchStartX - swipeThreshold && currentIndex < getMaxIndex()) {
            currentIndex++;
            updateCarousel();
        } else if (touchEndX > touchStartX + swipeThreshold && currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    }
});
</script>

    <!-- FAQ Section -->
    <div class="container mx-auto px-6  max-w-4xl">
        
    <!-- FAQ Section -->
<div class="container mx-auto px-6  max-w-4xl">
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-5xl font-hedvig text-navy mb-4">We're Here to Help</h2>
            <p class="text-dark text-lg max-w-3xl mx-auto">Find answers to the most common questions about VJay Relova Funeral Services and our bereavement support.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
        </div>
        
        <!-- FAQ Accordion -->
        <div class="space-y-6 mb-12">
            <!-- FAQ Item 1 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What should I do when a death occurs?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>When a death occurs, the first step is to notify the appropriate authorities:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>If the death occurs at home, call emergency services (911) or the person's doctor.</li>
                            <li>If the death occurs at a hospital or care facility, staff will guide you through the initial procedures.</li>
                            <li>Once the death has been officially pronounced, contact us at GrievEase at our 24/7 number for immediate assistance.</li>
                        </ul>
                        <p class="mt-4">Our compassionate staff will guide you through the next steps, including transportation of your loved one to our facility and beginning the arrangement process.</p>
                        <p class="mt-4"><strong>Important:</strong> In cases of accident or disease-related deaths, there may be additional processes involving SOCO (Scene of the Crime Operatives) that need to be followed before the burial can proceed.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">How do I plan a meaningful funeral service?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Planning a meaningful funeral service involves several considerations:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Reflect on your loved one's personality, interests, and wishes</li>
                            <li>Consider religious or cultural traditions that were important to them</li>
                            <li>Select meaningful music, readings, or other elements that celebrate their life</li>
                            <li>Decide on personalization options like photo displays, memory tables, or tribute videos</li>
                        </ul>
                        <p class="mt-4">At VJay Relova Funeral Services, we believe that the personal connection between our staff and your family is essential. Our owner will personally talk with you to understand your specific needs and requests. We'll guide you through each step, helping you create a service that truly honors your loved one's life and legacy.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What is the difference between burial and cremation?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>The main differences between burial and cremation involve:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li><strong>Process:</strong> Burial preserves the body in a casket placed in the ground or a mausoleum, while cremation reduces the body to cremated remains through heat.</li>
                            <li><strong>Timeline:</strong> Burials typically occur within a week of death, while cremation offers more flexibility for memorial service timing.</li>
                            <li><strong>Memorialization:</strong> Burial provides a permanent gravesite to visit, while cremated remains can be kept in an urn, scattered, or placed in a columbarium.</li>
                            <li><strong>Cost:</strong> Cremation is generally less expensive than traditional burial due to fewer required elements.</li>
                        </ul>
                        <p class="mt-4">For cremation services, we partner with third-party crematoriums. The advantage of arranging cremation through us is that we can help prioritize your scheduling needs. We can arrange for cremation on a specific day of your choosing, even if there are others scheduled for cremation on the same day.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 4 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What are the benefits of pre-planning a funeral?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Pre-planning a funeral offers several significant benefits:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Ensures your specific wishes are known and honored</li>
                            <li>Relieves your loved ones from making difficult decisions during a time of grief</li>
                            <li>Provides opportunity to make thoughtful, informed choices without time pressure</li>
                            <li>Allows you to handle financial arrangements in advance, potentially saving money</li>
                            <li>Creates peace of mind for you and your family</li>
                        </ul>
                        <p class="mt-4">We offer lifeplan installment plans for those who wish to plan ahead. Some clients prefer our services over other lifeplan companies because our packages are complete and customizable, with negotiable prices. We work with you to ensure everything you desire for your funeral service is included in your plan.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 5 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">How much does a funeral typically cost?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Our funeral packages range from ₱25,000 for a complete basic package to ₱500,000 for premium services. We've provided services up to ₱800,000-1,000,000 for clients with specific requirements.</p>
                        <p class="mt-2">A typical funeral package may include:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Casket (various options available, including imported brass caskets)</li>
                            <li>Flowers (replaced twice for premium packages)</li>
                            <li>Chapel rental with curtains and lighting</li>
                            <li>Embalming services with licensed professionals</li>
                            <li>Hearse and transportation</li>
                            <li>Viewing equipment (water dispenser, tent, chairs, tables)</li>
                        </ul>
                        <p class="mt-4">At VJay Relova Funeral Services, we're committed to transparency in pricing. Our packages are customizable, and prices are negotiable based on your needs. We offer automatic 20% discounts for PWDs and senior citizens, and we can discuss other discount options based on your situation.</p>
                        <p class="mt-2">For services over ₱100,000, we require a 30% down payment. For services under ₱100,000, payment is typically made before or on the day of burial.</p>
                    </div>
                </div>
            </div>
            <!-- View All FAQs Link -->
            <div class="text-center mt-8">
                <a href="faqs.php" class="text-yellow-600 hover:text-yellow-700  text-lg">
                    View All FAQs <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
    </div>
    </div>
    </div>

    <!-- Traditional Funeral Modal (Hidden by Default) -->
<div id="traditionalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[80vh]">
        <!-- Scroll container for both columns -->
        <div class="modal-scroll-container grid grid-cols-1 md:grid-cols-2 overflow-y-auto max-h-[80vh]">
            <!-- Left Side: Package Details -->
            <div class="bg-cream p-8">
                <!-- Package Image -->
                <div class="mb-6">
                    <img id="traditionalPackageImage" src="" alt="" class="w-full h-64 object-cover rounded-lg mb-4">
                </div>

                <!-- Package Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 id="traditionalPackageName" class="text-2xl font-hedvig text-navy"></h2>
                    <div id="traditionalPackagePrice" class="text-3xl font-hedvig text-yellow-600"></div>
                </div>

                <!-- Package Description -->
                <p id="traditionalPackageDesc" class="text-dark mb-6"></p>

                <!-- Main Package Details -->
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-xl font-hedvig text-navy mb-4">Package Includes:</h3>
                    <ul id="traditionalPackageFeatures" class="space-y-2">
                        <!-- Features will be inserted here by JavaScript -->
                    </ul>
                </div>

                
            </div>

            <!-- Right Side: Traditional Booking Form -->
            <div class="bg-white p-8 border-l border-gray-200 overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-hedvig text-navy">Book Your Package</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <form id="traditionalBookingForm" class="space-y-4">
                    <input type="hidden" id="traditionalSelectedPackageName" name="packageName">
                    <input type="hidden" id="traditionalSelectedPackagePrice" name="packagePrice">

                    <input type="hidden" id="casketID" name="casketId" value="">
                    <input type="hidden" id="customerID" name="customerId" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                    <input type="hidden" id="branchID" name="branchId" value="<?php echo isset($_SESSION['branch_loc']) ? $_SESSION['branch_loc'] : ''; ?>">

                    <input type="hidden" id="flowerDesign" name="flowerArrangement" value="">
                    <input type="hidden" id="inclusions" name="selectedAddons" value="">
                    <input type="hidden" id="notes" name="bookingNotes" value="">

                    <input type="hidden" id="serviceID" name="serviceId" value="">
                     
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-hedvig text-navy mb-4">Deceased Information</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="traditionalDeceasedFirstName" class="block text-sm font-medium text-navy mb-2">First Name *</label>
                                <input type="text" id="traditionalDeceasedFirstName" name="deceasedFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDeceasedMiddleName" class="block text-sm font-medium text-navy mb-2">Middle Name</label>
                                <input type="text" id="traditionalDeceasedMiddleName" name="deceasedMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-3/4 px-2 mb-3 sm:mb-0">
                                <label for="traditionalDeceasedLastName" class="block text-sm font-medium text-navy mb-1">Last Name *</label>
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
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="traditionalDateOfBirth" class="block text-sm font-medium text-navy mb-2">Date of Birth</label>
                                <input type="date" id="traditionalDateOfBirth" name="dateOfBirth" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDateOfDeath" class="block text-sm font-medium text-navy mb-2">Date of Death *</label>
                                <input type="date" id="traditionalDateOfDeath" name="dateOfDeath" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDateOfBurial" class="block text-sm font-medium text-navy mb-2">Date of Burial</label>
                                <input type="date" id="traditionalDateOfBurial" name="dateOfBurial" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
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
                                <label for="traditionalDeceasedRegion" class="block text-sm font-medium text-navy mb-1">Region</label>
                                <select id="traditionalDeceasedRegion" name="deceasedRegion" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">Select Region</option>
                                    <option value="NCR">National Capital Region (NCR)</option>
                                    <option value="CAR">Cordillera Administrative Region (CAR)</option>
                                    <option value="Region I">Ilocos Region (Region I)</option>
                                    <!-- Add more regions as needed -->
                                </select>
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="traditionalDeceasedProvince" class="block text-sm font-medium text-navy mb-1">Province</label>
                                <select id="traditionalDeceasedProvince" name="deceasedProvince" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">Select Province</option>
                                    <!-- Provinces will be populated by JavaScript based on selected region -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="traditionalDeceasedCity" class="block text-sm font-medium text-navy mb-1">City/Municipality</label>
                                <select id="traditionalDeceasedCity" name="deceasedCity" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">Select City/Municipality</option>
                                    <!-- Cities will be populated by JavaScript based on selected province -->
                                </select>
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="traditionalDeceasedBarangay" class="block text-sm font-medium text-navy mb-1">Barangay</label>
                                <select id="traditionalDeceasedBarangay" name="deceasedBarangay" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">Select Barangay</option>
                                    <!-- Barangays will be populated by JavaScript based on selected city -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="traditionalDeceasedStreet" class="block text-sm font-medium text-navy mb-1">Street/Block/House Number</label>
                            <input type="text" id="traditionalDeceasedStreet" name="deceasedStreet" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="Enter detailed street address">
                        </div>
                        
                        <input type="hidden" id="deceasedAddress" name="deceasedAddress">
                        
                        <div class="flex items-center mt-3 md:mt-4">
                            <input type="checkbox" id="traditionalWithCremate" name="with_cremate" value="yes" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                            <label for="traditionalWithCremate" class="ml-2 block text-sm text-navy">
                                Include cremation service
                            </label>
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
                        <div id="qrCodeModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-hedvig text-navy">Scan to Pay</h3>
                                    <button id="closeQrModal" class="text-gray-500 hover:text-navy">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <div class="flex flex-col items-center justify-center">
                                    <img id="qrCodeImage" src="..\image\gcashqrvjay.jpg" alt="Payment QR Code" class="w-64 h-64 object-contain mb-4">
                                    <p class="text-center text-sm text-gray-600 mb-2">Scan this QR code with your GCash app to make payment</p>
                                    <p class="text-center font-bold text-yellow-600" id="qrCodeAmount">Amount: ₱0</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GCash Upload with Preview -->
                        <div class="mb-4">
                            <label for="traditionalGcashReceipt" class="block text-sm font-medium text-navy mb-1">Payment Proof</label>
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
                            <label for="traditionalReferenceNumber" class="block text-sm font-medium text-navy mb-1">Reference Number *</label>
                            <input type="text" id="traditionalReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="e.g. 1234567890">
                        </div>
                    </div>

                    <div class="bg-cream p-4 rounded-lg">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="traditionalTotalPrice" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
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

<!-- Custom Package Selection Modal (Hidden by Default) -->
<div id="customPackageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[80vh]">
        <div class="modal-scroll-container overflow-y-auto max-h-[80vh]">
            <div class="bg-cream p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-hedvig text-navy">Custom Memorial Package</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <p class="text-dark mb-8">Create your personalized memorial package by selecting the options that best suit your needs and preferences. Our team will assist you throughout the process.</p>
                
                <!-- Package Options Selection -->
                <div id="customStepOptions" class="custom-step">
                    <h3 class="text-xl font-hedvig text-navy mb-6">Select Package Components</h3>
                    
                    <div class="space-y-8">
                        <!-- Casket Selection -->
                        
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-lg font-hedvig text-navy mb-4">Select Casket</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="casketOptionsContainer">
                                <!-- Casket options will be loaded here via AJAX -->
                                <div class="text-center py-8">
                                    <i class="fas fa-spinner fa-spin text-2xl text-yellow-600"></i>
                                    <p class="mt-2">Loading casket options...</p>
                                </div>
                            </div>
                        </div>                                                              
                        
                        
                        
                        <!-- Flower Arrangements -->
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-lg font-hedvig text-navy mb-4">Flower Arrangements</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="border rounded-lg p-4 cursor-pointer flower-option" data-price="15000" data-name="Standard Floral Package">
                                    <p class="text-sm text-gray-600 mb-2">Initial Flower Arrangement</p>
                                    <p class="text-yellow-600">₱15,000</p>
                                </div>
                                <div class="border rounded-lg p-4 cursor-pointer flower-option" data-price="25000" data-name="Premium Floral Package">
                                    <p class="text-sm text-gray-600 mb-2">1 Flower Replacement</p>
                                    <p class="text-yellow-600">₱25,000</p>
                                </div>
                                <div class="border rounded-lg p-4 cursor-pointer flower-option" data-price="40000" data-name="Luxury Floral Package">
                                    <p class="text-sm text-gray-600 mb-2">2 Flower Replacement</p>
                                    <p class="text-yellow-600">₱40,000</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Services Checkboxes -->
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-lg font-hedvig text-navy mb-4">Additional Services</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="customCatering" name="additionalServices" value="25000" class="custom-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Catering Service (100 pax)">
                                    <label for="customCatering" class="ml-3">
                                        <span class="block font-medium mb-1">Catering Service (100 pax)</span>
                                        <span class="block text-sm text-gray-600 mb-1">Food and refreshments for 100 people</span>
                                        <span class="text-yellow-600">₱25,000</span>
                                    </label>
                                </div>
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="customVideo" name="additionalServices" value="15000" class="custom-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Video Memorial Package">
                                    <label for="customVideo" class="ml-3">
                                        <span class="block font-medium mb-1">Video Memorial Package</span>
                                        <span class="block text-sm text-gray-600 mb-1">Professional photo/video service</span>
                                        <span class="text-yellow-600">₱15,000</span>
                                    </label>
                                </div>
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="customTransport" name="additionalServices" value="8000" class="custom-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Additional Transportation">
                                    <label for="customTransport" class="ml-3">
                                        <span class="block font-medium mb-1">Additional Transportation</span>
                                        <span class="block text-sm text-gray-600 mb-1">For family members (up to 10 people)</span>
                                        <span class="text-yellow-600">₱8,000</span>
                                    </label>
                                </div>
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="customLiveStream" name="additionalServices" value="12000" class="custom-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Live Streaming Service">
                                    <label for="customLiveStream" class="ml-3">
                                        <span class="block font-medium mb-1">Live Streaming Service</span>
                                        <span class="block text-sm text-gray-600 mb-1">For remote family and friends</span>
                                        <span class="text-yellow-600">₱12,000</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label for="customNotes" class="block text-lg font-hedvig text-navy mb-2">Additional Notes</label>
                            <textarea id="customNotes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" placeholder="Any special requests or additional information you'd like us to know..."></textarea>
                            <p class="text-sm text-gray-500 mt-1">Please include any special requests or requirements for your package.</p>
                        </div>

                    </div>
                    
                    <!-- Package Summary -->
                    <div class="mt-8 bg-white p-6 rounded-lg shadow">
                        <h4 class="text-lg font-hedvig text-navy mb-4">Package Summary</h4>

                        <div class="flex justify-between text-sm">
                            <span>Base Package</span>
                            <span>₱35,000</span>
                        </div>
                        
                        <div id="customSelectionsSummary" class="space-y-2 mb-4">
                            <p class="text-gray-500 italic">No items selected yet</p>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between font-bold mb-2">
                                <span>Total Package Price:</span>
                                <span id="customTotalPrice" class="text-yellow-600">₱35,000</span>
                            </div>
                            
                            <div id="customTraditionalPayment">
                                <div class="flex justify-between text-sm mb-2">
                                    <span>Required Downpayment (30%):</span>
                                    <span id="customDownpayment" class="text-yellow-600">₱10,500</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-center mt-8">
                        <button id="proceedToBooking" class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-lg shadow-md transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Continue to Booking Form
                        </button>
                    </div>
                </div>
                
                <!-- Booking Form -->
                <div id="customStepBooking" class="custom-step hidden">
                    <div class="flex items-center mb-6">
                        <button id="backToOptions" class="text-yellow-600 hover:text-yellow-700 mr-4">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <h3 class="text-xl font-hedvig text-navy">Complete Your Booking</h3>
                    </div>
                    
                    <p class="mb-8">Please provide your information to complete the booking process.</p>
                    
                    <!-- Will load traditional form here via JS -->
                    <div id="customBookingFormContainer">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    // Add this script in the <head> section or before the closing </body> tag
document.addEventListener('DOMContentLoaded', function() {
    // Name fields validation
    const nameFields = ['traditionalDeceasedFirstName', 'traditionalDeceasedMiddleName', 'traditionalDeceasedLastName'];
    
    nameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                // Remove numbers and special characters (except apostrophes and hyphens)
                let value = this.value.replace(/[^a-zA-Z'\s-]/g, '');
                
                // Remove consecutive spaces (but allow single space after at least 2 characters)
                value = value.replace(/(\S{2,})\s{2,}/g, '$1 ');
                
                // Capitalize first letter of each word
                value = value.toLowerCase().replace(/(^|\s)([a-z])/g, function(match) {
                    return match.toUpperCase();
                });
                
                // Remove leading spaces
                value = value.trimStart();
                
                this.value = value;
            });
            
            // Handle paste event
            field.addEventListener('paste', function(e) {
                e.preventDefault();
                let pastedText = (e.clipboardData || window.clipboardData).getData('text');
                
                // Clean the pasted text
                pastedText = pastedText.replace(/[^a-zA-Z'\s-]/g, '');
                pastedText = pastedText.replace(/(\S{2,})\s{2,}/g, '$1 ');
                pastedText = pastedText.toLowerCase().replace(/(^|\s)([a-z])/g, function(match) {
                    return match.toUpperCase();
                });
                pastedText = pastedText.trimStart();
                
                // Insert at cursor position
                const startPos = this.selectionStart;
                const endPos = this.selectionEnd;
                this.value = this.value.substring(0, startPos) + pastedText + this.value.substring(endPos);
                
                // Move cursor to end of inserted text
                this.selectionStart = this.selectionEnd = startPos + pastedText.length;
            });
        }
    });

    // Date fields validation
    const dobField = document.getElementById('traditionalDateOfBirth');
    const dodField = document.getElementById('traditionalDateOfDeath');
    const burialField = document.getElementById('traditionalDateOfBurial');
    
    // Set max date for date of birth (100 years ago)
    if (dobField) {
        const today = new Date();
        const maxDate = new Date(today.getFullYear() - 100, today.getMonth(), today.getDate());
        dobField.max = formatDate(maxDate);
        dobField.max = formatDate(today); // Also can't be in the future
        
        dobField.addEventListener('change', function() {
            if (dodField.value) {
                if (new Date(this.value) > new Date(dodField.value)) {
                    alert('Date of birth must be before date of death');
                    this.value = '';
                }
            }
            updateDateConstraints();
        });
    }
    
    if (dodField) {
        dodField.addEventListener('change', function() {
            if (dobField.value && new Date(this.value) < new Date(dobField.value)) {
                alert('Date of death must be after date of birth');
                this.value = '';
            }
            if (burialField.value && new Date(this.value) > new Date(burialField.value)) {
                alert('Date of death must be before date of burial');
                this.value = '';
            }
            updateDateConstraints();
        });
    }
    
    if (burialField) {
        // Set min date to tomorrow
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        burialField.min = formatDate(tomorrow);
        
        burialField.addEventListener('change', function() {
            if (dodField.value && new Date(this.value) < new Date(dodField.value)) {
                alert('Date of burial must be after date of death');
                this.value = '';
            }
            updateDateConstraints();
        });
    }
    
    function updateDateConstraints() {
        // Update date of death constraints based on date of birth
        if (dobField.value) {
            dodField.min = formatDate(new Date(dobField.value));
        }
        
        // Update date of burial constraints based on date of death
        if (dodField.value) {
            burialField.min = formatDate(new Date(dodField.value));
            const nextDay = new Date(dodField.value);
            nextDay.setDate(nextDay.getDate() + 1);
            burialField.min = formatDate(nextDay);
        }
    }
    
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Death certificate upload validation
    const deathCertInput = document.getElementById('traditionalDeathCertificate');
    if (deathCertInput) {
        deathCertInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    alert('Please upload a JPG, JPEG, or PNG file for the death certificate.');
                    this.value = '';
                    document.getElementById('traditionalDeathCertFileName').textContent = 'No file chosen';
                    document.getElementById('deathCertPreviewContainer').classList.add('hidden');
                    document.getElementById('removeDeathCert').classList.add('hidden');
                } else {
                    document.getElementById('traditionalDeathCertFileName').textContent = file.name;
                    
                    // Show preview for images
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('deathCertImage').src = e.target.result;
                            document.getElementById('deathCertImagePreview').classList.remove('hidden');
                            document.getElementById('deathCertPreviewContainer').classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    }
                    
                    document.getElementById('removeDeathCert').classList.remove('hidden');
                }
            }
        });
    }
    
    // Remove death certificate button
    const removeDeathCert = document.getElementById('removeDeathCert');
    if (removeDeathCert) {
        removeDeathCert.addEventListener('click', function() {
            deathCertInput.value = '';
            document.getElementById('traditionalDeathCertFileName').textContent = 'No file chosen';
            document.getElementById('deathCertPreviewContainer').classList.add('hidden');
            this.classList.add('hidden');
        });
    }

    // Address fields validation
    const regionField = document.getElementById('traditionalDeceasedRegion');
    const provinceField = document.getElementById('traditionalDeceasedProvince');
    const cityField = document.getElementById('traditionalDeceasedCity');
    const barangayField = document.getElementById('traditionalDeceasedBarangay');
    const streetField = document.getElementById('traditionalDeceasedStreet');
    
    if (streetField) {
        streetField.addEventListener('input', function() {
            // Remove multiple spaces
            this.value = this.value.replace(/\s{2,}/g, ' ').trim();
            
            // Capitalize first letter of each word
            this.value = this.value.toLowerCase().replace(/(^|\s)([a-z])/g, function(match) {
                return match.toUpperCase();
            });
        });
    }
    
    // Cascading dropdowns for address
    if (regionField) {
        regionField.addEventListener('change', function() {
            provinceField.innerHTML = '<option value="">Select Province</option>';
            cityField.innerHTML = '<option value="">Select City/Municipality</option>';
            barangayField.innerHTML = '<option value="">Select Barangay</option>';
            
            if (this.value) {
                // In a real implementation, you would fetch provinces based on region
                // This is just a placeholder
                fetchProvinces(this.value).then(provinces => {
                    provinces.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province;
                        option.textContent = province;
                        provinceField.appendChild(option);
                    });
                });
            }
        });
    }
    
    if (provinceField) {
        provinceField.addEventListener('change', function() {
            cityField.innerHTML = '<option value="">Select City/Municipality</option>';
            barangayField.innerHTML = '<option value="">Select Barangay</option>';
            
            if (this.value) {
                // Fetch cities based on province
                fetchCities(this.value).then(cities => {
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city;
                        option.textContent = city;
                        cityField.appendChild(option);
                    });
                });
            }
        });
    }
    
    if (cityField) {
        cityField.addEventListener('change', function() {
            barangayField.innerHTML = '<option value="">Select Barangay</option>';
            
            if (this.value) {
                // Fetch barangays based on city
                fetchBarangays(this.value).then(barangays => {
                    barangays.forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay;
                        option.textContent = barangay;
                        barangayField.appendChild(option);
                    });
                });
            }
        });
    }
    
    // Payment reference number validation
    const refNumberField = document.getElementById('traditionalReferenceNumber');
    if (refNumberField) {
        refNumberField.addEventListener('input', function() {
            // Remove non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 20 characters
            if (this.value.length > 20) {
                this.value = this.value.substring(0, 20);
            }
        });
        
        // Handle paste event
        refNumberField.addEventListener('paste', function(e) {
            e.preventDefault();
            let pastedText = (e.clipboardData || window.clipboardData).getData('text');
            
            // Clean the pasted text
            pastedText = pastedText.replace(/\D/g, '');
            if (pastedText.length > 20) {
                pastedText = pastedText.substring(0, 20);
            }
            
            // Insert at cursor position
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            this.value = this.value.substring(0, startPos) + pastedText + this.value.substring(endPos);
            
            // Move cursor to end of inserted text
            this.selectionStart = this.selectionEnd = startPos + pastedText.length;
        });
    }

    // GCash receipt upload validation
    const gcashInput = document.getElementById('traditionalGcashReceipt');
    if (gcashInput) {
        gcashInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    alert('Please upload a JPG, JPEG, or PNG file for the GCash receipt.');
                    this.value = '';
                    document.getElementById('traditionalGcashFileName').textContent = 'No file chosen';
                    document.getElementById('gcashPreviewContainer').classList.add('hidden');
                    document.getElementById('removeGcash').classList.add('hidden');
                } else {
                    document.getElementById('traditionalGcashFileName').textContent = file.name;
                    
                    // Show preview for images
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('gcashImage').src = e.target.result;
                            document.getElementById('gcashImagePreview').classList.remove('hidden');
                            document.getElementById('gcashPreviewContainer').classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    }
                    
                    document.getElementById('removeGcash').classList.remove('hidden');
                }
            }
        });
    }
    
    // Remove GCash receipt button
    const removeGcash = document.getElementById('removeGcash');
    if (removeGcash) {
        removeGcash.addEventListener('click', function() {
            gcashInput.value = '';
            document.getElementById('traditionalGcashFileName').textContent = 'No file chosen';
            document.getElementById('gcashPreviewContainer').classList.add('hidden');
            this.classList.add('hidden');
        });
    }

    // Form submission validation
    const bookingForm = document.getElementById('traditionalBookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate required name fields
            if (!document.getElementById('traditionalDeceasedFirstName').value.trim() || 
                document.getElementById('traditionalDeceasedFirstName').value.trim().length < 2) {
                alert('Please enter a valid first name (at least 2 characters)');
                isValid = false;
            }
            
            if (!document.getElementById('traditionalDeceasedLastName').value.trim() || 
                document.getElementById('traditionalDeceasedLastName').value.trim().length < 2) {
                alert('Please enter a valid last name (at least 2 characters)');
                isValid = false;
            }
            
            // Validate date of death
            if (!document.getElementById('traditionalDateOfDeath').value) {
                alert('Please select a date of death');
                isValid = false;
            }
            
            // Validate death certificate
            if (!document.getElementById('traditionalDeathCertificate').files[0]) {
                alert('Please upload a death certificate');
                isValid = false;
            }
            
            // Validate address
            if (!document.getElementById('traditionalDeceasedRegion').value ||
                !document.getElementById('traditionalDeceasedProvince').value ||
                !document.getElementById('traditionalDeceasedCity').value ||
                !document.getElementById('traditionalDeceasedBarangay').value ||
                !document.getElementById('traditionalDeceasedStreet').value.trim()) {
                alert('Please complete the address information');
                isValid = false;
            }
            
            // Validate payment
            if (!document.getElementById('traditionalGcashReceipt').files[0]) {
                alert('Please upload a GCash receipt');
                isValid = false;
            }
            
            if (!document.getElementById('traditionalReferenceNumber').value.trim()) {
                alert('Please enter a reference number');
                isValid = false;
            }
            
            if (isValid) {
                // Combine address components into JSON string
                const address = {
                    region: document.getElementById('traditionalDeceasedRegion').value,
                    province: document.getElementById('traditionalDeceasedProvince').value,
                    city: document.getElementById('traditionalDeceasedCity').value,
                    barangay: document.getElementById('traditionalDeceasedBarangay').value,
                    street: document.getElementById('traditionalDeceasedStreet').value.trim()
                };
                document.getElementById('deceasedAddress').value = JSON.stringify(address);
                
                // Show loading indicator
                showLoader();
            } else {
                e.preventDefault();
            }
        });
    }

    // Placeholder functions for address data fetching
    function fetchProvinces(region) {
        // In a real implementation, this would be an API call
        return new Promise(resolve => {
            // Simulate API delay
            setTimeout(() => {
                const provinces = {
                    'NCR': ['Metro Manila'],
                    'CAR': ['Abra', 'Apayao', 'Benguet', 'Ifugao', 'Kalinga', 'Mountain Province'],
                    'Region I': ['Ilocos Norte', 'Ilocos Sur', 'La Union', 'Pangasinan']
                };
                resolve(provinces[region] || []);
            }, 300);
        });
    }
    
    function fetchCities(province) {
        return new Promise(resolve => {
            setTimeout(() => {
                const cities = {
                    'Metro Manila': ['Manila', 'Quezon City', 'Makati', 'Pasig', 'Taguig'],
                    'Benguet': ['Baguio City', 'La Trinidad', 'Itogon'],
                    'Pangasinan': ['Dagupan City', 'San Carlos City', 'Lingayen']
                };
                resolve(cities[province] || []);
            }, 300);
        });
    }
    
    function fetchBarangays(city) {
        return new Promise(resolve => {
            setTimeout(() => {
                const barangays = {
                    'Manila': ['Ermita', 'Malate', 'Paco', 'Pandacan'],
                    'Baguio City': ['Camp Allen', 'Camp 7', 'Loakan'],
                    'Dagupan City': ['Barangay 1', 'Barangay 2', 'Barangay 3']
                };
                resolve(barangays[city] || []);
            }, 300);
        });
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
                    <a href="..\privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                    <a href="..\termsofservice.php" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

<!-- Add this JavaScript code -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables to store custom package selections
    let selectedCasket = null;
    let selectedFlowers = null;
    let selectedAddons = [];
    let totalPackagePrice = 35000; // Starting price of 35,000 pesos
    let basePackagePrice = 35000; // Store base price separately
    
    // Get the modal elements
    const customPackageModal = document.getElementById('customPackageModal');
    const traditionalModal = document.getElementById('traditionalModal');
    
    // Function to open custom package modal
    function openCustomPackageModal() {
        // Reset selections
        resetCustomSelections();
        
        // Show the modal
        customPackageModal.classList.remove('hidden');
    }
    
    // Function to close custom package modal
    function closeCustomPackageModal() {
        customPackageModal.classList.add('hidden');
    }
    
    function fetchCasketsByBranch() {
        // Get the branch_id from session (you may need to pass this from PHP to JS)
        const branchId = <?php echo isset($_SESSION['branch_loc']) ? json_encode($_SESSION['branch_loc']) : 'null'; ?>;
        console.log("branch ID:",branchId);
        // Make an AJAX request to fetch caskets
        fetch(`booking/fetch_caskets.php?branch_id=${branchId}`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('casketOptionsContainer');
                container.innerHTML = '';
                
                if (data.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 col-span-3 text-center py-8">No caskets available for this branch.</p>';
                    return;
                }
                
                data.forEach(casket => {
                    const casketOption = document.createElement('div');
                    casketOption.className = 'border rounded-lg p-4 cursor-pointer casket-option';
                    casketOption.dataset.price = casket.price;
                    casketOption.dataset.name = casket.item_name;
                    casketOption.dataset.id = casket.inventory_id;
                    
                    casketOption.innerHTML = `
                        <img src="${casket.inventory_img ? '../admin/' + casket.inventory_img : '/api/placeholder/300/200'}" alt="${casket.item_name}" class="w-full h-32 object-cover rounded-lg mb-2">
                        <h5 class="font-medium mb-1">${casket.item_name}</h5>
                        <p class="text-yellow-600">₱${casket.price.toLocaleString()}</p>
                    `;
                    
                    casketOption.addEventListener('click', function() {
                        // Clear previous selection
                        document.querySelectorAll('.casket-option').forEach(el => {
                            el.classList.remove('border-yellow-600', 'border-2');
                        });
                        
                        // Mark this option as selected
                        this.classList.add('border-yellow-600', 'border-2');
                        
                        // Store selected casket
                        selectedCasket = {
                            id: this.dataset.id,
                            name: this.dataset.name,
                            price: parseInt(this.dataset.price)
                        };
                        
                        document.getElementById('casketID').value = selectedCasket.id;
                        console.log("clicked casket: ", selectedCasket);
                        updateCustomSummary();
                        checkRequiredSelections();
                    });
                    
                    container.appendChild(casketOption);
                });
            })
            .catch(error => {
                console.error('Error fetching caskets:', error);
                document.getElementById('casketOptionsContainer').innerHTML = 
                    '<p class="text-red-500 col-span-3 text-center py-8">Error loading caskets. Please try again.</p>';
            });
    }

    // Call this function when the custom package modal opens
    document.querySelectorAll('button.customtraditionalpckg').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            fetchCasketsByBranch();
            openCustomPackageModal();
        });
    });

    // Attach event listener to the custom package button
    document.querySelectorAll('button.customtraditionalpckg').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            openCustomPackageModal();
        });
    });
    
    // Close modal when clicking the close button
    document.querySelectorAll('.closeModalBtn').forEach(button => {
        button.addEventListener('click', function() {
            if (this.closest('#customPackageModal')) {
                closeCustomPackageModal();
            } else if (this.closest('#traditionalModal')) {
                traditionalModal.classList.add('hidden');
            }
        });
    });
    
    // Close modal when clicking outside the modal content
    customPackageModal.addEventListener('click', function(e) {
        if (e.target === customPackageModal) {
            closeCustomPackageModal();
        }
    });
    
    traditionalModal.addEventListener('click', function(e) {
        if (e.target === traditionalModal) {
            traditionalModal.classList.add('hidden');
        }
    });
    
    // Function to reset all custom selections
    function resetCustomSelections() {
        selectedCasket = null;
        selectedFlowers = null;
        selectedAddons = [];
        totalPackagePrice = basePackagePrice;
        
        // Reset UI selections
        document.querySelectorAll('.casket-option, .flower-option').forEach(el => {
            el.classList.remove('border-yellow-600', 'border-2');
        });
        
        document.querySelectorAll('.custom-addon').forEach(el => {
            el.checked = false;
        });
        
        // Reset summary but keep base price
        updateCustomSummary();
        
        // Disable continue button
        document.getElementById('proceedToBooking').disabled = true;
    }
    
    // Function to show a specific step in the custom package form
    function showCustomStep(stepId) {
        document.querySelectorAll('.custom-step').forEach(step => {
            step.classList.add('hidden');
        });
        document.getElementById(stepId).classList.remove('hidden');
    }
    
    // Casket selection
    document.querySelectorAll('.casket-option').forEach(option => {
        option.addEventListener('click', function() {
            // Clear previous selection
            document.querySelectorAll('.casket-option').forEach(el => {
                el.classList.remove('border-yellow-600', 'border-2');
            });
            
            // Mark this option as selected
            this.classList.add('border-yellow-600', 'border-2');
            
            // Store selected casket only if it has an ID
            if (this.dataset.id) {
                selectedCasket = {
                    id: this.dataset.id,
                    name: this.dataset.name,
                    price: parseInt(this.dataset.price)
                };
            } else {
                selectedCasket = null;
            }
            
            updateCustomSummary();
            checkRequiredSelections();
        });
    });
    
    
    // Flower arrangements selection
    document.querySelectorAll('.flower-option').forEach(option => {
        option.addEventListener('click', function() {
            // Clear previous selection
            document.querySelectorAll('.flower-option').forEach(el => {
                el.classList.remove('border-yellow-600', 'border-2');
            });
            
            // Mark this option as selected
            this.classList.add('border-yellow-600', 'border-2');
            
            // Store selected flowers
            selectedFlowers = {
                name: this.dataset.name,
                price: parseInt(this.dataset.price)
            };
            
            updateCustomSummary();
            checkRequiredSelections();
        });
    });
    
    // Additional services checkboxes (custom package)
    document.querySelectorAll('.custom-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Update selected addons list
            selectedAddons = [];
            document.querySelectorAll('.custom-addon:checked').forEach(checked => {
                selectedAddons.push({
                    name: checked.dataset.name,
                    price: parseInt(checked.value)
                });
            });
            
            updateCustomSummary();
        });
    });
    
    
    // Function to check if required selections are made
    function checkRequiredSelections() {
        // Enable proceed button only if all required selections are made
        const requiredSelectionsComplete = selectedFlowers;
        document.getElementById('proceedToBooking').disabled = !requiredSelectionsComplete;
    }
    
    // Function to update the custom package summary
    function updateCustomSummary() {
        const summaryElement = document.getElementById('customSelectionsSummary');
        let summaryHTML = '';
        
        // Reset total to base price
        totalPackagePrice = basePackagePrice;
        
        // Add casket info if selected (simplified check)
        if (selectedCasket) {
            summaryHTML += `<div class="flex justify-between text-sm">
                <span>${selectedCasket.name}</span>
                <span>₱${selectedCasket.price.toLocaleString()}</span>
            </div>`;
            totalPackagePrice += selectedCasket.price;
        }
        
        // Rest of the function remains the same...
        // Add flower arrangements info if selected
        if (selectedFlowers) {
            summaryHTML += `<div class="flex justify-between text-sm">
                <span>${selectedFlowers.name}</span>
                <span>₱${selectedFlowers.price.toLocaleString()}</span>
            </div>`;
            totalPackagePrice += selectedFlowers.price;
        }
        
        // Add additional services if any
        if (selectedAddons.length > 0) {
            selectedAddons.forEach(addon => {
                summaryHTML += `<div class="flex justify-between text-sm">
                    <span>${addon.name}</span>
                    <span>₱${addon.price.toLocaleString()}</span>
                </div>`;
                totalPackagePrice += addon.price;
            });
        }
        
        // Update summary section
        if (summaryHTML) {
            summaryElement.innerHTML = summaryHTML;
        } else {
            summaryElement.innerHTML = '<p class="text-gray-500 italic">No additional items selected</p>';
        }
        
        // Update total price display
        document.getElementById('customTotalPrice').textContent = `₱${totalPackagePrice.toLocaleString()}`;
        
        // Update downpayment (30%)
        const downpayment = Math.ceil(totalPackagePrice * 0.3);
        document.getElementById('customDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        console.log('Selected casket:', selectedCasket);
        console.log('Selected flowers:', selectedFlowers);
        console.log('Selected addons:', selectedAddons);
        console.log('Current total:', totalPackagePrice);
    }
    updateCustomSummary();
    
    // Proceed to booking button click event
    document.getElementById('proceedToBooking').addEventListener('click', function() {
        // Gather all custom package selections
        const packageName = 'Custom Memorial Package';

        const customPackageData = {
            packageType: 'custom',
            casket: selectedCasket,
            flowerArrangement: selectedFlowers,
            additionalServices: selectedAddons,
            notes: document.getElementById('customNotes').value,
            packageTotal: totalPackagePrice,
            downpayment: Math.ceil(totalPackagePrice * 0.3)
        };

        // Log all the data to console
        console.log('Custom Package Selections:', customPackageData);
        console.log('--- Detailed Breakdown ---');
        console.log('Casket:', customPackageData.casket ? {
            id: customPackageData.casket.id,
            name: customPackageData.casket.name,
            price: customPackageData.casket.price
        } : 'None selected');
        console.log('Flower Arrangement:', customPackageData.flowerArrangement || 'None selected');
        console.log('Additional Services:', customPackageData.additionalServices.length > 0 ? 
            customPackageData.additionalServices : 'None selected');
        console.log('Notes:', customPackageData.notes || 'No notes');
        console.log('Package Total:', customPackageData.packageTotal);
        console.log('Downpayment (30%):', customPackageData.downpayment);
        openTraditionalModalWithCustomPackage(packageName, totalPackagePrice, customPackageData);
    });
    
    // Function to open traditional booking modal with custom package data
    // Function to open traditional booking modal with custom package data
    function openTraditionalModalWithCustomPackage(packageName, packagePrice, customPackageData) {
        // Get the traditional modal elements
        const packageNameElement = document.getElementById('traditionalPackageName');
        const packagePriceElement = document.getElementById('traditionalPackagePrice');
        const packageFeaturesElement = document.getElementById('traditionalPackageFeatures');
        const hiddenPackageName = document.getElementById('traditionalSelectedPackageName');
        const hiddenPackagePrice = document.getElementById('traditionalSelectedPackagePrice');
        const totalPriceElement = document.getElementById('traditionalTotalPrice');
        const downpaymentElement = document.getElementById('traditionalDownpayment');
        const amountDueElement = document.getElementById('traditionalAmountDue');
        
        // Set the package details
        packageNameElement.textContent = packageName;
        packagePriceElement.textContent = `₱${packagePrice.toLocaleString()}`;
        hiddenPackageName.value = packageName;
        hiddenPackagePrice.value = packagePrice;
        
        // Build features list from custom package data
        packageFeaturesElement.innerHTML = '';
        
        // Add casket if selected
        if (customPackageData.casket) {
            const li = document.createElement('li');
            li.className = 'flex items-start';
            li.innerHTML = `
                <i class="fas fa-check-circle mr-2 mt-1 text-yellow-600"></i>
                <span>${customPackageData.casket.name} (₱${customPackageData.casket.price.toLocaleString()})</span>
            `;
            packageFeaturesElement.appendChild(li);
        }
        
        // Add flowers if selected
        if (customPackageData.flowerArrangement) {
            const li = document.createElement('li');
            li.className = 'flex items-start';
            li.innerHTML = `
                <i class="fas fa-check-circle mr-2 mt-1 text-yellow-600"></i>
                <span>${customPackageData.flowerArrangement.name} (₱${customPackageData.flowerArrangement.price.toLocaleString()})</span>
            `;
            packageFeaturesElement.appendChild(li);
        }
        
        // Add additional services if any
        if (customPackageData.additionalServices && customPackageData.additionalServices.length > 0) {
            customPackageData.additionalServices.forEach(service => {
                const li = document.createElement('li');
                li.className = 'flex items-start';
                li.innerHTML = `
                    <i class="fas fa-check-circle mr-2 mt-1 text-yellow-600"></i>
                    <span>${service.name} (₱${service.price.toLocaleString()})</span>
                `;
                packageFeaturesElement.appendChild(li);
            });
        }
        
        // Add notes if provided
        if (customPackageData.notes) {
            const li = document.createElement('li');
            li.className = 'flex items-start';
            li.innerHTML = `
                <i class="fas fa-check-circle mr-2 mt-1 text-yellow-600"></i>
                <span>Special Notes: ${customPackageData.notes}</span>
            `;
            packageFeaturesElement.appendChild(li);
        }
        
        // Calculate and display payment information
        const downpayment = Math.ceil(packagePrice * 0.3);
        totalPriceElement.textContent = `₱${packagePrice.toLocaleString()}`;
        downpaymentElement.textContent = `₱${downpayment.toLocaleString()}`;
        amountDueElement.textContent = `₱${downpayment.toLocaleString()}`;
        
        // Reset additional services checkboxes
        document.querySelectorAll('.traditional-addon').forEach(checkbox => {
            checkbox.checked = false;
        });

        document.getElementById('flowerDesign').value = customPackageData.flowerArrangement ? 
        customPackageData.flowerArrangement.name : '';
    
        // Convert selectedAddons array to a comma-separated string of names
        const addonNames = customPackageData.additionalServices.map(addon => addon.name);
        document.getElementById('inclusions').value = JSON.stringify(addonNames);
        
        document.getElementById('notes').value = customPackageData.notes || '';
        
        // Show the modal
        traditionalModal.classList.remove('hidden');
        // Close the custom modal
        closeCustomPackageModal();
    }
    
    document.getElementById('cremationCheckbox').addEventListener('change', function() {
        updateTraditionalPaymentSummary();
    });

    // Function to update payment summary in traditional modal
    function updateTraditionalPaymentSummary() {
        const basePrice = parseInt(document.getElementById('traditionalSelectedPackagePrice').value) || 0;
        let additionalCost = 0;
        
        // Check if cremation is selected
        const cremationCheckbox = document.getElementById('cremationCheckbox');
        if (cremationCheckbox.checked) {
            additionalCost += parseInt(cremationCheckbox.value);
        }
        
        const totalPrice = basePrice + additionalCost;
        const downpayment = Math.ceil(totalPrice * 0.3);
        
        document.getElementById('traditionalTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('traditionalSelectedPackagePrice').value = totalPrice;
        document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;
    }
    
    // Back to options button
    document.getElementById('backToOptions').addEventListener('click', function() {
        showCustomStep('customStepOptions');
    });


    

});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show traditional modal directly when package is selected
    
    document.querySelectorAll('.selectPackageBtn').forEach(button => {
        button.addEventListener('click', function() {
            console.log('Select Package button clicked');
            // Get package details from the parent card
            const packageCard = this.closest('.package-card');
            if (!packageCard) return; // Safety check
            
            const packageName = packageCard.dataset.name;
            const packagePrice = packageCard.dataset.price;
            const packageImage = packageCard.dataset.image || '';
            const serviceId = packageCard.dataset.serviceId; // Get service_id
            console.log('service1: ', packageCard.dataset.serviceId);
            
            
            document.getElementById('serviceID').value = serviceId;    

            // Store package details in sessionStorage for later use
            sessionStorage.setItem('selectedPackageName', packageName);
            sessionStorage.setItem('selectedPackagePrice', packagePrice);
            sessionStorage.setItem('selectedPackageImage', packageImage);
            sessionStorage.setItem('selectedServiceId', serviceId); // Store service_id
            
            // Get other details from the card content
            const features = Array.from(packageCard.querySelectorAll('ul li')).map(li => li.innerHTML);
            sessionStorage.setItem('selectedPackageFeatures', JSON.stringify(features));
            
            // Open traditional modal directly
            openTraditionalModal();
        });
    });
    
    // Function to open traditional modal with package details
    function openTraditionalModal() {
        // Get stored package details
        const packageName = sessionStorage.getItem('selectedPackageName');
        const packagePrice = sessionStorage.getItem('selectedPackagePrice');
        const packageImage = sessionStorage.getItem('selectedPackageImage');
        const packageFeatures = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
        const serviceId = sessionStorage.getItem('selectedServiceId');
        
        // Update modal title
        document.querySelector('#traditionalModal .font-hedvig.text-2xl.text-navy').textContent = 'Book Your Package';
        
        // Update traditional modal with package details
        document.getElementById('traditionalPackageName').textContent = packageName;
        document.getElementById('traditionalPackagePrice').textContent = `₱${parseInt(packagePrice).toLocaleString()}`;
        document.getElementById('serviceID').value = serviceId;
        console.log('service: ', serviceId);
        // Only set image src if packageImage exists
        if (packageImage) {
            document.getElementById('traditionalPackageImage').src = packageImage;
            document.getElementById('traditionalPackageImage').alt = packageName;
        }
        
        // Calculate downpayment (30%)
        const totalPrice = parseInt(packagePrice);
        const downpayment = Math.ceil(totalPrice * 0.3);
        
        document.getElementById('traditionalTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;

        // Update features list
        const featuresList = document.getElementById('traditionalPackageFeatures');
        featuresList.innerHTML = '';
        packageFeatures.forEach(feature => {
            featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
        });
        
        // Update the form's hidden fields with package info
        document.getElementById('traditionalSelectedPackageName').value = packageName;
        document.getElementById('traditionalSelectedPackagePrice').value = packagePrice;
        
        // Show traditional modal
        document.getElementById('traditionalModal').classList.remove('hidden');
    }
    
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Close modals when close button is clicked
    document.querySelectorAll('.closeModalBtn').forEach(button => {
        button.addEventListener('click', function() {
            closeAllModals();
        });
    });

    // Function to close all modals
    function closeAllModals() {
        document.getElementById('traditionalModal').classList.add('hidden');
    }

    // Close modals when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });

    // Close modals when clicking outside of modal content
    document.querySelectorAll('#traditionalModal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            // Check if the click was directly on the modal background (not its children)
            if (event.target === modal) {
                closeAllModals();
            }
        });
    });

    document.getElementById('traditionalBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        // Check if this is a custom package submission
        if (document.getElementById('traditionalSelectedPackageName').value === 'Custom Memorial Package') {
            const totalPackagePrice = parseFloat(document.getElementById('traditionalSelectedPackagePrice').value);
            
            // Create FormData object for handling files
            const formData = new FormData();
            
            // Add all the basic form fields
            formData.append('packageType', 'custom');
            formData.append('casket', document.getElementById('casketID').value);
            formData.append('flowerArrangement', document.getElementById('flowerDesign').value);
            formData.append('additionalServices', document.getElementById('inclusions').value);
            formData.append('branchId', document.getElementById('branchID').value);
            formData.append('customerId', document.getElementById('customerID').value);
            formData.append('notes', document.getElementById('customNotes').value); // Make sure this ID exists
            
            // Deceased info
            formData.append('deceasedFirstName', document.getElementById('traditionalDeceasedFirstName').value);
            formData.append('deceasedMiddleName', document.getElementById('traditionalDeceasedMiddleName').value);
            formData.append('deceasedLastName', document.getElementById('traditionalDeceasedLastName').value);
            formData.append('deceasedSuffix', document.getElementById('traditionalDeceasedSuffix').value);
            formData.append('dateOfBirth', document.getElementById('traditionalDateOfBirth').value);
            formData.append('dateOfDeath', document.getElementById('traditionalDateOfDeath').value);
            formData.append('dateOfBurial', document.getElementById('traditionalDateOfBurial').value);
            formData.append('deceasedAddress', document.getElementById('traditionalDeceasedAddress').value);
            
            // Document files - add the actual files
            if (document.getElementById('traditionalDeathCertificate').files[0]) {
                formData.append('deathCertificate', document.getElementById('traditionalDeathCertificate').files[0]);
            }
            
            if (document.getElementById('traditionalGcashReceipt').files[0]) {
                formData.append('paymentReceipt', document.getElementById('traditionalGcashReceipt').files[0]);
            }
            
            formData.append('referenceNumber', document.getElementById('traditionalReferenceNumber').value);
            formData.append('cremationSelected', document.getElementById('cremationCheckbox').checked ? 'yes' : 'no');
            formData.append('packageTotal', totalPackagePrice);
            formData.append('downpayment', Math.ceil(totalPackagePrice * 0.3));

            // For debugging - log all form data
            console.log('Submitting form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            // Send data to PHP handler
            fetch('booking/insert_custom_booking.php', {
                method: 'POST',
                body: formData // No need to set Content-Type, fetch sets it automatically with boundary for FormData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response:', data);
                if (data.status === 'success') {
                    alert('Custom package booking created successfully! Booking ID: ' + data.bookingId);
                    closeAllModals();
                    // Optionally redirect or refresh the page
                } else {
                    alert('Error creating booking: ' + data.message);
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('An error occurred while submitting the form: ' + error.message);
            });
        } else {
            const serviceId = document.getElementById('serviceID').value;
            console.log('Submitting with serviceId:', serviceId);
            // Gather all form data
            const packageData = {
            packageType: 'traditional',
            packageName: document.getElementById('traditionalSelectedPackageName').value,
            packagePrice: parseInt(document.getElementById('traditionalSelectedPackagePrice').value),
            deceasedInfo: {
                firstName: document.getElementById('traditionalDeceasedFirstName').value,
                middleName: document.getElementById('traditionalDeceasedMiddleName').value,
                lastName: document.getElementById('traditionalDeceasedLastName').value,
                suffix: document.getElementById('traditionalDeceasedSuffix').value,
                dateOfBirth: document.getElementById('traditionalDateOfBirth').value,
                dateOfDeath: document.getElementById('traditionalDateOfDeath').value,
                dateOfBurial: document.getElementById('traditionalDateOfBurial').value,
                address: document.getElementById('traditionalDeceasedAddress').value
            },
            documents: {
                deathCertificate: document.getElementById('traditionalDeathCertificate').files[0]?.name || null,
                paymentReceipt: document.getElementById('traditionalGcashReceipt').files[0]?.name || null,
                referenceNumber: document.getElementById('traditionalReferenceNumber').value
            },
            additionalServices: [],
            cremationSelected: document.getElementById('cremationCheckbox').checked,
            packageTotal: parseInt(document.getElementById('traditionalSelectedPackagePrice').value),
            downpayment: Math.ceil(parseInt(document.getElementById('traditionalSelectedPackagePrice').value) * 0.3),
            serviceId: document.getElementById('serviceID').value
            };

            // Log data for debugging (can be removed in production)
            console.log('Traditional Package Booking Data:', packageData);
            console.log('--- Detailed Breakdown ---');
            console.log('Package Name:', packageData.packageName);
            console.log('Package Price:', packageData.packagePrice);
            console.log('Deceased Information:', packageData.deceasedInfo);
            console.log('Documents:', packageData.documents);
            console.log('Additional Services:', packageData.additionalServices);
            console.log('Cremation Selected:', packageData.cremationSelected);
            console.log('Package Total:', packageData.packageTotal);
            console.log('Downpayment (30%):', packageData.downpayment);

            // Create FormData to handle file uploads
            const formData = new FormData();
            
            // Add structured data as JSON string
            formData.append('packageData', JSON.stringify(packageData));
            
            // Add the files directly for upload
            if (document.getElementById('traditionalDeathCertificate').files[0]) {
                formData.append('deathCertificate', document.getElementById('traditionalDeathCertificate').files[0]);
            }
            
            if (document.getElementById('traditionalGcashReceipt').files[0]) {
                formData.append('paymentReceipt', document.getElementById('traditionalGcashReceipt').files[0]);
            }
            
            // Add individual fields for compatibility with existing PHP code
            formData.append('serviceId', serviceId);
            formData.append('branchId', document.getElementById('branchID').value);
            formData.append('customerId', document.getElementById('customerID').value);
            formData.append('packageName', packageData.packageName);
            formData.append('packagePrice', packageData.packagePrice);
            formData.append('cremationSelected', packageData.cremationSelected);
            formData.append('packageTotal', packageData.packageTotal);

            
            // Add deceased info fields
            Object.entries(packageData.deceasedInfo).forEach(([key, value]) => {
                formData.append(`deceased_${key}`, value);
            });
            
            formData.append('referenceNumber', packageData.documents.referenceNumber);
            
            // Send to server
            fetch('booking/insert_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Booking successful!');
                    closeAllModals();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the form.');
            });
        }
    });

});
    
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
</script>


<?php include 'customService/chat_elements.html'; ?>
</body>
</html>
