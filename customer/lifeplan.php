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
                $query = "SELECT first_name , last_name , branch_loc , email , birthdate FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $first_name = $row['first_name']; // We're confident user_id exists
                $last_name = $row['last_name'];
                $email = $row['email'];
                $branch_id = $row['branch_loc'];
                
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
                
                function getImageUrl($image_path) {
                    if (empty($image_path)) {
                        return 'assets/images/placeholder.jpg';
                    }
                    
                    if (!preg_match('/^(http|\/)/i', $image_path)) {
                        return '../../admin/servicesManagement/' . $image_path;
                    }
                    
                    return $image_path;
                }

                $query = "SELECT s.service_id, s.branch_id, s.service_name, s.description, s.selling_price, s.image_url, 
                                 i.item_name AS casket_name, s.flower_design, s.inclusions
                          FROM services_tb s
                          LEFT JOIN inventory_tb i ON s.casket_id = i.inventory_id
                          WHERE s.status = 'active' AND s.branch_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $branch_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $packagesFromDB = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                

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
                while ($row = $result->fetch_assoc()) {
                    $row['image_url'] = getImageUrl($row['image_url']);
                    $packagesFromDB[] = $row;
                }
                // Convert to JSON for JavaScript
                $packagesJson = json_encode($packagesFromDB);




?>

<script src="customer_support.js"></script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - LifePlan</title>
    <?php include 'faviconLogo.php'; ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600&family=Cinzel:wght@400;500;600;700&family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../tailwind.js"></script>
    <style>
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
        }
        .modal {
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }
        .modal.active {
            opacity: 1;
            pointer-events: auto;
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

<!-- Cremation Services Main Content -->
<div class="bg-cream py-20">
    <div class="container mx-auto px-6 max-w-6xl">
        <!-- Hero Section -->
        <div class="relative rounded-2xl overflow-hidden mb-16 shadow-xl">
            <div class="h-64 bg-cover bg-center" style="background-image: url('../Landing_Page/Landing_images/black-bg-image.jpg')">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 flex flex-col justify-center items-center text-white p-8">
                    <h1 class="text-5xl font-hedvig text-center mb-6">Life Plan</h1>
                    <p class="text-lg max-w-3xl text-center">Taking the time to plan now means peace of mind for both you and your loved ones later.</p>
                </div>
            </div>
        </div>

        <!-- Benefits Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <!-- Benefit 1 -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <i class="fas fa-heart text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy text-center mb-4">Emotional Peace</h3>
                <p class="text-dark text-sm text-center">Relieve your loved ones of the burden of making difficult decisions during their time of grief.</p>
            </div>
            
            <!-- Benefit 2 -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <i class="fas fa-coins text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy text-center mb-4">Financial Security</h3>
                <p class="text-dark text-sm text-center">Lock in today's prices and protect your family from future inflation and unexpected costs.</p>
            </div>
            
            <!-- Benefit 3 -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <i class="fas fa-clipboard-check text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy text-center mb-4">Personal Wishes</h3>
                <p class="text-dark text-sm text-center">Ensure your life is celebrated exactly how you envision, with every detail respected and honored.</p>
            </div>
        </div>
        
        <section id="lifeplan" class="mb-8 mt-16">
    <div class="flex justify-center mb-8">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mr-4">
                <i class="fas fa-clipboard-list text-2xl"></i>
            </div>
            <h2 class="font-hedvig text-4xl text-navy">Life Plan</h2>
        </div>
    </div>

    <!-- Paragraph - Centered -->  
    <div class="flex justify-center mb-8">  
        <p class="text-dark max-w-3xl text-center">We provide flexible and compassionate life plan options, including a 5-year installment payment plan with 0% interest for your chosen traditional package, ensuring affordability without compromising dignity or care.</p>  
    </div>  
    
    <div class="max-w-lg mx-auto">
        <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col max-h-[480px]" data-price="35000" data-service="cremate" data-name="Direct Cremation">
            <div class="h-12 bg-navy flex items-center justify-center">
                <h4 class="text-white font-hedvig text-xl">Flexible Payment Plan</h4>
                <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                    <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                </div>
            </div>
            <div class="py-7 px-5 flex flex-col">
                <div class="text-center mb-4">
                    <span class="text-3xl font-hedvig text-navy">5-Year Payment Option</span>
                    <p class="text-dark mt-2">Total Package Price by 60 Months</p>
                </div>
                <ul class="space-y-2 mb-4">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">0% Interest Guaranteed</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Fixed Monthly Payment</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">No Hidden Fees</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Spread Cost Over 5 Years</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Equal Monthly Installments</span>
                    </li>
                </ul>
                <button class="block w-full mt-4 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                    Select Package
                </button>
            </div>
        </div>
    </div>
</section>
        
        <!-- How It Works Section - Improved Timeline Design -->
        <div class="mb-8 mt-16">
            <div class="text-center mb-12">
                <h3 class="text-5xl font-hedvig text-navy mb-4">How Life Planning Works</h3>
                <p class="text-dark max-w-3xl mx-auto">Our simple process guides you through each step of creating your personalized life plan.</p>
            </div>
            
            <!-- Timeline Container -->
            <div class="relative mx-auto px-4">
                <!-- Connecting Line - Vertical on mobile, Horizontal on larger screens -->
                <div class="hidden md:block absolute top-1/2 left-0 right-0 h-1 bg-yellow-600 -translate-y-1/2 z-0"></div>
                <div class="md:hidden absolute left-1/2 top-0 bottom-0 w-1 bg-yellow-600 -translate-x-1/2 z-0"></div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 md:gap-8 relative z-10">
                    <!-- Step 1 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">1</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Consultation</h4>
                            <p class="text-dark text-sm">Meet with our planning specialists to discuss your wishes and preferences. We'll guide you through available options.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-handshake text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">2</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Personalization</h4>
                            <p class="text-dark text-sm">Customize your plan with specific selections and personal touches that reflect your wishes and values.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-paint-brush text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">3</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Documentation</h4>
                            <p class="text-dark text-sm">Receive comprehensive documentation of your arrangements and preferences for your records.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-file-signature text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">4</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Peace of Mind</h4>
                            <p class="text-dark text-sm">Rest assured knowing your wishes will be honored and your loved ones protected from difficult decisions.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-heart text-3xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
<div class="container mx-auto px-6 py-12 max-w-3xl">
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-5xl font-hedvig text-navy mb-4">We're Here to Help</h2>
            <p class="text-dark text-lg max-w-2xl mx-auto">Find answers to the most common questions about VJay Relova Funeral Services and our bereavement support.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
        </div>

<!-- FAQ Accordion -->
<div class="space-y-6">
            <!-- FAQ Item 1 -->
            <!-- FAQ Item on Pre-need Planning -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">Do you offer lifeplans?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>Yes, we offer lifeplans for those who wish to arrange their funeral services in advance. Our pre-need plans offer several advantages:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>Complete packages with all necessary services included</li>
                <li>Flexible, negotiable pricing unlike the fixed prices of other pre-need providers</li>
                <li>Customizable options to meet your specific preferences</li>
                <li>No hidden charges for special requests</li>
            </ul>
            <p class="mt-4">Many clients choose our pre-need plans over other providers because we provide more comprehensive packages without charging extra for additional requests. Our personalized approach ensures that all your wishes are honored exactly as you specify.</p>
            <p class="mt-2">To discuss pre-need planning options, we recommend scheduling an in-person consultation with our owner, Virgillo Jay G. Relova, who personally meets with each client to understand their specific needs and build a connection that ensures the highest quality of service.</p>
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

    <!-- JavaScript for FAQ Accordion -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const answer = this.nextElementSibling;
                    const icon = this.querySelector('.fa-chevron-down');
                    
                    // Toggle current answer
                    answer.classList.toggle('hidden');
                    icon.classList.toggle('rotate-180');
                });
            });
        })
    </script>    

                <!-- Testimonials Section -->
                <div class="bg-navy/5 py-12 px-6 rounded-xl mb-16">
                    <h3 class="text-3xl font-hedvig text-navy text-center mb-12">What Our Clients Say</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <?php
                        // Include database connection
                        include '../db_connect.php';
                        
                        // Fetch feedback with status 'Show' and service type 'life-plan' only
                        $sql = "SELECT f.*, 
                                       CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name, 
                                              CASE WHEN u.suffix IS NOT NULL AND u.suffix != '' THEN CONCAT(' ', u.suffix) ELSE '' END) as customer_name
                                FROM feedback_tb f 
                                INNER JOIN users u ON f.customer_id = u.id 
                                WHERE f.status = 'Show' 
                                AND f.service_type = 'life-plan'
                                ORDER BY f.created_at DESC 
                                LIMIT 2";
                        
                        $result = $conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                // Generate star rating based on rating value
                                $stars = '';
                                $rating = $row['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        $stars .= '<i class="fas fa-star"></i>';
                                    } else {
                                        $stars .= '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <!-- Dynamic Life Plan Testimonial -->
                                <div class="bg-white p-6 rounded-lg shadow-md">
                                    <div class="flex items-center mb-4">
                                        <div class="text-yellow-600 mr-3">
                                            <i class="fas fa-quote-left text-3xl"></i>
                                        </div>
                                        <div>
                                            <div class="text-yellow-600 mb-1">
                                                <?php echo $stars; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-dark italic mb-4"><?php echo htmlspecialchars($row['feedback_text']); ?></p>
                                    <p class="font-hedvig text-navy">- <?php echo htmlspecialchars($row['customer_name']); ?></p>
                                </div>
                                <?php
                            }
                        } else {
                            // Fallback to sample testimonials if no life-plan feedback found
                            ?>
                            <!-- Fallback Life Plan Testimonial 1 -->
                            <div class="bg-white p-6 rounded-lg shadow-md">
                                <div class="flex items-center mb-4">
                                    <div class="text-yellow-600 mr-3">
                                        <i class="fas fa-quote-left text-3xl"></i>
                                    </div>
                                    <div>
                                        <div class="text-yellow-600 mb-1">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-dark italic mb-4">Planning ahead with GrievEase was one of the best decisions I've made. The team was compassionate and patient, guiding me through every option. I now have peace of mind knowing my family won't face difficult decisions later.</p>
                                <p class="font-hedvig text-navy">- Maria Reyes, 67</p>
                            </div>
                            
                            <!-- Fallback Life Plan Testimonial 2 -->
                            <div class="bg-white p-6 rounded-lg shadow-md">
                                <div class="flex items-center mb-4">
                                    <div class="text-yellow-600 mr-3">
                                        <i class="fas fa-quote-left text-3xl"></i>
                                    </div>
                                    <div>
                                        <div class="text-yellow-600 mb-1">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-dark italic mb-4">After experiencing the stress of arranging a funeral for my father, I decided to plan ahead for myself. The Premium Plan offered exactly what I wanted, and the financial arrangements were clear and secure.</p>
                                <p class="font-hedvig text-navy">- Antonio Lim, 58</p>
                            </div>
                            <?php
                        }
                        
                        // Close connection
                        $conn->close();
                        ?>
                    </div>
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
                    <a href="termsofservice.php" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Traditional Packages Selection Modal (Hidden by Default) -->
