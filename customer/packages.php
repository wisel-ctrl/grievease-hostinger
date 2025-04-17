<?php
session_start();

require_once '../db_connect.php'; // Database connection

// Get user's first name from database
$user_id = $_SESSION['user_id'];
$query = "SELECT u.first_name, u.last_name, u.email, u.birthdate, u.branch_loc, b.branch_id 
          FROM users u
          LEFT JOIN branch_tb b ON u.branch_loc = b.branch_name
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
$stmt->close();

// Fetch packages from database
$packages = [];
$query = "SELECT s.service_id, s.service_name, s.description, s.selling_price, s.image_url 
                 , i.item_name as casket_name, s.flower_design, s.inclusions
          FROM services_tb s
          LEFT JOIN inventory_tb i ON s.casket_id = i.inventory_id
          WHERE s.status = 'active'";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Parse inclusions (assuming it's stored as a JSON string or comma-separated)
        $inclusions = [];
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
            'image' => $row['image_url'],
            'features' => $inclusions,
            'service' => 'traditional' // Assuming all are traditional for now
        ];
    }
    $result->free();
}

$conn->close();
?>

<script src="customer_support.js"></script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - GrievEase Funeral Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
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
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">2</span>
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
                <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">2</span>
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
            <!-- Legacy Tribute Package -->
            <!-- Eternal Remembrance Package -->
            <!-- Heritage Memorial Package -->
            <!-- Serene Passage Package -->
            <!-- Dignified Farewell Package -->
            <!-- Peaceful Journey Package -->
            <!-- Cherished Memories Package -->
            <!-- Gentle Passage Package -->
            <!-- Sincere Tribute Package -->
            <!-- Heartfelt Farewell Package -->
            <!-- Simple Dignity Package -->
            <!-- Essential Remembrance Package -->
            <!-- Essential Remembrance Package -->
        <!-- No Results Message (hidden by default) -->
        <div id="no-results" class="hidden text-center py-12">
            <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-2xl font-hedvig text-navy mb-2">No Packages Found</h3>
            <p class="text-gray-600 max-w-md mx-auto">We couldn't find any packages matching your criteria. Try adjusting your filters or search terms.</p>
            <button id="reset-filters-no-results" class="mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                Reset All Filters
            </button>
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
                    <h2 id="traditionalPackageName" class="text-3xl font-hedvig text-navy"></h2>
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

            <!-- Right Side: Booking Form -->
            <div class="bg-white p-8 border-l border-gray-200 overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-hedvig text-navy">Book Your Traditional Service</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <form id="traditionalBookingForm" class="space-y-4">
                <input type="hidden" id="traditionalSelectedPackagePrice" name="packagePrice">
                <input type="hidden" id="traditionalServiceId" name="service_id">
                <input type="hidden" id="traditionalBranchId" name="branch_id">
                <input type="hidden" name="customerID" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                    
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
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="traditionalDeceasedLastName" class="block text-sm font-medium text-navy mb-2">Last Name *</label>
                                <input type="text" id="traditionalDeceasedLastName" name="deceasedLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDeceasedSuffix" class="block text-sm font-medium text-navy mb-2">Suffix</label>
                                <input type="text" id="traditionalDeceasedSuffix" name="deceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
                        <div class="mt-4">
                            <label for="traditionalDeathCertificate" class="block text-sm font-medium text-navy mb-2">Death Certificate</label>
                            <input type="file" id="traditionalDeathCertificate" name="deathCertificate" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                        </div>
                        <div class="mt-4">
                            <label for="traditionalDeceasedAddress" class="block text-sm font-medium text-navy mb-2">Address of the Deceased</label>
                            <textarea id="traditionalDeceasedAddress" name="deceasedAddress" rows="3" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"></textarea>
                        </div>

                        <div class="flex items-center">
                        <input type="checkbox" id="traditionalWithCremate" name="with_cremate" value="yes" class="h-4 w-4 mt-2 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                        <label for="traditionalWithCremate" class="ml-2 block text-sm text-navy">
                            Include cremation service
                        </label>
                    </div>

                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-hedvig text-navy mb-4">Payment</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="traditionalGcashReceipt" class="block text-sm font-medium text-navy mb-2">GCash Receipt *</label>
                                <input type="file" id="traditionalGcashReceipt" name="gcashReceipt" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalReferenceNumber" class="block text-sm font-medium text-navy mb-2">GCash Reference Number *</label>
                                <input type="text" id="traditionalReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
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

