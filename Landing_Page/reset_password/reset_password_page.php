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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white p-8 rounded-xl shadow-md">
        <h2 class="text-2xl font-bold text-center mb-6 text-navy">Reset Your Password</h2>
        
        <form id="resetPasswordForm" class="space-y-4">
            <!-- Hidden input for token -->
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <!-- New Password -->
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                    New Password
                </label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"
                        placeholder="Enter new password"
                    >
                    <span 
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-500 hover:text-yellow-600" 
                        id="toggleNewPassword"
                    >
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <p id="password-strength" class="text-xs mt-1 text-gray-500">Password strength: Weak</p>
            </div>
            
            <!-- Confirm New Password -->
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Confirm New Password
                </label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"
                        placeholder="Confirm new password"
                    >
                    <span 
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-500 hover:text-yellow-600" 
                        id="toggleConfirmPassword"
                    >
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full bg-yellow-600 text-white py-2 rounded-lg hover:bg-yellow-700 transition duration-300 mt-4"
            >
                Reset Password
            </button>
        </form>
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

        // Form submission
        resetForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            // Client-side validations
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

            // Create FormData
            const formData = new FormData(this);

            // Send AJAX request
            fetch('process_password_reset.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
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
                        text: data.message,
                        icon: "error",
                        button: "Try Again"
                    });
                }
            })
            .catch(error => {
                swal({
                    title: "Error",
                    text: "An unexpected error occurred",
                    icon: "error",
                    button: "OK"
                });
            });
        });
    });
    </script>
</body>
</html>