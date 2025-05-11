
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for correct user type based on directory
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
            session_destroy();
            header("Location: ../Landing_Page/login.php");
    }
    exit();
}

// Session timeout (30 minutes)
$session_timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../Landing_Page/login.php?timeout=1");
    exit();
}

$_SESSION['last_activity'] = time();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../addressDB.php';
require_once '../db_connect.php';

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, middle_name, last_name, email, phone_number, birthdate, 
          region, city, province, barangay, street_address, zip_code FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name'];
$last_name = $row['last_name'];
$middle_name = $row['middle_name'];
$email = $row['email'];
$phone_number = $row['phone_number'];
$birthdate = $row['birthdate'];
$region = $row['region'];
$city = $row['city'];
$province = $row['province'];
$barangay = $row['barangay'];
$street_address = $row['street_address'];
$zip_code = $row['zip_code'];

$uploadedImagePath = null;

// Notification count
$notifications_count = [
    'total' => 0,
    'pending' => 0,
    'accepted' => 0,
    'declined' => 0,
    'id_validation' => 0
];

// Life plan bookings
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

    // ID validation status
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

// Fetch uploaded ID image
$query = "SELECT image_path FROM valid_id_tb WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($uploadedImagePath);
$stmt->fetch();
$stmt->close();

// Get regions
$regions = [];
$region_query = "SELECT region_id, region_name FROM table_region";
$region_result = $addressDB->query($region_query);
while ($row = $region_result->fetch_assoc()) {
    $regions[] = $row;
}
$addressDB->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - My Profile</title>
    <?php include 'faviconLogo.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../tailwind.js" defer></script>
    <style>
        :root {
            --primary: #d9a404; /* Warm gold */
            --primary-dark: #b38503;
            --secondary: #2d3748; /* Soft navy */
            --cream: #f9f6f0;
            --gray: #e2e8f0;
            --navbar-height: 4rem;
            --section-spacing: 2rem;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--cream);
            color: var(--secondary);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: var(--secondary);
            height: var(--navbar-height);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sidebar {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .card {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .modal {
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            transition: opacity 0.3s ease;
        }

        .modal-content {
            border-radius: 1rem;
            max-width: 90%;
            width: 600px;
            background-color: white;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--gray);
            color: var(--secondary);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        .input-field {
            border: 1px solid var(--gray);
            border-radius: 0.5rem;
            padding: 0.75rem;
            width: 100%;
            transition: border-color 0.3s ease;
        }

        .input-field:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(217, 164, 4, 0.1);
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--cream);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        /* Accessibility */
        [aria-current="page"] {
            background-color: var(--primary);
            color: white;
        }

        /* Mobile Menu Animation */
        .mobile-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }

        .mobile-menu.open {
            transform: translateX(0);
        }

        /* Tooltip Styling */
        .tooltip {
            position: relative;
        }

        .tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--secondary);
            color: white;
            padding: 0.5rem;
            border-radius: 0.25rem;
            white-space: nowrap;
            z-index: 10;
        }
    </style>
