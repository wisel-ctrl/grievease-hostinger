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
                }
                $conn->close();
?>

<script src="customer_support.js"></script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - FAQs</title>
    <?php include 'faviconLogo.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600&family=Cinzel:wght@400;500;600;700&family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../tailwind.js"></script>
    <style>
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
        }
    </style>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig mt-[var(--navbar-height)]">
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
    <!-- Hero Section -->
    <div class="relative w-full h-64 overflow-hidden">
        <!-- Background Image with Gradient Overlay -->
        <div class="absolute inset-0 bg-center bg-cover bg-no-repeat"
             style="background-image: url('../Landing_Page/Landing_images/black-bg-image.jpg');">
            <!-- Multi-layered gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-black/80"></div>
        </div>
        
        <!-- Content Container -->
        <div class="relative h-full flex flex-col items-center justify-center px-6 md:px-12 z-10">
            <h1 class="font-hedvig text-4xl md:text-5xl text-white text-shadow-lg mb-4 text-center">
                Frequently Asked Questions
            </h1>
            <p class="text-white/90 text-center max-w-2xl text-lg">
                Answers to common questions about our funeral services
            </p>
        </div>
        
        <!-- Decorative Elements -->
        <div class="absolute top-6 right-6 w-16 h-16 border-t-2 border-r-2 border-white/20 pointer-events-none"></div>
        <div class="absolute bottom-6 left-6 w-16 h-16 border-b-2 border-l-2 border-white/20 pointer-events-none"></div>
    </div>
    
    <!-- FAQ Section -->
    <div class="container mx-auto px-6 py-12 max-w-4xl">
        <!-- Section Header -->
        
        <!-- FAQ Categories -->
<div class="mb-12 flex flex-wrap justify-center gap-4">
    <button class="category-btn active px-5 py-2 rounded-full bg-navy text-white font-medium" data-category="all">All Questions</button>
    <button class="category-btn px-5 py-2 rounded-full bg-white text-navy border border-navy/20 font-medium hover:bg-navy/5 transition-colors" data-category="life-plan">Life Plan</button>
    <button class="category-btn px-5 py-2 rounded-full bg-white text-navy border border-navy/20 font-medium hover:bg-navy/5 transition-colors" data-category="services">Services</button>
    <button class="category-btn px-5 py-2 rounded-full bg-white text-navy border border-navy/20 font-medium hover:bg-navy/5 transition-colors" data-category="costs">Costs</button>
</div>

