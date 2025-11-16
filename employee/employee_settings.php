<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for employee user type (user_type = 2)
if ($_SESSION['user_type'] != 2) {
    switch ($_SESSION['user_type']) {
        case 1: // Admin
            header("Location: ../admin/admin_index.php");
            break;
        case 3: // Customer
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

// Update last activity time
$_SESSION['last_activity'] = time();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../db_connect.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Get employee data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// Extract name variables for sidebar
$first_name = $employee['first_name'];
$last_name = $employee['last_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../profile_picture/';
            $file_name = 'emp_' . $user_id . '_' . time() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $destination = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                // Update database
                $update_query = "UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $file_name, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Profile picture updated successfully!";
                    $employee['profile_picture'] = $file_name;
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $_SESSION['error_message'] = "Failed to update profile picture in database.";
                }
            } else {
                $_SESSION['error_message'] = "Failed to upload file.";
            }
        } else {
            $_SESSION['error_message'] = "Only JPG, PNG, and GIF files are allowed.";
        }
    }
    
    // Handle personal details update
    if (isset($_POST['update_personal'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $middle_name = trim($_POST['middle_name']);
        $suffix = trim($_POST['suffix']);
        $birthdate = $_POST['birthdate'];
        $phone_number = $_POST['phone_number'];
        $email = $_POST['email'];
        
        // Validate name inputs
        $name_pattern = "/^[a-zA-Z\s'-]+$/"; // Allows letters, spaces, hyphens, and apostrophes
        $errors = [];
        
        if (!preg_match($name_pattern, $first_name) || empty($first_name)) {
            $errors[] = "First name can only contain letters, spaces, hyphens, or apostrophes.";
        }
        if (!preg_match($name_pattern, $last_name) || empty($last_name)) {
            $errors[] = "Last name can only contain letters, spaces, hyphens, or apostrophes.";
        }
        if (!empty($middle_name) && !preg_match($name_pattern, $middle_name)) {
            $errors[] = "Middle name can only contain letters, spaces, hyphens, or apostrophes.";
        }
        if (!empty($suffix) && !preg_match($name_pattern, $suffix)) {
            $errors[] = "Suffix can only contain letters, spaces, hyphens, or apostrophes.";
        }
        
        // Validate phone number
        if (!preg_match("/^\+63[0-9]{10}$|^[0-9]{11}$/", $phone_number)) {
            $errors[] = "Phone number must be exactly 11 digits, or 13 characters starting with +63 followed by 10 digits.";
        }
        
        // Check if email exists (excluding current user)
        $email_check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $email_stmt = $conn->prepare($email_check_query);
        $email_stmt->bind_param("si", $email, $user_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        
        if ($email_result->num_rows > 0 && $email !== $employee['email']) {
            $errors[] = "Email already exists in the database.";
        }
        
        // Check if phone number exists (excluding current user)
        $phone_check_query = "SELECT id FROM users WHERE phone_number = ? AND id != ?";
        $phone_stmt = $conn->prepare($phone_check_query);
        $phone_stmt->bind_param("si", $phone_number, $user_id);
        $phone_stmt->execute();
        $phone_result = $phone_stmt->get_result();
        
        if ($phone_result->num_rows > 0 && $phone_number !== $employee['phone_number']) {
            $errors[] = "Phone number already exists in the database.";
        }
        
        if (empty($errors)) {
            $update_query = "UPDATE users SET 
                            first_name = ?, 
                            last_name = ?, 
                            middle_name = ?, 
                            suffix = ?, 
                            birthdate = ?, 
                            phone_number = ?, 
                            email = ?,
                            updated_at = NOW()
                            WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssssssi", $first_name, $last_name, $middle_name, $suffix, $birthdate, $phone_number, $email, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Personal details updated successfully!";
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['error_message'] = "Error updating personal details: " . $conn->error;
            }
        } else {
            $_SESSION['error_message'] = implode(" ", $errors);
        }
    }
    
    // Handle password change
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $employee['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Password updated successfully!";
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $_SESSION['error_message'] = "Error updating password: " . $conn->error;
                }
            } else {
                $_SESSION['error_message'] = "New passwords do not match!";
            }
        } else {
            $_SESSION['error_message'] = "Current password is incorrect!";
        }
    }
}

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Settings - GrievEase</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="tailwind.js"></script>
    <?php include 'faviconLogo.php'; ?>
    <style>
        #suffix-suggestions {
            display: none;
            position: absolute;
            z-index: 50;
            width: 100%;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            margin-top: 0.25rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            max-height: 150px;
            overflow-y: auto;
        }
        
        #suffix-suggestions div {
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        #suffix-suggestions div:hover {
            background-color: #f3f4f6;
        }
        
        #suffix-suggestions .bg-sidebar-bg {
            background-color: #f3f4f6;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            #main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .w-\[calc\(100\%-16rem\)\] {
                width: 100% !important;
            }
            
            /* Mobile-friendly touch targets */
            .mobile-touch-target {
                min-height: 44px;
                min-width: 44px;
            }
            
            /* Mobile table scrolling */
            .mobile-table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Mobile modal adjustments */
            .mobile-modal {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
            }
            
            /* Hide desktop hamburger on mobile */
            #hamburger-menu {
                display: none !important;
            }
            
            /* Show mobile hamburger */
            #mobile-hamburger {
                display: block !important;
            }
            
            /* Sidebar positioning for mobile */
            #sidebar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                height: 100vh !important;
                z-index: 50 !important;
                transform: translateX(-100%) !important;
                transition: transform 0.3s ease-in-out !important;
            }
            
            #sidebar.translate-x-0 {
                transform: translateX(0) !important;
            }
            
            #sidebar.-translate-x-full {
                transform: translateX(-100%) !important;
            }
            
            /* Mobile overlay */
            #mobile-overlay {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                background: rgba(0, 0, 0, 0.5) !important;
                z-index: 40 !important;
            }
            
            /* Ensure main content is below sidebar on mobile */
            .main-content {
                position: relative !important;
                z-index: 1 !important;
            }
            
            /* Show sidebar text when sidebar is open on mobile */
            #sidebar.translate-x-0 .sidebar-link span {
                display: inline !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            #sidebar.translate-x-0 .menu-header {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            #sidebar.translate-x-0 #sidebar > div:first-child > * {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            /* Ensure sidebar links have proper spacing on mobile when open */
            #sidebar.translate-x-0 .sidebar-link {
                justify-content: flex-start !important;
                padding-left: 1.25rem !important;
                padding-right: 1.25rem !important;
            }
            
            #sidebar.translate-x-0 .sidebar-link i {
                margin-right: 0.75rem !important;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            /* Tablet adjustments */
            .main-content {
                margin-left: 14rem;
                width: calc(100% - 14rem);
            }
        }
    </style>
