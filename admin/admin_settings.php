<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600&family=Hedvig+Letters+Serif:opsz@12..24&display=swap" rel="stylesheet">
</head>
<body class="flex bg-gray-50">

    <?php
    session_start();
    
    include 'faviconLogo.php'; 
    
    // Check for admin user type (user_type = 1)
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
        switch ($_SESSION['user_type']) {
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
    // Check for session timeout (30 minutes)
    $session_timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
        session_unset();
        session_destroy();
        header("Location: ../Landing_Page/login.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    require_once '../db_connect.php';
    
    $admin_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        $email_stmt->bind_param("si", $email, $admin_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        
        if ($email_result->num_rows > 0 && $email !== $admin['email']) {
            $errors[] = "Email already exists in the database.";
        }
        
        // Check if phone number exists (excluding current user)
        $phone_check_query = "SELECT id FROM users WHERE phone_number = ? AND id != ?";
        $phone_stmt = $conn->prepare($phone_check_query);
        $phone_stmt->bind_param("si", $phone_number, $admin_id);
        $phone_stmt->execute();
        $phone_result = $phone_stmt->get_result();
        
        if ($phone_result->num_rows > 0 && $phone_number !== $admin['phone_number']) {
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
            $stmt->bind_param("sssssssi", $first_name, $last_name, $middle_name, $suffix, $birthdate, $phone_number, $email, $admin_id);
            
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
    } elseif (isset($_POST['update_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (password_verify($current_password, $admin['password'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("si", $hashed_password, $admin_id);
                    
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
        } elseif (isset($_FILES['profile_picture'])) {
            $target_dir = "../profile_picture/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
            $new_filename = "admin_" . $admin_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    $relative_path = "profile_picture/" . $new_filename;
                    $update_query = "UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("si", $relative_path, $admin_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Profile picture updated successfully!";
                        $admin['profile_picture'] = $relative_path;
                        header("Location: ".$_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        $_SESSION['error_message'] = "Error updating profile picture in database: " . $conn->error;
                    }
                } else {
                    $_SESSION['error_message'] = "Sorry, there was an error uploading your file.";
                }
            } else {
                $_SESSION['error_message'] = "File is not an image.";
            }
        }
        
    
        if (isset($_POST['add_gcash_qr'])) {
            $qr_number = trim($_POST['qr_number']);
            
            // Validate QR number
            if (empty($qr_number)) {
                $_SESSION['error_message'] = "GCash number is required!";
            } elseif (!preg_match("/^[0-9]{11}$/", $qr_number)) {
                $_SESSION['error_message'] = "GCash number must be 11 digits!";
            } elseif (!isset($_FILES['qr_image'])) {
                $_SESSION['error_message'] = "QR Code image is required!";
            } else {
                // Check if QR number already exists
                $check_query = "SELECT id FROM gcash_qr_tb WHERE qr_number = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("s", $qr_number);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $_SESSION['error_message'] = "GCash number already exists!";
                } else {
                    // Handle file upload
                    $target_dir = "../GCash/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES["qr_image"]["name"], PATHINFO_EXTENSION);
                    $new_filename = "gcash_" . time() . "." . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES["qr_image"]["tmp_name"], $target_file)) {
                        $relative_path = "GCash/" . $new_filename;
                        $ph_time = date('Y-m-d H:i:s'); // Current Philippines time
                        $insert_query = "INSERT INTO gcash_qr_tb (qr_number, qr_image, created_at, updated_at) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($insert_query);
                        $stmt->bind_param("ssss", $qr_number, $relative_path, $ph_time, $ph_time);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "GCash QR Code added successfully!";
                        } else {
                            $_SESSION['error_message'] = "Error adding GCash QR Code: " . $conn->error;
                            unlink($target_file); // Delete the uploaded file if DB insert fails
                        }
                    } else {
                        $_SESSION['error_message'] = "Sorry, there was an error uploading your file.";
                    }
                }
            }
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } elseif (isset($_POST['toggle_availability'])) {
            $qr_id = $_POST['qr_id'];
            $action = $_POST['action'];
            $ph_time = date('Y-m-d H:i:s'); // Current Philippines time
            
            $update_query = "UPDATE gcash_qr_tb SET is_available = ?, updated_at = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $new_status = ($action == 'disable') ? 0 : 1;
            $stmt->bind_param("isi", $new_status, $ph_time, $qr_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "GCash QR Code availability updated!";
            } else {
                $_SESSION['error_message'] = "Error updating GCash QR Code: " . $conn->error;
            }
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    }
    
    $success_message = $_SESSION['success_message'] ?? null;
    $error_message = $_SESSION['error_message'] ?? null;
    unset($_SESSION['success_message']);
    unset($_SESSION['error_message']);
    

    // Fetch all GCash QR Codes
    $gcash_qr_query = "SELECT * FROM gcash_qr_tb ORDER BY is_available DESC, created_at DESC";
    $gcash_qr_result = $conn->query($gcash_qr_query);
    $gcash_qr_codes = $gcash_qr_result->fetch_all(MYSQLI_ASSOC);
    ?>
    
    <?php include 'admin_sidebar.php'; ?>
    
    <div id="main-content" class="p-8 bg-navy min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
        <!-- Mobile Hamburger Menu -->
        <button id="mobile-hamburger" class="lg:hidden p-3 bg-sidebar-bg rounded-xl shadow-card text-sidebar-text hover:text-sidebar-accent hover:bg-sidebar-hover transition-all duration-300 mb-6">
            <i class="fas fa-bars text-lg"></i>
        </button>
        
        <!-- Page Header -->
        <div class="mb-10">
            <h1 class="font-bold text-primary-foreground mb-2">Admin Settings</h1>
            <p class="text-dark">Manage your personal information and account settings</p>
        </div>
        
        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="bg-green-50 border-l-4 border-success text-green-800 px-6 py-4 rounded-lg relative mb-6 shadow-input" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-success mr-3"></i>
                    <span class="font-medium"><?php echo $success_message; ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="bg-red-50 border-l-4 border-error text-red-800 px-6 py-4 rounded-lg relative mb-6 shadow-input" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-error mr-3"></i>
                    <span class="font-medium"><?php echo $error_message; ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Picture Section -->
            <div class="bg-sidebar-bg rounded-2xl shadow-card p-8 col-span-1 border border-border">
                <div class="flex items-center mb-6">
                    <i class="fas fa-camera text-sidebar-accent mr-3 text-xl"></i>
                    <h2 class="text-2xl font-semibold text-primary-foreground">Profile Picture</h2>
                </div>
                <div class="flex flex-col items-center">
                    <?php if (!empty($admin['profile_picture'])): ?>
                        <img id="profile-preview" 
                             src="../<?php echo $admin['profile_picture']; ?>" 
                             alt="Profile" class="w-40 h-40 rounded-full object-cover border-4 border-border shadow-card transition-all duration-300 group-hover:shadow-lg">
                    <?php else: ?>
                        <div id="profile-preview" class="w-40 h-40 rounded-full bg-sidebar-accent flex items-center justify-center border-4 border-border shadow-card transition-all duration-300 group-hover:shadow-lg">
                            <span class="text-white text-2xl font-bold">
                                <?php echo strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" class="w-full">
                        <div class="mb-6">
                            <label class="block text-primary-foreground font-medium mb-3 font-inter" for="profile_picture">Upload New Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" 
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-secondary file:text-secondary-foreground hover:file:bg-sidebar-hover"
                                   accept="image/*">
                        </div>
                        <button type="submit" 
                                class="w-full bg-sidebar-accent hover:bg-darkgold text-white py-3 px-6 rounded-xl transition-all duration-300 font-medium shadow-input hover:shadow-card transform hover:-translate-y-0.5">
                            <i class="fas fa-upload mr-2"></i>
                            Update Picture
                        </button>
                    </form>
                    <p class="text-dark text-sm mt-4 text-center font-inter">JPG, PNG or GIF. Max size 2MB</p>
                </div>
            </div>
            
            <!-- Personal Details Section -->
            <div class="bg-sidebar-bg rounded-2xl shadow-card p-8 col-span-2 border border-border">
                <div class="flex items-center mb-6">
                    <i class="fas fa-user-edit text-sidebar-accent mr-3 text-xl"></i>
                    <h2 class="text-2xl font-semibold text-primary-foreground">Personal Details</h2>
                </div>
                <form method="post" id="personal-details-form">
                    <input type="hidden" name="update_personal" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="first_name" class="block text-primary-foreground font-medium font-inter">First Name</label>
                            <input type="text" id="first_name" name="first_name" required
                                   pattern="[a-zA-Z\s'-]+"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($admin['first_name']); ?>">
                            <p id="first_name_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="last_name" class="block text-primary-foreground font-medium font-inter">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required
                                   pattern="[a-zA-Z\s'-]+"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($admin['last_name']); ?>">
                            <p id="last_name_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="middle_name" class="block text-primary-foreground font-medium font-inter">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name"
                                   pattern="[a-zA-Z\s'-]*"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($admin['middle_name']); ?>">
                            <p id="middle_name_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="suffix" class="block text-primary-foreground font-medium font-inter">Suffix</label>
                            <input type="text" id="suffix" name="suffix"
                                   pattern="[a-zA-Z\s'-]*"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($admin['suffix']); ?>">
                            <p id="suffix_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="birthdate" class="block text-primary-foreground font-medium font-inter">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" required
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($admin['birthdate']); ?>">
                            <p id="birthdate_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="space-y-2">
                            <label for="phone_number" class="block text-primary-foreground font-medium font-inter">Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" required
                                   pattern="(\+63[0-9]{10}|[0-9]{11})"
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($admin['phone_number']); ?>">
                            <p id="phone_number_error" class="text-error text-sm hidden"></p>
                        </div>
                        <div class="md:col-span-2 space-y-2">
                            <label for="email" class="block text-primary-foreground font-medium font-inter">Email</label>
                            <input type="email" id="email" name="email" required
                                   class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                   value="<?php echo htmlspecialchars($admin['email']); ?>">
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
            <div class="bg-sidebar-bg rounded-2xl shadow-card p-8 col-span-1 lg:col-span-3 border border-border">
                <div class="flex items-center mb-6">
                    <i class="fas fa-lock text-sidebar-accent mr-3 text-xl"></i>
                    <h2 class="text-2xl font-semibold text-primary-foreground">Change Password</h2>
                </div>
                <form method="post" id="password-form">
                    <input type="hidden" name="update_password" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label for="current_password" class="block text-primary-foreground font-medium font-inter">Current Password</label>
                            <div class="relative">
                                <input type="password" id="current_password" name="current_password" required
                                       class="w-full px-4 py-3 pr-12 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary">
                                <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-dark hover:text-sidebar-accent transition-colors duration-300" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="new_password" class="block text-primary-foreground font-medium font-inter">New Password</label>
                            <div class="relative">
                                <input type="password" id="new_password" name="new_password" required minlength="6"
                                       class="w-full px-4 py-3 pr-12 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary">
                                <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-dark hover:text-sidebar-accent transition-colors duration-300" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="confirm_password" class="block text-primary-foreground font-medium font-inter">Confirm Password</label>
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
            
            
            <!-- GCash QR Codes Section -->