<!-- FAQ Accordion -->
<div class="space-y-6 mb-16">
    <!-- ===== LIFE PLAN CATEGORY ===== -->
    <!-- FAQ Item 4 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="life-plan">
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

    <!-- FAQ Item 12 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="life-plan">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">How do you handle cases where families cannot afford to pay?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>We understand that losing a loved one is difficult enough without financial worries. Our approach includes:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Accepting what the family can pay when they truly have no means</li>
                    <li>Offering interest-free installment plans with flexible terms</li>
                    <li>Never stopping a service once it has begun, regardless of payment status</li>
                </ul>
                <p class="mt-4">While we must maintain our business, we prioritize compassion and community relationships above all. We've been known to donate services or caskets in extreme hardship cases.</p>
            </div>
        </div>
    </div>

    <!-- ===== SERVICES CATEGORY ===== -->
    <!-- FAQ Item 1 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
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
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
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
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
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
    
    <!-- FAQ Item 6 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">What documents and information will I need when making arrangements?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>When making funeral arrangements, you'll need the following information about the deceased:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Full legal name and home address</li>
                    <li>Date of birth</li>
                    <li>Relative</li>
                    
                </ul>
                <p class="mt-4">You will also need to provide a death certificate. In cases involving accidents or disease-related deaths, additional documentation may be required. Our staff will help you with the legal requirements and necessary paperwork.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 7 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">What is included in your funeral packages?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>Our funeral packages are comprehensive and include:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li><strong>Casket:</strong> Options range from standard to premium imported models</li>
                    <li><strong>Flowers:</strong> For basic packages, flowers last until the end of the service. For premium packages (₱100,000+), we change flowers twice during the wake</li>
                    <li><strong>Viewing area:</strong> Complete setup with curtains, lights, and necessary equipment</li>
                    <li><strong>Amenities:</strong> Water dispenser, tent, chairs, and tables</li>
                    <li><strong>Transportation:</strong> Hearse and other necessary vehicles</li>
                    <li><strong>Professional services:</strong> Licensed embalmer and staff assistance</li>
                </ul>
                <p class="mt-4">We can accommodate extended wakes, even up to 2 weeks. The only additional services required for extended wakes would be fresh flowers and possible re-embalming if necessary. We can handle up to 12 services simultaneously with our own equipment, and can arrange for additional equipment if needed.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 8 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">Can you accommodate different religious and cultural traditions?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>Yes, we have extensive experience accommodating diverse religious and cultural funeral traditions. Our staff is trained to respect and facilitate various customs including:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Christian, Catholic, Protestant, and Orthodox services</li>
                    <li>Muslim/Islamic traditions</li>
                    <li>Other cultural and religious ceremonies</li>
                </ul>
                <p class="mt-4">We understand the importance of honoring your loved one's beliefs and traditions. Our facilities are designed to be adaptable to different ceremonial needs, and our staff approaches each tradition with respect and careful attention to detail.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 10 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">What is the embalming process?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>Our embalming process is carried out with the utmost respect and professionalism:</p>
                <ol class="list-decimal ml-6 mt-2 space-y-1">
                    <li>The deceased is brought to our facility after all legal requirements are met</li>
                    <li>Our licensed embalmers carefully clean the body</li>
                    <li>Formalin is professionally injected in a process that takes 2-3 hours</li>
                    <li>After completion, the body is placed in the chosen casket</li>
                    <li>The casket is then transported to the viewing location where our team has already prepared the viewing area</li>
                </ol>
                <p class="mt-4">Our embalming team consists of a Chief Embalmer who oversees the process and Assistant Embalmers who help with the procedure. All of our embalmers are professionally trained and licensed to ensure the highest quality of care for your loved one.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 13 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">Can we extend the wake period if needed?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>Yes, we can accommodate extended wakes:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Wakes can be extended up to 2 weeks if needed</li>
                    <li>For premium packages (₱100,000+), we provide fresh flower replacements during extended wakes</li>
                    <li>Additional embalming (re-injection of formalin) may be required for longer viewings</li>
                    <li>Chapel rental extension fees apply (₱6,000 per day)</li>
                </ul>
                <p class="mt-4">We understand that families sometimes need more time for relatives to arrive or to complete arrangements. Our staff will work with you to ensure your loved one is properly cared for throughout the extended viewing period.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 14 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">What happens if we need more services than you can handle at once?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>We have contingency plans for high-demand periods:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Our standard capacity is 12 simultaneous services</li>
                    <li>During peak times, we rent additional equipment as needed</li>
                    <li>We've successfully handled days with 6-7 new services starting</li>
                    <li>There is no absolute limit to the number of families we can serve</li>
                </ul>
                <p class="mt-4">While we maintain our own inventory of caskets and equipment, we have established relationships with nearby funeral homes to ensure we can meet all community needs, even during unusually busy periods.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 16 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">How do you handle deaths from accidents or disease?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>These cases require special procedures:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>We coordinate with SOCO (Scene of the Crime Operatives) when needed</li>
                    <li>We wait for official death certificates and clearance before beginning services</li>
                    <li>The burial may be delayed while investigations or special processes are completed</li>
                    <li>Our staff is trained to handle these sensitive situations with extra care</li>
                </ul>
                <p class="mt-4">While these cases are more complicated, we guide families through every step of the process, ensuring all legal requirements are met while providing compassionate care.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 17 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">Can we rent just certain items or services without a full package?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>Yes, we offer à la carte services in certain situations:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Chapel rental only (₱6,000 per day)</li>
                    <li>Casket sales or rentals (especially for cremation cases)</li>
                    <li>Transportation services</li>
                    <li>Embalming services</li>
                    <li>Viewing equipment (tents, chairs, etc.)</li>
                </ul>
                <p class="mt-4">This is common when families are transferring a loved one from another location or when they've arranged some elements themselves but need specific services from us. We're happy to accommodate partial service requests.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 18 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">How do you handle special requests from families?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>We welcome and accommodate special requests:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Families can make requests directly to the owner during arrangements</li>
                    <li>We can connect families with suppliers for out-of-package items</li>
                    <li>Religious and cultural customs are respectfully accommodated</li>
                    <li>Personalized elements can be added to services</li>
                </ul>
                <p class="mt-4">Because the owner handles all client interactions personally, we can be more flexible than larger funeral homes in accommodating unique requests. If we can't provide something directly, we'll help you find someone who can.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 19 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">What are your business hours?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>Our services are available 24/7 because:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Death can occur at any time, day or night</li>
                    <li>Our staff is always on call for emergencies</li>
                    <li>Administrative offices have standard hours, but our service never stops</li>
                </ul>
                <p class="mt-4">While walk-in arrangements are preferred during daytime hours, we're available whenever you need us. Our commitment is to be there for families whenever death occurs.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 20 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">Where are your branches located?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>We currently operate in several locations:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li><strong>Main Branch:</strong> Pila, Laguna</li>
                    <li><strong>Other Branches:</strong> Paete, Laguna</li>
                </ul>
                <p class="mt-4">Our business began in 1980 as St. Anthony Funeral Services, became Relova Funeral Services, and has operated as VJay Relova Funeral Services since 2016. We've been serving these communities with compassion and professionalism for decades.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 15 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">What makes your funeral services different from competitors?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>Our unique advantages include:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Personal service directly from the owner for all arrangements</li>
                    <li>Complete packages with no hidden fees (unlike some competitors who charge extra for requests)</li>
                    <li>Flexible, negotiable pricing rather than fixed rates</li>
                    <li>Ability to customize services to each family's specific needs</li>
                    <li>Longstanding community relationships since 1980</li>
                    <li>Compassionate approach to families in difficult situations</li>
                </ul>
                <p class="mt-4">We build genuine connections with families, which is why many choose us over larger corporate funeral providers.</p>
            </div>
        </div>
    </div>

    <!-- ===== COSTS CATEGORY ===== -->
    <!-- FAQ Item 5 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="costs">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">How much does a funeral typically cost?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>Our funeral packages range from ₱35,000 for a complete basic package to ₱500,000 for premium services. We've provided services up to ₱800,000-1,000,000 for clients with specific requirements.</p>
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
    
    <!-- FAQ Item 9 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="costs">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">What payment options do you offer?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>We offer flexible payment options to accommodate your needs:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>For services over ₱100,000, we require a 30% down payment</li>
                    <li>For services under ₱100,000, payment is typically made before or on the day of burial</li>
                    <li>We offer interest-free installment plans for those who need additional time to pay</li>
                    <li>Early payment discounts may be available in some cases</li>
                </ul>
                <p class="mt-4">We understand that this is a difficult time, and we're committed to working with you to find a payment solution that meets your needs. Please speak with our owner directly about your specific situation, as we can often customize payment terms.</p>
            </div>
        </div>
    </div>
    
    <!-- FAQ Item 11 -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="costs">
        <button class="faq-question w-full text-left p-6 focus:outline-none">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-hedvig text-navy">Do you offer discounts on your funeral services?</h3>
                <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
            </div>
        </button>
        <div class="faq-answer px-6 pb-6 hidden">
            <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                <p>Yes, we offer several discount options:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Automatic 20% discount for PWDs and senior citizens</li>
                    <li>Situational discounts based on family circumstances and negotiations</li>
                    <li>Early payment discounts may be available</li>
                    <li>Special considerations for families facing financial hardship</li>
                </ul>
                <p class="mt-4">We understand that each family's situation is unique, and we're committed to working with you to find a solution that respects your needs while maintaining the quality of our services.</p>
            </div>
        </div>
    </div>
    <!-- ===== LIFE PLAN CATEGORY ===== -->