<!-- Lifeplan Modal (Hidden by Default) -->
<div id="lifeplanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[80vh]">
        <!-- Scroll container for both columns -->
        <div class="modal-scroll-container grid grid-cols-1 md:grid-cols-2 overflow-y-auto max-h-[80vh]">
            <!-- Left Side: Package Details -->
            <div class="bg-cream p-8">
                <!-- Package Image -->
                <div class="mb-6">
                    <img id="lifeplanPackageImage" src="" alt="" class="w-full h-64 object-cover rounded-lg mb-4">
                </div>

                <!-- Package Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 id="lifeplanPackageName" class="text-3xl font-hedvig text-navy"></h2>
                    <div id="lifeplanPackagePrice" class="text-3xl font-hedvig text-yellow-600"></div>
                </div>

                <!-- Package Description -->
                <p id="lifeplanPackageDesc" class="text-dark mb-6"></p>

                <!-- Main Package Details -->
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-xl font-hedvig text-navy mb-4">Package Includes:</h3>
                    <ul id="lifeplanPackageFeatures" class="space-y-2">
                        <!-- Features will be inserted here by JavaScript -->
                    </ul>
                </div>
            </div>

            <!-- Right Side: Booking Form -->
            <div class="bg-white p-8 border-l border-gray-200 overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-hedvig text-navy">Book Your Lifeplan</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <form id="lifeplanBookingForm" class="space-y-4">
                    <input type="hidden" id="lifeplanSelectedPackageName" name="packageName">
                    <input type="hidden" id="lifeplanSelectedPackagePrice" name="packagePrice">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-hedvig text-navy mb-4">Plan Holder Information</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="lifeplanHolderFirstName" class="block text-sm font-medium text-navy mb-2">First Name *</label>
                                <input type="text" id="lifeplanHolderFirstName" name="holderFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanHolderMiddleName" class="block text-sm font-medium text-navy mb-2">Middle Name</label>
                                <input type="text" id="lifeplanHolderMiddleName" name="holderMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="lifeplanHolderLastName" class="block text-sm font-medium text-navy mb-2">Last Name *</label>
                                <input type="text" id="lifeplanHolderLastName" name="holderLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanHolderSuffix" class="block text-sm font-medium text-navy mb-2">Suffix</label>
                                <input type="text" id="lifeplanHolderSuffix" name="holderSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="lifeplanDateOfBirth" class="block text-sm font-medium text-navy mb-2">Date of Birth *</label>
                                <input type="date" id="lifeplanDateOfBirth" name="dateOfBirth" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanContactNumber" class="block text-sm font-medium text-navy mb-2">Contact Number *</label>
                                <input type="tel" id="lifeplanContactNumber" name="contactNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="lifeplanEmailAddress" class="block text-sm font-medium text-navy mb-2">Email Address *</label>
                            <input type="email" id="lifeplanEmailAddress" name="emailAddress" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                        </div>
                        <div class="mt-4">
                            <label for="lifeplanHolderAddress" class="block text-sm font-medium text-navy mb-2">Current Address *</label>
                            <textarea id="lifeplanHolderAddress" name="holderAddress" rows="3" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"></textarea>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-hedvig text-navy mb-4">Payment Plan</h3>
                        <div class="flex items-center mb-4">
                            <label class="block text-sm font-medium text-navy mb-2 mr-4">Payment Term:</label>
                            <select id="lifeplanPaymentTerm" name="paymentTerm" class="px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                <option value="60">5 Years (60 Monthly Payments)</option>
                                <option value="36">3 Years (36 Monthly Payments)</option>
                                <option value="24">2 Years (24 Monthly Payments)</option>
                                <option value="12">1 Year (12 Monthly Payments)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="lifeplanGcashReceipt" class="block text-sm font-medium text-navy mb-2">First Payment Receipt *</label>
                                <input type="file" id="lifeplanGcashReceipt" name="gcashReceipt" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanReferenceNumber" class="block text-sm font-medium text-navy mb-2">GCash Reference Number *</label>
                                <input type="text" id="lifeplanReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                    </div>

                    <div class="bg-cream p-4 rounded-lg">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="lifeplanTotalPrice" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
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
const packagesFromDB = <?php echo json_encode($packages); ?>;
console.log('packages from db',packagesFromDB);

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded and parsed');
    // Show service type selection modal for all packages
    // Use event delegation for the selectPackageBtn
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

    document.getElementById('traditionalBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        const formEntries = {};
        for (let pair of formData.entries()) {
            formEntries[pair[0]] = pair[1];
        }
        console.log("Form data entries:", formEntries);

        fetch('booking/booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            
            if (data.success) {
                alert('Booking successful!');
                // Redirect or show success message
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });

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
        
        // Calculate addons total
        document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
            addonTotal += parseInt(checkbox.value);
        });
        
        // Update totals
        const totalPrice = basePrice + addonTotal;
        const downpayment = Math.ceil(totalPrice * 0.3);
        
        document.getElementById('traditionalTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;
        
        // Update hidden fields
        document.getElementById('traditionalSelectedPackagePrice').value = totalPrice;
    }

    // Lifeplan addon checkbox event handling
    document.querySelectorAll('.lifeplan-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateLifeplanTotal();
        });
    });

    // Function to update lifeplan total price when addons are selected
    function updateLifeplanTotal() {
        const basePrice = parseInt(sessionStorage.getItem('selectedPackagePrice') || '0');
        let addonTotal = 0;
        
        // Calculate addons total
        document.querySelectorAll('.lifeplan-addon:checked').forEach(checkbox => {
            addonTotal += parseInt(checkbox.value);
        });
        
        // Update totals
        const totalPrice = basePrice + addonTotal;
        const months = parseInt(document.getElementById('lifeplanPaymentTerm').value);
        const monthlyPayment = Math.ceil(totalPrice / months);
        
        document.getElementById('lifeplanTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
        
        // Update hidden fields
        document.getElementById('lifeplanSelectedPackagePrice').value = totalPrice;
    }

    // Function to open traditional modal with package details
    function openTraditionalModal() {
        // Get stored package details
        const packageName = sessionStorage.getItem('selectedPackageName');
        const packagePrice = sessionStorage.getItem('selectedPackagePrice');
        const packageImage = sessionStorage.getItem('selectedPackageImage');
        const packageFeatures = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
        
        // Find the full package details from packagesFromDB to get the service_id
        const selectedPackage = packagesFromDB.find(pkg => pkg.name === packageName);
        
        // Update modal title
        document.querySelector('#traditionalModal .font-hedvig.text-2xl.text-navy').textContent = 'Book Your Package';
        
        // Update traditional modal with package details
        document.getElementById('traditionalPackageName').textContent = packageName;
        document.getElementById('traditionalPackagePrice').textContent = `₱${parseInt(packagePrice).toLocaleString()}`;
        
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
        document.getElementById('traditionalSelectedPackagePrice').value = totalPrice;
        document.getElementById('traditionalServiceId').value = selectedPackage.id; // Set the service_id
        document.getElementById('traditionalBranchId').value = <?php echo $branch_id; ?>; // Set the branch_id from PHP
        
        // Show traditional modal
        document.getElementById('traditionalModal').classList.remove('hidden');
    }
    
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Lifeplan Service button click event
    document.getElementById('lifeplanServiceBtn').addEventListener('click', function() {
        // Hide service type modal
        document.getElementById('serviceTypeModal').classList.add('hidden');
        
        // Get stored package details
        const packageName = sessionStorage.getItem('selectedPackageName');
        const packagePrice = sessionStorage.getItem('selectedPackagePrice');
        const packageImage = sessionStorage.getItem('selectedPackageImage');
        const packageFeatures = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
        
        // Update lifeplan modal with package details
        document.getElementById('lifeplanPackageName').textContent = packageName;
        document.getElementById('lifeplanPackagePrice').textContent = `₱${parseInt(packagePrice).toLocaleString()}`;
        
        if (packageImage) {
            document.getElementById('lifeplanPackageImage').src = packageImage;
            document.getElementById('lifeplanPackageImage').alt = packageName;
        }
        
        // Calculate monthly payment (default: 5 years / 60 months)
        const totalPrice = parseInt(packagePrice);
        const monthlyPayment = Math.ceil(totalPrice / 60);
        
        document.getElementById('lifeplanTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;

        // Update features list
        const featuresList = document.getElementById('lifeplanPackageFeatures');
        featuresList.innerHTML = '';
        packageFeatures.forEach(feature => {
            featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
        });
        
        // Update the form's hidden fields with package info
        document.getElementById('lifeplanSelectedPackageName').value = packageName;
        document.getElementById('lifeplanSelectedPackagePrice').value = packagePrice;
        
        // Show lifeplan modal
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
            // Check if the click was directly on the modal background (not its children)
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
        document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
    });

    // Form submission for Lifeplan
    document.getElementById('lifeplanBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        // Add booking submission logic here
        alert('Lifeplan booking submitted successfully!');
        closeAllModals();
    });

    // Process packages from database
    const processedPackages = packagesFromDB.map(pkg => {
        console.log('Processing package:', pkg); // Log each package being processed
        // Determine icon based on package price or name
        let icon = 'leaf'; // default icon
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
    console.log('Processed packages:', processedPackages); // Log the processed packages
    // Initial render
    renderPackages(processedPackages);
});

// Function to render packages
function renderPackages(filteredPackages) {
    console.log('Rendering packages:', filteredPackages); // Log packages being rendered
    const container = document.getElementById('packages-container');
    const noResults = document.getElementById('no-results');
    
    // Check if elements exist
    if (!container || !noResults) {
        console.error('Required DOM elements not found!');
        return;
    }

    container.innerHTML = '';

    if (filteredPackages.length === 0) {
        console.log('No packages to display, showing no-results message');
        noResults.classList.remove('hidden');
        return;
    } else {
        noResults.classList.add('hidden');
    }


    filteredPackages.forEach(pkg => {
        console.log('Creating card for package:', pkg); // Log each package being rendered
        const packageCard = document.createElement('div');
        packageCard.className = 'package-card bg-white rounded-[20px] shadow-lg overflow-hidden';
        packageCard.setAttribute('data-price', pkg.price);
        packageCard.setAttribute('data-service', pkg.service);
        packageCard.setAttribute('data-name', pkg.name);
        packageCard.setAttribute('data-image', pkg.image);
        
        packageCard.innerHTML = `
            <div class="flex flex-col h-full"> <!-- Main flex container -->
                <!-- Image section (unchanged) -->
                <div class="h-48 bg-cover bg-center relative" style="background-image: url('${pkg.image}')">
                    <div class="absolute inset-0 bg-black/40 group-hover:bg-black/30 transition-all duration-300"></div>
                    <div class="absolute top-4 right-4 w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white">
                        <i class="fas fa-${pkg.icon} text-xl"></i>
                    </div>
                </div>
                
                <!-- Content section with consistent sizing -->
                <div class="p-6 flex flex-col flex-grow">
                    <!-- Title (unchanged) -->
                    <h3 class="text-2xl font-hedvig text-navy mb-3">${pkg.name}</h3>
                    
                    <!-- Description with fixed height and line clamping -->
                    <p class="text-dark mb-4 line-clamp-3 h-[72px] overflow-hidden">${pkg.description}</p>
                    
                    <!-- Price with consistent sizing -->
                    <div class="text-3xl font-hedvig text-yellow-600 mb-4 h-12 flex items-center">₱${pkg.price.toLocaleString()}</div>
                    
                    <!-- Features list with scroll if needed -->
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
                    
                    <!-- Button at the bottom -->
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

    let filteredPackages = packagesFromDB.filter(pkg => 
        (searchTerm === '' || pkg.name.toLowerCase().includes(searchTerm) || 
         pkg.description.toLowerCase().includes(searchTerm) ||
         pkg.features.some(f => f.toLowerCase().includes(searchTerm)))
    );

    if (priceSort === 'asc') {
        filteredPackages.sort((a, b) => a.price - b.price);
    } else if (priceSort === 'desc') {
        filteredPackages.sort((a, b) => b.price - a.price);
    }

    renderPackages(filteredPackages);
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('priceSort').value = '';
    renderPackages(packagesFromDB);
}

// Event Listeners
document.getElementById('searchInput').addEventListener('input', filterAndSortPackages);
document.getElementById('priceSort').addEventListener('change', filterAndSortPackages);
document.getElementById('resetFilters').addEventListener('click', resetFilters);

function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
</script>

</body>
</html>