<div class="bg-sidebar-bg rounded-2xl shadow-card p-8 col-span-1 lg:col-span-3 border border-border">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <i class="fas fa-qrcode text-sidebar-accent mr-3 text-xl"></i>
            <h2 class="text-2xl font-semibold text-primary-foreground">GCash QR Codes Management</h2>
        </div>
        <button id="open-gcash-modal" 
                class="bg-sidebar-accent hover:bg-darkgold text-white py-2 px-6 rounded-xl transition-all duration-300 font-medium shadow-input hover:shadow-card">
            <i class="fas fa-plus-circle mr-2"></i>
            Add GCash QR Code
        </button>
    </div>
    
    <!-- Tab Filter -->
    <div class="flex border-b border-border mb-6">
        <button class="tab-filter px-4 py-2 font-medium text-primary-foreground border-b-2 border-sidebar-accent" data-filter="all">
            All QR Codes
        </button>
        <button class="tab-filter px-4 py-2 font-medium text-dark hover:text-primary-foreground" data-filter="available">
            Available
        </button>
        <button class="tab-filter px-4 py-2 font-medium text-dark hover:text-primary-foreground" data-filter="unavailable">
            Unavailable
        </button>
    </div>
    
    <!-- Existing QR Codes List -->
    <div>
        <?php if (empty($gcash_qr_codes)): ?>
            <div class="bg-secondary p-6 rounded-xl text-center">
                <p class="text-dark">No GCash QR Codes found.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="gcash-qr-container">
                <?php 
                // Display only first 3 QR codes initially
                $display_limit = 3;
                $display_count = 0;
                foreach ($gcash_qr_codes as $qr): 
                    if ($display_count < $display_limit): 
                        $display_count++;
                ?>
                    <div class="gcash-qr-item bg-<?php echo $qr['is_available'] ? 'secondary' : 'red-50'; ?> p-6 rounded-xl shadow-input border border-<?php echo $qr['is_available'] ? 'border' : 'error'; ?>" 
                         data-available="<?php echo $qr['is_available'] ? '1' : '0'; ?>">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="font-semibold text-primary-foreground"><?php echo htmlspecialchars($qr['qr_number']); ?></h4>
                                <p class="text-sm text-dark">
                                    <?php echo $qr['is_available'] ? 'Available' : 'Not Available'; ?>
                                </p>
                            </div>
                            <span class="text-xs text-dark">
                                <?php echo date('M d, Y h:i A', strtotime($qr['updated_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="mb-4 flex justify-center">
                            <img src="../<?php echo htmlspecialchars($qr['qr_image']); ?>" 
                                 alt="GCash QR Code" 
                                 class="h-40 w-40 object-contain border border-border rounded-lg">
                        </div>
                        
                        <div class="flex space-x-2">
                            <form method="post" class="flex-1">
                                <input type="hidden" name="qr_id" value="<?php echo $qr['id']; ?>">
                                <input type="hidden" name="action" value="<?php echo $qr['is_available'] ? 'disable' : 'enable'; ?>">
                                <button type="submit" name="toggle_availability"
                                        class="w-full py-2 px-4 rounded-xl transition-all duration-300 font-medium shadow-input <?php echo $qr['is_available'] ? 'bg-error hover:bg-red-700 text-white' : 'bg-success hover:bg-green-700 text-white'; ?>">
                                    <i class="fas <?php echo $qr['is_available'] ? 'fa-times-circle' : 'fa-check-circle'; ?> mr-2"></i>
                                    <?php echo $qr['is_available'] ? 'Mark as Unavailable' : 'Mark as Available'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>
            
            <?php if (count($gcash_qr_codes) > $display_limit): ?>
                <div class="mt-6 text-center">
                    <button id="show-all-qr" class="bg-sidebar-accent hover:bg-darkgold text-white py-2 px-6 rounded-xl transition-all duration-300 font-medium shadow-input hover:shadow-card">
                        <i class="fas fa-eye mr-2"></i>
                        Show All QR Codes
                    </button>
                </div>
                
                <!-- Hidden QR Codes (will be shown when "Show All" is clicked) -->
                <div id="hidden-qr-codes" class="hidden">
                    <?php 
                    $display_count = 0;
                    foreach ($gcash_qr_codes as $qr): 
                        if ($display_count >= $display_limit): 
                    ?>
                        <div class="gcash-qr-item bg-<?php echo $qr['is_available'] ? 'secondary' : 'red-50'; ?> p-6 rounded-xl shadow-input border border-<?php echo $qr['is_available'] ? 'border' : 'error'; ?>" 
                             data-available="<?php echo $qr['is_available'] ? '1' : '0'; ?>">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="font-semibold text-primary-foreground"><?php echo htmlspecialchars($qr['qr_number']); ?></h4>
                                    <p class="text-sm text-dark">
                                        <?php echo $qr['is_available'] ? 'Available' : 'Not Available'; ?>
                                    </p>
                                </div>
                                <span class="text-xs text-dark">
                                    <?php echo date('M d, Y h:i A', strtotime($qr['updated_at'])); ?>
                                </span>
                            </div>
                            
                            <div class="mb-4 flex justify-center">
                                <img src="../<?php echo htmlspecialchars($qr['qr_image']); ?>" 
                                     alt="GCash QR Code" 
                                     class="h-40 w-40 object-contain border border-border rounded-lg">
                            </div>
                            
                            <div class="flex space-x-2">
                                <form method="post" class="flex-1">
                                    <input type="hidden" name="qr_id" value="<?php echo $qr['id']; ?>">
                                    <input type="hidden" name="action" value="<?php echo $qr['is_available'] ? 'disable' : 'enable'; ?>">
                                    <button type="submit" name="toggle_availability"
                                            class="w-full py-2 px-4 rounded-xl transition-all duration-300 font-medium shadow-input <?php echo $qr['is_available'] ? 'bg-error hover:bg-red-700 text-white' : 'bg-success hover:bg-green-700 text-white'; ?>">
                                        <i class="fas <?php echo $qr['is_available'] ? 'fa-times-circle' : 'fa-check-circle'; ?> mr-2"></i>
                                        <?php echo $qr['is_available'] ? 'Mark as Unavailable' : 'Mark as Available'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php 
                        endif;
                        $display_count++;
                    endforeach; 
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
            
            <!-- Add GCash QR Modal -->
            <div id="gcash-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-sidebar-bg px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-semibold text-primary-foreground">Add New GCash QR Code</h3>
                                <button id="close-gcash-modal" class="text-dark hover:text-sidebar-accent">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <form method="post" enctype="multipart/form-data" id="gcash-form">
                                <div class="space-y-4">
                                    <div>
                                        <label for="modal_qr_number" class="block text-primary-foreground font-medium font-inter">GCash Number</label>
                                        <input type="text" id="modal_qr_number" name="qr_number" required
                                               pattern="[0-9]{11}"
                                               class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary"
                                               placeholder="11-digit GCash number">
                                        <p id="modal_qr_number_error" class="text-error text-sm hidden"></p>
                                    </div>
                                    
                                    <div>
                                        <label for="modal_qr_image" class="block text-primary-foreground font-medium font-inter">QR Code Image</label>
                                        <input type="file" id="modal_qr_image" name="qr_image" required
                                               class="w-full px-4 py-3 border border-input-border rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent shadow-input transition-all duration-300 bg-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-sidebar-bg file:text-secondary-foreground hover:file:bg-sidebar-hover"
                                               accept="image/*">
                                        <p id="modal_qr_image_error" class="text-error text-sm hidden"></p>
                                    </div>
                                </div>
                                
                                <div class="mt-6 flex justify-end space-x-3">
                                    <button type="button" id="cancel-gcash-modal"
                                            class="px-4 py-2 border border-input-border rounded-xl text-primary-foreground hover:bg-secondary transition-all duration-300">
                                        Cancel
                                    </button>
                                    <button type="submit" name="add_gcash_qr"
                                            class="bg-sidebar-accent hover:bg-darkgold text-white py-2 px-6 rounded-xl transition-all duration-300 font-medium shadow-input hover:shadow-card">
                                        <i class="fas fa-plus-circle mr-2"></i>
                                        Add QR Code
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profile-preview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Password visibility toggle
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
        
        // Password match validation
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
        
        // Sidebar toggle for mobile
        document.getElementById('mobile-hamburger').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        });
        
        // Add subtle animations on form focus
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.classList.add('transform', 'scale-[1.02]');
            });
            
            input.addEventListener('blur', function() {
                this.classList.remove('transform', 'scale-[1.02]');
            });
        });
        
        // Real-time validation