<!-- FAQ Item 21 (New) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="life-plan">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">Do you offer lifeplan installment plans for those who are not yet deceased?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>Yes, we offer lifeplan installment plans. Many clients prefer our services over other providers because:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>Our packages are complete and customizable</li>
                <li>Prices are negotiable (unlike fixed-price plans from larger companies)</li>
                <li>We work with you to tailor a plan that meets your specific needs and budget</li>
            </ul>
            <p class="mt-4">You can start planning and paying for funeral services in advance, ensuring your wishes are honored while easing the financial burden on your family.</p>
        </div>
    </div>
</div>

<!-- FAQ Item 22 (New) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="life-plan">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">What happens if a family cannot pay the full amount for services?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>We prioritize compassion and community relationships in these situations:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>We offer interest-free installment plans with flexible terms</li>
                <li>May accept partial payments in cases of extreme hardship</li>
                <li>Never stop a service once it has begun, regardless of payment status</li>
                <li>In rare cases, may donate services or caskets</li>
            </ul>
            <p class="mt-4">Our priority is serving families during difficult times while maintaining the sustainability of our business.</p>
        </div>
    </div>
</div>

<!-- ===== SERVICES CATEGORY ===== -->
<!-- FAQ Item 23 (New) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">How do you handle deaths from accidents or disease?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>These sensitive cases require special procedures:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>Coordinate with SOCO (Scene of the Crime Operatives) when required</li>
                <li>Wait for official documentation before proceeding with services</li>
                <li>May involve delays for investigations or special processes</li>
                <li>Our staff provides extra care and guidance throughout</li>
            </ul>
            <p class="mt-4">While more complicated, we ensure all legal requirements are met while treating the deceased and family with dignity.</p>
        </div>
    </div>