</head>
<body class="antialiased">
    <!-- Navigation Bar -->
    <nav class="navbar fixed top-0 left-0 w-full z-50 flex items-center justify-between px-4 py-3">
        <a href="index.php" class="flex items-center space-x-2">
            <img src="../Landing_Page/Landing_images/logo.png" alt="GrievEase Logo" class="h-10 w-9">
            <span class="text-2xl font-bold text-white">GrievEase</span>
        </a>
        <div class="hidden md:flex items-center space-x-6">
            <a href="index.php" class="text-white hover:text-var(--primary) transition">Home</a>
            <a href="about.php" class="text-white hover:text-var(--primary) transition">About</a>
            <a href="lifeplan.php" class="text-white hover:text-var(--primary) transition">Life Plan</a>
            <a href="traditional_funeral.php" class="text-white hover:text-var(--primary) transition">Traditional Funeral</a>
            <a href="packages.php" class="text-white hover:text-var(--primary) transition">Packages</a>
            <a href="faqs.php" class="text-white hover:text-var(--primary) transition">FAQs</a>
            <a href="notification.php" class="relative text-white hover:text-var(--primary)">
                <i class="fas fa-bell"></i>
                <?php if ($notifications_count['pending'] > 0 || $notifications_count['id_validation'] > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-var(--primary) text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                        <?php echo $notifications_count['pending'] + $notifications_count['id_validation']; ?>
                    </span>
                <?php endif; ?>
            </a>
            <div class="relative group">
                <button class="flex items-center space-x-2 focus:outline-none">
                    <div class="w-8 h-8 rounded-full bg-var(--primary) flex items-center justify-center text-white">
                        <?php echo htmlspecialchars(strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1))); ?>
                    </div>
                    <span class="text-white"><?php echo htmlspecialchars(ucwords($first_name . ' ' . $last_name)); ?></span>
                    <i class="fas fa-chevron-down text-white"></i>
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <div class="p-3 border-b">
                        <p class="text-sm font-medium"><?php echo htmlspecialchars(ucwords($first_name . ' ' . $last_name)); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($email); ?></p>
                    </div>
                    <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                    <a href="../logout.php" class="block px-4 py-2 text-sm text-red-500 hover:bg-gray-100">Sign Out</a>
                </div>
            </div>
        </div>
        <button class="md:hidden text-white focus:outline-none" onclick="toggleMobileMenu()">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </nav>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="mobile-menu hidden md:hidden fixed top-[var(--navbar-height)] left-0 right-0 bg-var(--secondary) z-40 p-4">
        <div class="space-y-2">
            <a href="index.php" class="block text-white py-2 px-4 hover:bg-gray-700 rounded">Home</a>
            <a href="about.php" class="block text-white py-2 px-4 hover:bg-gray-700 rounded">About</a>
            <a href="lifeplan.php" class="block text-white py-2 px-4 hover:bg-gray-700 rounded">Life Plan</a>
            <a href="traditional_funeral.php" class="block text-white py-2 px-4 hover:bg-gray-700 rounded">Traditional Funeral</a>
            <a href="packages.php" class="block text-white py-2 px-4 hover:bg-gray-700 rounded">Packages</a>
            <a href="faqs.php" class="block text-white py-2 px-4 hover:bg-gray-700 rounded">FAQs</a>
            <a href="notification.php" class="block text-white py-2 px-4 hover:bg-gray-700 rounded relative">
                Notifications
                <?php if ($notifications_count['pending'] > 0 || $notifications_count['id_validation'] > 0): ?>
                    <span class="absolute top-2 right-4 bg-var(--primary) text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                        <?php echo $notifications_count['pending'] + $notifications_count['id_validation']; ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="profile.php" class="block text-white py-2 px-4 hover:bg-gray-700 rounded">My Profile</a>
            <a href="../logout.php" class="block text-red-400 py-2 px-4 hover:bg-gray-700 rounded">Sign Out</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="min-h-screen pt-[var(--navbar-height)]">
        <!-- Profile Header -->
        <div class="relative h-48 bg-gradient-to-r from-var(--secondary) to-gray-700">
            <div class="absolute bottom-0 left-0 right-0 p-6 flex items-end">
                <div class="flex items-center space-x-4">
                    <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center text-2xl font-bold text-var(--secondary)">
                        <?php echo htmlspecialchars(strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1))); ?>
                    </div>
                    <div>
                        <h1 class="text-2xl text-white font-bold"><?php echo htmlspecialchars(ucwords($first_name . ' ' . $last_name)); ?></h1>
                        <p class="text-gray-300 text-sm">Member since <?php echo date('F Y', strtotime(isset($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s'))); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sidebar p-4">
                        <h3 class="text-lg font-semibold text-var(--secondary) mb-4">Account</h3>
                        <nav>
                            <ul class="space-y-2">
                                <li>
                                    <a href="#" class="profile-tab flex items-center p-3 rounded-lg text-white bg-var(--primary)" data-tab="personal-info" aria-current="page">
                                        <i class="fas fa-user mr-2"></i> Personal Information
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="profile-tab flex items-center p-3 rounded-lg hover:bg-var(--gray) text-var(--secondary)" data-tab="bookings">
                                        <i class="fas fa-calendar-check mr-2"></i> My Bookings
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="profile-tab flex items-center p-3 rounded-lg hover:bg-var(--gray) text-var(--secondary)" data-tab="transaction-logs">
                                        <i class="fas fa-credit-card mr-2"></i> Payment History
                                    </a>
                                </li>
                                <li>
                                    <a href="../logout.php" class="flex items-center p-3 rounded-lg hover:bg-var(--gray) text-red-500">
                                        <i class="fas fa-sign-out-alt mr-2"></i> Log Out
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <!-- Personal Information -->
                    <div id="personal-info" class="tab-content card p-6 fade-in">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-semibold text-var(--secondary)">Personal Information</h3>
                            <div class="flex space-x-3">
                                <button id="open-change-password-modal" class="btn-secondary flex items-center">
                                    <i class="fas fa-lock mr-2"></i> Change Password
                                </button>
                                <button id="edit-profile-btn" class="btn-primary flex items-center">
                                    <i class="fas fa-edit mr-2"></i> Edit Profile
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Info -->
                            <div class="card p-4">
                                <h4 class="text-lg font-medium mb-4">Basic Information</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm text-gray-500">Full Name</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo htmlspecialchars(ucwords($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name)); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-500">Date of Birth</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo date('F d, Y', strtotime($birthdate)); ?></p>
                                    </div>
                                </div>
                            </div>
                            <!-- Contact Info -->
                            <div class="card p-4">
                                <h4 class="text-lg font-medium mb-4">Contact Information</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm text-gray-500">Email</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo htmlspecialchars($email); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-500">Phone</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo htmlspecialchars($phone_number ?: 'N/A'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <!-- Address Info -->
                            <div class="card p-4 md:col-span-2">
                                <h4 class="text-lg font-medium mb-4">Address</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-500">Region</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo htmlspecialchars($region ?: 'N/A'); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-500">Province</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo htmlspecialchars($province ?: 'N/A'); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-500">City</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo htmlspecialchars($city ?: 'N/A'); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-500">Barangay</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo htmlspecialchars($barangay ?: 'N/A'); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-500">Street Address</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo htmlspecialchars($street_address ?: 'N/A'); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-500">Zip Code</label>
                                        <p class="text-var(--secondary) font-medium"><?php echo htmlspecialchars($zip_code ?: 'N/A'); ?></p>
                                    </div>
                                </div>
                                <?php if ($street_address && $city): ?>
                                    <div class="mt-4">
                                        <label class="block text-sm text-gray-500">Complete Address</label>
                                        <p class="text-var(--secondary) font-medium">
                                            <?php echo htmlspecialchars(implode(', ', array_filter([$street_address, $barangay, $city, $province, $region, $zip_code]))); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Identity Verification -->
                        <div class="mt-6 card p-4">
                            <h4 class="text-lg font-medium mb-4">Identity Verification</h4>
                            <?php if ($uploadedImagePath): ?>
                                <?php
                                $status_query = "SELECT is_validated, decline_reason FROM valid_id_tb WHERE id = ?";
                                $status_stmt = $conn->prepare($status_query);
                                $status_stmt->bind_param("i", $user_id);
                                $status_stmt->execute();
                                $status_result = $status_stmt->get_result();
                                $status_row = $status_result->fetch_assoc();
                                $id_status = $status_row ? $status_row['is_validated'] : 'no';
                                $decline_reason = $status_row ? $status_row['decline_reason'] : '';
                                $status_stmt->close();

                                $statusText = $statusClass = $iconColor = $icon = '';
                                switch ($id_status) {
                                    case 'no':
                                        $statusText = 'Pending';
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        $iconColor = 'text-yellow-500';
                                        $icon = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>';
                                        break;
                                    case 'valid':
                                        $statusText = 'Approved';
                                        $statusClass = 'bg-green-100 text-green-800';
                                        $iconColor = 'text-green-500';
                                        $icon = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';
                                        break;
                                    case 'denied':
                                        $statusText = 'Declined';
                                        $statusClass = 'bg-red-100 text-red-800';
                                        $iconColor = 'text-red-500';
                                        $icon = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
                                        break;
                                }
                                ?>
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium mr-2">Status:</span>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm <?php echo $statusClass; ?>">
                                            <svg class="w-4 h-4 mr-1 <?php echo $iconColor; ?>" fill="currentColor" viewBox="0 0 20 20"><?php echo $icon; ?></svg>
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>
                                    <?php if ($id_status === 'denied' && $decline_reason): ?>
                                        <button class="text-red-600 hover:text-red-800 text-sm flex items-center" onclick="openDeclineReasonModal('<?php echo htmlspecialchars($decline_reason); ?>')">
                                            <i class="fas fa-info-circle mr-1"></i> View Details
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <img src="../Uploads/valid_ids/<?php echo htmlspecialchars($uploadedImagePath); ?>" alt="Uploaded ID" class="w-full h-auto rounded-lg cursor-pointer" onclick="openImageModal('../Uploads/valid_ids/<?php echo htmlspecialchars($uploadedImagePath); ?>')" loading="lazy">
                            <?php else: ?>
                                <div class="text-center p-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50" onclick="openEditProfileToIDUpload()">
                                    <i class="fas fa-id-card text-4xl text-var(--primary) mb-2"></i>
                                    <p class="text-var(--secondary) font-medium">No ID uploaded</p>
                                    <p class="text-sm text-gray-500">Upload a valid ID to verify your account</p>
                                    <button class="btn-primary mt-4">Upload ID</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="edit-profile-modal" class="modal fixed inset-0 hidden items-center justify-center z-50">
        <div class="modal-content p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-var(--secondary)">Edit Profile</h3>
                <button id="close-edit-profile-modal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="profile-form" action="update_profile.php" method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="firstName" class="block text-sm font-medium">First Name</label>
                        <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($first_name); ?>" class="input-field" required>
                    </div>
                    <div>
                        <label for="lastName" class="block text-sm font-medium">Last Name</label>
                        <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($last_name); ?>" class="input-field" required>
                    </div>
                    <div>
                        <label for="middleName" class="block text-sm font-medium">Middle Name</label>
                        <input type="text" id="middleName" name="middleName" value="<?php echo htmlspecialchars($middle_name); ?>" class="input-field">
                    </div>
                    <div>
                        <label for="dob" class="block text-sm font-medium">Date of Birth</label>
                        <input type="date" id="dob" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>" class="input-field">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium">Phone Number</label>
                        <input type="tel" id="phone" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" class="input-field" required>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="input-field" readonly>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="region" class="block text-sm font-medium">Region</label>
                    <select id="region" name="region" class="input-field" required onchange="updateProvinces()">
                        <option value="" disabled>Select Region</option>
                        <?php foreach ($regions as $reg): ?>
                            <option value="<?php echo $reg['region_id']; ?>" <?php echo $region == $reg['region_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($reg['region_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="province" class="block text-sm font-medium">Province</label>
                        <select id="province" name="province" class="input-field" required onchange="updateCities()">
                            <option value="" disabled selected>Select Province</option>
                        </select>
                    </div>
                    <div>
                        <label for="city" class="block text-sm font-medium">City</label>
                        <select id="city" name="city" class="input-field" required onchange="updateBarangays()">
                            <option value="" disabled selected>Select City</option>
                        </select>
                    </div>
                    <div>
                        <label for="barangay" class="block text-sm font-medium">Barangay</label>
                        <select id="barangay" name="barangay" class="input-field" required>
                            <option value="" disabled selected>Select Barangay</option>
                        </select>
                    </div>
                    <div>
                        <label for="street_address" class="block text-sm font-medium">Street Address</label>
                        <input type="text" id="street_address" name="street_address" value="<?php echo htmlspecialchars($street_address); ?>" class="input-field" required>
                    </div>
                    <div>
                        <label for="zip" class="block text-sm font-medium">Zip Code</label>
                        <input type="text" id="zip" name="zip_code" value="<?php echo htmlspecialchars($zip_code); ?>" class="input-field" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="id-upload" class="block text-sm font-medium">Upload ID</label>
                    <input type="file" id="id-upload" name="id_upload" accept="image/jpeg,image/png" class="input-field">
                    <div id="image-preview-container" class="mt-2 h-32 bg-gray-100 rounded-lg flex items-center justify-center">
                        <p class="text-gray-500 text-sm">Preview will appear here</p>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancel-edit-profile" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="change-password-modal" class="modal fixed inset-0 hidden items-center justify-center z-50">
        <div class="modal-content p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-var(--secondary)">Change Password</h3>
                <button id="close-change-password-modal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="password-form" action="change_password.php" method="POST">
                <div class="mb-4">
                    <label for="current-password" class="block text-sm font-medium">Current Password</label>
                    <div class="relative">
                        <input type="password" id="current-password" name="current_password" class="input-field" required>
                        <button type="button" class="password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500" data-target="current-password">
                            <i class="fas fa-eye eye-show"></i>
                            <i class="fas fa-eye-slash eye-hide hidden"></i>
                        </button>
                    </div>
                    <p id="current-password-error" class="mt-1 text-sm text-red-600 hidden"></p>
                </div>
                <div class="mb-4">
                    <label for="new-password" class="block text-sm font-medium">New Password</label>
                    <div class="relative">
                        <input type="password" id="new-password" name="new_password" class="input-field" required>
                        <button type="button" class="password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500" data-target="new-password">
                            <i class="fas fa-eye eye-show"></i>
                            <i class="fas fa-eye-slash eye-hide hidden"></i>
                        </button>
                    </div>
                    <p id="new-password-error" class="mt-1 text-sm text-red-600 hidden"></p>
                    <ul class="mt-2 text-sm text-gray-500 space-y-1">
                        <li id="length-check">At least 8 characters</li>
                        <li id="uppercase-check">At least one uppercase letter</li>
                        <li id="lowercase-check">At least one lowercase letter</li>
                        <li id="number-check">At least one number</li>
                        <li id="special-check">At least one special character</li>
                    </ul>
                </div>
                <div class="mb-4">
                    <label for="confirm-password" class="block text-sm font-medium">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="confirm-password" name="confirm_password" class="input-field" required>
                        <button type="button" class="password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500" data-target="confirm-password">
                            <i class="fas fa-eye eye-show"></i>
                            <i class="fas fa-eye-slash eye-hide hidden"></i>
                        </button>
                    </div>
                    <p id="confirm-password-error" class="mt-1 text-sm text-red-600 hidden"></p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancel-change-password" class="btn-secondary">Cancel</button>
                    <button type="button" id="submit-change-password" class="btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
            menu.classList.toggle('open');
        }

        // Tab Switching
        document.querySelectorAll('.profile-tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
                document.querySelectorAll('.profile-tab').forEach(t => t.removeAttribute('aria-current'));
                document.getElementById(this.dataset.tab).classList.remove('hidden');
                this.setAttribute('aria-current', 'page');
            });
        });

        // Modal Functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            modal.querySelector('.modal-content').classList.add('fade-in');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.querySelector('.modal-content').classList.remove('fade-in');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        // Edit Profile Modal
        document.getElementById('edit-profile-btn').addEventListener('click', () => openModal('edit-profile-modal'));
        document.getElementById('close-edit-profile-modal').addEventListener('click', () => closeModal('edit-profile-modal'));
        document.getElementById('cancel-edit-profile').addEventListener('click', () => closeModal('edit-profile-modal'));

        // Change Password Modal
        document.getElementById('open-change-password-modal').addEventListener('click', () => openModal('change-password-modal'));
        document.getElementById('close-change-password-modal').addEventListener('click', () => closeModal('change-password-modal'));
        document.getElementById('cancel-change-password').addEventListener('click', () => closeModal('change-password-modal'));

        // Password Toggle
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = document.getElementById(this.dataset.target);
                const show = this.querySelector('.eye-show');
                const hide = this.querySelector('.eye-hide');
                input.type = input.type === 'password' ? 'text' : 'password';
                show.classList.toggle('hidden');
                hide.classList.toggle('hidden');
            });
        });

        // Form Validation (Simplified for brevity)
        function validateForm() {
            let isValid = true;
            ['firstName', 'lastName', 'phone', 'region', 'province', 'city', 'barangay', 'street_address', 'zip'].forEach(id => {
                const field = document.getElementById(id);
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    const error = document.createElement('p');
                    error.className = 'text-sm text-red-600 mt-1';
                    error.textContent = `${id.replace('_', ' ')} is required`;
                    field.parentNode.appendChild(error);
                } else {
                    field.classList.remove('border-red-500');
                    const error = field.parentNode.querySelector('.text-red-600');
                    if (error) error.remove();
                }
            });
            return isValid;
        }

        document.getElementById('profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateForm()) {
                this.submit();
            }
        });

        // Image Preview
        document.getElementById('id-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('image-preview-container');
            preview.innerHTML = '';
            if (file && ['image/jpeg', 'image/png'].includes(file.type) && file.size <= 5 * 1024 * 1024) {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'max-h-full max-w-full object-contain';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '<p class="text-red-600 text-sm">Please upload a JPG/PNG image (max 5MB)</p>';
            }
        });

        // Address Dropdowns (Placeholder for AJAX)
        function updateProvinces() {
            // Implement AJAX call to fetch provinces
        }
        function updateCities() {
            // Implement AJAX call to fetch cities
        }
        function updateBarangays() {
            // Implement AJAX call to fetch barangays
        }
    </script>
</body>
</html>