const namePattern = /^[a-zA-Z\s'-]+$/;
    const phonePattern = /^\+63[0-9]{10}$|^[0-9]{11}$/;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const originalEmail = '<?php echo htmlspecialchars($admin['email']); ?>';
    const originalPhone = '<?php echo htmlspecialchars($admin['phone_number']); ?>';
    const originalValues = {
        first_name: '<?php echo htmlspecialchars($admin['first_name']); ?>',
        last_name: '<?php echo htmlspecialchars($admin['last_name']); ?>',
        middle_name: '<?php echo htmlspecialchars($admin['middle_name']); ?>',
        suffix: '<?php echo htmlspecialchars($admin['suffix']); ?>',
        birthdate: '<?php echo htmlspecialchars($admin['birthdate']); ?>',
        phone_number: '<?php echo htmlspecialchars($admin['phone_number']); ?>',
        email: '<?php echo htmlspecialchars($admin['email']); ?>'
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
    
    // Name validation
    ['first_name', 'last_name', 'middle_name', 'suffix'].forEach(field => {
        const input = document.getElementById(field);
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
    
    // Phone number validation
    const phoneInput = document.getElementById('phone_number');
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
                    body: `phone_number=${encodeURIComponent(value)}&user_id=${<?php echo $admin_id; ?>}`
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
    
    // Email validation
    const emailInput = document.getElementById('email');
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
                    body: `email=${encodeURIComponent(value)}&user_id=${<?php echo $admin_id; ?>}`
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
    
    // Form submission validation
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

// GCash QR Modal functionality
const openModalBtn = document.getElementById('open-gcash-modal');
const closeModalBtn = document.getElementById('close-gcash-modal');
const cancelModalBtn = document.getElementById('cancel-gcash-modal');
const modal = document.getElementById('gcash-modal');

openModalBtn.addEventListener('click', () => {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
});

closeModalBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    resetModalForm();
});

cancelModalBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    resetModalForm();
});

modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        resetModalForm();
    }
});

function resetModalForm() {
    document.getElementById('modal_qr_number').value = '';
    document.getElementById('modal_qr_image').value = '';
    document.getElementById('modal_qr_number_error').classList.add('hidden');
    document.getElementById('modal_qr_image_error').classList.add('hidden');
}

// Modal form validation
document.getElementById('modal_qr_number').addEventListener('input', function() {
    const qrNumber = this.value.trim();
    const errorElement = document.getElementById('modal_qr_number_error');
    
    if (!/^[0-9]{0,11}$/.test(qrNumber)) {
        errorElement.textContent = 'GCash number must be 11 digits only';
        errorElement.classList.remove('hidden');
    } else if (qrNumber.length === 11) {
        errorElement.textContent = '';
        errorElement.classList.add('hidden');
    } else if (qrNumber.length > 0) {
        errorElement.textContent = 'GCash number must be 11 digits';
        errorElement.classList.remove('hidden');
    } else {
        errorElement.textContent = '';
        errorElement.classList.add('hidden');
    }
});

document.getElementById('modal_qr_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const errorElement = document.getElementById('modal_qr_image_error');
    
    if (file) {
        // Check file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            errorElement.textContent = 'File size must be less than 2MB';
            errorElement.classList.remove('hidden');
            this.value = '';
        } else if (!file.type.match('image.*')) {
            errorElement.textContent = 'File must be an image';
            errorElement.classList.remove('hidden');
            this.value = '';
        } else {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        }
    }
});

