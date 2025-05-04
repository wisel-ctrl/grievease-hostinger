<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

require_once '../addressDB.php'; 
require_once '../db_connect.php'; 

// Handle AJAX requests for validation
if (isset($_GET['check_email'])) {
    $email = $_GET['check_email'];
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    echo json_encode(['exists' => $row['count'] > 0]);
    exit();
}

if (isset($_GET['check_phone'])) {
    $phone = $_GET['check_phone'];
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE phone_number = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    echo json_encode(['exists' => $row['count'] > 0]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic information
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $birthdate = $_POST["birthdate"];
    $phone_number = trim($_POST["phone_number"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Address information
    $region_name = $_POST["region_name"];
    $province_name = $_POST["province_name"];
    $city_name = $_POST["city_name"];
    $barangay_name = $_POST["barangay_name"];
    $street_address = trim($_POST["street_address"]);
    $zip_code = trim($_POST["zip_code"]);
    
    // Server-side validation
    $errors = [];
    
    // Check age (must be 18 or older)
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    if ($age < 18) {
        $errors[] = "You must be 18 years or older to register.";
    }
    

    
    // Phone validation (Philippine format)
    if (!preg_match("/^(09|\+639)\d{9}$/", $phone_number)) {
        $errors[] = "Phone number must be a valid Philippine number (e.g., 09XXXXXXXXX or +639XXXXXXXXX).";
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        $errors[] = "Email already exists.";
    }
    
    // Check if phone exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE phone_number = ?");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        $errors[] = "Phone number already exists.";
    }
    
    // Password validation
    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[^A-Za-z0-9]/", $password)) {
        $errors[] = "Password must be strong (at least 8 characters with uppercase, number, and special character).";
    }
    
    // Password match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $user_type = 1; // Admin user type
        $is_verified = 1; // Verified
        
        // Prepare SQL statement with all fields
        $sql = "INSERT INTO users (
            first_name, middle_name, last_name, birthdate, phone_number, 
            email, password, user_type, is_verified, region, province, 
            city, barangay, street_address, zip_code
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssissssss", 
            $first_name, $middle_name, $last_name, $birthdate, $phone_number,
            $email, $hashed_password, $user_type, $is_verified, $region_name, $province_name,
            $city_name, $barangay_name, $street_address, $zip_code
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Admin account created successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
    
    exit(); // Stop execution after AJAX response
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600&family=Hedvig+Letters+Serif:wght@400&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.28.0/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --color-navy: #F0F4F8;
            --color-cream: #F9F6F0;
            --color-dark: #4A5568;
            --color-gold: #D69E2E;
            --color-darkgold: #B7791F;
            --color-primary: #F8FAFC;
            --color-primary-foreground: #334155;
            --color-secondary: #F1F5F9;
            --color-secondary-foreground: #334155;
            --color-border: #E4E9F0;
            --color-input-border: #D3D8E1;
            --color-error: #E53E3E;
            --color-success: #38A169;
            --color-sidebar-bg: #FFFFFF;
            --color-sidebar-hover: #F1F5F9;
            --color-sidebar-text: #334155;
            --color-sidebar-accent: #CA8A04;
            --color-sidebar-border: #E2E8F0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-cream);
        }
        
        .form-input:focus {
            border-color: var(--color-gold);
            box-shadow: 0 0 0 3px rgba(214, 158, 46, 0.1);
        }
        .btn-primary {
            background-color: var(--color-gold);
        }
        
        .btn-primary:hover {
            background-color: var(--color-darkgold);
        }
        
        .address-loading {
            display: none;
            color: var(--color-gold);
        }
        
        .input-error {
            border-color: var(--color-error) !important;
        }
        
        .error-message {
            color: var(--color-error);
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .success-indicator {
            color: var(--color-success);
        }
        
        .header-nav {
            background-color: var(--color-navy);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .header-nav .logo {
            font-family: 'Cinzel', serif;
            font-weight: 600;
            color: var(--color-dark);
            font-size: 1.5rem;
        }
        
        .header-nav .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .header-nav .nav-link {
            color: var(--color-sidebar-text);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .header-nav .nav-link:hover {
            color: var(--color-gold);
        }
        
        .header-nav .nav-link.active {
            color: var(--color-gold);
            font-weight: 600;
        }
        
        .header-nav .logout-btn {
            background-color: var(--color-error);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .header-nav .logout-btn:hover {
            background-color: #c53030;
        }

        /* Additional custom styles */
        .form-container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 900px;
            margin: 2rem auto;
            overflow: hidden;
        }

        .form-header {
            background-color: var(--color-navy);
            padding: 1.5rem;
            border-bottom: 1px solid var(--color-sidebar-border);
        }

        .section-divider {
            margin: 1.5rem 0;
            height: 1px;
            background-color: var(--color-border);
        }

        .password-strength-weak {
            background-color: #FC8181;
        }
        
        .password-strength-medium {
            background-color: #F6AD55;
        }
        
        .password-strength-strong {
            background-color: #68D391;
        }
        
        .password-strength-very-strong {
            background-color: #38A169;
        }
    </style>
</head>
<body class="bg-cream min-h-screen">
    <!-- Navigation header -->
    <header class="header-nav">
        <div class="logo">Super Admin Panel</div>
        <div class="nav-links">
            <a href="index.php" class="nav-link active">
                <i data-feather="home"></i>
                <span>Home</span>
            </a>
            <a href="logout.php" class="logout-btn">
                <i data-feather="log-out"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Main content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Form container with proper styling -->
        <div class="form-container">
            <!-- Form header -->
            <div class="form-header">
                <h1 class="text-2xl font-cinzel font-semibold text-dark">Create Admin Account</h1>
                <p class="text-sm text-sidebar-text mt-1">Add a new administrator to the system</p>
            </div>
            
        <!-- Form -->
        <form id="admin-form" class="px-6 py-8 space-y-6">
            <!-- Response message container -->
            <div id="response-message" class="hidden p-4 rounded-md"></div>
            
            <!-- Name fields - 3 column grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                    <label for="first_name" class="block text-sm font-medium text-sidebar-text">First Name</label>
                    <input type="text" id="first_name" name="first_name" required class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                    <div class="error-message" id="first_name_error"></div>
                </div>
                
                <div class="space-y-2">
                    <label for="middle_name" class="block text-sm font-medium text-sidebar-text">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                    <p class="text-xs text-gray-500 mt-1">Optional</p>
                    <div class="error-message" id="middle_name_error"></div>
                </div>
                
                <div class="space-y-2">
                    <label for="last_name" class="block text-sm font-medium text-sidebar-text">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                    <div class="error-message" id="last_name_error"></div>
                </div>
            </div>
            
            <!-- Birthdate and Phone Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="birthdate" class="block text-sm font-medium text-sidebar-text">Birthdate</label>
                    <input type="date" id="birthdate" name="birthdate" required class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                    <div class="error-message" id="birthdate_error"></div>
                </div>
                
                <div class="space-y-2">
                    <label for="phone_number" class="block text-sm font-medium text-sidebar-text">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" required placeholder="09XXXXXXXXX" class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                    <div class="error-message" id="phone_number_error"></div>
                </div>
            </div>
            
            <!-- Email -->
            <div class="space-y-2">
                <label for="email" class="block text-sm font-medium text-sidebar-text">Email Address</label>
                <input type="email" id="email" name="email" required class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                <div class="error-message" id="email_error"></div>
            </div>
            
            <!-- Address Section -->
            <div class="space-y-4 border-t border-sidebar-border pt-4">
                <h3 class="text-lg font-medium text-sidebar-text">Address Information</h3>
                
                <!-- Region and Province -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="region" class="block text-sm font-medium text-sidebar-text">Region</label>
                        <select id="region" name="region" required class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                            <option value="">Select Region</option>
                            <!-- Regions will be loaded via JavaScript -->
                        </select>
                        <span id="region-loading" class="address-loading text-xs">Loading regions...</span>
                        <input type="hidden" id="region_name" name="region_name">
                        <div class="error-message" id="region_error"></div>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="province" class="block text-sm font-medium text-sidebar-text">Province</label>
                        <select id="province" name="province" required disabled class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                            <option value="">Select Province</option>
                        </select>
                        <span id="province-loading" class="address-loading text-xs">Select region first</span>
                        <input type="hidden" id="province_name" name="province_name">
                        <div class="error-message" id="province_error"></div>
                    </div>
                </div>
                
                <!-- City and Barangay -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="city" class="block text-sm font-medium text-sidebar-text">City/Municipality</label>
                        <select id="city" name="city" required disabled class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                            <option value="">Select City</option>
                        </select>
                        <span id="city-loading" class="address-loading text-xs">Select province first</span>
                        <input type="hidden" id="city_name" name="city_name">
                        <div class="error-message" id="city_error"></div>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="barangay" class="block text-sm font-medium text-sidebar-text">Barangay</label>
                        <select id="barangay" name="barangay" required disabled class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                            <option value="">Select Barangay</option>
                        </select>
                        <span id="barangay-loading" class="address-loading text-xs">Select city first</span>
                        <input type="hidden" id="barangay_name" name="barangay_name">
                        <div class="error-message" id="barangay_error"></div>
                    </div>
                </div>
                
                <!-- Street Address and Zip Code -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="street_address" class="block text-sm font-medium text-sidebar-text">Street Address</label>
                        <input type="text" id="street_address" name="street_address" required class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                        <div class="error-message" id="street_address_error"></div>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="zip_code" class="block text-sm font-medium text-sidebar-text">Zip Code</label>
                        <input type="text" id="zip_code" name="zip_code" required class="form-input w-full rounded-md border border-input-border p-2 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                        <div class="error-message" id="zip_code_error"></div>
                    </div>
                </div>
            </div>
            
            <!-- Password with strength indicator -->
            <div class="space-y-2">
                <label for="password" class="block text-sm font-medium text-sidebar-text">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required class="form-input w-full rounded-md border border-input-border p-2 pr-10 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                    <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon hidden">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
                
                <div class="mt-2">
                    <div class="h-1 w-full bg-gray-200 rounded-full overflow-hidden">
                        <div id="password-strength" class="h-1 w-0 transition-all duration-300 ease-in-out"></div>
                    </div>
                    <p id="password-strength-text" class="text-xs mt-1 text-gray-500">Password strength</p>
                </div>
                <div class="error-message" id="password_error"></div>
            </div>
            
            <!-- Confirm Password -->
            <div class="space-y-2">
                <label for="confirm_password" class="block text-sm font-medium text-sidebar-text">Confirm Password</label>
                <div class="relative">
                    <input type="password" id="confirm_password" name="confirm_password" required class="form-input w-full rounded-md border border-input-border p-2 pr-10 text-dark focus:outline-none focus:ring-2 focus:ring-gold/30">
                    <button type="button" id="toggle-confirm-password" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-confirm">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon-confirm hidden">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
                <p id="password-match" class="text-xs mt-1 text-gray-500 hidden"></p>
                <div class="error-message" id="confirm_password_error"></div>
            </div>
            
            <!-- Submit button -->
            <div class="pt-4">
                <button type="submit" id="submit-btn" class="btn-primary w-full text-white py-2 px-4 rounded-md font-medium transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold">
                    Create Admin Account
                </button>
            </div>
        </form>
    </div>

    <script>
        // Global variables
        let isFormValid = false;
        let passwordStrength = 0;
        
        // DOM elements
        const form = document.getElementById('admin-form');
        const submitBtn = document.getElementById('submit-btn');
        const firstNameInput = document.getElementById('first_name');
        const middleNameInput = document.getElementById('middle_name');
        const lastNameInput = document.getElementById('last_name');
        const birthdateInput = document.getElementById('birthdate');
        const phoneNumberInput = document.getElementById('phone_number');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        } else {
            console.error("Form #admin-form not found!");
        }
                
        // Password visibility toggle
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.querySelector('.eye-icon');
            const eyeOffIcon = document.querySelector('.eye-off-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        });
        
        // Confirm Password visibility toggle
        document.getElementById('toggle-confirm-password').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const eyeIcon = document.querySelector('.eye-icon-confirm');
            const eyeOffIcon = document.querySelector('.eye-off-icon-confirm');
            
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                confirmPasswordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        });
        
        // Validation functions
        function validateName(input, errorId, fieldName) {
            const value = input.value.trim(); // This trims the value first
            const errorElement = document.getElementById(errorId);
            
            // Check if empty (only for required fields)
            if (input.required && value === '') {
                errorElement.textContent = `${fieldName} is required.`;
                input.classList.add('input-error');
                return false;
            }
            
            // Skip validation if optional field is empty
            if (!input.required && value === '') {
                errorElement.textContent = '';
                input.classList.remove('input-error');
                return true;
            }
            
            // Check if contains at least one letter (not just spaces/special chars)
            if (!value.match(/[a-zA-Z]/) || !value.match(/^[a-zA-Z\s-']+$/)) {
                errorElement.textContent = `${fieldName} must contain valid characters (letters, spaces, hyphens, apostrophes).`;
                input.classList.add('input-error');
                return false;
            }
            
            errorElement.textContent = '';
            input.classList.remove('input-error');
            return true;
        }
        
        function validateAge() {
            const birthdate = new Date(birthdateInput.value);
            const today = new Date();
            const errorElement = document.getElementById('birthdate_error');
            
            if (!birthdateInput.value) {
                errorElement.textContent = 'Birthdate is required.';
                birthdateInput.classList.add('input-error');
                return false;
            }
            
            // Calculate age
            let age = today.getFullYear() - birthdate.getFullYear();
            const monthDiff = today.getMonth() - birthdate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }
            
            if (age < 18) {
                errorElement.textContent = 'You must be 18 years or older to register.';
                birthdateInput.classList.add('input-error');
                return false;
            }
            
            errorElement.textContent = '';
            birthdateInput.classList.remove('input-error');
            return true;
        }
        
        function validatePhoneNumber() {
            const phone = phoneNumberInput.value.trim();
            const errorElement = document.getElementById('phone_number_error');
            
            if (!phone) {
                errorElement.textContent = 'Phone number is required.';
                phoneNumberInput.classList.add('input-error');
                return false;
            }
            
            // Check for Philippine phone format
            const phoneRegex = /^(09|\+639)\d{9}$/;
            if (!phoneRegex.test(phone)) {
                errorElement.textContent = 'Must be a valid Philippine number (e.g., 09XXXXXXXXX or +639XXXXXXXXX).';
                phoneNumberInput.classList.add('input-error');
                return false;
            }
            
            // Check if phone exists in database
            checkPhoneExists(phone);
            return true;
        }
        
        function checkPhoneExists(phone) {
            const errorElement = document.getElementById('phone_number_error');
            
            fetch(`?check_phone=${encodeURIComponent(phone)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data); 
                    if (data.exists) {
                        errorElement.textContent = 'This phone number is already registered.';
                        phoneNumberInput.classList.add('input-error');
                        return false;
                    } else {
                        errorElement.textContent = '';
                        phoneNumberInput.classList.remove('input-error');
                        return true;
                    }
                })
                .catch(error => {
                    console.error('Error checking phone number:', error);
                    return false;
                });
        }
        
        function validateEmail() {
            const email = emailInput.value.trim();
            const errorElement = document.getElementById('email_error');
            
            if (!email) {
                errorElement.textContent = 'Email address is required.';
                emailInput.classList.add('input-error');
                return false;
            }
            
            // Basic email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errorElement.textContent = 'Please enter a valid email address.';
                emailInput.classList.add('input-error');
                return false;
            }
            
            // Check if email exists in database
            checkEmailExists(email);
            return true;
        }
        
        function checkEmailExists(email) {
            const errorElement = document.getElementById('email_error');
            
            fetch(`?check_email=${encodeURIComponent(email)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        errorElement.textContent = 'This email is already registered.';
                        emailInput.classList.add('input-error');
                        return false;
                    } else {
                        errorElement.textContent = '';
                        emailInput.classList.remove('input-error');
                        return true;
                    }
                })
                .catch(error => {
                    console.error('Error checking email:', error);
                    return false;
                });
        }
        
        function validatePassword() {
            const password = passwordInput.value;
            const errorElement = document.getElementById('password_error');
            
            if (!password) {
                errorElement.textContent = 'Password is required.';
                passwordInput.classList.add('input-error');
                return false;
            }
            
            // Password strength criteria
            const hasMinLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^A-Za-z0-9]/.test(password);
            
            // Calculate strength (0-4)
            passwordStrength = 0;
            if (hasMinLength) passwordStrength++;
            if (hasUppercase) passwordStrength++;
            if (hasNumber) passwordStrength++;
            if (hasSpecial) passwordStrength++;
            
            // Update UI
            const strengthBar = document.getElementById('password-strength');
            const strengthText = document.getElementById('password-strength-text');
            
            if (passwordStrength === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = '#e53e3e';
                strengthText.textContent = 'Password strength: Very weak';
                strengthText.style.color = '#e53e3e';
            } else if (passwordStrength === 1) {
                strengthBar.style.width = '25%';
                strengthBar.style.backgroundColor = '#e53e3e';
                strengthText.textContent = 'Password strength: Weak';
                strengthText.style.color = '#e53e3e';
            } else if (passwordStrength === 2) {
                strengthBar.style.width = '50%';
                strengthBar.style.backgroundColor = '#ed8936';
                strengthText.textContent = 'Password strength: Fair';
                strengthText.style.color = '#ed8936';
            } else if (passwordStrength === 3) {
                strengthBar.style.width = '75%';
                strengthBar.style.backgroundColor = '#ecc94b';
                strengthText.textContent = 'Password strength: Good';
                strengthText.style.color = '#ecc94b';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#48bb78';
                strengthText.textContent = 'Password strength: Strong';
                strengthText.style.color = '#48bb78';
            }
            
            // Check if password meets minimum requirements
            if (passwordStrength < 3) {
                errorElement.textContent = 'Password must include at least 8 characters with uppercase, number, and special character.';
                passwordInput.classList.add('input-error');
                return false;
            }
            
            errorElement.textContent = '';
            passwordInput.classList.remove('input-error');
            return true;
        }
        
        function validateConfirmPassword() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const errorElement = document.getElementById('confirm_password_error');
            const matchElement = document.getElementById('password-match');
            
            if (!confirmPassword) {
                errorElement.textContent = 'Please confirm your password.';
                confirmPasswordInput.classList.add('input-error');
                matchElement.classList.add('hidden');
                return false;
            }
            
            if (password !== confirmPassword) {
                errorElement.textContent = 'Passwords do not match.';
                confirmPasswordInput.classList.add('input-error');
                matchElement.textContent = 'Passwords do not match';
                matchElement.classList.remove('hidden');
                matchElement.style.color = '#e53e3e';
                return false;
            }
            
            errorElement.textContent = '';
            confirmPasswordInput.classList.remove('input-error');
            matchElement.textContent = 'Passwords match';
            matchElement.classList.remove('hidden');
            matchElement.style.color = '#48bb78';
            return true;
        }
        
        // Address dropdown functions
        // Address Selector Logic
        document.addEventListener('DOMContentLoaded', function() {
            // Load regions on page load
            loadRegions();
            
            // Region change event
            document.getElementById('region').addEventListener('change', function() {
                const regionSelect = this;
                const regionName = regionSelect.options[regionSelect.selectedIndex].text;
                document.getElementById('region_name').value = regionName;
                const regionId = this.value;
                const provinceSelect = document.getElementById('province');
                const citySelect = document.getElementById('city');
                const barangaySelect = document.getElementById('barangay');
                
                // Reset downstream selects
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                provinceSelect.disabled = true;
                citySelect.innerHTML = '<option value="">Select City</option>';
                citySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
                
                if (regionId) {
                    loadProvinces(regionId);
                }
            });
            
            // Province change event
            document.getElementById('province').addEventListener('change', function() {
                const provinceSelect = this;
                const provinceName = provinceSelect.options[provinceSelect.selectedIndex].text;
                document.getElementById('province_name').value = provinceName;
                const provinceId = this.value;
                const citySelect = document.getElementById('city');
                const barangaySelect = document.getElementById('barangay');
                
                // Reset downstream selects
                citySelect.innerHTML = '<option value="">Select City</option>';
                citySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
                
                if (provinceId) {
                    loadCities(provinceId);
                }
            });
            
            // City change event
            document.getElementById('city').addEventListener('change', function() {
                const citySelect = this;
                const cityName = citySelect.options[citySelect.selectedIndex].text;
                document.getElementById('city_name').value = cityName;
                const cityId = this.value;
                const barangaySelect = document.getElementById('barangay');
                
                // Reset barangay select
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
                
                if (cityId) {
                    loadBarangays(cityId);
                }
            });
            
            // Modify the barangay change event
            document.getElementById('barangay').addEventListener('change', function() {
                const barangaySelect = this;
                const barangayName = barangaySelect.options[barangaySelect.selectedIndex].text;
                document.getElementById('barangay_name').value = barangayName;
            });
        });
        
        function loadRegions() {
            const regionSelect = document.getElementById('region');
            const loadingElement = document.getElementById('region-loading');
            
            loadingElement.style.display = 'block';
            
            fetch('../customer/address/get_regions.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.message);
                    }
                    
                    regionSelect.innerHTML = '<option value="">Select Region</option>';
                    data.forEach(region => {
                        const option = document.createElement('option');
                        option.value = region.region_id;
                        option.textContent = region.region_name;
                        option.setAttribute('data-name', region.region_name); // Store the name as data attribute
                        regionSelect.appendChild(option);
                    });
                    
                    // Then you can access the name like this in the change events:
                    const regionName = regionSelect.options[regionSelect.selectedIndex].getAttribute('data-name');
                    
                    loadingElement.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error loading regions:', error);
                    loadingElement.textContent = 'Error loading regions. Please try again.';
                });
        }
        
        function loadProvinces(regionId) {
            const provinceSelect = document.getElementById('province');
            const loadingElement = document.getElementById('province-loading');
            
            loadingElement.textContent = 'Loading provinces...';
            loadingElement.style.display = 'block';
            provinceSelect.disabled = true;
            
            fetch(`../customer/address/get_provinces.php?region_id=${regionId}`)
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
                    loadingElement.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error loading provinces:', error);
                    loadingElement.textContent = 'Error loading provinces. Please try again.';
                });
        }
        
        function loadCities(provinceId) {
            const citySelect = document.getElementById('city');
            const loadingElement = document.getElementById('city-loading');
            
            loadingElement.textContent = 'Loading cities...';
            loadingElement.style.display = 'block';
            citySelect.disabled = true;
            
            fetch(`../customer/address/get_cities.php?province_id=${provinceId}`)
                .then(response => response.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    data.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.municipality_id;
                        option.textContent = city.municipality_name;
                        citySelect.appendChild(option);
                    });
                    
                    citySelect.disabled = false;
                    loadingElement.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error loading cities:', error);
                    loadingElement.textContent = 'Error loading cities. Please try again.';
                });
        }
        
        function loadBarangays(cityId) {
            const barangaySelect = document.getElementById('barangay');
            const loadingElement = document.getElementById('barangay-loading');
            
            loadingElement.textContent = 'Loading barangays...';
            loadingElement.style.display = 'block';
            barangaySelect.disabled = true;
            
            fetch(`../customer/address/get_barangays.php?city_id=${cityId}`)
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
                    loadingElement.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error loading barangays:', error);
                    loadingElement.textContent = 'Error loading barangays. Please try again.';
                });
        }
        
        function validateAllFields() {
            const isFirstNameValid = validateName(firstNameInput, 'first_name_error', 'First name');
            const isMiddleNameValid = validateName(middleNameInput, 'middle_name_error', 'Middle name');
            const isLastNameValid = validateName(lastNameInput, 'last_name_error', 'Last name');
            const isAgeValid = validateAge();
            const isPhoneValid = validatePhoneNumber();
            const isEmailValid = validateEmail();
            const isPasswordValid = validatePassword();
            const isConfirmPasswordValid = validateConfirmPassword();
            
            // Address validation
            const isRegionValid = validateDropdown('region', 'region_error', 'Region');
            const isProvinceValid = validateDropdown('province', 'province_error', 'Province');
            const isCityValid = validateDropdown('city', 'city_error', 'City/Municipality');
            const isBarangayValid = validateDropdown('barangay', 'barangay_error', 'Barangay');
            const isStreetValid = validateRequired('street_address', 'street_address_error', 'Street address');
            const isZipValid = validateZipCode();
            
            return isFirstNameValid && isMiddleNameValid && isLastNameValid && 
                   isAgeValid && isPhoneValid && isEmailValid && 
                   isPasswordValid && isConfirmPasswordValid &&
                   isRegionValid && isProvinceValid && isCityValid && isBarangayValid &&
                   isStreetValid && isZipValid;
        }
        
        function validateDropdown(id, errorId, fieldName) {
            const dropdown = document.getElementById(id);
            const errorElement = document.getElementById(errorId);
            
            if (!dropdown.value) {
                errorElement.textContent = `${fieldName} is required.`;
                dropdown.classList.add('input-error');
                return false;
            }
            
            errorElement.textContent = '';
            dropdown.classList.remove('input-error');
            return true;
        }
        
        function validateRequired(id, errorId, fieldName) {
            const input = document.getElementById(id);
            const errorElement = document.getElementById(errorId);
            
            if (!input.value.trim()) {
                errorElement.textContent = `${fieldName} is required.`;
                input.classList.add('input-error');
                return false;
            }
            
            errorElement.textContent = '';
            input.classList.remove('input-error');
            return true;
        }
        
        function validateZipCode() {
            const zipInput = document.getElementById('zip_code');
            const errorElement = document.getElementById('zip_code_error');
            const zipValue = zipInput.value.trim();
            
            if (!zipValue) {
                errorElement.textContent = 'Zip code is required.';
                zipInput.classList.add('input-error');
                return false;
            }
            
            // Check if zip code is numeric and has correct length (4 digits in Philippines)
            if (!/^\d{4}$/.test(zipValue)) {
                errorElement.textContent = 'Zip code must be 4 digits.';
                zipInput.classList.add('input-error');
                return false;
            }
            
            errorElement.textContent = '';
            zipInput.classList.remove('input-error');
            return true;
        }
        
        function handleFormSubmit(event) {
            event.preventDefault();
            
            // Validate all fields
            if (!validateAllFields()) {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please check all fields and correct the errors.',
                    confirmButtonColor: '#D69E2E'
                });
                return;
            }
            
            // Disable submit button and show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Creating account...';
            
            // Prepare form data
            const formData = new FormData(form);
            
            // Submit the form via AJAX
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error("Network error");
                return response.json();
            })
            .then(data => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Admin Account';
                
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#D69E2E'
                    }).then(() => {
                        // Redirect to admin list page
                        window.location.href = 'index.php';
                    });
                } else {
                    // Show error message
                    let errorMessage = 'Please correct the following errors:';
                    if (data.errors && data.errors.length > 0) {
                        errorMessage += '<ul class="mt-2 text-left">';
                        data.errors.forEach(error => {
                            errorMessage += `<li>• ${error}</li>`;
                        });
                        errorMessage += '</ul>';
                    } else {
                        errorMessage = data.message || 'An error occurred. Please try again.';
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMessage,
                        confirmButtonColor: '#D69E2E'
                    });
                }
            })
            .catch(error => {
                console.error("Fetch error:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to submit form. Please try again.',
                });
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('admin-form').addEventListener('submit', handleFormSubmit);
        });
    </script>
</body>
</html>