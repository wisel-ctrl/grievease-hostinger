<?php
session_start();
// Include database connection
require_once '../db_connect.php';
require_once '../addressDB.php';

// Check if user is locked out
if (isset($_SESSION['lockout_time'])) {
    $lockout_time = $_SESSION['lockout_time'];
    $current_time = time();
    $time_remaining = $lockout_time - $current_time;
    
    if ($time_remaining > 0) {
        // User is still locked out
        $minutes = floor($time_remaining / 60);
        $seconds = $time_remaining % 60;
        die(json_encode([
            'success' => false, 
            'message' => "Too many failed attempts. Please try again in $minutes minutes and $seconds seconds."
        ]));
    } else {
        // Lockout period has ended, reset attempts
        unset($_SESSION['attempts']);
        unset($_SESSION['lockout_time']);
    }
}

// Initialize error and success messages
$errors = [];
$success = false;

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email format
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to check if email already exists
function email_exists($conn, $email) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    return $count > 0;
}

// Function to validate password strength
function validate_password($password) {
    // At least 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // Check for at least one special character
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        return false;
    }
    
    return true;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form inputs
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $middle_name = !empty($_POST['middle_name']) ? sanitize_input($_POST['middle_name']) : null;
    $suffix = !empty($_POST['suffix']) ? sanitize_input($_POST['suffix']) : null;
    $birthdate = sanitize_input($_POST['birthdate']);
    $phone_number = sanitize_input($_POST['phone_number']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = 69; // Set as Super Admin (0)
    $region = !empty($_POST['region']) ? sanitize_input($_POST['region']) : null;
    $province = !empty($_POST['province']) ? sanitize_input($_POST['province']) : null;
    $city = !empty($_POST['city']) ? sanitize_input($_POST['city']) : null;
    $barangay = !empty($_POST['barangay']) ? sanitize_input($_POST['barangay']) : null;
    $street_address = !empty($_POST['street_address']) ? sanitize_input($_POST['street_address']) : null;
    $zip_code = !empty($_POST['zip_code']) ? sanitize_input($_POST['zip_code']) : null;
    
     $region_name = null;
    $province_name = null;
    $city_name = null;
    $barangay_name = null;
    
    if ($region) {
        $stmt = $addressDB->prepare("SELECT region_name FROM table_region WHERE region_id = ?");
        $stmt->bind_param("i", $region);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $region_name = $row['region_name'];
        }
        $stmt->close();
    }
    
    if ($province) {
        $stmt = $addressDB->prepare("SELECT province_name FROM table_province WHERE province_id = ?");
        $stmt->bind_param("i", $province);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $province_name = $row['province_name'];
        }
        $stmt->close();
    }
    
    if ($city) {
        $stmt = $addressDB->prepare("SELECT municipality_name FROM table_municipality WHERE municipality_id = ?");
        $stmt->bind_param("i", $city);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $city_name = $row['municipality_name'];
        }
        $stmt->close();
    }
    
    if ($barangay) {
        $stmt = $addressDB->prepare("SELECT barangay_name FROM table_barangay WHERE barangay_id = ?");
        $stmt->bind_param("i", $barangay);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $barangay_name = $row['barangay_name'];
        }
        $stmt->close();
    }
    
    
    // Validate required fields
    if (empty($first_name)) {
        $errors[] = "First name is required.";
    } elseif (!preg_match("/^[A-Za-z\s\-']{2,50}$/", $first_name)) {
        $errors[] = "First name should contain only letters, spaces, hyphens, or apostrophes (2-50 characters).";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required.";
    } elseif (!preg_match("/^[A-Za-z\s\-']{2,50}$/", $last_name)) {
        $errors[] = "Last name should contain only letters, spaces, hyphens, or apostrophes (2-50 characters).";
    }
    
    // Validate birthdate (must be at least 18 years old)
    if (empty($birthdate)) {
        $errors[] = "Birthdate is required.";
    } else {
        $birth_date = new DateTime($birthdate);
        $today = new DateTime();
        $diff = $today->diff($birth_date);
        
        if ($diff->y < 18) {
            $errors[] = "You must be at least 18 years old to register.";
        }
    }

    // Validate phone number
    if (empty($phone_number)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^(\+?\d{1,3}[\s-]?)?\(?(?:\d{3})\)?[\s.-]?\d{3}[\s.-]?\d{4}$/', $phone_number)) {
        $errors[] = "Please enter a valid phone number.";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!validate_email($email)) {
        $errors[] = "Please enter a valid email address.";
    } elseif (email_exists($conn, $email)) {
        $errors[] = "Email already exists. Please use a different email.";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (!validate_password($password)) {
        $errors[] = "Password must be at least 8 characters with at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }

    // Validate password confirmation
    if (empty($confirm_password)) {
        $errors[] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Validate zip code if provided
    if (!empty($zip_code) && !preg_match('/^\d{4,10}$/', $zip_code)) {
        $errors[] = "Please enter a valid zip code.";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and execute the SQL query - now using the names instead of IDs
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, middle_name, suffix, birthdate, phone_number, email, password, user_type, region, province, city, barangay, street_address, zip_code, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        
        $stmt->bind_param("sssssssisssssss", 
            $first_name, 
            $last_name, 
            $middle_name, 
            $suffix, 
            $birthdate, 
            $phone_number, 
            $email, 
            $hashed_password, 
            $user_type, 
            $region_name,  // Using name instead of ID
            $province_name, // Using name instead of ID
            $city_name,    // Using name instead of ID
            $barangay_name, // Using name instead of ID
            $street_address, 
            $zip_code
        );
    
        
        if ($stmt->execute()) {
            // Return success response as JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Registration successful!']);
            exit();
        } else {
            // Return error response as JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again later.']);
            exit();
        }
    } else {
        // Return validation errors as JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600;700&family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .password-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .password-modal {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        
        .password-input {
            letter-spacing: 0.5em;
            font-size: 1.5rem;
            text-align: center;
            padding: 0.5rem;
            width: 100%;
            margin: 1rem 0;
        }
        
        .hidden-content {
            display: none;
        }
        /* Add to your existing styles */
        .lockout-message {
            color: #E53E3E;
            font-weight: bold;
            margin-top: 1rem;
        }
        .attempts-count {
            color: #E53E3E;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
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
                        'navy': '#2D2B30',
                        'cream': '#F9F6F0',
                        'dark': '#1E1E1E',
                        'gold': '#C9A773',
                        'darkgold': '#B08D50',
                        'primary': '#2D2B30',
                        'primary-foreground': '#FFFFFF',
                        'secondary': '#F1F5F9',
                        'secondary-foreground': '#1E1E1E',
                        'border': '#E4E9F0',
                        'input-border': '#D3D8E1',
                        'error': '#E53E3E',
                        'success': '#38A169',
                    },
                    boxShadow: {
                        'input': '0 1px 2px rgba(0, 0, 0, 0.05)',
                        'card': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    }
                }
            }
        };
    </script>
</head>

    <!-- Password Modal -->
    <div id="passwordModal" class="password-modal-backdrop">
        <div class="password-modal">
            <h2 class="text-2xl font-cinzel text-navy mb-4">Super Admin Access</h2>
            <p class="text-gray-700 mb-6">Enter the 6-digit security code to continue</p>
            <input type="password" id="securityCode" class="password-input border border-input-border rounded-md" maxlength="6" inputmode="numeric" pattern="\d{6}" placeholder="------">
            <p id="passwordError" class="text-error text-sm mt-2 hidden">Incorrect code. Please try again.</p>
            <div id="attemptsCount" class="attempts-count hidden"></div>
            <div id="lockoutMessage" class="lockout-message hidden"></div>
            <button id="submitPassword" class="w-full bg-navy hover:bg-opacity-90 text-white font-medium py-2 px-4 rounded-md mt-4">
                Verify
            </button>
        </div>
    </div>
    
    <!-- Main Content (hidden initially) -->
    <div id="mainContent" class="hidden-content">


<body class="bg-cream min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-4xl bg-white rounded-lg shadow-card">
        <!-- Header -->
        <div class="bg-navy rounded-t-lg p-6 text-center">
            <h1 class="text-3xl md:text-4xl font-playfair text-white font-bold">Super Admin Registration</h1>
            <p class="text-gold mt-2 font-inter">Create your super admin account</p>
        </div>

        <!-- Form -->
        <form id="registrationForm" class="p-6" onsubmit="return false;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information Section -->
                <div class="md:col-span-2 mb-4">
                    <h2 class="text-2xl font-cinzel text-navy border-b border-gold pb-2 mb-4">Personal Information</h2>
                </div>

                <!-- First Name -->
                <div class="mb-4">
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name*</label>
                    <input type="text" id="first_name" name="first_name" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" required>
                    <p class="text-error text-xs mt-1 hidden" id="first_name_error"></p>
                </div>

                <!-- Last Name -->
                <div class="mb-4">
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name*</label>
                    <input type="text" id="last_name" name="last_name" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" required>
                    <p class="text-error text-xs mt-1 hidden" id="last_name_error"></p>
                </div>

                <!-- Middle Name -->
                <div class="mb-4">
                    <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name (Optional)</label>
                    <input type="text" id="middle_name" name="middle_name" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent">
                </div>

                <!-- Suffix -->
                <div class="mb-4">
                    <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix (Optional)</label>
                    <select id="suffix" name="suffix" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent">
                        <option value="">None</option>
                        <option value="Jr.">Jr.</option>
                        <option value="Sr.">Sr.</option>
                        <option value="II">II</option>
                        <option value="III">III</option>
                        <option value="IV">IV</option>
                    </select>
                </div>

                <!-- Birthdate -->
                <div class="mb-4">
                    <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1">Birthdate* (Must be at least 18 years old)</label>
                    <input type="date" id="birthdate" name="birthdate" max="" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" required>
                    <p class="text-error text-xs mt-1 hidden" id="birthdate_error"></p>
                </div>

                <!-- Phone Number -->
                <div class="mb-4">
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number*</label>
                    <input type="tel" id="phone_number" name="phone_number" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" placeholder="e.g., +639123456789" required>
                    <p class="text-error text-xs mt-1 hidden" id="phone_number_error"></p>
                </div>

                <!-- Contact Information Section -->
                <div class="md:col-span-2 mb-4">
                    <h2 class="text-2xl font-cinzel text-navy border-b border-gold pb-2 mb-4">Account Information</h2>
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address*</label>
                    <input type="email" id="email" name="email" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" required>
                    <p class="text-error text-xs mt-1 hidden" id="email_error"></p>
                </div>

                <!-- User Type (Hidden - Since this is for Super Admin) -->
                <input type="hidden" id="user_type" name="user_type" value="0">

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password*</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" required>
                        <button type="button" id="togglePassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-gray-500 text-xs mt-1">Password must be at least 8 characters with uppercase, lowercase, number and special character</p>
                    <p class="text-error text-xs mt-1 hidden" id="password_error"></p>
                </div>

                <!-- Confirm Password -->
                <div class="mb-4">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password*</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" required>
                        <button type="button" id="toggleConfirmPassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-error text-xs mt-1 hidden" id="confirm_password_error"></p>
                </div>

                <!-- Address Information Section -->
                <div class="md:col-span-2 mb-4">
                    <h2 class="text-2xl font-cinzel text-navy border-b border-gold pb-2 mb-4">Address Information</h2>
                </div>

                <!-- Region -->
                <div class="mb-4">
                    <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region*</label>
                    <select id="region" name="region" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" required>
                        <option value="">Select Region</option>
                    </select>
                    <p class="text-error text-xs mt-1 hidden" id="region_error"></p>
                </div>
                
                <!-- Province -->
                <div class="mb-4">
                    <label for="province" class="block text-sm font-medium text-gray-700 mb-1">Province*</label>
                    <select id="province" name="province" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" disabled required>
                        <option value="">Select Province</option>
                    </select>
                    <p class="text-error text-xs mt-1 hidden" id="province_error"></p>
                </div>
                
                <!-- City -->
                <div class="mb-4">
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City/Municipality*</label>
                    <select id="city" name="city" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" disabled required>
                        <option value="">Select City/Municipality</option>
                    </select>
                    <p class="text-error text-xs mt-1 hidden" id="city_error"></p>
                </div>
                
                <!-- Barangay -->
                <div class="mb-4">
                    <label for="barangay" class="block text-sm font-medium text-gray-700 mb-1">Barangay*</label>
                    <select id="barangay" name="barangay" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent" disabled required>
                        <option value="">Select Barangay</option>
                    </select>
                    <p class="text-error text-xs mt-1 hidden" id="barangay_error"></p>
                </div>

                <!-- Street Address -->
                <div class="mb-4">
                    <label for="street_address" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                    <input type="text" id="street_address" name="street_address" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent">
                </div>

                <!-- Zip Code -->
                <div class="mb-4">
                    <label for="zip_code" class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label>
                    <input type="text" id="zip_code" name="zip_code" class="w-full p-3 border border-input-border rounded-md shadow-input focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent">
                    <p class="text-error text-xs mt-1 hidden" id="zip_code_error"></p>
                </div>

            </div>

            <div class="md:col-span-2 mt-4">
                <button type="submit" class="w-full bg-navy hover:bg-opacity-90 text-white font-medium py-3 px-4 rounded-md transition duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-gold focus:ring-offset-2">
                    Register Super Admin
                </button>
            </div>
        </form>
    </div>
    
     </div>

    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            const passwordModal = document.getElementById('passwordModal');
            const mainContent = document.getElementById('mainContent');
            const securityCodeInput = document.getElementById('securityCode');
            const submitPasswordBtn = document.getElementById('submitPassword');
            const passwordError = document.getElementById('passwordError');
            const attemptsCount = document.getElementById('attemptsCount');
            const lockoutMessage = document.getElementById('lockoutMessage');
            
            // Correct password (6-digit)
            const CORRECT_PASSWORD = '090909';
            const MAX_ATTEMPTS = 3;
            const LOCKOUT_DURATION = 300; // 5 minutes in seconds
            
            // Initialize attempts counter if not set
            if (!sessionStorage.getItem('attempts')) {
                sessionStorage.setItem('attempts', 0);
            }
            
            // Check if user is locked out
            const lockoutUntil = sessionStorage.getItem('lockout_until');
            if (lockoutUntil && new Date().getTime() < lockoutUntil) {
                // User is locked out
                showLockoutMessage(lockoutUntil);
                return;
            } else if (lockoutUntil) {
                // Lockout period has ended, reset attempts
                sessionStorage.removeItem('lockout_until');
                sessionStorage.setItem('attempts', 0);
            }
            
            // Show remaining attempts
            updateAttemptsDisplay();
            
            // Focus on the input field when modal appears
            securityCodeInput.focus();
            
            // Handle password submission
            submitPasswordBtn.addEventListener('click', function() {
                const enteredPassword = securityCodeInput.value.trim();
                let attempts = parseInt(sessionStorage.getItem('attempts')) || 0;
                
                if (enteredPassword === CORRECT_PASSWORD) {
                    // Correct password - reset attempts and proceed
                    sessionStorage.setItem('attempts', 0);
                    
                    // Hide modal and show content
                    passwordModal.style.display = 'none';
                    mainContent.style.display = 'block';
                    
                    // Now initialize the rest of your page functionality
                    initializePage();
                } else {
                    // Increment attempts
                    attempts++;
                    sessionStorage.setItem('attempts', attempts);
                    
                    // Show error and clear input
                    passwordError.classList.remove('hidden');
                    securityCodeInput.value = '';
                    securityCodeInput.focus();
                    
                    // Update attempts display
                    updateAttemptsDisplay();
                    
                    // Shake animation for wrong password
                    passwordModal.querySelector('.password-modal').animate([
                        { transform: 'translateX(0)' },
                        { transform: 'translateX(-10px)' },
                        { transform: 'translateX(10px)' },
                        { transform: 'translateX(-10px)' },
                        { transform: 'translateX(10px)' },
                        { transform: 'translateX(-10px)' },
                        { transform: 'translateX(0)' }
                    ], {
                        duration: 400
                    });
                    
                    // Check if max attempts reached
                    if (attempts >= MAX_ATTEMPTS) {
                        // Set lockout time (current time + lockout duration)
                        const lockoutTime = new Date().getTime() + (LOCKOUT_DURATION * 1000);
                        sessionStorage.setItem('lockout_until', lockoutTime);
                        
                        // Show lockout message
                        showLockoutMessage(lockoutTime);
                        
                        // Disable the input and button
                        securityCodeInput.disabled = true;
                        submitPasswordBtn.disabled = true;
                    }
                }
            });
            
            function updateAttemptsDisplay() {
                const attempts = parseInt(sessionStorage.getItem('attempts')) || 0;
                const remainingAttempts = MAX_ATTEMPTS - attempts;
                
                if (attempts > 0) {
                    attemptsCount.textContent = `Attempts remaining: ${remainingAttempts}`;
                    attemptsCount.classList.remove('hidden');
                } else {
                    attemptsCount.classList.add('hidden');
                }
            }
            
            function showLockoutMessage(lockoutUntil) {
                // Calculate remaining time
                const now = new Date().getTime();
                const remainingTime = Math.ceil((lockoutUntil - now) / 1000);
                const minutes = Math.floor(remainingTime / 60);
                const seconds = remainingTime % 60;
                
                // Show message
                lockoutMessage.textContent = `Too many failed attempts. Please try again in ${minutes}m ${seconds}s.`;
                lockoutMessage.classList.remove('hidden');
                
                // Disable input and button
                securityCodeInput.disabled = true;
                submitPasswordBtn.disabled = true;
                
                // Update countdown every second
                const countdownInterval = setInterval(() => {
                    const now = new Date().getTime();
                    const remainingTime = Math.ceil((lockoutUntil - now) / 1000);
                    
                    if (remainingTime <= 0) {
                        clearInterval(countdownInterval);
                        lockoutMessage.classList.add('hidden');
                        securityCodeInput.disabled = false;
                        submitPasswordBtn.disabled = false;
                        sessionStorage.removeItem('lockout_until');
                        sessionStorage.setItem('attempts', 0);
                        updateAttemptsDisplay();
                    } else {
                        const minutes = Math.floor(remainingTime / 60);
                        const seconds = remainingTime % 60;
                        lockoutMessage.textContent = `Too many failed attempts. Please try again in ${minutes}m ${seconds}s.`;
                    }
                }, 1000);
            }
            
            // Also allow Enter key to submit
            securityCodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    submitPasswordBtn.click();
                }
            });
            
            // Prevent copy/paste in password field
            securityCodeInput.addEventListener('paste', function(e) {
                e.preventDefault();
            });
            
            // Prevent non-numeric input
            securityCodeInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });

  function initializePage() {
            // This function will contain all your existing DOMContentLoaded code
            // Move all your existing initialization code here
            
            // Set max date for birthdate (18 years ago from today)
            const today = new Date();
            const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            document.getElementById('birthdate').max = eighteenYearsAgo.toISOString().split('T')[0];

            // Toggle password visibility
            document.getElementById('togglePassword').addEventListener('click', function() {
                const passwordInput = document.getElementById('password');
                togglePasswordVisibility(passwordInput, this);
            });

            document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
                const confirmPasswordInput = document.getElementById('confirm_password');
                togglePasswordVisibility(confirmPasswordInput, this);
            });

            function togglePasswordVisibility(input, button) {
                if (input.type === 'password') {
                    input.type = 'text';
                    button.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    `;
                } else {
                    input.type = 'password';
                    button.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    `;
                }
            }

            // Form validation
            const registrationForm = document.getElementById('registrationForm');
            registrationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                let isValid = true;

                // Reset all error messages
                const errorElements = document.querySelectorAll('[id$="_error"]');
                errorElements.forEach(element => {
                    element.classList.add('hidden');
                    element.textContent = '';
                });

                // Validate First Name
                const firstName = document.getElementById('first_name').value.trim();
                if (firstName === '') {
                    showError('first_name_error', 'First name is required');
                    isValid = false;
                } else if (!/^[A-Za-z\s\-']{2,50}$/.test(firstName)) {
                    showError('first_name_error', 'First name should contain only letters, spaces, hyphens, or apostrophes (2-50 characters)');
                    isValid = false;
                }

                // Validate Last Name
                const lastName = document.getElementById('last_name').value.trim();
                if (lastName === '') {
                    showError('last_name_error', 'Last name is required');
                    isValid = false;
                } else if (!/^[A-Za-z\s\-']{2,50}$/.test(lastName)) {
                    showError('last_name_error', 'Last name should contain only letters, spaces, hyphens, or apostrophes (2-50 characters)');
                    isValid = false;
                }

                // Validate Birthdate
                const birthdate = new Date(document.getElementById('birthdate').value);
                const today = new Date();
                const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
                
                if (isNaN(birthdate.getTime())) {
                    showError('birthdate_error', 'Birthdate is required');
                    isValid = false;
                } else if (birthdate > eighteenYearsAgo) {
                    showError('birthdate_error', 'You must be at least 18 years old to register');
                    isValid = false;
                }

                // Validate Phone Number
                const phoneNumber = document.getElementById('phone_number').value.trim();
                if (phoneNumber === '') {
                    showError('phone_number_error', 'Phone number is required');
                    isValid = false;
                } else if (!/^(\+?\d{1,3}[\s-]?)?\(?(?:\d{3})\)?[\s.-]?\d{3}[\s.-]?\d{4}$/.test(phoneNumber)) {
                    showError('phone_number_error', 'Please enter a valid phone number');
                    isValid = false;
                }

                // Validate Email
                const email = document.getElementById('email').value.trim();
                if (email === '') {
                    showError('email_error', 'Email is required');
                    isValid = false;
                } else if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
                    showError('email_error', 'Please enter a valid email address');
                    isValid = false;
                }

                // Validate Password
                const password = document.getElementById('password').value;
                if (password === '') {
                    showError('password_error', 'Password is required');
                    isValid = false;
                } else if (password.length < 8) {
                    showError('password_error', 'Password must be at least 8 characters long');
                    isValid = false;
                } else if (!/[A-Z]/.test(password)) {
                    showError('password_error', 'Password must contain at least one uppercase letter');
                    isValid = false;
                } else if (!/[a-z]/.test(password)) {
                    showError('password_error', 'Password must contain at least one lowercase letter');
                    isValid = false;
                } else if (!/[0-9]/.test(password)) {
                    showError('password_error', 'Password must contain at least one number');
                    isValid = false;
                } else if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
                    showError('password_error', 'Password must contain at least one special character');
                    isValid = false;
                }

                // Validate Confirm Password
                const confirmPassword = document.getElementById('confirm_password').value;
                if (confirmPassword === '') {
                    showError('confirm_password_error', 'Please confirm your password');
                    isValid = false;
                } else if (confirmPassword !== password) {
                    showError('confirm_password_error', 'Passwords do not match');
                    isValid = false;
                }

                // Validate Zip Code (if provided)
                const zipCode = document.getElementById('zip_code').value.trim();
                if (zipCode !== '' && !/^\d{4,10}$/.test(zipCode)) {
                    showError('zip_code_error', 'Please enter a valid zip code');
                    isValid = false;
                }
                
                if (isValid) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Processing',
                        html: 'Please wait while we register your account...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit form via AJAX
                    const formData = new FormData(registrationForm);
                    
                    fetch('register.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                html: data.message,
                                confirmButtonColor: '#2D2B30',
                            }).then(() => {
                                // Optionally reset the form
                                registrationForm.reset();
                                // Reset address dropdowns
                                document.getElementById('province').innerHTML = '<option value="">Select Province</option>';
                                document.getElementById('city').innerHTML = '<option value="">Select City/Municipality</option>';
                                document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';
                                document.getElementById('province').disabled = true;
                                document.getElementById('city').disabled = true;
                                document.getElementById('barangay').disabled = true;
                                // Reload regions
                                document.getElementById('region').innerHTML = '<option value="">Select Region</option>';
                                fetchRegions();
                            });
                        } else {
                            // Show error message
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                html: data.message,
                                confirmButtonColor: '#2D2B30',
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An unexpected error occurred. Please try again.',
                            confirmButtonColor: '#2D2B30',
                        });
                        console.error('Error:', error);
                    });
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });

            function showError(elementId, message) {
                const errorElement = document.getElementById(elementId);
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
            }
            
            // Address dropdown functionality
            // Load regions on page load
            fetchRegions();
        
            // Region change event
            document.getElementById('region').addEventListener('change', function() {
                const regionId = this.value;
                const provinceDropdown = document.getElementById('province');
                const cityDropdown = document.getElementById('city');
                const barangayDropdown = document.getElementById('barangay');
                
                // Reset and disable dependent dropdowns
                provinceDropdown.innerHTML = '<option value="">Select Province</option>';
                provinceDropdown.disabled = !regionId;
                
                cityDropdown.innerHTML = '<option value="">Select City/Municipality</option>';
                cityDropdown.disabled = true;
                
                barangayDropdown.innerHTML = '<option value="">Select Barangay</option>';
                barangayDropdown.disabled = true;
                
                if (regionId) {
                    fetchProvinces(regionId);
                }
            });
        
            // Province change event
            document.getElementById('province').addEventListener('change', function() {
                const provinceId = this.value;
                const cityDropdown = document.getElementById('city');
                const barangayDropdown = document.getElementById('barangay');
                
                // Reset and disable dependent dropdowns
                cityDropdown.innerHTML = '<option value="">Select City/Municipality</option>';
                cityDropdown.disabled = !provinceId;
                
                barangayDropdown.innerHTML = '<option value="">Select Barangay</option>';
                barangayDropdown.disabled = true;
                
                if (provinceId) {
                    fetchCities(provinceId);
                }
            });
        
            // City change event
            document.getElementById('city').addEventListener('change', function() {
                const cityId = this.value;
                const barangayDropdown = document.getElementById('barangay');
                
                // Reset and disable dependent dropdown
                barangayDropdown.innerHTML = '<option value="">Select Barangay</option>';
                barangayDropdown.disabled = !cityId;
                
                if (cityId) {
                    fetchBarangays(cityId);
                }
            });
            
            // Function to fetch regions
            function fetchRegions() {
                fetch('../customer/address/get_regions.php')
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        const regionDropdown = document.getElementById('region');
                        data.forEach(function(region) {
                            const option = document.createElement('option');
                            option.value = region.region_id;
                            option.textContent = region.region_name;
                            regionDropdown.appendChild(option);
                        });
                    })
                    .catch(function(error) {
                        console.error('Error loading regions:', error);
                    });
            }
            
            // Function to fetch provinces
            function fetchProvinces(regionId) {
                fetch('../customer/address/get_provinces.php?region_id=' + regionId)
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        const provinceDropdown = document.getElementById('province');
                        data.forEach(function(province) {
                            const option = document.createElement('option');
                            option.value = province.province_id;
                            option.textContent = province.province_name;
                            provinceDropdown.appendChild(option);
                        });
                        provinceDropdown.disabled = false;
                    })
                    .catch(function(error) {
                        console.error('Error loading provinces:', error);
                    });
            }
            
            // Function to fetch cities
            function fetchCities(provinceId) {
                fetch('../customer/address/get_cities.php?province_id=' + provinceId)
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        const cityDropdown = document.getElementById('city');
                        data.forEach(function(city) {
                            const option = document.createElement('option');
                            option.value = city.municipality_id;
                            option.textContent = city.municipality_name;
                            cityDropdown.appendChild(option);
                        });
                        cityDropdown.disabled = false;
                    })
                    .catch(function(error) {
                        console.error('Error loading cities:', error);
                    });
            }
            
            // Function to fetch barangays
            function fetchBarangays(cityId) {
                fetch('../customer/address/get_barangays.php?city_id=' + cityId)
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        const barangayDropdown = document.getElementById('barangay');
                        data.forEach(function(barangay) {
                            const option = document.createElement('option');
                            option.value = barangay.barangay_id;
                            option.textContent = barangay.barangay_name;
                            barangayDropdown.appendChild(option);
                        });
                        barangayDropdown.disabled = false;
                    })
                    .catch(function(error) {
                        console.error('Error loading barangays:', error);
                    });
            }
        }
    </script>
</body>
</html>