document.getElementById('gcash-form').addEventListener('submit', function(e) {
    const qrNumber = document.getElementById('modal_qr_number').value.trim();
    const qrImage = document.getElementById('modal_qr_image').value;
    
    if (!/^[0-9]{11}$/.test(qrNumber)) {
        e.preventDefault();
        const errorElement = document.getElementById('modal_qr_number_error');
        errorElement.textContent = 'GCash number must be 11 digits';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    if (!qrImage) {
        e.preventDefault();
        const errorElement = document.getElementById('modal_qr_image_error');
        errorElement.textContent = 'QR Code image is required';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    return true;
});

// Tab filtering for GCash QR Codes
document.querySelectorAll('.tab-filter').forEach(button => {
    button.addEventListener('click', function() {
        // Update active tab
        document.querySelectorAll('.tab-filter').forEach(btn => {
            btn.classList.remove('border-b-2', 'border-sidebar-accent', 'text-primary-foreground');
            btn.classList.add('text-dark');
        });
        this.classList.add('border-b-2', 'border-sidebar-accent', 'text-primary-foreground');
        this.classList.remove('text-dark');
        
        const filter = this.dataset.filter;
        const allItems = document.querySelectorAll('.gcash-qr-item');
        
        allItems.forEach(item => {
            switch(filter) {
                case 'all':
                    item.style.display = 'block';
                    break;
                case 'available':
                    item.style.display = item.dataset.available === '1' ? 'block' : 'none';
                    break;
                case 'unavailable':
                    item.style.display = item.dataset.available === '0' ? 'block' : 'none';
                    break;
            }
        });
    });
});

// Show All QR Codes functionality
document.getElementById('show-all-qr')?.addEventListener('click', function() {
    const hiddenQRCodes = document.getElementById('hidden-qr-codes');
    const container = document.getElementById('gcash-qr-container');
    
    // Append all hidden QR codes to the container
    while (hiddenQRCodes.firstChild) {
        container.appendChild(hiddenQRCodes.firstChild);
    }
    
    // Remove the "Show All" button and hidden container
    this.parentElement.remove();
    hiddenQRCodes.remove();
    
    // Reapply any active filter
    const activeTab = document.querySelector('.tab-filter.border-sidebar-accent');
    if (activeTab && activeTab.dataset.filter !== 'all') {
        activeTab.click();
    }
});
    </script>
</body>
</html>