</div>

<!-- FAQ Item 24 (New) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">Can we rent specific items without a full package?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>Yes, we offer à la carte services including:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>Chapel rental (₱6,000 per day)</li>
                <li>Casket sales or rentals (especially for cremation)</li>
                <li>Transportation services</li>
                <li>Embalming services</li>
                <li>Viewing equipment (tents, chairs, etc.)</li>
            </ul>
            <p class="mt-4">This is ideal for families who need specific services or are transferring a loved one from another location.</p>
        </div>
    </div>
</div>

<!-- FAQ Item 25 (New) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="services">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">What's included in cremation packages?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>Our cremation services include:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>Coordination with third-party crematoriums</li>
                <li>Priority scheduling for your preferred date</li>
                <li>Optional wake services before cremation</li>
                <li>Single payment convenience for combined services</li>
            </ul>
            <p class="mt-4">While we don't operate the crematorium, we handle all arrangements and can prioritize your schedule even if others are booked for the same day.</p>
        </div>
    </div>
</div>

<!-- ===== COSTS CATEGORY ===== -->
<!-- FAQ Item 26 (New) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="costs">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">What is your pricing range?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>Our service ranges include:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>Basic package: ₱35,000 (complete service)</li>
                <li>Premium packages: Up to ₱500,000</li>
                <li>Deluxe imported caskets: ₱350,000+</li>
                <li>Most expensive service provided: ₱1 million</li>
            </ul>
            <p class="mt-4">Prices are negotiable and we offer automatic 20% discounts for PWDs and senior citizens.</p>
        </div>
    </div>
</div>

<!-- FAQ Item 27 (New) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden" data-category="costs">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">How do you handle unpaid balances?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>Our approach to unpaid balances:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>Maintain detailed records of outstanding payments</li>
                <li>Offer flexible repayment plans (sometimes spanning years)</li>
                <li>Work with families waiting for insurance/benefits claims</li>
                <li>Prioritize service over immediate payment collection</li>
            </ul>
            <p class="mt-4">While unpaid balances are common in our industry, we believe maintaining community relationships is most important.</p>
        </div>
    </div>
</div>
</div>
    </div>
   <!-- Add this after your FAQ items but before your footer -->