</head>
<body class="flex bg-navy font-inter">
  <?php include 'employee_sidebar.php'; ?>
  
  <!-- Mobile Overlay -->
  <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

  <div id="main-content" class="p-4 sm:p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
        <!-- Mobile Hamburger Menu -->
        <button id="mobile-hamburger" class="lg:hidden p-3 bg-sidebar-bg rounded-xl shadow-card text-sidebar-text hover:text-sidebar-accent hover:bg-sidebar-hover transition-all duration-300 mb-4 sm:mb-6">
            <i class="fas fa-bars text-lg"></i>
        </button>
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 bg-white p-4 sm:p-5 rounded-lg shadow-sidebar">
            <div class="w-full sm:w-auto">
                <h1 class="text-xl sm:text-2xl font-bold text-sidebar-text">Employee Settings</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Manage your personal information and account settings</p>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="bg-green-50 border-l-4 border-success text-green-800 px-4 sm:px-6 py-3 sm:py-4 rounded-lg relative mb-4 sm:mb-6 shadow-input" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-success mr-3"></i>
                    <span class="font-medium text-sm sm:text-base"><?php echo $success_message; ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="bg-red-50 border-l-4 border-error text-red-800 px-4 sm:px-6 py-3 sm:py-4 rounded-lg relative mb-4 sm:mb-6 shadow-input" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-error mr-3"></i>
                    <span class="font-medium text-sm sm:text-base"><?php echo $error_message; ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Profile Picture Section -->
            <div class="bg-white rounded-xl shadow-sidebar border border-sidebar-border p-4 sm:p-6 col-span-1 lg:col-span-1">
                <div class="flex items-center mb-4 sm:mb-6">
                    <i class="fas fa-camera text-sidebar-accent mr-3 text-lg sm:text-xl"></i>
                    <h2 class="text-lg sm:text-xl font-semibold text-sidebar-text">Profile Picture</h2>
                </div>
                <div class="flex flex-col items-center">
                    <?php if (!empty($employee['profile_picture'])): ?>
                        <img id="profile-preview" 
                             src="../profile_picture/<?php echo $employee['profile_picture']; ?>" 
                             alt="Profile" class="w-32 h-32 sm:w-40 sm:h-40 rounded-full object-cover border-4 border-sidebar-border shadow-card transition-all duration-300 group-hover:shadow-lg">
                    <?php else: ?>
                        <div id="profile-preview" class="w-32 h-32 sm:w-40 sm:h-40 rounded-full bg-sidebar-accent flex items-center justify-center border-4 border-sidebar-border shadow-card transition-all duration-300 group-hover:shadow-lg">
                            <span class="text-white text-xl sm:text-2xl font-bold">
                                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" class="w-full">
                        <div class="mb-6">
                            <label class="block text-primary-foreground font-medium mb-3 font-inter" for="profile_picture">Upload New Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" 
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-secondary file:text-secondary-foreground hover:file:bg-sidebar-hover"
                                   accept="image/*">
                        </div>
                        <button type="submit" id="update-picture-btn"
                                class="w-full bg-sidebar-accent hover:bg-darkgold text-white py-3 px-6 rounded-xl transition-all duration-300 font-medium shadow-input hover:shadow-card transform hover:-translate-y-0.5 opacity-50 cursor-not-allowed"
                                disabled>
                            <i class="fas fa-upload mr-2"></i>
                            Update Picture
                        </button>
                    </form>
                    
                    <?php if (!empty($employee['profile_picture'])): ?>
                        <div class="mt-4 w-full">
                            <button type="button" id="remove-profile-picture" 
                                    class="w-full bg-red-500 hover:bg-red-600 text-white py-3 px-6 rounded-xl transition-all duration-300 font-medium shadow-input hover:shadow-card transform hover:-translate-y-0.5">
                                <i class="fas fa-trash-alt mr-2"></i>
                                Remove Profile Picture
                            </button>
                        </div>
                    <?php endif; ?>
                    <p class="text-dark text-sm mt-4 text-center font-inter">JPG, PNG or GIF. Max size 2MB</p>
                </div>
            </div>
            
            <!-- Personal Details Section -->
            <div class="bg-white rounded-xl shadow-sidebar border border-sidebar-border p-4 sm:p-6 col-span-1 lg:col-span-2">
                <div class="flex items-center mb-4 sm:mb-6">
                    <i class="fas fa-user-edit text-sidebar-accent mr-3 text-lg sm:text-xl"></i>
                    <h2 class="text-lg sm:text-xl font-semibold text-sidebar-text">Personal Details</h2>
                </div>
                <form method="post" id="personal-details-form">
                    <input type="hidden" name="update_personal" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="first_name" class="block text-primary-foreground font-medium font-inter">First Name</label>
                            <input type="text" id="first_name" name="first_name" required
                                   pattern="[a-zA-Z\s'-]+"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($employee['first_name']); ?>">
                            <p id="first_name_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="last_name" class="block text-primary-foreground font-medium font-inter">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required
                                   pattern="[a-zA-Z\s'-]+"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($employee['last_name']); ?>">
                            <p id="last_name_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="middle_name" class="block text-primary-foreground font-medium font-inter">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name"
                                   pattern="[a-zA-Z\s'-]*"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($employee['middle_name']); ?>">
                            <p id="middle_name_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="suffix" class="block text-primary-foreground font-medium font-inter">Suffix</label>
                            <div class="relative">
                                <input type="text" id="suffix" name="suffix"
                                       pattern="[a-zA-Z\s'-]*"
                                       class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                       value="<?php echo htmlspecialchars($employee['suffix']); ?>"
                                       autocomplete="off">
                                <div id="suffix-suggestions" class="absolute z-10 w-full bg-white border border-input-border rounded-xl mt-1 shadow-lg hidden max-h-60 overflow-y-auto"></div>
                            </div>
                            <p id="suffix_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="birthdate" class="block text-primary-foreground font-medium font-inter">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" required
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($employee['birthdate']); ?>">
                            <p id="birthdate_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="phone_number" class="block text-primary-foreground font-medium font-inter">Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" required
                                   pattern="(\+63[0-9]{10}|[0-9]{11})"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($employee['phone_number']); ?>">
                            <p id="phone_number_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="md:col-span-2 space-y-2">
                            <label for="email" class="block text-primary-foreground font-medium font-inter">Email</label>
                            <input type="email" id="email" name="email" required
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($employee['email']); ?>">
                            <p id="email_error" class="text-error text-sm hidden"></p>
                        </div>
                    </div>
                    <div class="mt-8">
                        <button type="submit" 
                                class="bg-sidebar-accent hover:bg-darkgold text-white py-3 px-8 rounded-xl transition-all duration-300 font-medium shadow-input hover:shadow-card transform hover:-translate-y-0.5">
                            <i class="fas fa-save mr-2"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Change Password Section -->
            <div class="bg-white rounded-xl shadow-sidebar border border-sidebar-border p-4 sm:p-6 col-span-1 lg:col-span-3">
                <div class="flex items-center mb-4 sm:mb-6">
                    <i class="fas fa-lock text-sidebar-accent mr-3 text-lg sm:text-xl"></i>
                    <h2 class="text-lg sm:text-xl font-semibold text-sidebar-text">Change Password</h2>
                </div>
                <form method="post" id="password-form">
                    <input type="hidden" name="update_password" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label for="current_password" class="block text-sm sm:text-base font-medium text-gray-700">Current Password</label>
                            <div class="relative">
                                <input type="password" id="current_password" name="current_password" required
                                       class="w-full px-4 py-3 pr-12 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary">
                                <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-dark hover:text-sidebar-accent transition-colors duration-300" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="new_password" class="block text-sm sm:text-base font-medium text-gray-700">New Password</label>
                            <div class="relative">
                                <input type="password" id="new_password" name="new_password" required minlength="6"
                                       class="w-full px-4 py-3 pr-12 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary">
                                <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-dark hover:text-sidebar-accent transition-colors duration-300" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="confirm_password" class="block text-sm sm:text-base font-medium text-gray-700">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                       class="w-full px-4 py-3 pr-12 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary">
                                <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-dark hover:text-sidebar-accent transition-colors duration-300" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password_icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8">
                        <button type="submit" 
                                class="bg-sidebar-accent hover:bg-darkgold text-white py-3 px-8 rounded-xl transition-all duration-300 font-medium shadow-input hover:shadow-card transform hover:-translate-y-0.5">
                            <i class="fas fa-key mr-2"></i>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

  <script src="sidebar.js"></script>
    <script>
        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('profile-preview');
                    if (preview.tagName === 'IMG') {
                        preview.src = event.target.result;
                    } else {
                        // Replace the div with an img element
                        const newPreview = document.createElement('img');
                        newPreview.id = 'profile-preview';
                        newPreview.src = event.target.result;
                        newPreview.alt = 'Profile Preview';
                        newPreview.className = 'w-40 h-40 rounded-full object-cover border-4 border-border shadow-card transition-all duration-300 group-hover:shadow-lg';
                        preview.parentNode.replaceChild(newPreview, preview);
                    }
                }
                reader.readAsDataURL(file);
            }
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Real-time validation
        const namePattern = /^[a-zA-Z\s'-]+$/;
        const phonePattern = /^\+63[0-9]{10}$|^[0-9]{11}$/;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const originalEmail = '<?php echo htmlspecialchars($employee['email']); ?>';
        const originalPhone = '<?php echo htmlspecialchars($employee['phone_number']); ?>';
        const originalValues = {
            first_name: '<?php echo htmlspecialchars($employee['first_name']); ?>',
            last_name: '<?php echo htmlspecialchars($employee['last_name']); ?>',
            middle_name: '<?php echo htmlspecialchars($employee['middle_name']); ?>',
            suffix: '<?php echo htmlspecialchars($employee['suffix']); ?>',
            birthdate: '<?php echo htmlspecialchars($employee['birthdate']); ?>',
            phone_number: '<?php echo htmlspecialchars($employee['phone_number']); ?>',
            email: '<?php echo htmlspecialchars($employee['email']); ?>'
        };
        const submitButton = document.querySelector('#personal-details-form button[type="submit"]');
        
        function updateSubmitButtonState() {
            const hasError = ['first_name_error', 'last_name_error', 'middle_name_error', 'suffix_error', 
                             'phone_number_error', 'email_error'].some(id => 
                             !document.getElementById(id).classList.contains('hidden'));
            const hasChanges = ['first_name', 'last_name', 'middle_name', 'suffix', 'birthdate', 'phone_number', 'email'].some(field => 
                document.getElementById(field).value.trim() !== originalValues[field]);
            
            submitButton.disabled = hasError || !hasChanges;
            submitButton.classList.toggle('opacity-50', hasError || !hasChanges);
            submitButton.classList.toggle('cursor-not-allowed', hasError || !hasChanges);
        }
        
        // Function to show/hide error messages
        function showError(fieldId, message) {
            const errorElement = document.getElementById(`${fieldId}_error`);
            if (message) {
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
            } else {
                errorElement.textContent = '';
                errorElement.classList.add('hidden');
            }
            updateSubmitButtonState();
        }
        
        // --- START: NEW INPUT VALIDATION FUNCTIONS ---
        
        /**
         * Cleans name input: no numbers, no consecutive spaces after 2 characters.
         * @param {Event} e - The input event.
         */
        function validateNameInput(e) {
            let input = e.target;
            let value = input.value;
            let cursorPosition = input.selectionStart;

            // 1. No numbers allowed
            let sanitized = value.replace(/[0-9]/g, '');

            // 2. No consecutive spaces allowed unless there is a 2 character input already
            // This regex specifically targets '  ' (two or more spaces)
            if (sanitized.length > 2) {
                const originalLength = sanitized.length;
                sanitized = sanitized.replace(/ {2,}/g, ' ');

                // Adjust cursor position if characters were removed before it
                const diff = originalLength - sanitized.length;
                if (diff > 0 && cursorPosition > 0) {
                    // Try to guess where the cursor should be
                    const beforeCursor = value.substring(0, cursorPosition);
                    const newBeforeCursor = beforeCursor.replace(/[0-9]| {2,}/g, (match) => {
                        // If it's a number, it's removed
                        if (/[0-9]/.test(match)) return '';
                        // If it's multiple spaces, replace with one space
                        if (/ {2,}/.test(match)) return ' ';
                        return match;
                    });
                    cursorPosition = newBeforeCursor.length;
                }
            }
            
            if (value !== sanitized) {
                input.value = sanitized;
                input.setSelectionRange(cursorPosition, cursorPosition);
            }

            // Client-side visual validation
            const field = input.id;
            const cleanValue = input.value.trim();

            if ((field === 'first_name' || field === 'last_name') && !cleanValue) {
                showError(field, 'This field is required');
            } else if (cleanValue && !namePattern.test(cleanValue)) {
                showError(field, 'Can only contain letters, spaces, hyphens, or apostrophes.');
            } else {
                showError(field, '');
            }
        }
        
        /**
         * Cleans password input: no spaces allowed.
         * @param {Event} e - The input event.
         */
        function validatePasswordInput(e) {
            let input = e.target;
            let value = input.value;
            let sanitized = value.replace(/\s/g, ''); // Remove all spaces
            
            if (value !== sanitized) {
                input.value = sanitized;
            }
        }
        
        /**
         * Cleans phone number input: only numbers and optional leading '+'. Enforces PH number format (09...).
         * @param {Event} e - The input event.
         */
        function validatePhoneNumberInput(e) {
            let input = e.target;
            let value = input.value;
            let sanitized = value.replace(/[^0-9+]/g, ''); // Allow only numbers and '+'

            // Further restrict to common PH formats: 09... or +639...
            if (sanitized.startsWith('+') && sanitized.length > 1) {
                sanitized = '+' + sanitized.substring(1).replace(/\+/g, '');
            } else {
                sanitized = sanitized.replace(/\+/g, '');
            }
            
            if (value !== sanitized) {
                input.value = sanitized;
            }

            // Client-side visual validation
            const cleanValue = input.value.trim();
            if (!cleanValue) {
                showError('phone_number', 'Phone number is required');
                return;
            }
            if (!phonePattern.test(cleanValue)) {
                showError('phone_number', 'Phone number must be 11 digits (09xxxxxxxxx) or 13 characters (+639xxxxxxxxx).');
            } else {
                // If the pattern is met, proceed to check_credentials (existing logic)
            }
            // The existing `phoneInput.addEventListener('input', async function() { ... })` handles the DB check
        }
        
        /**
         * Cleans email input: no consecutive multiple spaces allowed.
         * @param {Event} e - The input event.
         */
        function validateEmailInput(e) {
            let input = e.target;
            let value = input.value;
            let sanitized = value.replace(/ {2,}/g, ' '); // Replace 2 or more consecutive spaces with a single space
            
            if (value !== sanitized) {
                input.value = sanitized;
            }

            // Client-side visual validation
            const cleanValue = input.value.trim();
            if (!cleanValue) {
                showError('email', 'Email is required');
                return;
            }
            if (!emailPattern.test(cleanValue)) {
                showError('email', 'Please enter a valid email address');
            } else {
                // If the pattern is met, proceed to check_credentials (existing logic)
            }
            // The existing `emailInput.addEventListener('input', async function() { ... })` handles the DB check
        }
        
        // Apply new input listeners
        ['first_name', 'last_name', 'middle_name', 'suffix'].forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.addEventListener('input', validateNameInput);
            }
        });
        
        ['current_password', 'new_password', 'confirm_password'].forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.addEventListener('input', validatePasswordInput);
            }
        });

        const phoneInput = document.getElementById('phone_number');
        if (phoneInput) {
            phoneInput.addEventListener('input', validatePhoneNumberInput);
        }
        
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('input', validateEmailInput);
        }

        // --- END: NEW INPUT VALIDATION FUNCTIONS ---

        // Name validation (Existing logic for the server-side patterns/requirements)
        ['first_name', 'last_name', 'middle_name', 'suffix'].forEach(field => {
            const input = document.getElementById(field);
            // The logic below is still needed to perform the required *visual* validation (showError)
            // even if `validateNameInput` handles the *cleaning* of the input in real-time.
            input.addEventListener('input', function() {
                const value = this.value.trim();
                if ((field === 'first_name' || field === 'last_name') && !value) {
                    showError(field, 'This field is required');
                } else if (value && !namePattern.test(value)) {
                    showError(field, 'Can only contain letters, spaces, hyphens, or apostrophes');
                } else {
                    showError(field, '');
                }
            });
        });
        
        // Phone number validation (Existing logic for DB check)
        
        phoneInput.addEventListener('input', async function() {
            const value = this.value.trim();
            if (!value) {
                showError('phone_number', 'Phone number is required');
                return;
            }
            if (!phonePattern.test(value)) {
                showError('phone_number', 'Phone number must be 11 digits, or 13 characters starting with +63');
                return;
            }
            
            if (value !== originalPhone) {
                try {
                    const response = await fetch('settings/check_credentials.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `phone_number=${encodeURIComponent(value)}&user_id=${<?php echo $user_id; ?>}`
                    });
                    const data = await response.json();
                    if (data.exists) {
                        showError('phone_number', 'Phone number already exists');
                    } else {
                        showError('phone_number', '');
                    }
                } catch (error) {
                    showError('phone_number', 'Error checking phone number');
                }
            } else {
                showError('phone_number', '');
            }
        });
        
        // Email validation (Existing logic for DB check)
        
        emailInput.addEventListener('input', async function() {
            const value = this.value.trim();
            if (!value) {
                showError('email', 'Email is required');
                return;
            }
            if (!emailPattern.test(value)) {
                showError('email', 'Please enter a valid email address');
                return;
            }
            
            if (value !== originalEmail) {
                try {
                    const response = await fetch('settings/check_credentials.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `email=${encodeURIComponent(value)}&user_id=${<?php echo $user_id; ?>}`
                    });
                    const data = await response.json();
                    if (data.exists) {
                        showError('email', 'Email already exists');
                    } else {
                        showError('email', '');
                    }
                } catch (error) {
                    showError('email', 'Error checking email');
                }
            } else {
                showError('email', '');
            }
        });
        
        // Form submission validation (Existing logic)
        document.getElementById('personal-details-form').addEventListener('submit', function(e) {
            let hasError = false;
            let hasChanges = false;
            
            ['first_name', 'last_name', 'middle_name', 'suffix', 'birthdate'].forEach(field => {
                const input = document.getElementById(field);
                const value = input.value.trim();
                if ((field === 'first_name' || field === 'last_name') && !value) {
                    showError(field, 'This field is required');
                    hasError = true;
                } else if ((field === 'middle_name' || field === 'suffix') && value && !namePattern.test(value)) {
                    showError(field, 'Can only contain letters, spaces, hyphens, or apostrophes');
                    hasError = true;
                }
                if (value !== originalValues[field]) {
                    hasChanges = true;
                }
            });
            
            const phone = phoneInput.value.trim();
            if (!phone) {
                showError('phone_number', 'Phone number is required');
                hasError = true;
            } else if (!phonePattern.test(phone)) {
                showError('phone_number', 'Phone number must be 11 digits, or 13 characters starting with +63');
                hasError = true;
            }
            if (phone !== originalValues.phone_number) {
                hasChanges = true;
            }
            
            const email = emailInput.value.trim();
            if (!email) {
                showError('email', 'Email is required');
                hasError = true;
            } else if (!emailPattern.test(email)) {
                showError('email', 'Please enter a valid email address');
                hasError = true;
            }
            if (email !== originalValues.email) {
                hasChanges = true;
            }
            
            if (!hasChanges) {
                e.preventDefault();
                const messageDiv = document.createElement('div');
                messageDiv.className = 'bg-yellow-50 border-l-4 border-yellow-600 text-yellow-800 px-6 py-4 rounded-lg relative mb-6 shadow-input';
                messageDiv.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-yellow-600 mr-3"></i>
                        <span class="font-medium">No changes were made to the form.</span>
                    </div>
                `;
                const mainContent = document.getElementById('main-content');
                const existingMessages = mainContent.querySelectorAll('.bg-yellow-50');
                existingMessages.forEach(msg => msg.remove());
                mainContent.insertBefore(messageDiv, mainContent.children[2]);
                setTimeout(() => messageDiv.remove(), 3000);
            }
            
            if (hasError) {
                e.preventDefault();
            }
        });

        // Password match validation (Existing logic)
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
        
        // Sidebar toggle for mobile (Existing logic)
        document.getElementById('mobile-hamburger').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        });
        
        // Add subtle animations on form focus (Existing logic)
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.classList.add('transform', 'scale-[1.02]');
            });
            
            input.addEventListener('blur', function() {
                this.classList.remove('transform', 'scale-[1.02]');
            });
        });
        
        // Suffix suggestions functionality (Existing logic)
        const suffixOptions = ["Jr", "Sr", "II", "III", "IV"];
        
        function setupSuffixTypeahead() {
            const suffixInput = document.getElementById('suffix');
            const suggestionsContainer = document.getElementById('suffix-suggestions');
            
            if (!suffixInput || !suggestionsContainer) {
                console.error('Could not find suffix input or suggestions container');
                return;
            }
            
            suffixInput.addEventListener('input', function() {
                const value = this.value.trim();
                suggestionsContainer.innerHTML = '';
                
                if (value === '') {
                    suggestionsContainer.style.display = 'none';
                    return;
                }
                
                const filteredOptions = suffixOptions.filter(option => 
                    option.toLowerCase().includes(value.toLowerCase())
                );
                
                if (filteredOptions.length === 0) {
                    suggestionsContainer.style.display = 'none';
                    return;
                }
                
                filteredOptions.forEach(option => {
                    const div = document.createElement('div');
                    div.className = 'px-4 py-2 cursor-pointer hover:bg-sidebar-bg transition-colors duration-200';
                    div.textContent = option;
                    div.addEventListener('click', () => {
                        suffixInput.value = option;
                        suggestionsContainer.style.display = 'none';
                        validateNameInput({ target: suffixInput }); // Use the new validator
                    });
                    suggestionsContainer.appendChild(div);
                });
                
                suggestionsContainer.style.display = 'block';
            });
            
            // Hide suggestions when clicking outside
            document.addEventListener('click', (e) => {
                if (e.target !== suffixInput && !suggestionsContainer.contains(e.target)) {
                    suggestionsContainer.style.display = 'none';
                }
            });
            
            // Handle arrow keys navigation
            suffixInput.addEventListener('keydown', function(e) {
                const visibleSuggestions = suggestionsContainer.querySelectorAll('div');
                if (visibleSuggestions.length === 0) return;
                
                const activeSuggestion = suggestionsContainer.querySelector('.bg-sidebar-bg');
                let nextSuggestion;
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!activeSuggestion) {
                        nextSuggestion = visibleSuggestions[0];
                    } else {
                        const nextIndex = Array.from(visibleSuggestions).indexOf(activeSuggestion) + 1;
                        nextSuggestion = visibleSuggestions[nextIndex] || visibleSuggestions[0];
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (!activeSuggestion) {
                        nextSuggestion = visibleSuggestions[visibleSuggestions.length - 1];
                    } else {
                        const prevIndex = Array.from(visibleSuggestions).indexOf(activeSuggestion) - 1;
                        nextSuggestion = visibleSuggestions[prevIndex] || visibleSuggestions[visibleSuggestions.length - 1];
                    }
                } else if (e.key === 'Enter' && activeSuggestion) {
                    e.preventDefault();
                    suffixInput.value = activeSuggestion.textContent;
                    suggestionsContainer.style.display = 'none';
                    validateNameInput({ target: suffixInput }); // Use the new validator
                    return;
                } else if (e.key === 'Escape') {
                    suggestionsContainer.style.display = 'none';
                    return;
                } else {
                    return; // Not a navigation key
                }
                
                // Update active suggestion
                if (activeSuggestion) {
                    activeSuggestion.classList.remove('bg-sidebar-bg');
                }
                if (nextSuggestion) {
                    nextSuggestion.classList.add('bg-sidebar-bg');
                }
            });
            
            // Show all options when input is focused and empty
            suffixInput.addEventListener('focus', function() {
                if (this.value.trim() === '') {
                    suggestionsContainer.innerHTML = '';
                    suffixOptions.forEach(option => {
                        const div = document.createElement('div');
                        div.className = 'px-4 py-2 cursor-pointer hover:bg-sidebar-bg transition-colors duration-200';
                        div.textContent = option;
                        div.addEventListener('click', () => {
                            suffixInput.value = option;
                            suggestionsContainer.style.display = 'none';
                            validateNameInput({ target: suffixInput }); // Use the new validator
                        });
                        suggestionsContainer.appendChild(div);
                    });
                    suggestionsContainer.style.display = 'block';
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            setupSuffixTypeahead();
            
        });
        
        // Profile picture upload validation (Existing logic)
const profilePictureInput = document.getElementById('profile_picture');
const updatePictureBtn = document.getElementById('update-picture-btn');

function updateProfilePictureButtonState() {
    const hasFile = profilePictureInput.files.length > 0;
    
    if (hasFile) {
        updatePictureBtn.disabled = false;
        updatePictureBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        updatePictureBtn.classList.add('hover:bg-darkgold', 'hover:shadow-card', 'transform', 'hover:-translate-y-0.5');
    } else {
        updatePictureBtn.disabled = true;
        updatePictureBtn.classList.add('opacity-50', 'cursor-not-allowed');
        updatePictureBtn.classList.remove('hover:bg-darkgold', 'hover:shadow-card', 'transform', 'hover:-translate-y-0.5');
    }
}

// Initial state
updateProfilePictureButtonState();

// Update state when file input changes
profilePictureInput.addEventListener('change', updateProfilePictureButtonState);

// Profile picture form submission validation (Existing logic)
document.querySelector('form[enctype="multipart/form-data"]').addEventListener('submit', function(e) {
    const profilePicture = document.getElementById('profile_picture');
    
    if (!profilePicture.files || profilePicture.files.length === 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'No Image Selected',
            text: 'Please select an image file to upload first.',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        return false;
    }
    
    // Additional file validation
    const file = profilePicture.files[0];
    const maxSize = 2 * 1024 * 1024; // 2MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    if (!allowedTypes.includes(file.type)) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Invalid File Type',
            text: 'Please select a valid image file (JPG, PNG, or GIF).',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        return false;
    }
    
    if (file.size > maxSize) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'File Too Large',
            text: 'Please select an image smaller than 2MB.',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        return false;
    }
    
    return true;
});

// Enhanced Profile Picture Preview with File Validation (Existing logic)
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const maxSize = 2 * 1024 * 1024; // 2MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    if (file) {
        // Validate file type
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Please select a valid image file (JPG, PNG, or GIF).',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            this.value = '';
            updateProfilePictureButtonState();
            return;
        }
        
        // Validate file size
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please select an image smaller than 2MB.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            this.value = '';
            updateProfilePictureButtonState();
            return;
        }
        
        // If validation passes, show preview
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('profile-preview').src = event.target.result;
        };
        reader.readAsDataURL(file);
        updateProfilePictureButtonState();
    } else {
        updateProfilePictureButtonState();
    }
});

// Remove Profile Picture functionality (Existing logic)
document.getElementById('remove-profile-picture')?.addEventListener('click', function() {
    Swal.fire({
        title: 'Remove Profile Picture?',
        text: "Are you sure you want to remove your profile picture?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to remove profile picture
            fetch('settings/remove_profile_picture.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'remove_profile_picture=1&user_type=employee'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire(
                        'Removed!',
                        'Your profile picture has been removed.',
                        'success'
                    ).then(() => {
                        // Update the profile preview to show initials
                        const profilePreview = document.getElementById('profile-preview');
                        profilePreview.outerHTML = `
                            <div id="profile-preview" class="w-40 h-40 rounded-full bg-sidebar-accent flex items-center justify-center border-4 border-border shadow-card transition-all duration-300 group-hover:shadow-lg">
                                <span class="text-white text-2xl font-bold font-inter">
                                    <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                </span>
                            </div>
                        `;
                        // Hide the remove button
                        document.getElementById('remove-profile-picture').remove();
                    });
                } else {
                    Swal.fire(
                        'Error!',
                        data.message || 'Failed to remove profile picture.',
                        'error'
                    );
                }
            })
            .catch(error => {
                Swal.fire(
                    'Error!',
                    'An error occurred while removing profile picture.',
                    'error'
                );
            });
        }
    });
});

// Override the sidebar.js mobile functionality with our own (Existing logic)
document.addEventListener('DOMContentLoaded', function() {
  // Wait a bit for sidebar.js to load, then override its functionality
  setTimeout(function() {
    // Get all elements
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("mobile-overlay");
    const mobileMenuBtn = document.getElementById('mobile-hamburger');
    
    console.log('Initializing mobile menu override...');
    console.log('Sidebar:', sidebar);
    console.log('Overlay:', overlay);
    console.log('Mobile button:', mobileMenuBtn);
    
    // Remove any existing event listeners by cloning the button
    if (mobileMenuBtn) {
      const newMobileMenuBtn = mobileMenuBtn.cloneNode(true);
      mobileMenuBtn.parentNode.replaceChild(newMobileMenuBtn, mobileMenuBtn);
      
      // Add our own event listener
      newMobileMenuBtn.addEventListener('click', function(e) {
        console.log('Mobile hamburger clicked - our handler');
        e.preventDefault();
        e.stopPropagation();
        toggleMobileSidebar();
      });
    }
    
    // Function to toggle mobile sidebar
    function toggleMobileSidebar() {
      if (!sidebar) return;
      
      const isOpen = sidebar.classList.contains('translate-x-0');
      
      if (isOpen) {
        // Close sidebar
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        if (overlay) {
          overlay.classList.add('hidden');
        }
        console.log('Closing mobile sidebar');
      } else {
        // Open sidebar
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        if (overlay) {
          overlay.classList.remove('hidden');
        }
        console.log('Opening mobile sidebar');
      }
    }
    
    // Set initial state for mobile
    if (window.innerWidth < 768 && sidebar) {
      sidebar.classList.remove('translate-x-0');
      sidebar.classList.add('-translate-x-full');
      if (overlay) {
        overlay.classList.add('hidden');
      }
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
      if (window.innerWidth < 768 && 
          sidebar && !sidebar.contains(event.target) && 
          !event.target.closest('#mobile-hamburger')) {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        if (overlay) {
          overlay.classList.add('hidden');
        }
      }
    });
    
    // Close sidebar when overlay is clicked
    if (overlay) {
      overlay.addEventListener('click', function() {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
      });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 768 && sidebar) {
        // Reset to desktop state
        sidebar.classList.remove('-translate-x-full', 'translate-x-0');
        if (overlay) {
          overlay.classList.add('hidden');
        }
      } else if (window.innerWidth < 768 && sidebar) {
        // Set mobile state
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        if (overlay) {
          overlay.classList.add('hidden');
        }
      }
    });
  }, 100); // 100ms delay to ensure sidebar.js has loaded
});
    </script>
</body>
</html>