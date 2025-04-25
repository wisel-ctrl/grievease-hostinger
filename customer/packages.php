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

$query = "SELECT s.service_id, s.service_name, s.description, s.selling_price, s.image_url, 
                 i.item_name AS casket_name, s.flower_design, s.inclusions
          FROM services_tb s
          LEFT JOIN inventory_tb i ON s.casket_id = i.inventory_id
          WHERE s.status = 'active' AND s.branch_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $branch_id); // "i" stands for integer
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
    'declined' => 0
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
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - GrievEase Funeral Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --navbar-height: 64px;
            --section-spacing: 4rem;
        }
        .package-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .package-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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
                    <i class="fas fa-bell"></i>
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
            class="w-full md:w-2/5 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"
        >
            <option value="">Sort by Price</option>
            <option value="asc">Low to High</option>
            <option value="desc">High to Low</option>
        </select>

        <!-- Reset Filters Button -->
        <button 
            id="resetFilters" 
            class="w-full md:w-1/5 px-6 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg flex items-center justify-center space-x-2 transition duration-300"
        >
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
                    <a href="privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                    <a href="#" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
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
                    <span class="text-sm text-gray-600 mt-2 text-center">Pre-need funeral planning</span>
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
                <!-- Mobile Progress Indicator -->
                <div class="flex items-center justify-between mb-4 md:hidden">
                    <div class="flex items-center">
                        <div class="h-6 w-6 rounded-full bg-yellow-600 text-white flex items-center justify-center text-xs font-bold">1</div>
                        <div class="h-1 w-8 bg-yellow-600"></div>
                        <div class="h-6 w-6 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center text-xs font-bold">2</div>
                    </div>
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
                    <button id="continueToFormBtn" class="mt-4 w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
                        <span>Continue to Booking</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- Right Side: Booking Form -->
            <div class="bg-white p-4 md:p-8 border-t md:border-t-0 md:border-l border-gray-200 overflow-y-auto form-section hidden md:block">
                <!-- Header and back button for mobile -->
                <div class="flex justify-between items-center mb-4">
                    <!-- Mobile Progress Indicator (Form View) -->
                    <div class="flex items-center md:hidden">
                        <div class="h-6 w-6 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center text-xs font-bold">1</div>
                        <div class="h-1 w-8 bg-yellow-600"></div>
                        <div class="h-6 w-6 rounded-full bg-yellow-600 text-white flex items-center justify-center text-xs font-bold">2</div>
                    </div>
                    <h2 class="text-xl md:text-2xl font-hedvig text-navy hidden md:block">Book Your Traditional Service</h2>
                    <div class="flex items-center">
                        <button id="backToDetailsBtn" class="text-navy hover:text-yellow-600 flex items-center md:hidden">
                            <i class="fas fa-arrow-left text-lg mr-2"></i>
                            <span>Back</span>
                        </button>
                        <button class="closeModalBtn text-gray-500 hover:text-navy ml-4">
                            <i class="fas fa-times text-xl md:text-2xl"></i>
                        </button>
                    </div>
                </div>

                <h2 class="text-xl font-hedvig text-navy mb-4 md:hidden">Book Your Traditional Service</h2>

                <form id="traditionalBookingForm" class="space-y-4">
                    <input type="hidden" id="traditionalSelectedPackagePrice" name="packagePrice">
                    <input type="hidden" id="traditionalServiceId" name="service_id">
                    <input type="hidden" id="traditionalBranchId" name="branch_id">
                    <input type="hidden" name="customerID" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Deceased Information</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <label for="traditionalDeceasedFirstName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">First Name *</label>
                                <input type="text" id="traditionalDeceasedFirstName" name="deceasedFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDeceasedMiddleName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Middle Name</label>
                                <input type="text" id="traditionalDeceasedMiddleName" name="deceasedMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <label for="traditionalDeceasedLastName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Last Name *</label>
                                <input type="text" id="traditionalDeceasedLastName" name="deceasedLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDeceasedSuffix" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Suffix</label>
                                <input type="text" id="traditionalDeceasedSuffix" name="deceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:gap-4">
                            <!-- Mobile-friendly date entry - one per row -->
                            <div class="mb-2">
                                <label for="traditionalDateOfBirth" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Date of Birth</label>
                                <input type="date" id="traditionalDateOfBirth" name="dateOfBirth" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div class="mb-2">
                                <label for="traditionalDateOfDeath" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Date of Death *</label>
                                <input type="date" id="traditionalDateOfDeath" name="dateOfDeath" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDateOfBurial" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Date of Burial</label>
                                <input type="date" id="traditionalDateOfBurial" name="dateOfBurial" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <!-- Restore original layout for medium screens and up -->
                        <div class="hidden md:grid md:grid-cols-3 md:gap-4 md:mt-4">
                            <div>
                                <label for="traditionalDateOfBirthMd" class="block text-sm font-medium text-navy mb-2">Date of Birth</label>
                                <input type="date" id="traditionalDateOfBirthMd" name="dateOfBirth" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDateOfDeathMd" class="block text-sm font-medium text-navy mb-2">Date of Death *</label>
                                <input type="date" id="traditionalDateOfDeathMd" name="dateOfDeath" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDateOfBurialMd" class="block text-sm font-medium text-navy mb-2">Date of Burial</label>
                                <input type="date" id="traditionalDateOfBurialMd" name="dateOfBurial" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="mt-3 md:mt-4">
                            <label for="traditionalDeathCertificate" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Death Certificate</label>
                            <div class="relative">
                                <input type="file" id="traditionalDeathCertificate" name="deathCertificate" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs md:text-sm px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                <div class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, JPEG, PNG</div>
                            </div>
                        </div>
                        <div class="mt-3 md:mt-4">
                            <label for="traditionalDeceasedAddress" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Address of the Deceased</label>
                            <textarea id="traditionalDeceasedAddress" name="deceasedAddress" rows="2" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"></textarea>
                        </div>
                        
                        <div class="flex items-center mt-3 md:mt-4">
                            <input type="checkbox" id="traditionalWithCremate" name="with_cremate" value="yes" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                            <label for="traditionalWithCremate" class="ml-2 block text-xs md:text-sm text-navy">
                                Include cremation service
                            </label>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Payment</h3>
                        <div class="grid grid-cols-1 gap-3 md:gap-4">
                            <div>
                                <label for="traditionalGcashReceipt" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">GCash Receipt</label>
                                <div class="relative">
                                    <input type="file" id="traditionalGcashReceipt" name="gcashReceipt" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs md:text-sm px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <div class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, JPEG, PNG</div>
                                </div>
                            </div>
                            <div>
                                <label for="traditionalReferenceNumber" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">GCash Reference Number *</label>
                                <input type="text" id="traditionalReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <!-- Restore original layout for medium screens -->
                        <div class="hidden md:grid md:grid-cols-2 md:gap-4 md:mt-4">
                            <div>
                                <label for="traditionalGcashReceiptMd" class="block text-sm font-medium text-navy mb-2">GCash Receipt</label>
                                <input type="file" id="traditionalGcashReceiptMd" name="gcashReceipt" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalReferenceNumberMd" class="block text-sm font-medium text-navy mb-2">GCash Reference Number *</label>
                                <input type="text" id="traditionalReferenceNumberMd" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
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

                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
                        <span>Confirm Booking</span>
                        <i class="fas fa-check ml-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

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

                <!-- Mobile-only summary and navigation button -->
                <div class="mt-6 border-t border-gray-200 pt-4 md:hidden">
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="lifeplanTotalPriceMobile" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-navy">Payment Term</span>
                            <span id="lifeplanPaymentTermDisplayMobile" class="text-yellow-600">5 Years (60 Monthly Payments)</span>
                        </div>
                        <div class="flex justify-between font-bold mt-2 pt-2 border-t border-gray-300">
                            <span class="text-navy">Monthly Payment</span>
                            <span id="lifeplanMonthlyPaymentMobile" class="text-yellow-600">₱0</span>
                        </div>
                    </div>
                    <button id="continueToLifeplanFormBtn" class="mt-4 w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
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
                        <button id="backToLifeplanDetailsBtn" class="mr-2 text-gray-500 hover:text-navy md:hidden">
                            <i class="fas fa-arrow-left text-lg"></i>
                        </button>
                        <button class="closeModalBtn text-gray-500 hover:text-navy">
                            <i class="fas fa-times text-xl md:text-2xl"></i>
                        </button>
                    </div>
                </div>

                <form id="lifeplanBookingForm" class="space-y-4">
                    <input type="hidden" id="lifeplanSelectedPackageName" name="packageName">
                    <input type="hidden" id="lifeplanSelectedPackagePrice" name="packagePrice">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Plan Holder Information</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <label for="lifeplanHolderFirstName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">First Name *</label>
                                <input type="text" id="lifeplanHolderFirstName" name="holderFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanHolderMiddleName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Middle Name</label>
                                <input type="text" id="lifeplanHolderMiddleName" name="holderMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <label for="lifeplanHolderLastName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Last Name *</label>
                                <input type="text" id="lifeplanHolderLastName" name="holderLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanHolderSuffix" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Suffix</label>
                                <input type="text" id="lifeplanHolderSuffix" name="holderSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                            <div>
                                <label for="lifeplanDateOfBirth" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Date of Birth *</label>
                                <input type="date" id="lifeplanDateOfBirth" name="dateOfBirth" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanContactNumber" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Contact Number *</label>
                                <input type="tel" id="lifeplanContactNumber" name="contactNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="mt-3 md:mt-4">
                            <label for="lifeplanEmailAddress" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Email Address *</label>
                            <input type="email" id="lifeplanEmailAddress" name="emailAddress" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                        </div>
                        <div class="mt-3 md:mt-4">
                            <label for="lifeplanHolderAddress" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Current Address *</label>
                            <textarea id="lifeplanHolderAddress" name="holderAddress" rows="2" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"></textarea>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Payment Plan</h3>
                        <div class="flex items-center mb-3 md:mb-4">
                            <label class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2 mr-4">Payment Term:</label>
                            <select id="lifeplanPaymentTerm" name="paymentTerm" class="px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                <option value="60">5 Years (60 Monthly Payments)</option>
                                <option value="36">3 Years (36 Monthly Payments)</option>
                                <option value="24">2 Years (24 Monthly Payments)</option>
                                <option value="12">1 Year (12 Monthly Payments)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                            <div>
                                <label for="lifeplanGcashReceipt" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">First Payment Receipt *</label>
                                <input type="file" id="lifeplanGcashReceipt" name="gcashReceipt" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs md:text-sm px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanReferenceNumber" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">GCash Reference Number *</label>
                                <input type="text" id="lifeplanReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
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
    
    if (continueBtn && backBtn && detailsSection && formSection) {
        continueBtn.addEventListener('click', function() {
            detailsSection.classList.add('hidden');
            formSection.classList.remove('hidden');
        });
        
        backBtn.addEventListener('click', function() {
            formSection.classList.add('hidden');
            detailsSection.classList.remove('hidden');
        });
    }

    if (continueToLifeplanFormBtn && backToLifeplanDetailsBtn && lifeplanDetailsSection && lifeplanFormSection) {
    continueToLifeplanFormBtn.addEventListener('click', function() {
        lifeplanDetailsSection.classList.add('hidden');
        lifeplanFormSection.classList.remove('hidden');
    });
    
    backToLifeplanDetailsBtn.addEventListener('click', function() {
        lifeplanFormSection.classList.add('hidden');
        lifeplanDetailsSection.classList.remove('hidden');
    });
}
    
    // Make sure the modal close button works for both sections
    document.querySelectorAll('.closeModalBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('traditionalModal').classList.add('hidden');
            // Reset to show details when modal is reopened
            detailsSection.classList.remove('hidden');
            formSection.classList.add('hidden');
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

    // Function to open traditional modal with package details
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
        
        // Reset form section visibility
        detailsSection.classList.remove('hidden');
        formSection.classList.add('hidden');
        
        document.getElementById('traditionalModal').classList.remove('hidden');
    }
    
    // Reset addons when modal is opened
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Lifeplan Service button click event
    document.getElementById('lifeplanServiceBtn').addEventListener('click', function() {
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
        const monthlyPayment = Math.ceil(totalPrice / 60);
        
        document.getElementById('lifeplanTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('lifeplanTotalPriceMobile').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
        document.getElementById('lifeplanMonthlyPaymentMobile').textContent = `₱${monthlyPayment.toLocaleString()}`;

        const featuresList = document.getElementById('lifeplanPackageFeatures');
        featuresList.innerHTML = '';
        packageFeatures.forEach(feature => {
            featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
        });
        
        document.getElementById('lifeplanSelectedPackageName').value = packageName;
        document.getElementById('lifeplanSelectedPackagePrice').value = packagePrice;
        
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

    // Function to close all modals
    function closeAllModals() {
        document.getElementById('traditionalModal').classList.add('hidden');
        document.getElementById('lifeplanModal').classList.add('hidden');
        document.getElementById('serviceTypeModal').classList.add('hidden');
        
        // Reset traditional modal to show details section
        detailsSection.classList.remove('hidden');
        formSection.classList.add('hidden');
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

    // Update lifeplan monthly payment when payment term changes
    document.getElementById('lifeplanPaymentTerm').addEventListener('change', function() {
        updateLifeplanTotal();
        const months = parseInt(this.value);
        const totalPrice = parseInt(sessionStorage.getItem('selectedPackagePrice') || '0');
        const monthlyPayment = Math.ceil(totalPrice / months);
        
        let termText = '';
        if (months === 60) termText = '5 Years (60 Monthly Payments)';
        else if (months === 36) termText = '3 Years (36 Monthly Payments)';
        else if (months === 24) termText = '2 Years (24 Monthly Payments)';
        else if (months === 12) termText = '1 Year (12 Monthly Payments)';
        
        document.getElementById('lifeplanPaymentTermDisplay').textContent = termText;
        document.getElementById('lifeplanPaymentTermDisplayMobile').textContent = termText;
        document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
        document.getElementById('lifeplanMonthlyPaymentMobile').textContent = `₱${monthlyPayment.toLocaleString()}`;
    });

    // Form submission for Lifeplan
    document.getElementById('lifeplanBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
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
                fetch('booking/lifeplan_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Lifeplan booking submitted successfully!',
                            icon: 'success',
                            confirmButtonColor: '#d97706'
                        });
                        closeAllModals();
                        formElement.reset();
                        showNotification('New Lifeplan', 'Your lifeplan has been confirmed. Click to view details.', 'notification.php');
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

function filterAndSortPackages() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const priceSort = document.getElementById('priceSort').value;
    const packagesContainer = document.getElementById('packages-container');
    const noResults = document.getElementById('no-results');
    
    let visibleCount = 0;
    document.querySelectorAll('.package-card').forEach(card => {
        const packageName = card.dataset.name.toLowerCase();
        const packageDescription = card.querySelector('p').textContent.toLowerCase();
        const featureTexts = Array.from(card.querySelectorAll('ul li')).map(li => li.textContent.toLowerCase());
        
        const matchesSearch = searchTerm === '' || 
                            packageName.includes(searchTerm) || 
                            packageDescription.includes(searchTerm) ||
                            featureTexts.some(text => text.includes(searchTerm));
        
        if (matchesSearch) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });
    
    if (visibleCount === 0) {
        noResults.classList.remove('hidden');
    } else {
        noResults.classList.add('hidden');
    }
    
    if (priceSort) {
        const cards = Array.from(packagesContainer.querySelectorAll('.package-card:not(.hidden)'));
        cards.sort((a, b) => {
            const priceA = parseFloat(a.dataset.price);
            const priceB = parseFloat(b.dataset.price);
            return priceSort === 'asc' ? priceA - priceB : priceB - priceA;
        });
        
        cards.forEach(card => {
            packagesContainer.appendChild(card);
        });
    }
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('priceSort').value = '';
    
    document.querySelectorAll('.package-card').forEach(card => {
        card.classList.remove('hidden');
    });
    
    document.getElementById('no-results').classList.add('hidden');
}

// Event Listeners
document.getElementById('searchInput').addEventListener('input', filterAndSortPackages);
document.getElementById('priceSort').addEventListener('change', filterAndSortPackages);
document.getElementById('resetFilters').addEventListener('click', resetFilters);
document.getElementById('reset-filters-no-results').addEventListener('click', resetFilters);

function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
</script>
</body>
</html>