<div id="traditionalPackagesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-6xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh]">
        <div class="flex flex-col max-h-[90vh]">
            <!-- Header with close button -->
            <div class="bg-navy p-6 flex justify-between items-center">
                <h2 class="text-2xl font-hedvig text-white">Select Traditional Package</h2>
                <button class="closePackagesModalBtn text-white hover:text-yellow-300">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Packages grid -->
            <div class="p-6 bg-cream overflow-y-auto modal-scroll-container">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="traditionalPackagesGrid">
                    <!-- Packages will be dynamically inserted here -->
                    
                </div>
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
                <div class="flex justify-between items-center mb-2 md:mb-2">
                    <h2 id="traditionalPackageName" class="text-xl md:text-2xl font-hedvig text-navy"></h2>
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

                
                
                <!-- Mobile-only continue button -->
                <div class="mt-6 border-t border-gray-200 pt-4 md:hidden">
                    <button type="button" id="continueToFormBtn" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                        Continue to Booking
                    </button>
                </div>
            </div>

            <!-- Right Side: Traditional Booking Form -->
            <div class="bg-white p-4 md:p-8 border-t md:border-t-0 md:border-l border-gray-200 overflow-y-auto form-section hidden md:block">
                <!-- Header and back button for mobile -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl md:text-2xl font-hedvig text-navy">Book Your Package</h2>
                    <div class="flex items-center">
                        <button type="button" id="backToDetailsBtn" class="mr-2 text-gray-500 hover:text-navy md:hidden flex items-center">
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
                    <input type="hidden" id="deceasedAddress" name="deceasedAddress">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Benefeciary Information</h3>
                        
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
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Comaker Information</h3>
                        
                        <!-- First Name & Middle Name (Side by side) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="comakerFirstName" class="block text-sm font-medium text-navy mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" id="comakerFirstName" name="comakerFirstName" required 
                                    class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" 
                                    pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" 
                                    title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="comakerMiddleName" class="block text-sm font-medium text-navy mb-1">Middle Name</label>
                                <input type="text" id="comakerMiddleName" name="comakerMiddleName" 
                                    class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" 
                                    pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" 
                                    title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                        </div>
                        
                        <!-- Last Name & Suffix (Side by side) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-3/4 px-2 mb-3 sm:mb-0">
                                <label for="comakerLastName" class="block text-sm font-medium text-navy mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" id="comakerLastName" name="comakerLastName" required 
                                    class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" 
                                    pattern="[A-Za-z'-][A-Za-z'-]*( [A-Za-z'-]+)*" 
                                    title="Please enter a valid name (letters only, no leading spaces, numbers or symbols)">
                            </div>
                            <div class="w-full sm:w-1/4 px-2">
                                <label for="comakerSuffix" class="block text-sm font-medium text-navy mb-1">Suffix</label>
                                <select id="comakerSuffix" name="comakerSuffix" 
                                    class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
                        
                        <!-- Occupation -->
                        <div class="mb-3">
                            <label for="comakerOccupation" class="block text-sm font-medium text-navy mb-1">Occupation <span class="text-red-500">*</span></label>
                            <input type="text" id="comakerOccupation" name="comakerOccupation" required 
                                class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" 
                                placeholder="Enter occupation">
                        </div>
                        
                        <!-- Address Section -->
                        <div class="mb-4">
                            <input type="hidden" id="comakerAddress" name="comakerAddress">
                            <h4 class="text-sm font-medium text-navy mb-2">Address</h4>
                            
                            <!-- Region & Province (Side by side) -->
                            <div class="flex flex-wrap -mx-2 mb-3">
                                <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                    <label for="comakerRegion" class="block text-sm font-medium text-navy mb-1">Region <span class="text-red-500">*</span></label>
                                    <select id="comakerRegion" name="comakerRegion" required
                                        class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                        <option value="">Select Region</option>
                                        <!-- Regions will be populated by JavaScript -->
                                    </select>
                                </div>
                                <div class="w-full sm:w-1/2 px-2">
                                    <label for="comakerProvince" class="block text-sm font-medium text-navy mb-1">Province/City <span class="text-red-500">*</span></label>
                                    <select id="comakerProvince" name="comakerProvince" required disabled
                                        class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                        <option value="">Select Province/City</option>
                                        <!-- Provinces will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Municipality & Barangay (Side by side) -->
                            <div class="flex flex-wrap -mx-2 mb-3">
                                <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                    <label for="comakerMunicipality" class="block text-sm font-medium text-navy mb-1">Municipality <span class="text-red-500">*</span></label>
                                    <select id="comakerMunicipality" name="comakerMunicipality" required disabled
                                        class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                        <option value="">Select Municipality</option>
                                        <!-- Municipalities will be populated by JavaScript -->
                                    </select>
                                </div>
                                <div class="w-full sm:w-1/2 px-2">
                                    <label for="comakerBarangay" class="block text-sm font-medium text-navy mb-1">Barangay <span class="text-red-500">*</span></label>
                                    <select id="comakerBarangay" name="comakerBarangay" required disabled
                                        class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                        <option value="">Select Barangay</option>
                                        <!-- Barangays will be populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Street & Zip Code (Side by side) -->
                            <div class="flex flex-wrap -mx-2 mb-3">
                                <div class="w-full sm:w-4/4 px-2 mb-3 sm:mb-0">
                                    <label for="comakerStreet" class="block text-sm font-medium text-navy mb-1">Street/Block/House Number <span class="text-red-500">*</span></label>
                                    <input type="text" id="comakerStreet" name="comakerStreet" required 
                                        class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" 
                                        placeholder="e.g. 123 Main Street">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Valid ID Upload Section -->
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-navy mb-2">Valid ID</h4>
                            
                            <!-- ID Type Selection -->
                            <div class="mb-3">
                                <label for="comakerIdType" class="block text-sm font-medium text-navy mb-1">ID Type <span class="text-red-500">*</span></label>
                                <select id="comakerIdType" name="comakerIdType" required
                                    class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">Select ID Type</option>
                                    <option value="passport">Passport</option>
                                    <option value="drivers_license">Driver's License</option>
                                    <option value="sss_id">SSS ID</option>
                                    <option value="philhealth_id">PhilHealth ID</option>
                                    <option value="tin_id">TIN ID</option>
                                    <option value="postal_id">Postal ID</option>
                                    <option value="voters_id">Voter's ID</option>
                                    <option value="prc_id">PRC ID</option>
                                    <option value="umid">UMID</option>
                                    <option value="senior_citizen_id">Senior Citizen ID</option>
                                    <option value="pwd_id">PWD ID</option>
                                    <option value="barangay_id">Barangay ID</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <!-- ID Number -->
                            <div class="mb-3">
                                <label for="comakerIdNumber" class="block text-sm font-medium text-navy mb-1">ID Number/Code <span class="text-red-500">*</span></label>
                                <input type="text" id="comakerIdNumber" name="comakerIdNumber" required 
                                    class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" 
                                    placeholder="Enter ID number or code">
                            </div>
                            
                            <!-- ID Image Upload -->
                            <div class="mb-3">
                                <label for="comakerIdImage" class="block text-sm font-medium text-navy mb-1">Upload Valid ID <span class="text-red-500">*</span></label>
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
                                            <img id="comakerIdImageDisplay" src="" alt="ID Preview" class="w-full h-auto max-h-48 object-contain">
                                        </div>
                                    </div>
                                    
                                    <!-- Remove Button -->
                                    <button type="button" id="removeComakerIdImage" class="text-xs text-red-600 hover:text-red-800 mt-2 hidden">
                                        <i class="fas fa-trash-alt mr-1"></i> Remove file
                                    </button>
                                    
                                    <input type="file" id="comakerIdImage" name="comakerIdImage" accept=".jpg,.jpeg,.png,.pdf" class="hidden" required>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG, PDF (Max 5MB)</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Additional Services</h3>
                        
                        <div class="mb-3">
                            <div class="flex items-center">
                                <input type="checkbox" id="cremationOption" name="cremationOption" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded" value="40000">
                                <label for="cremationOption" class="ml-2 block text-sm text-navy">
                                    Include Cremation Services (+₱40,000)
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 ml-6">Select this option if you wish to include cremation services in your Lifeplan package.</p>
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
                                    <button type="button" id="lifeplanCloseQrModal" class="text-gray-500 hover:text-navy">
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
                                <button type="button" id="closePrivacyModal" class="text-gray-500 hover:text-navy">
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
                                    <li><strong>Secure Storage:</strong> Personal data is stored in secure, encrypted databases with restricted access.</li>
                                    <li><strong>Encryption:</strong> We use SSL encryption for data transmission between your browser and our servers.</li>
                                    <li><strong>Access Controls:</strong> Only authorized staff with specific job functions have access to personal information.</li>
                                    <li><strong>Regular Audits:</strong> We conduct regular security assessments and staff training on privacy practices.</li>
                                </ul>

                                <h4 class="font-medium text-navy">Cookies and Tracking</h4>
                                <p>Our website uses cookies and similar technologies to enhance your browsing experience and collect information about how you use our site.</p>
                                <p><strong>Types of Cookies We Use:</strong></p>
                                <ul class="list-disc ml-5 space-y-1">
                                    <li>Essential Cookies: Required for the website to function properly</li>
                                    <li>Analytical Cookies: Help us understand how visitors interact with our website</li>
                                    <li>Functional Cookies: Remember your preferences and settings</li>
                                </ul>
                                <p>You can control cookie settings through your browser preferences. However, disabling certain cookies may affect the functionality of our website.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Terms of Service Modal -->
                        <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-hedvig text-navy">Terms of Service</h3>
                                <button type="button" id="closeTermsModal" class="text-gray-500 hover:text-navy">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            <div class="text-sm text-gray-700 space-y-4">
                                <p>By accessing and using the GrievEase website and services, you agree to be bound by these Terms of Service.</p>
                                <p>Last Updated: March 2024</p>

                                <h4 class="font-medium text-navy">1. Acceptance of Terms</h4>
                                <p>By accessing and using the GrievEase website and services, you agree to be bound by these Terms of Service. If you do not agree with these terms, please do not use our services.</p>

                                <h4 class="font-medium text-navy">2. Services</h4>
                                <p>GrievEase provides funeral and memorial services with the utmost compassion and respect. We reserve the right to modify, suspend, or discontinue any aspect of our services at any time.</p>

                                <h4 class="font-medium text-navy">3. User Responsibilities</h4>
                                <ul class="list-disc ml-5 space-y-1">
                                    <li>Provide accurate and complete information during service arrangements</li>
                                    <li>Respect the guidelines and policies of our funeral home</li>
                                    <li>Treat our staff with dignity and respect</li>
                                </ul>

                                <h4 class="font-medium text-navy">4. Privacy</h4>
                                <p>We are committed to protecting your privacy. Please review our Privacy Policy, which explains how we collect, use, and protect your personal information.</p>

                                <h4 class="font-medium text-navy">5. Payment and Fees</h4>
                                <p>All fees for services are due at the time of service unless otherwise arranged. We accept various payment methods and can discuss payment plans during consultation.</p>

                                <h4 class="font-medium text-navy">6. Limitation of Liability</h4>
                                <p>GrievEase strives to provide compassionate and professional services. However, we are not liable for any indirect, incidental, or consequential damages arising from our services.</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="lifeplanSubmitBtn" 
                        class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 opacity-50 cursor-not-allowed" 
                        disabled>
                        Confirm Lifeplan Booking
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Add JavaScript for comaker ID image upload functionality
document.addEventListener('DOMContentLoaded', function() {
    const comakerIdInput = document.getElementById('comakerIdImage');
    const comakerIdFileName = document.getElementById('comakerIdFileName');
    const comakerIdPreviewContainer = document.getElementById('comakerIdPreviewContainer');
    const comakerIdImagePreview = document.getElementById('comakerIdImagePreview');
    const comakerIdImageDisplay = document.getElementById('comakerIdImageDisplay');
    const removeComakerIdImageBtn = document.getElementById('removeComakerIdImage');

    // Handle comaker ID image upload
    if (comakerIdInput) {
        comakerIdInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Update file name display
                comakerIdFileName.textContent = file.name;
                
                // Show preview container
                comakerIdPreviewContainer.classList.remove('hidden');
                removeComakerIdImageBtn.classList.remove('hidden');
                
                // Handle different file types
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        comakerIdImageDisplay.src = e.target.result;
                        comakerIdImagePreview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    // For PDF files, show a placeholder
                    comakerIdImagePreview.classList.add('hidden');
                    comakerIdPreviewContainer.innerHTML = '<div class="p-4 text-center"><i class="fas fa-file-pdf text-red-500 text-3xl mb-2"></i><p class="text-sm text-gray-600">' + file.name + '</p></div>';
                }
            }
        });
    }

    // Handle remove comaker ID image
    if (removeComakerIdImageBtn) {
        removeComakerIdImageBtn.addEventListener('click', function() {
            comakerIdInput.value = '';
            comakerIdFileName.textContent = 'No file chosen';
            comakerIdPreviewContainer.classList.add('hidden');
            comakerIdImagePreview.classList.add('hidden');
            removeComakerIdImageBtn.classList.add('hidden');
            comakerIdImageDisplay.src = '';
        });
    }

    // Add address dropdown functionality for comaker
    // You'll need to integrate this with your existing address API calls
    const comakerRegion = document.getElementById('comakerRegion');
    const comakerProvince = document.getElementById('comakerProvince');
    const comakerMunicipality = document.getElementById('comakerMunicipality');
    const comakerBarangay = document.getElementById('comakerBarangay');

    if (comakerRegion) {
        comakerRegion.addEventListener('change', function() {
            if (this.value) {
                comakerProvince.disabled = false;
                // Load provinces based on selected region
                // You'll need to integrate with your existing address loading function
            } else {
                comakerProvince.disabled = true;
                comakerMunicipality.disabled = true;
                comakerBarangay.disabled = true;
            }
        });
    }

    if (comakerProvince) {
        comakerProvince.addEventListener('change', function() {
            if (this.value) {
                comakerMunicipality.disabled = false;
                // Load municipalities based on selected province
            } else {
                comakerMunicipality.disabled = true;
                comakerBarangay.disabled = true;
            }
        });
    }

    if (comakerMunicipality) {
        comakerMunicipality.addEventListener('change', function() {
            if (this.value) {
                comakerBarangay.disabled = false;
                // Load barangays based on selected municipality
            } else {
                comakerBarangay.disabled = true;
            }
        });
    }
});
</script>

