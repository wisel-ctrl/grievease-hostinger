<?php
session_start();

// If user is already logged in, redirect them to appropriate page
if (isset($_SESSION['user_id'])) {
    // Determine redirect based on user_type
    switch ($_SESSION['user_type']) {
        case 1:
            header("Location: ../admin/admin_index.php");
            break;
        case 2:
            header("Location: ../employee/index.php");
            break;
        case 3:
            header("Location: ../customer/index.php");
            break;
        default:
            // If somehow user_type is invalid, destroy session and reload login
            session_destroy();
    }
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GrievEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Serif&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="../tailwind.js"></script>
</head>
<body class="font-hedvig text-navy antialiased">
    <!-- Semi-transparent overlay -->
    <div class="fixed inset-0 z-[-1] bg-black bg-opacity-60"></div>
    
    <!-- Background image -->
    <div class="fixed inset-0 z-[-2] bg-[url('Landing_images/black-bg-image.jpg')] bg-cover bg-center bg-no-repeat"></div>
    <header class="w-full max-w-7xl mx-auto px-4 py-5">
    <div class="flex items-center">
            <img src="Landing_images/logo.png" alt="GrievEase Logo" class="h-12 w-auto">
            <span class="text-yellow-600 text-2xl ml-3">
                <a href="../index.php" class="text-yellow-600 text-3xl">GrievEase</a>
            </span>
        </div>
    </header>

    <main class="flex items-center justify-center px-4 min-h-[calc(100vh-96px)]">
    <div class="w-full max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-12">
        <!-- Left Side (Hero Text) - Hidden on mobile -->
        <div class="hidden md:block w-full md:w-1/2 max-w-xl">
            <h1 class="font-alexbrush text-5xl leading-tight text-white text-shadow-lg mb-6">
                Mula noon,
                hanggang ngayon.<br>
                <span class="text-yellow-600">A funeral service
                with a Heart...</span>
            </h1>
        </div>
        
        <!-- Right Side (Registration Form) - Full width on mobile -->
<div class="w-full md:w-1/2 max-w-md mx-auto">
    <div class="bg-black bg-opacity-25 backdrop-blur-md rounded-xl p-6 shadow-card">
        <div class="text-center mb-4">
            <h2 class="text-xl font-hedvig text-white">Member Login</h2>
        </div>
        
        <form id="loginForm" class="space-y-3">
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-medium text-white mb-1">Email Address</label>
                <div class="relative">
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200 pr-8"
                    >
                    <span class="absolute right-2 top-1/2 transform -translate-y-1/2 text-yellow-600 text-sm">
                        <i class="fas fa-envelope"></i>
                    </span>
                </div>
            </div>
            
            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-medium text-white mb-1">Password</label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200 pr-8"
                    >
                    <span 
                        class="absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer text-yellow-600 hover:text-navy transition text-sm" 
                        id="passwordToggle"
                    >
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <!-- Forgot Password Link -->
                <div class="text-right mt-1">
                    <a href="#" class="text-xs text-yellow-600 hover:underline">Forgot Password?</a>
                </div>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full bg-gradient-to-r from-yellow-600 to-darkgold text-white py-2 px-4 rounded-lg text-sm font-medium shadow-lg hover:shadow-xl transition-all duration-300 mt-4 flex items-center justify-center"
            >Login </button>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-xs font-medium text-white">
                Need an Account? <a href="register.php" class="text-yellow-600 hover:underline">Get Started</a>
            </p>
        </div>
    </div>
</div>
    </div>
</main>

<!-- Forgot Password Modal -->
<div id="forgotPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 shadow-lg max-w-md w-full mx-4">
        <div class="text-center mb-4">
            <h3 class="text-xl font-bold font-hedvig">Reset Your Password</h3>
            <p class="text-gray-600 mt-1">Enter your email address and we'll send you a link to reset your password.</p>
        </div>
        
        <div id="resetStep1" class="space-y-4">
            <div>
                <label for="resetEmail" class="font-bold block mb-1 text-sm font-hedvig">Email Address</label>
                <input 
                    type="email" 
                    id="resetEmail" 
                    name="resetEmail" 
                    placeholder="Enter your email address" 
                    class="w-full py-2 px-3 border border-border rounded-radius transition-colors duration-300 h-10 font-hedvig"
                    required
                >
            </div>
            
            <button 
                type="button" 
                id="sendResetLink"
                class="font-hedvig bg-primary text-white py-2 px-4 border-none rounded-lg text-base font-semibold cursor-pointer shadow-md transition-all duration-300 hover:bg-gray-800 w-full"
            >
                Send Reset Link
            </button>
        </div>
        
        <!-- Success Message (initially hidden) -->
        <div id="resetStep2" class="hidden text-center py-4 space-y-4">
            <div class="mx-auto w-16 h-16 bg-success bg-opacity-20 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-check text-success text-2xl"></i>
            </div>
            
            <h4 class="text-lg font-hedvig font-bold">Check Your Email</h4>
            <p class="text-sm text-gray-600">We've sent a password reset link to your email address. Please check your inbox and follow the instructions.</p>
            
            <div class="mt-4">
                <button 
                    type="button" 
                    id="backToLogin"
                    class="font-hedvig bg-primary text-white py-2 px-4 border-none rounded-lg text-base font-semibold cursor-pointer shadow-md transition-all duration-300 hover:bg-gray-800 w-full"
                >
                    Back to Login
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add this script before the closing body tag -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const forgotPasswordLink = document.querySelector('a[href="#"]'); // The "Forgot Password?" link
        const modal = document.getElementById('forgotPasswordModal');
        const modalContent = document.getElementById('modalContent');
        const modalBackdrop = document.getElementById('modalBackdrop');
        const closeModalBtn = document.getElementById('closeModal');
        const resetStep1 = document.getElementById('resetStep1');
        const resetStep2 = document.getElementById('resetStep2');
        const sendResetLinkBtn = document.getElementById('sendResetLink');
        const backToLoginBtn = document.getElementById('backToLogin');
        const resetEmailInput = document.getElementById('resetEmail');
        
        // Open modal when clicking "Forgot Password?"
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            openModal();
        });
        
        // Close modal when clicking the close button or backdrop
        closeModalBtn.addEventListener('click', closeModal);
        modalBackdrop.addEventListener('click', closeModal);
        
        // Send reset link button click
        sendResetLinkBtn.addEventListener('click', function() {
        const email = resetEmailInput.value.trim();
        
        if (!email) {
            swal({
                title: "Error",
                text: "Please enter your email address",
                icon: "error",
                button: "OK",
            });
            return;
        }
        
        if (!isValidEmail(email)) {
            swal({
                title: "Error",
                text: "Please enter a valid email address",
                icon: "error",
                button: "OK",
            });
            return;
        }
        
        // Show loading state
        const originalButtonText = sendResetLinkBtn.innerHTML;
        sendResetLinkBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
        sendResetLinkBtn.disabled = true;
        
        // Send AJAX request to reset_password.php
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'reset_password/reset_password.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            // Reset button state
            sendResetLinkBtn.innerHTML = originalButtonText;
            sendResetLinkBtn.disabled = false;
            
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    
                    if (response.status === 'success') {
                        resetStep1.classList.add('hidden');
                        resetStep2.classList.remove('hidden');
                    } else {
                        swal({
                            title: "Error",
                            text: response.message || "Could not process your request",
                            icon: "error",
                            button: "OK",
                        });
                    }
                } catch (e) {
                    swal({
                        title: "Error",
                        text: "Invalid server response",
                        icon: "error",
                        button: "OK",
                    });
                }
            }
        };
        
        xhr.onerror = function() {
            // Reset button state
            sendResetLinkBtn.innerHTML = originalButtonText;
            sendResetLinkBtn.disabled = false;
            
            swal({
                title: "Connection Error",
                text: "Could not connect to the server. Please check your internet connection.",
                icon: "error",
                button: "OK",
            });
        };
        
        // Send the email
        xhr.send(`email=${encodeURIComponent(email)}`);
    });

        
        // Back to login button click
        backToLoginBtn.addEventListener('click', function() {
            closeModal();
            
            // Reset the form for next time
            setTimeout(function() {
                resetEmailInput.value = '';
                resetStep2.classList.add('hidden');
                resetStep1.classList.remove('hidden');
            }, 300);
        });
        
        // Functions
        function openModal() {
            modal.classList.remove('hidden');
            // Use setTimeout to ensure the transition works
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }
        
        function closeModal() {
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            
            // Delay hiding the modal until the animation completes
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Close modal when pressing escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    });
  
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
    
    #modalContent {
        animation: scaleIn 0.3s ease forwards;
    }
    
    #modalBackdrop {
        animation: fadeIn 0.3s ease forwards;
    }
