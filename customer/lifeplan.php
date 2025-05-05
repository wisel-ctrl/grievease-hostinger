
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
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Flexible Payment Option</span>
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
                <div class="bg-navy/5 py-12 px-6 rounded-xl">
                    <h3 class="text-3xl font-hedvig text-navy text-center mb-12">What Our Clients Say</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Testimonial 1 -->
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
                        
                        <!-- Testimonial 2 -->
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
                    </div>
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
                    <a href="..\privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                    <a href="..\termsofservice.php" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Traditional Packages Selection Modal (Hidden by Default) -->
<div id="traditionalPackagesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-6xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh]">
        <div class="modal-scroll-container overflow-y-auto max-h-[90vh]">
            <!-- Header with close button -->
            <div class="bg-navy p-6 flex justify-between items-center">
                <h2 class="text-2xl font-hedvig text-white">Select Traditional Package</h2>
                <button class="closePackagesModalBtn text-white hover:text-yellow-300">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Packages grid -->
            <div class="p-6 bg-cream">
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

                
                
                <!-- Mobile-only continue button -->
                <div class="mt-6 border-t border-gray-200 pt-4 md:hidden">
                    <button id="continueToFormBtn" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
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
                        <button id="backToDetailsBtn" class="mr-2 text-gray-500 hover:text-navy md:hidden flex items-center">
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
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Plan Holder Information</h3>
                        
                        <!-- First Name & Middle Name (Side by side) -->
                        <div class="flex flex-wrap -mx-2 mb-3">
                            <div class="w-full sm:w-1/2 px-2 mb-3 sm:mb-0">
                                <label for="lifeplanHolderFirstName" class="block text-sm font-medium text-navy mb-1">First Name *</label>
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
                                <label for="lifeplanHolderLastName" class="block text-sm font-medium text-navy mb-1">Last Name *</label>
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
                                <label for="lifeplanDateOfBirth" class="block text-sm font-medium text-navy mb-1">Date of Birth *</label>
                                <input type="date" id="lifeplanDateOfBirth" name="dateOfBirth" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div class="w-full sm:w-1/2 px-2">
                                <label for="lifeplanContactNumber" class="block text-sm font-medium text-navy mb-1">Contact Number *</label>
                                <input type="tel" id="lifeplanContactNumber" name="contactNumber" required 
       pattern="09[0-9]{9}" 
       title="Please enter a valid Philippine mobile number starting with 09 (11 digits total)"
       class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="relationshipWithBeneficiary" class="block text-sm font-medium text-navy mb-1">
                                Relationship with the Beneficiary *
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
                            <input type="text" id="traditionalDeceasedAddress" name="deceasedAddress" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600" placeholder="e.g. 123 Main Street">
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
                            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-hedvig text-navy">Scan to Pay</h3>
                                    <button id="lifeplanCloseQrModal" class="text-gray-500 hover:text-navy">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <div class="flex flex-col items-center justify-center">
                                    <img id="lifeplanQrCodeImage" src="../image\gcashqrvjay.jpg" alt="Payment QR Code" class="w-64 h-64 object-contain mb-4">
                                    <p class="text-center text-sm text-gray-600 mb-2">Scan this QR code with your GCash app to make payment</p>
                                    <p class="text-center font-bold text-yellow-600" id="lifeplanQrCodeAmount">Amount: ₱0</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GCash Upload with Preview (Improved UI) -->
                        <div class="mb-4">
                            <label for="lifeplanGcashReceipt" class="block text-sm font-medium text-navy mb-1">First Payment Receipt</label>
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
                            <label for="lifeplanReferenceNumber" class="block text-sm font-medium text-navy mb-1">Reference Number *</label>
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

                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                        Confirm Lifeplan Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

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
    
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const city = citySelect.options[citySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    // Combine all address components into one string
    const combinedAddress = `${streetAddress}, ${barangay}, ${city}, ${province}, ${region}`;
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
    document.getElementById('traditionalBookingForm').addEventListener('submit', function(e) {
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
                    <p class="text-dark mt-1 text-sm">Monthly: ${formatMonthlyPayment(pkg.price)}</p>
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
    const paymentTerm = parseInt(paymentTermSelect.value);
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
                alert('Please fill in all required fields.');
                return;
            }
            
            // Get all form inputs
            const formData = new FormData(lifeplanForm);
            const formInputs = {};
            
            // Convert FormData to object
            for (let [key, value] of formData.entries()) {
                formInputs[key] = value;
            }
            
            // Get checkbox values
            const cremationOption = document.getElementById('cremationOption').checked;
            formInputs.cremationOption = cremationOption;
            
            // Get address components
            const region = document.getElementById('traditionalDeceasedRegion').value;
            const province = document.getElementById('traditionalDeceasedProvince').value;
            const city = document.getElementById('traditionalDeceasedCity').value;
            const barangay = document.getElementById('traditionalDeceasedBarangay').value;
            const streetAddress = document.getElementById('traditionalDeceasedAddress').value;
            
            // Add address to form inputs
            formInputs.addressComponents = {
                region,
                province,
                city,
                barangay,
                streetAddress
            };
            
            // Log all form inputs to console
            console.log('Form Inputs:', formInputs);
            
            // Get selected package and additional services
            const packageName = document.getElementById('lifeplanSelectedPackageName')?.value;
            const packagePrice = parseFloat(document.getElementById('lifeplanSelectedPackagePrice')?.value || 0);
            const selectedAddons = [];
            
            const cremationCheckbox = document.getElementById('cremationOption');
            if (cremationCheckbox && cremationCheckbox.checked) {
                selectedAddons.push({
                    name: 'Cremation Services',
                    price: parseFloat(cremationCheckbox.value)
                });
            }

            document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
                selectedAddons.push({
                    name: checkbox.dataset.name,
                    price: parseFloat(checkbox.value)
                });
            });
            
            // Get personal information
            const firstName = document.getElementById('lifeplanHolderFirstName')?.value;
            const lastName = document.getElementById('lifeplanHolderLastName')?.value;
            const email = document.getElementById('lifeplanEmailAddress')?.value;
            const phone = document.getElementById('lifeplanContactNumber')?.value;
            
            // Get payment information
            const paymentTerm = document.getElementById('lifeplanPaymentTerm')?.value;
            const refNumber = document.getElementById('lifeplanReferenceNumber')?.value;
            
            // Create booking data object
            const bookingData = {
                package: {
                    name: packageName,
                    price: packagePrice
                },
                addons: selectedAddons,
                customer: {
                    firstName,
                    lastName,
                    email,
                    phone
                },
                payment: {
                    term: paymentTerm,
                    referenceNumber: refNumber
                }
            };
            
            console.log('Booking data:', bookingData);
            
            // Here you would typically send this data to your server
            // For now, just show a success message
            alert('Thank you for your booking! We will contact you shortly.');
            document.getElementById('traditionalModal').classList.add('hidden');
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

    <?php include 'customService/chat_elements.html'; ?>
    
</body>
</html>