<div class="flex justify-center mb-16">
    <nav class="flex items-center space-x-2" id="faq-pagination">
        <!-- Pagination buttons will be inserted here by JavaScript -->
    </nav>
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
    
    <!-- Replace both script blocks with this single, optimized version -->
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== GLOBAL VARIABLES =====
    const faqItems = document.querySelectorAll('.bg-white.rounded-lg.shadow-md.overflow-hidden');
    const itemsPerPage = 10;
    let currentPage = 1;
    let currentCategory = 'all';
    const paginationContainer = document.getElementById('faq-pagination');
    const categoryButtons = document.querySelectorAll('.category-btn');

    // ===== INITIAL SETUP =====
    function initializeFAQ() {
        createPagination();
        filterAndShowItems();
        setupEventListeners();
    }

    // ===== PAGINATION FUNCTIONS =====
    function createPagination() {
        paginationContainer.innerHTML = '';
        
        const filteredItems = getFilteredItems();
        const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        
        if (totalPages <= 1) return;
        
        // Previous button
        const prevButton = document.createElement('button');
        prevButton.className = 'px-4 py-2 border rounded-md hover:bg-navy/10 transition-colors';
        prevButton.innerHTML = 'Previous';
        prevButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                filterAndShowItems();
            }
        });
        paginationContainer.appendChild(prevButton);
        
        // Page number buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.className = `px-4 py-2 border rounded-md ${i === currentPage ? 'bg-navy text-white' : 'hover:bg-navy/10'} transition-colors`;
            pageButton.textContent = i;
            pageButton.addEventListener('click', () => {
                currentPage = i;
                filterAndShowItems();
            });
            paginationContainer.appendChild(pageButton);
        }
        
        // Next button
        const nextButton = document.createElement('button');
        nextButton.className = 'px-4 py-2 border rounded-md hover:bg-navy/10 transition-colors';
        nextButton.innerHTML = 'Next';
        nextButton.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                filterAndShowItems();
            }
        });
        paginationContainer.appendChild(nextButton);
    }

    // ===== FILTERING FUNCTIONS =====
    function getFilteredItems() {
        return Array.from(faqItems).filter(item => {
            if (currentCategory === 'all') return true;
            return item.getAttribute('data-category') === currentCategory;
        });
    }

    function filterAndShowItems() {
        const filteredItems = getFilteredItems();
        const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        
        // Reset to page 1 if current page exceeds total pages
        if (currentPage > totalPages && totalPages > 0) {
            currentPage = 1;
        }
        
        // Hide all items first
        faqItems.forEach(item => {
            item.style.display = 'none';
        });
        
        // Calculate range for current page
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        // Show items for current page and category
        filteredItems.slice(startIndex, endIndex).forEach(item => {
            item.style.display = 'block';
        });
        
        // Recreate pagination with new item count
        createPagination();
        
        // Smooth scroll to FAQ section
        document.querySelector('.container.mx-auto.px-6.py-12')?.scrollIntoView({
            behavior: 'smooth'
        });
    }

    // ===== EVENT HANDLERS =====
    function setupEventListeners() {
        // Category buttons
        categoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Update active category
                currentCategory = this.getAttribute('data-category');
                currentPage = 1;
                
                // Update button styles
                categoryButtons.forEach(btn => {
                    btn.classList.remove('active', 'bg-navy', 'text-white');
                    btn.classList.add('bg-white', 'text-navy', 'border', 'border-navy/20', 'hover:bg-navy/5');
                });
                
                this.classList.add('active', 'bg-navy', 'text-white');
                this.classList.remove('bg-white', 'text-navy', 'border', 'border-navy/20', 'hover:bg-navy/5');
                
                // Filter and show items
                filterAndShowItems();
            });
        });
        
        // FAQ accordion toggle
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', function() {
                const answer = this.nextElementSibling;
                const icon = this.querySelector('.fa-chevron-down');
                
                // Toggle current answer
                answer.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            });
        });
    }

    // ===== LOADER ANIMATION =====
    const loader = document.getElementById('page-loader');
    function showLoader() {
        loader.classList.remove('opacity-0', 'pointer-events-none');
        document.body.style.overflow = 'hidden';
    }
    function hideLoader() {
        loader.classList.add('opacity-0', 'pointer-events-none');
        document.body.style.overflow = '';
    }
    
    // Internal link handling
    document.querySelectorAll('a[href]:not([href^="#"]):not([href^="http"]):not([href^="mailto"])').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                showLoader();
                setTimeout(() => window.location.href = this.getAttribute('href'), 800);
            }
        });
    });
    
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) hideLoader();
    });
    window.addEventListener('load', hideLoader);

    // Mobile menu toggle
    function toggleMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenu.classList.toggle('hidden');
    }

    // ===== INITIALIZATION =====
    initializeFAQ();
});
</script>


    <!-- Loading Animation Overlay -->
<div id="page-loader" class="fixed inset-0 bg-black bg-opacity-80 z-[999] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-500">
    <div class="text-center">
        <!-- Animated Candle -->
        <div class="relative w-full h-48 mb-6">
            <!-- Candle -->
            <div class="absolute left-1/2 transform -translate-x-1/2 bottom-0 w-16">
                <!-- Wick -->
                <div class="w-1 h-5 bg-gray-700 mx-auto mb-0 rounded-t-lg"></div>
                
                <!-- Animated Flame -->
                <div>
                    <!-- Outer Flame -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 bottom-[75px] w-6 h-12 bg-yellow-600/80 rounded-full blur-sm animate-pulse"></div>
                    
                    <!-- Inner Flame -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 bottom-[80px] w-3 h-10 bg-white/90 rounded-full blur-[2px] animate-flame"></div>
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