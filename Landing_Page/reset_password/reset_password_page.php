<?php
session_start();
require_once '../../db_connect.php';

date_default_timezone_set('Asia/Manila');

// Check if token is present in the URL
$token = isset($_GET['token']) ? urldecode($_GET['token']) : null;

// Validate token
if (!$token) {
    die("No reset token provided.");
}

// Check token validity
$stmt = $conn->prepare("SELECT id, email, reset_token, reset_token_expiry 
                        FROM users 
                        WHERE reset_token = ? 
                        AND reset_token_expiry >= NOW()");  // Changed to >= for inclusive check
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // For debugging, let's add more detailed error checking
    // In your existing code
    $stmt = $conn->prepare("SELECT id, email, reset_token, reset_token_expiry 
        FROM users 
        WHERE reset_token = ?");  // Remove time comparison from SQL
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            die("Invalid or expired token.");
        }
            $user = $result->fetch_assoc();

            // Manually check token expiration using PHP's timezone
            $current_time = date('Y-m-d H:i:s');
            $token_expiry = $user['reset_token_expiry'];
            
            if (strtotime($current_time) > strtotime($token_expiry)) {
                die("Token has expired.");
            }
    }


$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Reset Password</title>
    <link rel="icon" type="image/png" href="../Landing_images/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Serif&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="../../tailwind.js"></script>
</head>
<body class="font-hedvig text-navy antialiased bg-black">
    <!-- Remove the semi-transparent overlay and background image divs -->
    
    <header class="w-full max-w-7xl mx-auto px-4 py-5">
        <div class="flex items-center">
            <img src="../Landing_images/logo.png" alt="GrievEase Logo" class="h-12 w-auto">
            <span class="text-yellow-600 text-2xl ml-3">
                <a href="../../index.php" class="text-yellow-600 text-3xl">GrievEase</a>
            </span>
        </div>
    </header>

    <main class="flex items-center justify-center px-4 min-h-[calc(100vh-96px)]">
        <div class="w-full max-w-md mx-auto">
            <div class="bg-black bg-opacity-25 backdrop-blur-md rounded-xl p-6 shadow-card">
                <div class="text-center mb-4">
                    <h2 class="text-xl font-hedvig text-white">Reset Your Password</h2>
                </div>
                
                <form id="resetPasswordForm" class="space-y-3">
                    <!-- Hidden input for token -->
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <!-- New Password -->
                    <div>
                        <label for="new_password" class="block text-xs font-medium text-white mb-1">New Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                required 
                                class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200 pr-8"
                                placeholder="Enter new password"
                            >
                            <span 
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer text-yellow-600 hover:text-navy transition text-sm" 
                                id="toggleNewPassword"
                            >
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <p id="password-strength" class="text-xs mt-1 text-yellow-600">Password strength: Weak</p>
                    </div>
                    
                    <!-- Confirm New Password -->
                    <div>
                        <label for="confirm_password" class="block text-xs font-medium text-white mb-1">Confirm New Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required 
                                class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200 pr-8"
                                placeholder="Confirm new password"
                            >
                            <span 
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer text-yellow-600 hover:text-navy transition text-sm" 
                                id="toggleConfirmPassword"
                            >
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-yellow-600 to-darkgold text-white py-2 px-4 rounded-lg text-sm font-medium shadow-lg hover:shadow-xl transition-all duration-300 mt-4 flex items-center justify-center"
                    >
                        Reset Password
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-xs font-medium text-white">
                        Remember your password? <a href="../login.php" class="text-yellow-600 hover:underline">Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('password-strength');
            const toggleNewPassword = document.getElementById('toggleNewPassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const resetForm = document.getElementById('resetPasswordForm');

            // Password visibility toggle
            function setupPasswordToggle(passwordInput, toggleButton) {
                toggleButton.addEventListener('click', function() {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        passwordInput.type = 'password';
                        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                });
            }

            setupPasswordToggle(newPasswordInput, toggleNewPassword);
            setupPasswordToggle(confirmPasswordInput, toggleConfirmPassword);

            // Password strength checker
            function checkPasswordStrength(password) {
                const strengthChecks = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    specialChar: /[!@#$%^&*(),.?":{}|<>]/.test(password)
                };

                let strength = Object.values(strengthChecks).filter(Boolean).length;
                
                passwordStrength.textContent = strength <= 2 ? 'Password strength: Weak' :
                                               strength <= 3 ? 'Password strength: Medium' :
                                               'Password strength: Strong';
            }

            newPasswordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });

            // Prevent spaces in password inputs
            newPasswordInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/\s/g, '');
            });

            confirmPasswordInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/\s/g, '');
            });

            // Prevent spacebar key in password inputs
            newPasswordInput.addEventListener('keydown', function(e) {
                if (e.key === ' ') {
                    e.preventDefault();
                }
            });

            confirmPasswordInput.addEventListener('keydown', function(e) {
                if (e.key === ' ') {
                    e.preventDefault();
                }
            });

            // Form submission
            resetForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                // Client-side validations
                if (!newPassword || !confirmPassword) {
                    swal({
                        title: "Error",
                        text: "Please enter both passwords",
                        icon: "error",
                        button: "Try Again",
                    });
                    return;
                }

                if (newPassword !== confirmPassword) {
                    swal({
                        title: "Error",
                        text: "Passwords do not match",
                        icon: "error",
                        button: "Try Again",
                    });
                    return;
                }

                // Check password strength
                if (newPassword.length < 8) {
                    swal({
                        title: "Weak Password",
                        text: "Password must be at least 8 characters long",
                        icon: "error",
                        button: "Try Again",
                    });
                    return;
                }

                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Resetting...';
                submitButton.disabled = true;

                // Create FormData
                const formData = new FormData(this);

                // Send AJAX request
                fetch('process_password_reset.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button state
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;

                    if (data.status === 'success') {
                        swal({
                            title: "Success",
                            text: "Password reset successfully",
                            icon: "success",
                            button: "Login"
                        }).then(() => {
                            window.location.href = '../login.php';
                        });
                    } else {
                        swal({
                            title: "Error",
                            text: data.message || "An error occurred while resetting your password",
                            icon: "error",
                            button: "Try Again"
                        });
                    }
                })
                .catch(error => {
                    // Reset button state
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;

                    swal({
                        title: "Error",
                        text: "An unexpected error occurred",
                        icon: "error",
                        button: "OK"
                    });
                });
            });
        });

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
    </script>

    <!-- Add these styles to your head section -->
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes scaleIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        @keyframes flame {
            0%, 100% { transform: translateX(-50%) scale(1) rotate(-2deg); }
            50% { transform: translateX(-50%) scale(1.1) rotate(2deg); }
        }
        
        .animate-flame {
            animation: flame 2s ease-in-out infinite;
        }
    </style>
</body>
</html>