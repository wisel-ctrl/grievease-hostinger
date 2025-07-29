<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'playfair': ['"Playfair Display"', 'serif'],
                        'alexbrush': ['"Alex Brush"', 'cursive'],
                        'inter': ['Inter', 'sans-serif'],
                        'cinzel': ['Cinzel', 'serif'],
                        'hedvig': ['Hedvig Letters Serif', 'serif']
                    },
                    colors: {
                        'yellow': {
                            600: '#CA8A04',
                        },
                        'navy': '#F0F4F8',
                        'cream': '#F9F6F0',
                        'dark': '#4A5568',
                        'gold': '#D69E2E',
                        'darkgold': '#B7791F',
                        'primary': '#F8FAFC',
                        'primary-foreground': '#334155',
                        'secondary': '#F1F5F9',
                        'secondary-foreground': '#334155',
                        'border': '#E4E9F0',
                        'input-border': '#D3D8E1',
                        'error': '#E53E3E',
                        'success': '#38A169',
                        'sidebar-bg': '#FFFFFF',
                        'sidebar-hover': '#F1F5F9',
                        'sidebar-text': '#334155',
                        'sidebar-accent': '#CA8A04',
                        'sidebar-border': '#E2E8F0',
                    },
                    boxShadow: {
                        'input': '0 1px 2px rgba(0, 0, 0, 0.05)',
                        'card': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        'sidebar': '0 0 15px rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600&family=Hedvig+Letters+Serif:opsz@12..24&display=swap" rel="stylesheet">
</head>
<body class="flex bg-navy font-inter">
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
    }
    
    $success_message = $_SESSION['success_message'] ?? null;
    $error_message = $_SESSION['error_message'] ?? null;
    unset($_SESSION['success_message']);
    unset($_SESSION['error_message']);
    ?>
    
    <?php include 'admin_sidebar.php'; ?>
    
    <div id="main-content" class="p-8 bg-navy min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
        <!-- Mobile Hamburger Menu -->
        <button id="mobile-hamburger" class="lg:hidden p-3 bg-sidebar-bg rounded-xl shadow-card text-sidebar-text hover:text-sidebar-accent hover:bg-sidebar-hover transition-all duration-300 mb-6">
            <i class="fas fa-bars text-lg"></i>
        </button>
        
        <!-- Page Header -->
        <div class="mb-10">
            <h1 class="text-4xl font-bold text-primary-foreground mb-2 font-playfair">Admin Settings</h1>
            <p class="text-dark text-lg font-inter">Manage your personal information and account settings</p>
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
                    <h2 class="text-2xl font-semibold text-primary-foreground font-playfair">Profile Picture</h2>
                </div>
                <div class="flex flex-col items-center">
                    <?php if (!empty($admin['profile_picture'])): ?>
                        <img id="profile-preview" 
                             src="../<?php echo $admin['profile_picture']; ?>" 
                             alt="Profile" class="w-40 h-40 rounded-full object-cover border-4 border-border shadow-card transition-all duration-300 group-hover:shadow-lg">
                    <?php else: ?>
                        <div id="profile-preview" class="w-40 h-40 rounded-full bg-sidebar-accent flex items-center justify-center border-4 border-border shadow-card transition-all duration-300 group-hover:shadow-lg">
                            <span class="text-white text-2xl font-bold font-inter">
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
                    <h2 class="text-2xl font-semibold text-primary-foreground font-playfair">Personal Details</h2>
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
                    <h2 class="text-2xl font-semibold text-primary-foreground font-playfair">Change Password</h2>
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

    </script>
</body>
</html>