</style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordField = document.getElementById('password');
            
            // Toggle password visibility
            passwordToggle.addEventListener('click', function() {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    passwordToggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordField.type = 'password';
                    passwordToggle.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
            
            // Form submission
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                
                // Validate inputs
                if (!email || !password) {
                    swal({
                        title: "Error",
                        text: "Please enter both email and password",
                        icon: "error",
                        button: "OK",
                    });
                    return;
                }
                
                // Show loading state
                const loginButton = this.querySelector('button[type="submit"]');
                const originalButtonText = loginButton.innerHTML;
                loginButton.innerHTML = 'Logging in...';
                loginButton.disabled = true;
                
                // Use AJAX to check credentials against multiple tables
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'check_login.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    // Reset button state
                    loginButton.innerHTML = originalButtonText;
                    loginButton.disabled = false;
                    
                    if (this.status === 200) {
                        try {
                            const response = JSON.parse(this.responseText);
                            
                            if (response.status === 'success') {
                                swal({
                                    title: "Welcome Back!",
                                    text: `You have successfully logged in as ${response.role}.`,
                                    icon: "success",
                                    button: "Continue",
                                }).then(() => {
                                    // Redirect based on user type
                                    window.location.href = response.redirect;
                                });
                            } else {
                                swal({
                                    title: "Login Failed",
                                    text: response.message,
                                    icon: "error",
                                    button: "Try Again",
                                });
                            }
                        } catch (e) {
                            swal({
                                title: "Error",
                                text: "Invalid server response. Please try again later.",
                                icon: "error",
                                button: "OK",
                            });
                        }
                    } else {
                        swal({
                            title: "Error",
                            text: "An error occurred during login. Please try again.",
                            icon: "error",
                            button: "OK",
                        });
                    }
                };
                
                xhr.onerror = function() {
                    // Reset button state
                    loginButton.innerHTML = originalButtonText;
                    loginButton.disabled = false;
                    
                    swal({
                        title: "Connection Error",
                        text: "Could not connect to the server. Please check your internet connection.",
                        icon: "error",
                        button: "OK",
                    });
                };
                
                // Send the login credentials to the server
                xhr.send(`email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`);
            });
        });
    </script>

    <!-- Add this to your index.html before the closing body tag -->

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
</script>

</body>
</html>