<script>
// Comaker Address handling functions
function fetchComakerRegions() {
    fetch('address/get_regions.php')
        .then(response => response.json())
        .then(data => {
            const regionSelect = document.getElementById('comakerRegion');
            regionSelect.innerHTML = '<option value="">Select Region</option>';
            
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching regions for comaker:', error));
}

function fetchComakerProvinces(regionId) {
    const provinceSelect = document.getElementById('comakerProvince');
    provinceSelect.innerHTML = '<option value="">Select Province/City</option>';
    provinceSelect.disabled = true;
    
    if (!regionId) return;
    
    fetch(`address/get_provinces.php?region_id=${regionId}`)
        .then(response => response.json())
        .then(data => {
            provinceSelect.innerHTML = '<option value="">Select Province/City</option>';
            
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error fetching provinces for comaker:', error));
}

function fetchComakerMunicipalities(provinceId) {
    const municipalitySelect = document.getElementById('comakerMunicipality');
    municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
    municipalitySelect.disabled = true;
    
    if (!provinceId) return;
    
    fetch(`address/get_cities.php?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
            
            data.forEach(municipality => {
                const option = document.createElement('option');
                option.value = municipality.municipality_id;
                option.textContent = municipality.municipality_name;
                municipalitySelect.appendChild(option);
            });
            
            municipalitySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching municipalities for comaker:', error));
}

function fetchComakerBarangays(municipalityId) {
    const barangaySelect = document.getElementById('comakerBarangay');
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (!municipalityId) return;
    
    fetch(`address/get_barangays.php?city_id=${municipalityId}`)
        .then(response => response.json())
        .then(data => {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching barangays for comaker:', error));
}

function updateComakerCombinedAddress() {
    const regionSelect = document.getElementById('comakerRegion');
    const provinceSelect = document.getElementById('comakerProvince');
    const municipalitySelect = document.getElementById('comakerMunicipality');
    const barangaySelect = document.getElementById('comakerBarangay');
    const streetAddress = document.getElementById('comakerStreet').value;
    
    // Get the TEXT values of the selected options, not the IDs
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const municipality = municipalitySelect.options[municipalitySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    // Create an array of non-empty address components
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (municipality) addressParts.push(municipality);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    
    // Join the parts with commas
    const combinedAddress = addressParts.join(', ');
    document.getElementById('comakerAddress').value = combinedAddress;
}

// Initialize comaker address dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchComakerRegions();
    
    // Set up event listeners for cascading dropdowns
    document.getElementById('comakerRegion').addEventListener('change', function() {
        fetchComakerProvinces(this.value);
        document.getElementById('comakerProvince').value = '';
        document.getElementById('comakerMunicipality').value = '';
        document.getElementById('comakerBarangay').value = '';
        document.getElementById('comakerMunicipality').disabled = true;
        document.getElementById('comakerBarangay').disabled = true;
        updateComakerCombinedAddress();
    });
    
    document.getElementById('comakerProvince').addEventListener('change', function() {
        fetchComakerMunicipalities(this.value);
        document.getElementById('comakerMunicipality').value = '';
        document.getElementById('comakerBarangay').value = '';
        document.getElementById('comakerBarangay').disabled = true;
        updateComakerCombinedAddress();
    });
    
    document.getElementById('comakerMunicipality').addEventListener('change', function() {
        fetchComakerBarangays(this.value);
        document.getElementById('comakerBarangay').value = '';
        updateComakerCombinedAddress();
    });
    
    document.getElementById('comakerBarangay').addEventListener('change', updateComakerCombinedAddress);
    document.getElementById('comakerStreet').addEventListener('input', updateComakerCombinedAddress);
    
    // Also update combined address when form is submitted
    const form = document.getElementById('lifeplanBookingForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            updateComakerCombinedAddress();
            // Continue with form submission
        });
    }
});
</script>

<script>
// Function to toggle submit button state
function toggleSubmitButton() {
    const checkbox = document.getElementById('termsCheckbox');
    const submitBtn = document.getElementById('lifeplanSubmitBtn');
    
    if (checkbox.checked) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

// Add event listener to checkbox
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('termsCheckbox');
    if (checkbox) {
        checkbox.addEventListener('change', toggleSubmitButton);
        // Initialize button state
        toggleSubmitButton();
    }

    // Privacy Policy Modal Functionality
    const privacyPolicyLink = document.getElementById('viewPrivacyPolicy');
    const privacyPolicyModal = document.getElementById('privacyPolicyModal');
    const closePrivacyModal = document.getElementById('closePrivacyModal');

    if (privacyPolicyLink && privacyPolicyModal) {
        privacyPolicyLink.addEventListener('click', function(e) {
            e.preventDefault();
            privacyPolicyModal.classList.remove('hidden');
        });
    }

    if (closePrivacyModal && privacyPolicyModal) {
        closePrivacyModal.addEventListener('click', function() {
            privacyPolicyModal.classList.add('hidden');
        });
    }

    // Terms of Service Modal Functionality
    const termsOfServiceLink = document.getElementById('viewTermsOfService');
    const termsOfServiceModal = document.getElementById('termsOfServiceModal');
    const closeTermsModal = document.getElementById('closeTermsModal');

    if (termsOfServiceLink && termsOfServiceModal) {
        termsOfServiceLink.addEventListener('click', function(e) {
            e.preventDefault();
            termsOfServiceModal.classList.remove('hidden');
        });
    }

    if (closeTermsModal && termsOfServiceModal) {
        closeTermsModal.addEventListener('click', function() {
            termsOfServiceModal.classList.add('hidden');
        });
    }

    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === privacyPolicyModal) {
            privacyPolicyModal.classList.add('hidden');
        }
        if (e.target === termsOfServiceModal) {
            termsOfServiceModal.classList.add('hidden');
        }
    });

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (privacyPolicyModal) privacyPolicyModal.classList.add('hidden');
            if (termsOfServiceModal) termsOfServiceModal.classList.add('hidden');
        }
    });
});
</script>

<script>
        // Address handling functions
function fetchRegions() {
    fetch('address/get_regions.php')
        .then(response => response.json())
        .then(data => {
            const regionSelect = document.getElementById('traditionalDeceasedRegion');
            regionSelect.innerHTML = '<option value="">Select Region</option>';
            
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching regions:', error));
}

function fetchProvinces(regionId) {
    const provinceSelect = document.getElementById('traditionalDeceasedProvince');
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    provinceSelect.disabled = true;
    
    if (!regionId) return;
    
    fetch(`address/get_provinces.php?region_id=${regionId}`)
        .then(response => response.json())
        .then(data => {
            provinceSelect.innerHTML = '<option value="">Select Province</option>';
            
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error fetching provinces:', error));
}

function fetchCities(provinceId) {
    const citySelect = document.getElementById('traditionalDeceasedCity');
    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
    citySelect.disabled = true;
    
    if (!provinceId) return;
    
    fetch(`address/get_cities.php?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.municipality_id;
                option.textContent = city.municipality_name;
                citySelect.appendChild(option);
            });
            
            citySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching cities:', error));
}

function fetchBarangays(cityId) {
    const barangaySelect = document.getElementById('traditionalDeceasedBarangay');
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (!cityId) return;
    
    fetch(`address/get_barangays.php?city_id=${cityId}`)
        .then(response => response.json())
        .then(data => {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching barangays:', error));
}

function updateCombinedAddress() {
    const regionSelect = document.getElementById('traditionalDeceasedRegion');
    const provinceSelect = document.getElementById('traditionalDeceasedProvince');
    const citySelect = document.getElementById('traditionalDeceasedCity');
    const barangaySelect = document.getElementById('traditionalDeceasedBarangay');
    const streetAddress = document.getElementById('traditionalDeceasedAddress').value;
    
    // Get the TEXT values of the selected options, not the IDs
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const city = citySelect.options[citySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    // Create an array of non-empty address components
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (city) addressParts.push(city);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    
    // Join the parts with commas
    const combinedAddress = addressParts.join(', ');
    document.getElementById('deceasedAddress').value = combinedAddress;
}

// Initialize address dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchRegions();
    
    // Set up event listeners for cascading dropdowns
    document.getElementById('traditionalDeceasedRegion').addEventListener('change', function() {
        fetchProvinces(this.value);
        document.getElementById('traditionalDeceasedProvince').value = '';
        document.getElementById('traditionalDeceasedCity').value = '';
        document.getElementById('traditionalDeceasedBarangay').value = '';
        document.getElementById('traditionalDeceasedCity').disabled = true;
        document.getElementById('traditionalDeceasedBarangay').disabled = true;
        updateCombinedAddress();
    });
    
    document.getElementById('traditionalDeceasedProvince').addEventListener('change', function() {
        fetchCities(this.value);
        document.getElementById('traditionalDeceasedCity').value = '';
        document.getElementById('traditionalDeceasedBarangay').value = '';
        document.getElementById('traditionalDeceasedBarangay').disabled = true;
        updateCombinedAddress();
    });
    
    document.getElementById('traditionalDeceasedCity').addEventListener('change', function() {
        fetchBarangays(this.value);
        document.getElementById('traditionalDeceasedBarangay').value = '';
        updateCombinedAddress();
    });
    
    document.getElementById('traditionalDeceasedBarangay').addEventListener('change', updateCombinedAddress);
    document.getElementById('traditionalDeceasedAddress').addEventListener('input', updateCombinedAddress);
    
    // Also update combined address when form is submitted
    document.getElementById('lifeplanBookingForm').addEventListener('submit', function(e) {
        updateCombinedAddress();
        // Continue with form submission
    });
});


function updateCombinedAddress() {
    const regionSelect = document.getElementById('traditionalDeceasedRegion');
    const provinceSelect = document.getElementById('traditionalDeceasedProvince');
    const citySelect = document.getElementById('traditionalDeceasedCity');
    const barangaySelect = document.getElementById('traditionalDeceasedBarangay');
    const streetAddress = document.getElementById('traditionalDeceasedAddress').value;
    
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const city = citySelect.options[citySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    // Create an array of non-empty address components
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (city) addressParts.push(city);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    
    // Join the parts with commas
    const combinedAddress = addressParts.join(', ');
    document.getElementById('deceasedAddress').value = combinedAddress;
}

    
// Package data array
// Package data array from database
const packagesFromDB = <?php echo $packagesJson; ?>;

// Transform database data to match our frontend structure
const packages = packagesFromDB.map(pkg => {
    // Build features list from database fields
    const features = [];
    if (pkg.casket_name) features.push(pkg.casket_name);
    if (pkg.flower_design) features.push(`Flower design: ${pkg.flower_design}`);
    if (pkg.inclusions) {
        // Split inclusions by comma if they're stored that way
        const inclusionList = pkg.inclusions.split(',');
        inclusionList.forEach(inclusion => {
            features.push(inclusion.trim());
        });
    }
    
    return {
        price: parseFloat(pkg.selling_price),
        service: "traditional", 
        name: pkg.service_name,
        description: pkg.description,
        image: "<?php echo getImageUrl('" + pkg.image_url + "'); ?>",
        icon: "star", // Default icon
        features: features,
        branch_id: pkg.branch_id,     // Add this line
        service_id: pkg.service_id 
    };
});


// Format price to Philippine Peso
function formatPrice(price) {
    return '₱' + price.toLocaleString();
}

// Calculate monthly payment based on selected term
function calculateMonthlyPayment(price, term) {
    return price / term;
}

// Format monthly payment
function formatMonthlyPayment(price, term = 60) {
    const monthly = calculateMonthlyPayment(price, term);
    return '₱' + monthly.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Function to populate the packages grid
function populatePackagesGrid() {
    const grid = document.getElementById('traditionalPackagesGrid');
    if (!grid) return; // Safety check
    
    grid.innerHTML = ''; // Clear existing content
    
    packages.forEach(pkg => {
        const card = document.createElement('div');
        card.className = 'package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col';
        card.dataset.price = pkg.price;
        card.dataset.name = pkg.name;
        card.dataset.service = pkg.service;
        card.dataset.image = pkg.image;
        card.dataset.description = pkg.description;
        
        
        // Create package card HTML
        card.innerHTML = `
            <div class="h-12 bg-navy flex items-center justify-center">
                <h4 class="text-white font-hedvig text-xl">${pkg.name}</h4>
                <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                    <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                </div>
            </div>
            <div class="p-6 flex flex-col flex-grow">
                <div class="mb-4">
                    <img src="${pkg.image}" alt="${pkg.name}" class="w-full h-40 object-cover rounded-lg">
                </div>
                <div class="text-center mb-4">
                    <span class="text-2xl font-hedvig text-navy">${formatPrice(pkg.price)}</span>
                    <p class="text-yellow-600 mt-1 text-sm">Monthly: ${formatMonthlyPayment(pkg.price)}</p>
                </div>
                <p class="text-dark mb-4 text-sm">${pkg.description}</p>
                <div class="flex-grow">
                    <h5 class="font-medium text-navy mb-2">Includes:</h5>
                    <ul class="space-y-1 mb-4">
                        ${pkg.features.map(feature => `
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 text-sm"></i>
                                <span class="text-dark text-sm">${feature}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
                <button class="select-package-btn block w-full mt-4 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg shadow-md transition-all duration-300 text-center text-sm"
                        data-package-id="${packages.indexOf(pkg)}">
                    Select Package
                </button>
            </div>
        `;
        
        grid.appendChild(card);
    });
    
    // Add event listeners to the new buttons
    document.querySelectorAll('.select-package-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const packageId = parseInt(this.dataset.packageId);
            const selectedPackage = packages[packageId];
            
            // Close the packages selection modal
            const packagesModal = document.getElementById('traditionalPackagesModal');
            if (packagesModal) {
                packagesModal.classList.add('hidden');
            }
            
            // Open the traditional package details modal with the selected package
            openTraditionalModal(selectedPackage);
        });
    });
}

// Function to open the packages selection modal
function openPackagesModal() {
    const modal = document.getElementById('traditionalPackagesModal');
    if (modal) {
        modal.classList.remove('hidden');
        populatePackagesGrid();
    }
}

// Function to open the traditional package details modal
function openTraditionalModal(packageDetails) {
    console.log('Package Details:', packageDetails)
    const modal = document.getElementById('traditionalModal');
    if (!modal) return;
    


    // Update the details modal with the selected package information
    const packageImage = document.getElementById('traditionalPackageImage');
    if (packageImage) packageImage.src = packageDetails.image;
    
    const packageName = document.getElementById('traditionalPackageName');
    if (packageName) packageName.textContent = packageDetails.name;
    
    const packagePrice = document.getElementById('traditionalPackagePrice');
    if (packagePrice) packagePrice.textContent = formatPrice(packageDetails.price);
    
    const packageDesc = document.getElementById('traditionalPackageDesc');
    if (packageDesc) packageDesc.textContent = packageDetails.description;
    
    // Set hidden form fields
    const hiddenNameField = document.getElementById('lifeplanSelectedPackageName');
    if (hiddenNameField) hiddenNameField.value = packageDetails.name;

    const hiddenBranch = document.getElementById('lifeplanBranchId');
    if (hiddenBranch) hiddenBranch.value = packageDetails.branch_id;

    const hiddenServiceID = document.getElementById('lifeplanServiceId');
    if (hiddenServiceID) hiddenServiceID.value = packageDetails.service_id;
    
    const hiddenPriceField = document.getElementById('lifeplanSelectedPackagePrice');
    if (hiddenPriceField) hiddenPriceField.value = packageDetails.price;
    
    // Populate features list
    const featuresList = document.getElementById('traditionalPackageFeatures');
    if (featuresList) {
        featuresList.innerHTML = '';
        packageDetails.features.forEach(feature => {
            const li = document.createElement('li');
            li.className = 'flex items-start';
            li.innerHTML = `
                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                <span class="text-dark">${feature}</span>
            `;
            featuresList.appendChild(li);
        });
    }
    
    // Update price information in modal
    updateLifeplanPriceCalculations(packageDetails.price);
    
    // Reset all addon checkboxes
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Calculate and display prices for mobile
    const totalPrice = packageDetails.price;
    const downpayment = Math.ceil(totalPrice * 0.3);
    
    const totalPriceMobile = document.getElementById('traditionalTotalPriceMobile');
    if (totalPriceMobile) totalPriceMobile.textContent = formatPrice(totalPrice);
    
    const amountDueMobile = document.getElementById('traditionalAmountDueMobile');
    if (amountDueMobile) amountDueMobile.textContent = formatPrice(downpayment);
    
    // Show the traditional modal
    modal.classList.remove('hidden');
}

// Function to update price calculations in the Lifeplan form
function updateLifeplanPriceCalculations(packagePrice, additionalCost = 0) {
    const totalPriceElement = document.getElementById('lifeplanTotalPrice');
    const monthlyPaymentElement = document.getElementById('lifeplanMonthlyPayment');
    const paymentTermSelect = document.getElementById('lifeplanPaymentTerm');
    const paymentTermDisplay = document.getElementById('lifeplanPaymentTermDisplay');
    
    if (!totalPriceElement || !monthlyPaymentElement || !paymentTermSelect) return;
    
    const totalPrice = packagePrice + additionalCost;
    const years = parseInt(paymentTermSelect.value);
    const paymentTerm = years * 12;
    const monthlyPayment = calculateMonthlyPayment(totalPrice, paymentTerm);
    
    totalPriceElement.textContent = formatPrice(totalPrice);
    monthlyPaymentElement.textContent = formatPrice(monthlyPayment);
    
    // Update payment term display
    if (paymentTermDisplay) {
        let termText = "";
        switch(paymentTerm) {
            case 60: termText = "5 Years (60 Monthly Payments)"; break;
            case 36: termText = "3 Years (36 Monthly Payments)"; break;
            case 24: termText = "2 Years (24 Monthly Payments)"; break;
            case 12: termText = "1 Year (12 Monthly Payments)"; break;
            default: termText = paymentTerm + " Monthly Payments";
        }
        paymentTermDisplay.textContent = termText;
    }
}

// Function to update totals when addons are selected
function updateTotalWithAddons(packagePrice) {
    let addonTotal = 0;
    
    // Add cremation cost if checked
    const cremationCheckbox = document.getElementById('cremationOption');
    if (cremationCheckbox && cremationCheckbox.checked) {
        addonTotal += parseInt(cremationCheckbox.value);
    }
    
    // Add other addons if any
    document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
        addonTotal += parseInt(checkbox.value);
    });
    
    const newTotal = packagePrice + addonTotal;
    
    // Update lifeplan form prices
    updateLifeplanPriceCalculations(packagePrice, addonTotal);
    
    // Update mobile price displays too
    const totalPriceMobile = document.getElementById('traditionalTotalPriceMobile');
    if (totalPriceMobile) totalPriceMobile.textContent = formatPrice(newTotal);
    
    const amountDueMobile = document.getElementById('traditionalAmountDueMobile');
    if (amountDueMobile) {
        const downpayment = Math.ceil(newTotal * 0.3);
        amountDueMobile.textContent = formatPrice(downpayment);
    }
}

// Initialize event listeners when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add initial mobile class setup
    if (window.innerWidth < 768) {
        const formSection = document.querySelector('.form-section');
        if (formSection) {
            formSection.classList.add('hidden');
        }
    }
    
    // Event listener for the Life Plan "Select Package" button
    const lifePlanButton = document.querySelector('#lifeplan button');
    if (lifePlanButton) {
        lifePlanButton.addEventListener('click', openPackagesModal);
    }
    
    // Event listeners for closing modals
    document.querySelectorAll('.closePackagesModalBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const packagesModal = document.getElementById('traditionalPackagesModal');
            if (packagesModal) packagesModal.classList.add('hidden');
        });
    });
    
    document.querySelectorAll('.closeModalBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const traditionalModal = document.getElementById('traditionalModal');
            if (traditionalModal) traditionalModal.classList.add('hidden');
        });
    });
    
    // Setup mobile navigation buttons
    const continueToFormBtn = document.getElementById('continueToFormBtn');
    if (continueToFormBtn) {
        continueToFormBtn.addEventListener('click', function() {
            const detailsSection = document.querySelector('.details-section');
            const formSection = document.querySelector('.form-section');
            
            if (detailsSection) detailsSection.classList.add('hidden');
            if (formSection) formSection.classList.remove('hidden');
        });
    }
    
    const backToDetailsBtn = document.getElementById('backToDetailsBtn');
    if (backToDetailsBtn) {
        backToDetailsBtn.addEventListener('click', function() {
            const formSection = document.querySelector('.form-section');
            const detailsSection = document.querySelector('.details-section');
            
            if (formSection) formSection.classList.add('hidden');
            if (detailsSection) detailsSection.classList.remove('hidden');
        });
    }
    
    // Add event listeners to addon checkboxes
    const cremationCheckbox = document.getElementById('cremationOption');
    if (cremationCheckbox) {
        cremationCheckbox.addEventListener('change', function() {
            const basePrice = parseInt(document.getElementById('lifeplanSelectedPackagePrice')?.value || 0);
            updateTotalWithAddons(basePrice);
        });
    }
    
    // Add event listener to payment term select
    const paymentTermSelect = document.getElementById('lifeplanPaymentTerm');
    if (paymentTermSelect) {
        paymentTermSelect.addEventListener('change', function() {
            const basePrice = parseInt(document.getElementById('lifeplanSelectedPackagePrice')?.value || 0);
            let addonTotal = 0;
            
            document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
                addonTotal += parseInt(checkbox.value);
            });
            
            updateLifeplanPriceCalculations(basePrice, addonTotal);
        });
    }
    
    // Form submission handler for lifeplan booking form
    const lifeplanForm = document.getElementById('lifeplanBookingForm');
    if (lifeplanForm) {
        lifeplanForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form fields
            const requiredFields = lifeplanForm.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please fill in all required fields.',
                    confirmButtonColor: '#3085d6',
                });
                return;
            }
            
            // Show confirmation dialog
            Swal.fire({
                title: 'Confirm Booking',
                text: 'Are you sure you want to submit this life plan booking?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Processing...',
                        html: 'Please wait while we process your booking.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Prepare FormData with all form inputs
                    const formData = new FormData(lifeplanForm);
                    
                    // Add checkbox values
                    const cremationOption = document.getElementById('cremationOption').checked;
                    formData.append('cremationOption', cremationOption ? '1' : '0');
                    
                    // Get address components
                    const region = document.getElementById('traditionalDeceasedRegion');
                    const province = document.getElementById('traditionalDeceasedProvince');
                    const city = document.getElementById('traditionalDeceasedCity');
                    const barangay = document.getElementById('traditionalDeceasedBarangay');

                    // Get the text of the selected option
                    const regionText = region.options[region.selectedIndex].text;
                    const provinceText = province.options[province.selectedIndex].text;
                    const cityText = city.options[city.selectedIndex].text;
                    const barangayText = barangay.options[barangay.selectedIndex].text;

                    const streetAddress = document.getElementById('traditionalDeceasedAddress').value;

                    // Add address to form data
                    formData.append('deceasedAddress', `${streetAddress}, ${barangayText}, ${cityText}, ${provinceText}, ${regionText}`);

                    
                    // Submit the form via AJAX
                    fetch('booking/lifeplan_booking_for_lifeplanpage.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message || 'Lifeplan booking submitted successfully!',
                                confirmButtonColor: '#3085d6',
                            }).then(() => {
                                // Redirect or close modal
                                document.getElementById('traditionalModal').classList.add('hidden');
                                // Optionally redirect or refresh the page
                                window.location.href = 'lifeplan.php';
                            });
                        } else {
                            throw new Error(data.message || 'Unknown error occurred');
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Failed to submit booking. Please try again.',
                            confirmButtonColor: '#3085d6',
                        });
                    });
                }
            });
        });
    }
    
    // Handle resize events
    window.addEventListener('resize', function() {
        const traditionalModal = document.getElementById('traditionalModal');
        if (traditionalModal && !traditionalModal.classList.contains('hidden')) {
            const detailsSection = document.querySelector('.details-section');
            const formSection = document.querySelector('.form-section');
            
            if (window.innerWidth >= 768) {
                // Desktop view - show both sections
                if (detailsSection) detailsSection.classList.remove('hidden');
                if (formSection) formSection.classList.remove('hidden');
            } else {
                // Mobile view - show details by default, hide form
                if (detailsSection) detailsSection.classList.remove('hidden');
                if (formSection) formSection.classList.add('hidden');
            }
        }
    });
});
</script>

<style>
/* Additional styles if needed */
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
</style>


    

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
        // Get all internal links that are not anchors, external links, or links with target="_blank"
        const internalLinks = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="http"]):not([href^="mailto"]):not([target="_blank"])');
        
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

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Name fields validation (First, Middle, Last)
    const nameFields = ['lifeplanHolderFirstName', 'lifeplanHolderMiddleName', 'lifeplanHolderLastName'];
    
    nameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function(e) {
                // Get current cursor position
                const cursorPos = this.selectionStart;
                
                // Remove any numbers, symbols, and extra spaces
                let cleanedValue = this.value.replace(/[^a-zA-Z\s]/g, '')
                    .replace(/\s{2,}/g, ' ')
                    .replace(/^\s+/, '');
                
                // Don't allow space as first character or unless there are already 2 characters
                if (cleanedValue.length < 2 && cleanedValue.includes(' ')) {
                    cleanedValue = cleanedValue.replace(/\s/g, '');
                }
                
                // Capitalize first letter of each word
                cleanedValue = cleanedValue.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
                    return a.toUpperCase();
                });
                
                this.value = cleanedValue;
                
                // Restore cursor position
                this.setSelectionRange(cursorPos, cursorPos);
            });
            
            // Handle paste event
            field.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = pastedText.replace(/[^a-zA-Z\s]/g, '')
                    .replace(/\s{2,}/g, ' ')
                    .replace(/^\s+/, '');
                
                if (cleanedText.length < 2 && cleanedText.includes(' ')) {
                    cleanedText = cleanedText.replace(/\s/g, '');
                }
                
                const capitalized = cleanedText.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
                    return a.toUpperCase();
                });
                
                // Insert the cleaned text at cursor position
                const startPos = this.selectionStart;
                const endPos = this.selectionEnd;
                const currentValue = this.value;
                
                this.value = currentValue.substring(0, startPos) + 
                              capitalized + 
                              currentValue.substring(endPos);
            });
        }
    });
    
    // Date of Birth validation
    const dobField = document.getElementById('lifeplanDateOfBirth');
    if (dobField) {
        const today = new Date();
        const hundredYearsAgo = new Date();
        hundredYearsAgo.setFullYear(today.getFullYear() - 100);
        
        dobField.max = today.toISOString().split('T')[0];
        dobField.min = hundredYearsAgo.toISOString().split('T')[0];
    }
    
    // Contact Number validation
    const contactField = document.getElementById('lifeplanContactNumber');
    if (contactField) {
        contactField.addEventListener('input', function() {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Ensure it starts with 09 and limit to 11 digits
            if (this.value.length > 0 && !this.value.startsWith('09')) {
                this.value = '09' + this.value.substring(2);
            }
            
            if (this.value.length > 11) {
                this.value = this.value.substring(0, 11);
            }
        });
        
        contactField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedText = pastedText.replace(/\D/g, '');
            
            let finalText = cleanedText;
            if (cleanedText.length > 0 && !cleanedText.startsWith('09')) {
                finalText = '09' + cleanedText.substring(2);
            }
            
            if (finalText.length > 11) {
                finalText = finalText.substring(0, 11);
            }
            
            // Insert at cursor position
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            this.value = currentValue.substring(0, startPos) + 
                         finalText + 
                         currentValue.substring(endPos);
        });
    }
    
    // Relationship with beneficiary validation
    const relationshipField = document.getElementById('relationshipWithBeneficiary');
    if (relationshipField) {
        relationshipField.addEventListener('input', function() {
            // Remove numbers and symbols
            let cleanedValue = this.value.replace(/[^a-zA-Z\s]/g, '')
                .replace(/\s{2,}/g, ' ')
                .replace(/^\s+/, '');
            
            // Don't allow space as first character or unless there are already 2 characters
            if (cleanedValue.length < 2 && cleanedValue.includes(' ')) {
                cleanedValue = cleanedValue.replace(/\s/g, '');
            }
            
            // Capitalize first letter
            if (cleanedValue.length > 0) {
                cleanedValue = cleanedValue.charAt(0).toUpperCase() + 
                               cleanedValue.slice(1).toLowerCase();
            }
            
            this.value = cleanedValue;
        });
        
        relationshipField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            let cleanedText = pastedText.replace(/[^a-zA-Z\s]/g, '')
                .replace(/\s{2,}/g, ' ')
                .replace(/^\s+/, '');
            
            if (cleanedText.length < 2 && cleanedText.includes(' ')) {
                cleanedText = cleanedText.replace(/\s/g, '');
            }
            
            if (cleanedText.length > 0) {
                cleanedText = cleanedText.charAt(0).toUpperCase() + 
                              cleanedText.slice(1).toLowerCase();
            }
            
            // Insert at cursor position
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            this.value = currentValue.substring(0, startPos) + 
                         cleanedText + 
                         currentValue.substring(endPos);
        });
    }
    
    // Street Address validation
    const addressField = document.getElementById('traditionalDeceasedAddress');
    if (addressField) {
        addressField.addEventListener('input', function() {
            // Remove multiple consecutive spaces
            let cleanedValue = this.value.replace(/\s{2,}/g, ' ')
                .replace(/^\s+/, '');
            
            // Don't allow space as first character or unless there are already 2 characters
            if (cleanedValue.length < 2 && cleanedValue.includes(' ')) {
                cleanedValue = cleanedValue.replace(/\s/g, '');
            }
            
            // Capitalize first letter of each word
            cleanedValue = cleanedValue.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
                return a.toUpperCase();
            });
            
            this.value = cleanedValue;
        });
        
        addressField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            let cleanedText = pastedText.replace(/\s{2,}/g, ' ')
                .replace(/^\s+/, '');
            
            if (cleanedText.length < 2 && cleanedText.includes(' ')) {
                cleanedText = cleanedText.replace(/\s/g, '');
            }
            
            cleanedText = cleanedText.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
                return a.toUpperCase();
            });
            
            // Insert at cursor position
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            this.value = currentValue.substring(0, startPos) + 
                         cleanedText + 
                         currentValue.substring(endPos);
        });
    }
    
    // Reference Number validation
    const refNumField = document.getElementById('lifeplanReferenceNumber');
    if (refNumField) {
        refNumField.addEventListener('input', function() {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 20 characters
            if (this.value.length > 20) {
                this.value = this.value.substring(0, 20);
            }
        });
        
        refNumField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedText = pastedText.replace(/\D/g, '');
            
            const finalText = cleanedText.substring(0, 20);
            
            // Insert at cursor position
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            this.value = currentValue.substring(0, startPos) + 
                         finalText + 
                         currentValue.substring(endPos);
        });
    }
    
    // Form submission validation
    const lifeplanForm = document.getElementById('lifeplanBookingForm');
    if (lifeplanForm) {
        lifeplanForm.addEventListener('submit', function(e) {
            // Check required name fields
            const firstName = document.getElementById('lifeplanHolderFirstName');
            const lastName = document.getElementById('lifeplanHolderLastName');
            
            if (firstName && firstName.value.trim().length < 2) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid First Name',
                    text: 'Please enter a valid first name (minimum 2 characters)',
                    confirmButtonColor: '#3085d6',
                });
                firstName.focus();
                return;
            }
            
            if (lastName && lastName.value.trim().length < 2) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Last Name',
                    text: 'Please enter a valid last name (minimum 2 characters)',
                    confirmButtonColor: '#3085d6',
                });
                lastName.focus();
                return;
            }
            
            // Check contact number
            const contactNumber = document.getElementById('lifeplanContactNumber');
            if (contactNumber && (contactNumber.value.length < 11 || !contactNumber.value.startsWith('09'))) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Contact Number',
                    text: 'Please enter a valid Philippine mobile number starting with 09 (11 digits)',
                    confirmButtonColor: '#3085d6',
                });
                contactNumber.focus();
                return;
            }
            
            // Check reference number
            const refNumber = document.getElementById('lifeplanReferenceNumber');
            if (refNumber && refNumber.value.length < 1) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Reference Number Required',
                    text: 'Please enter your payment reference number',
                    confirmButtonColor: '#3085d6',
                });
                refNumber.focus();
                return;
            }
        });
    }
});
</script>
<script>
    //script for pektyur

    // Function to handle QR code button click
document.getElementById('lifeplanShowQrCodeBtn')?.addEventListener('click', function() {
    // Get the total price from the form
    const totalPrice = parseFloat(document.getElementById('lifeplanSelectedPackagePrice')?.value || 0);
    
    // Calculate monthly payment (total price / 60 months)
    const monthlyPayment = totalPrice / 60;
    
    // Update the QR code modal with the amount
    const qrAmountElement = document.getElementById('lifeplanQrCodeAmount');
    if (qrAmountElement) {
        qrAmountElement.textContent = `Amount: ₱${monthlyPayment.toFixed(2)}`;
    }
    
    // Show the QR code modal
    const qrModal = document.getElementById('lifeplanQrCodeModal');
    if (qrModal) {
        qrModal.classList.remove('hidden');
    }
});

// Function to close QR code modal
document.getElementById('lifeplanCloseQrModal')?.addEventListener('click', function() {
    const qrModal = document.getElementById('lifeplanQrCodeModal');
    if (qrModal) {
        qrModal.classList.add('hidden');
    }
});

// Function to handle GCash receipt upload and preview
document.getElementById('lifeplanGcashReceipt')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewContainer = document.getElementById('lifeplanGcashPreviewContainer');
    const imagePreview = document.getElementById('lifeplanGcashImagePreview');
    const fileNameDisplay = document.getElementById('lifeplanGcashFileName');
    const removeButton = document.getElementById('removeLifeplanGcash');
    
    if (file) {
        // Update file name display
        fileNameDisplay.textContent = file.name;
        
        // Check if it's an image
        if (file.type.match('image.*')) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Set image source
                document.getElementById('lifeplanGcashImage').src = e.target.result;
                
                // Show image preview
                imagePreview.classList.remove('hidden');
                previewContainer.classList.remove('hidden');
            };
            
            reader.readAsDataURL(file);
        } else {
            // Hide image preview for non-image files
            imagePreview.classList.add('hidden');
            previewContainer.classList.remove('hidden');
        }
        
        // Show remove button
        removeButton.classList.remove('hidden');
    }
});

// Function to handle remove file button
document.getElementById('removeLifeplanGcash')?.addEventListener('click', function() {
    // Clear file input
    document.getElementById('lifeplanGcashReceipt').value = '';
    
    // Hide preview elements
    document.getElementById('lifeplanGcashPreviewContainer').classList.add('hidden');
    document.getElementById('lifeplanGcashImagePreview').classList.add('hidden');
    
    // Reset file name display
    document.getElementById('lifeplanGcashFileName').textContent = 'No file chosen';
    
    // Hide remove button
    this.classList.add('hidden');
});

// Function to view uploaded image in full screen
function viewUploadedImage() {
    const imageSrc = document.getElementById('lifeplanGcashImage').src;
    if (imageSrc) {
        // Create a modal for full-screen viewing
        const imageViewer = document.createElement('div');
        imageViewer.className = 'fixed inset-0 bg-black bg-opacity-90 z-[999] flex items-center justify-center';
        imageViewer.innerHTML = `
            <div class="relative max-w-full max-h-full p-4">
                <img src="${imageSrc}" alt="Uploaded Receipt" class="max-w-full max-h-[90vh] object-contain">
                <button class="absolute top-4 right-4 text-white text-2xl hover:text-yellow-500" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Add to body and show
        document.body.appendChild(imageViewer);
    }
}

// Add click event to image preview for viewing
document.getElementById('lifeplanGcashImagePreview')?.addEventListener('click', viewUploadedImage);
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Name fields validation (First, Middle, Last)
    const nameFields = ['lifeplanHolderFirstName', 'lifeplanHolderMiddleName', 'lifeplanHolderLastName'];
    
    nameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function(e) {
                // Get current cursor position
                const cursorPos = this.selectionStart;
                
                // Remove any numbers, symbols, and extra spaces
                let cleanedValue = this.value.replace(/[^a-zA-Z\s]/g, '')
                    .replace(/\s{2,}/g, ' ')
                    .replace(/^\s+/, '');
                
                // Don't allow space as first character or unless there are already 2 characters
                if (cleanedValue.length < 2 && cleanedValue.includes(' ')) {
                    cleanedValue = cleanedValue.replace(/\s/g, '');
                }
                
                // Capitalize first letter of each word
                cleanedValue = cleanedValue.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
                    return a.toUpperCase();
                });
                
                this.value = cleanedValue;
                
                // Restore cursor position
                this.setSelectionRange(cursorPos, cursorPos);
            });
            
            // Handle paste event
            field.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = pastedText.replace(/[^a-zA-Z\s]/g, '')
                    .replace(/\s{2,}/g, ' ')
                    .replace(/^\s+/, '');
                
                if (cleanedText.length < 2 && cleanedText.includes(' ')) {
                    cleanedText = cleanedText.replace(/\s/g, '');
                }
                
                const capitalized = cleanedText.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
                    return a.toUpperCase();
                });
                
                // Insert the cleaned text at cursor position
                const startPos = this.selectionStart;
                const endPos = this.selectionEnd;
                const currentValue = this.value;
                
                this.value = currentValue.substring(0, startPos) + 
                              capitalized + 
                              currentValue.substring(endPos);
            });
        }
    });
    
    // Date of Birth validation
    const dobField = document.getElementById('lifeplanDateOfBirth');
    if (dobField) {
        const today = new Date();
        const hundredYearsAgo = new Date();
        hundredYearsAgo.setFullYear(today.getFullYear() - 100);
        
        dobField.max = today.toISOString().split('T')[0];
        dobField.min = hundredYearsAgo.toISOString().split('T')[0];
    }
    
    // Contact Number validation
    const contactField = document.getElementById('lifeplanContactNumber');
    if (contactField) {
        contactField.addEventListener('input', function() {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Ensure it starts with 09 and limit to 11 digits
            if (this.value.length > 0 && !this.value.startsWith('09')) {
                this.value = '09' + this.value.substring(2);
            }
            
            if (this.value.length > 11) {
                this.value = this.value.substring(0, 11);
            }
        });
        
        contactField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedText = pastedText.replace(/\D/g, '');
            
            let finalText = cleanedText;
            if (cleanedText.length > 0 && !cleanedText.startsWith('09')) {
                finalText = '09' + cleanedText.substring(2);
            }
            
            if (finalText.length > 11) {
                finalText = finalText.substring(0, 11);
            }
            
            // Insert at cursor position
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            this.value = currentValue.substring(0, startPos) + 
                         finalText + 
                         currentValue.substring(endPos);
        });
    }
    
    // Relationship with beneficiary validation
    const relationshipField = document.getElementById('relationshipWithBeneficiary');
    if (relationshipField) {
        relationshipField.addEventListener('input', function() {
            // Remove numbers and symbols
            let cleanedValue = this.value.replace(/[^a-zA-Z\s]/g, '')
                .replace(/\s{2,}/g, ' ')
                .replace(/^\s+/, '');
            
            // Don't allow space as first character or unless there are already 2 characters
            if (cleanedValue.length < 2 && cleanedValue.includes(' ')) {
                cleanedValue = cleanedValue.replace(/\s/g, '');
            }
            
            // Capitalize first letter
            if (cleanedValue.length > 0) {
                cleanedValue = cleanedValue.charAt(0).toUpperCase() + 
                               cleanedValue.slice(1).toLowerCase();
            }
            
            this.value = cleanedValue;
        });
        
        relationshipField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            let cleanedText = pastedText.replace(/[^a-zA-Z\s]/g, '')
                .replace(/\s{2,}/g, ' ')
                .replace(/^\s+/, '');
            
            if (cleanedText.length < 2 && cleanedText.includes(' ')) {
                cleanedText = cleanedText.replace(/\s/g, '');
            }
            
            if (cleanedText.length > 0) {
                cleanedText = cleanedText.charAt(0).toUpperCase() + 
                              cleanedText.slice(1).toLowerCase();
            }
            
            // Insert at cursor position
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            this.value = currentValue.substring(0, startPos) + 
                         cleanedText + 
                         currentValue.substring(endPos);
        });
    }
    
    // Street Address validation
    const addressField = document.getElementById('traditionalDeceasedAddress');
    if (addressField) {
        addressField.addEventListener('input', function() {
            // Remove multiple consecutive spaces
            let cleanedValue = this.value.replace(/\s{2,}/g, ' ')
                .replace(/^\s+/, '');
            
            // Don't allow space as first character or unless there are already 2 characters
            if (cleanedValue.length < 2 && cleanedValue.includes(' ')) {
                cleanedValue = cleanedValue.replace(/\s/g, '');
            }
            
            // Capitalize first letter of each word
            cleanedValue = cleanedValue.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
                return a.toUpperCase();
            });
            
            this.value = cleanedValue;
        });
        
        addressField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            let cleanedText = pastedText.replace(/\s{2,}/g, ' ')
                .replace(/^\s+/, '');
            
            if (cleanedText.length < 2 && cleanedText.includes(' ')) {
                cleanedText = cleanedText.replace(/\s/g, '');
            }
            
            cleanedText = cleanedText.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
                return a.toUpperCase();
            });
            
            // Insert at cursor position
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            this.value = currentValue.substring(0, startPos) + 
                         cleanedText + 
                         currentValue.substring(endPos);
        });
    }
    
    // Reference Number validation
    const refNumField = document.getElementById('lifeplanReferenceNumber');
    if (refNumField) {
        refNumField.addEventListener('input', function() {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 20 characters
            if (this.value.length > 20) {
                this.value = this.value.substring(0, 20);
            }
        });
        
        refNumField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedText = pastedText.replace(/\D/g, '');
            
            const finalText = cleanedText.substring(0, 20);
            
            // Insert at cursor position
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            this.value = currentValue.substring(0, startPos) + 
                         finalText + 
                         currentValue.substring(endPos);
        });
    }
    
    // Form submission validation
    const lifeplanForm = document.getElementById('lifeplanBookingForm');
    if (lifeplanForm) {
        lifeplanForm.addEventListener('submit', function(e) {
            // Check required name fields
            const firstName = document.getElementById('lifeplanHolderFirstName');
            const lastName = document.getElementById('lifeplanHolderLastName');
            
            if (firstName && firstName.value.trim().length < 2) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid First Name',
                    text: 'Please enter a valid first name (minimum 2 characters)',
                    confirmButtonColor: '#3085d6',
                });
                firstName.focus();
                return;
            }
            
            if (lastName && lastName.value.trim().length < 2) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Last Name',
                    text: 'Please enter a valid last name (minimum 2 characters)',
                    confirmButtonColor: '#3085d6',
                });
                lastName.focus();
                return;
            }
            
            // Check contact number
            const contactNumber = document.getElementById('lifeplanContactNumber');
            if (contactNumber && (contactNumber.value.length < 11 || !contactNumber.value.startsWith('09'))) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Contact Number',
                    text: 'Please enter a valid Philippine mobile number starting with 09 (11 digits)',
                    confirmButtonColor: '#3085d6',
                });
                contactNumber.focus();
                return;
            }
            
            // Check reference number
            const refNumber = document.getElementById('lifeplanReferenceNumber');
            if (refNumber && refNumber.value.length < 1) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Reference Number Required',
                    text: 'Please enter your payment reference number',
                    confirmButtonColor: '#3085d6',
                });
                refNumber.focus();
                return;
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Handle GCash QR selection
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

    // Lifeplan QR code modal handling
    const showQrCodeBtn = document.getElementById('lifeplanShowQrCodeBtn');
    const qrCodeModal = document.getElementById('lifeplanQrCodeModal');
    const closeQrModal = document.getElementById('lifeplanCloseQrModal');
    const qrCodeAmount = document.getElementById('lifeplanQrCodeAmount');
    
    if (showQrCodeBtn && qrCodeModal) {
        showQrCodeBtn.addEventListener('click', function() {
            const totalPrice = parseFloat(document.getElementById('lifeplanSelectedPackagePrice')?.value || 0);
            const monthlyPayment = totalPrice / 60;
            qrCodeAmount.textContent = `Amount: ₱${monthlyPayment.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            qrCodeModal.classList.remove('hidden');
        });
        
        closeQrModal.addEventListener('click', function() {
            qrCodeModal.classList.add('hidden');
        });
        
        // Prevent closing by clicking outside
        qrCodeModal.addEventListener('click', function(e) {
            if (e.target === qrCodeModal) {
                e.stopPropagation(); // Prevent closing
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !qrCodeModal.classList.contains('hidden')) {
                qrCodeModal.classList.add('hidden');
            }
        });
    }
});
</script>

    <?php include 'customService/chat_elements.html'; ?>
    
</body>
</html>