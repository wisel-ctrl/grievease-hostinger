<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funeral Service Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pikaday/1.8.2/css/pikaday.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pikaday/1.8.2/pikaday.min.js"></script>
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
                    <h2 class="text-xl font-hedvig text-white">Create Account</h2>
                </div>
                
                <form id="registrationForm" class="space-y-3">
                    <!-- Name fields -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-1">
                            <label for="firstName" class="block text-xs font-medium text-white mb-1">First Name</label>
                            <input 
                                type="text" 
                                id="firstName" 
                                name="firstName" 
                                required 
                                class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200"
                            >
                        </div>
                        
                        <div class="col-span-1">
                            <label for="lastName" class="block text-xs font-medium text-white mb-1">Last Name</label>
                            <input 
                                type="text" 
                                id="lastName" 
                                name="lastName" 
                                required 
                                class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200"
                            >
                        </div>
                    </div>
                    
                    <!-- Middle Name & Birthdate combined -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-1">
                            <label for="middleName" class="block text-xs font-medium text-white mb-1">Middle Name</label>
                            <input 
                                type="text" 
                                id="middleName" 
                                name="middleName" 
                                class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200"
                            >
                        </div>
                        
                        <div class="col-span-1">
                            <label for="birthdate" class="block text-xs font-medium text-white mb-1">Birthdate</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="birthdate" 
                                    name="birthdate" 
                                    readonly
                                    required
                                    class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200 pr-8"
                                >
                                <span class="absolute right-2 top-1/2 transform -translate-y-1/2 text-yellow-600 text-sm">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center text-xs text-gray-500 -mt-2 ml-1" id="ageRequirement">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span>Must be 18 years or older</span>
                    </div>
                    
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
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirmPassword" class="block text-xs font-medium text-white mb-1">Confirm Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="confirmPassword" 
                                name="confirmPassword" 
                                required
                                class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200 pr-8"
                            >
                            <span 
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer text-yellow-600 hover:text-navy transition text-sm" 
                                id="confirmPasswordToggle"
                            >
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Password requirements (collapsed by default) -->
                    <div class="hidden" id="passwordRequirements">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <h4 class="text-xs text-gray-700 mb-1">Password Requirements:</h4>
                            <div class="grid grid-cols-2 gap-1 text-xs">
                                <div class="flex items-center text-gray-500" id="length">
                                    <i class="fas fa-times-circle mr-1 text-error"></i>
                                    <span>8+ characters</span>
                                </div>
                                <div class="flex items-center text-gray-500" id="uppercase">
                                    <i class="fas fa-times-circle mr-1 text-error"></i>
                                    <span>1 uppercase</span>
                                </div>
                                <div class="flex items-center text-gray-500" id="lowercase">
                                    <i class="fas fa-times-circle mr-1 text-error"></i>
                                    <span>1 lowercase</span>
                                </div>
                                <div class="flex items-center text-gray-500" id="number">
                                    <i class="fas fa-times-circle mr-1 text-error"></i>
                                    <span>1 number</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-yellow-600 to-darkgold text-white py-2 px-4 rounded-lg text-sm font-medium shadow-lg hover:shadow-xl transition-all duration-300 mt-4 flex items-center justify-center"
                    >
                        <i class="fas fa-user-plus mr-2"></i>
                        Create Account
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-xs text-white font-medium">
                        Already have an account? <a href="login.php" class="text-yellow-600 hover:underline">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>
    </div>

<!-- Additional CSS remains the same -->
<style>
    /* Focus and active states for the simpler transition effect */
    input:focus ~ label,
    input:not(:placeholder-shown) ~ label {
        transform: translateY(-1.5rem) scale(0.85);
        color: #1E1E1E;
    }
    
    input:focus {
        border-bottom-color: #C98522;
    }
    
    /* Success and error states */
    input.border-success {
        border-bottom-color: #2F9E44;
    }
    
    input.border-error {
        border-bottom-color: #F03E3E;
    }
    
    /* Text color change for success and error validation */
    .text-success {
        color: #2F9E44;
    }
    
    .text-error {
        color: #F03E3E;
    }
</style>

    <!-- Add this modal HTML before the closing </body> tag -->
    <!-- OTP Verification Modal -->
<div id="otpModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <!-- Modal Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" id="otpModalBackdrop"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 z-10 transform transition-all duration-300 scale-95 opacity-0" id="otpModalContent">
        <!-- Close Button -->
        <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-navy transition-colors" id="closeOtpModal">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-gray-200">
            <h3 class="text-xl font-hedvig font-bold text-navy">Email Verification</h3>
        </div>
        
        <!-- Modal Body -->
        <div class="px-6 py-5">
            <div class="space-y-4">
                <p class="text-sm text-gray-600">We've sent an OTP to your email.</p>
                <p class="text-sm text-gray-600" id="otpEmail"></p>
                
                <form id="otpForm">
                    <div>
                        <label for="otp" class="block text-xs font-medium text-gray-700 mb-1">Enter OTP</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="otp" 
                                name="otp" 
                                placeholder="Enter 6-digit OTP" 
                                required 
                                maxlength="6" 
                                minlength="6" 
                                pattern="[0-9]{6}"
                                class="w-full px-3 py-2 bg-white border border-input-border rounded-lg focus:ring-1 focus:ring-yellow-600 focus:border-yellow-600 outline-none transition-all duration-200 pr-8"
                            >
                            <span class="absolute right-2 top-1/2 transform -translate-y-1/2 text-yellow-600 text-sm">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="hidden" id="otpFormEmail" name="email">
                        <div class="text-xs text-gray-500 mt-1">
                            <span id="otpTimer">Expires in 10:00</span>
                        </div>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-yellow-600 to-darkgold text-white py-2 px-4 rounded-lg text-sm font-medium shadow-lg hover:shadow-xl transition-all duration-300 mt-4 flex items-center justify-center"
                    >
                        Verify OTP
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-sm text-gray-600">Didn't receive the code? <a href="#" id="resendOtp" class="text-yellow-600 font-medium hover:underline">Resend OTP</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- CALENDAR UI -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const birthdateInput = document.getElementById('birthdate');
            
            // Add custom CSS for Pikaday
            const style = document.createElement('style');
            style.textContent = `
                .pika-single {
                    font-family: 'Hedvig Letters Serif', serif;
                    border-radius: 0.5rem;
                    box-shadow: 0 10px 25px rgb(0, 0, 0);
                    border: 1px solidrgb(110, 110, 110);
                    padding: 0.rem;
                }
                .pika-title {
                    padding: 0.25rem;
                    text-align: center;
                }
                .pika-label {
                    font-size: 1rem;
                    font-weight: bold;
                    color: #1E1E1E;
                }
                .pika-button {
                    border-radius: 0.25rem !important;
                    text-align: center;
                    transition: all 0.2s ease;
                }
                .pika-button:hover {
                    background: #F1F5F9 !important;
                    color:rgb(0, 0, 0) !important;
                    box-shadow: none !important;
                }
                .is-selected .pika-button {
                    background: #C98522 !important;
                    box-shadow: none !important;
                    color: #fff !important;
                }
                .is-today .pika-button {
                    color: #C98522;
                    font-weight: bold;
                }
                .pika-prev, .pika-next {
                    background-color: #F1F5F9;
                    border-radius: 50%;
                    width: 24px;
                    height: 24px;
                }
            `;
            document.head.appendChild(style);
            
            // Initialize Pikaday
            const picker = new Pikaday({
                field: birthdateInput,
                format: 'YYYY-MM-DD',
                maxDate: new Date(),
                yearRange: [1900, new Date().getFullYear()],
                theme: 'custom-theme',
                bound: true,
                onSelect: function() {
                    validateAge(this.toString('YYYY-MM-DD'));
                }
            });
            
            // Age validation function
            function validateAge(birthdate) {
                const birthDate = new Date(birthdate);
                const today = new Date();
                
                // Calculate age
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                // Adjust age if birthday hasn't occurred yet this year
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                // Update UI based on age
                const ageRequirement = document.getElementById('ageRequirement');
                
                if (age >= 18) {
                    ageRequirement.classList.add('text-success');
                    ageRequirement.classList.remove('text-error');
                    ageRequirement.querySelector('i').className = 'fas fa-check-circle mr-1';
                    birthdateInput.classList.remove('border-error');
                    birthdateInput.classList.add('border-success');
                } else {
                    ageRequirement.classList.add('text-error');
                    ageRequirement.classList.remove('text-success');
                    ageRequirement.querySelector('i').className = 'fas fa-times-circle mr-1';
                    birthdateInput.classList.add('border-error');
                    birthdateInput.classList.remove('border-success');
                }
                
                // Trigger a synthetic event for form validation
                const changeEvent = new Event('change');
                birthdateInput.dispatchEvent(changeEvent);
            }
        });
    </script>
    
    <!-- VALIDATION INPUT -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all input fields
        const firstName = document.getElementById('firstName');
        const lastName = document.getElementById('lastName');
        const middleName = document.getElementById('middleName');
        const email = document.getElementById('email');
        
        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirmPassword');
        const birthdateField = document.getElementById('birthdate');
        const ageRequirement = document.getElementById('ageRequirement');
        const passwordRequirements = document.getElementById('passwordRequirements');
        const passwordRequirementsMet = document.getElementById('passwordRequirementsMet');
        const matchRequirement = document.getElementById('match');
        
        // OTP related elements
        const otpModal = document.getElementById('otpModal');
        const otpForm = document.getElementById('otpForm');
        const otpEmailDisplay = document.getElementById('otpEmail');
        const otpFormEmail = document.getElementById('otpFormEmail');
        const resendOtpButton = document.getElementById('resendOtp');
        const otpTimer = document.getElementById('otpTimer');
        let timerInterval;
        
        // Validation functions
        function validateName(input) {
            // Allow only letters, spaces between words, and apostrophes
            const nameRegex = /^[A-Za-zÀ-ÿ]+(['\s][A-Za-zÀ-ÿ]+)*$/;
            return nameRegex.test(input.value.trim());
        }
        
        function validateEmail(input) {
            // Comprehensive email validation
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            return emailRegex.test(input.value.trim());
        }
        
        function preventInvalidInput(input, validationFunc) {
            input.addEventListener('input', function(e) {
                // Remove leading and trailing spaces
                this.value = this.value.trim();
                // Prevent starting with a space or dot
                if (this.value.startsWith(' ') || this.value.startsWith('.')) {
                    this.value = this.value.replace(/^[\s.]+/, '');
                }
                // Remove consecutive spaces
                this.value = this.value.replace(/\s+/g, ' ');
                // Validate and add/remove error styling
                if (this.value && !validationFunc(this)) {
                    this.classList.add('border-error');
                    this.classList.remove('border-success');
                } else if (this.value) {
                    this.classList.remove('border-error');
                    this.classList.add('border-success');
                } else {
                    this.classList.remove('border-error');
                    this.classList.remove('border-success');
                }
            });
            
            // Prevent pasting invalid input
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = e.clipboardData.getData('text/plain').trim();

                // Validate pasted text
                if (validationFunc({ value: pastedText })) {
                    this.value = pastedText;
                    this.classList.remove('border-error');
                    this.classList.add('border-success');
                } else {
                    this.value = '';
                    this.classList.add('border-error');
                    this.classList.remove('border-success');

                    // Optional: Show error toast or alert
                    swal({
                        title: "Invalid Input",
                        text: "Please enter a valid input.",
                        icon: "error",
                        button: "OK",
                    });
                }
            });
        }
        
        // Apply validation to specific fields
        preventInvalidInput(firstName, validateName);
        preventInvalidInput(lastName, validateName);
        preventInvalidInput(middleName, input => {
            // Middle name is optional, so allow empty or valid name
            return input.value === '' || validateName(input);
        });
        preventInvalidInput(email, validateEmail);
        
        // Toggle password visibility
        passwordToggle.addEventListener('click', function() {
            togglePasswordVisibility(passwordField, passwordToggle);
        });
        
        confirmPasswordToggle.addEventListener('click', function() {
            togglePasswordVisibility(confirmPasswordField, confirmPasswordToggle);
        });
        
        function togglePasswordVisibility(field, toggle) {
            if (field.type === 'password') {
                field.type = 'text';
                toggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                field.type = 'password';
                toggle.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
        
        // Password validation
        const requirements = {
            length: { regex: /.{8,}/, element: document.getElementById('length') },
            uppercase: { regex: /[A-Z]/, element: document.getElementById('uppercase') },
            lowercase: { regex: /[a-z]/, element: document.getElementById('lowercase') },
            number: { regex: /[0-9]/, element: document.getElementById('number') },
            match: { element: document.getElementById('match') }
        };
        
        // Show requirements when password field is focused
        passwordField.addEventListener('focus', function() {
            passwordRequirements.style.display = 'block';
            passwordRequirementsMet.style.display = 'none';
        });
        
        // Validate password as user types
        passwordField.addEventListener('input', function() {
            const password = passwordField.value;
            
            // Prevent leading/trailing spaces in password
            this.value = this.value.trim();
            // Prevent spaces within password
            this.value = this.value.replace(/\s/g, '');
            
            // Check each requirement
            let allRequirementsMet = true;
            
            for (const [key, requirement] of Object.entries(requirements)) {
                if (key === 'match') continue;
                
                const isValid = requirement.regex.test(password);
                updateRequirement(requirement.element, isValid);
                
                if (!isValid) {
                    allRequirementsMet = false;
                }
            }
            
            // Update password field border
            if (password && !allRequirementsMet) {
                passwordField.classList.add('border-error');
                passwordField.classList.remove('border-success');
            } else if (password && allRequirementsMet) {
                passwordField.classList.remove('border-error');
                passwordField.classList.add('border-success');
                
                // Hide requirements list and show requirements met message
                passwordRequirements.style.display = 'none';
                passwordRequirementsMet.style.display = 'flex';
                passwordRequirementsMet.style.marginTop = '0';
            } else {
                passwordField.classList.remove('border-error');
                passwordField.classList.remove('border-success');
            }
            
            // Check if passwords match
            checkPasswordsMatch();
        });
        
        // Hide requirements when password field loses focus
        passwordField.addEventListener('blur', function() {
            // Only hide if all requirements are met or if the field is empty
            if ((passwordField.value === '') || isPasswordValid()) {
                passwordRequirements.style.display = 'none';
                if (isPasswordValid() && passwordField.value !== '') {
                    passwordRequirementsMet.style.display = 'flex';
                } else {
                    passwordRequirementsMet.style.display = 'none';
                }
            }
        });
        
        confirmPasswordField.addEventListener('input', function() {
            // Prevent leading/trailing spaces in confirm password
            this.value = this.value.trim();
            // Prevent spaces within confirm password
            this.value = this.value.replace(/\s/g, '');
            checkPasswordsMatch();
        });
        
        function checkPasswordsMatch() {
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            const isValid = password === confirmPassword && password !== '';
            
            updateRequirement(requirements.match.element, isValid);
            
            // Update confirm password field border
            if (confirmPassword && !isValid) {
                confirmPasswordField.classList.add('border-error');
                confirmPasswordField.classList.remove('border-success');
                matchRequirement.style.display = 'flex';
            } else if (confirmPassword && isValid) {
                confirmPasswordField.classList.remove('border-error');
                confirmPasswordField.classList.add('border-success');
                matchRequirement.style.display = 'none';
            } else {
                confirmPasswordField.classList.remove('border-error');
                confirmPasswordField.classList.remove('border-success');
                matchRequirement.style.display = 'none';
            }
        }
        
        // Show match requirement when confirm password field is focused
        confirmPasswordField.addEventListener('focus', function() {
            if (confirmPasswordField.value || passwordField.value) {
                matchRequirement.style.display = 'flex';
            }
        });
        
        // Hide match requirement when confirm password field loses focus
        confirmPasswordField.addEventListener('blur', function() {
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (password === confirmPassword && confirmPassword !== '') {
                matchRequirement.style.display = 'none';
            }
        });
        
        function updateRequirement(element, isValid) {
            if (isValid) {
                element.classList.add('text-success');
                element.classList.remove('text-error');
                element.querySelector('i').className = 'fas fa-check-circle';
            } else {
                element.classList.add('text-error');
                element.classList.remove('text-success');
                element.querySelector('i').className = 'fas fa-times-circle';
            }
        }
        
        function isPasswordValid() {
            const password = passwordField.value;
            
            for (const [key, requirement] of Object.entries(requirements)) {
                if (key === 'match') continue;
                if (!requirement.regex.test(password)) {
                    return false;
                }
            }
            
            return true;
        }
        
        // Age validation
        birthdateField.addEventListener('change', validateAge);
        
        function validateAge() {
            const birthdate = new Date(birthdateField.value);
            const today = new Date();
            
            // Calculate age
            let age = today.getFullYear() - birthdate.getFullYear();
            const monthDiff = today.getMonth() - birthdate.getMonth();
            
            // Adjust age if birthday hasn't occurred yet this year
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }
            
            // Check if age is valid
            const isValid = birthdate.toString() !== "Invalid Date" && age >= 18;
            
            // Update UI
            if (isValid) {
                ageRequirement.classList.add('text-success');
                ageRequirement.classList.remove('text-error');
                ageRequirement.querySelector('i').className = 'fas fa-check-circle mr-1';
                birthdateField.classList.remove('border-error');
                birthdateField.classList.add('border-success');
            } else if (birthdate.toString() !== "Invalid Date") {
                ageRequirement.classList.add('text-error');
                ageRequirement.classList.remove('text-success');
                ageRequirement.querySelector('i').className = 'fas fa-times-circle mr-1';
                birthdateField.classList.add('border-error');
                birthdateField.classList.remove('border-success');
            }
            
            return isValid;
        }
        
        // Function to start OTP timer
        function startOtpTimer(duration) {
            let timer = duration;
            let minutes, seconds;
            
            clearInterval(timerInterval);
            
            timerInterval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;
                otpTimer.textContent = `Expires in ${minutes}:${seconds}`;
                if (--timer < 0) {
                    clearInterval(timerInterval);
                    otpTimer.textContent = "OTP expired";
                    otpTimer.classList.add('text-error');
                    resendOtpButton.classList.remove('pointer-events-none', 'opacity-50');
                }
            }, 1000);
        }
        
        // Function to show OTP modal
        function showOtpModal(email) {
            otpEmailDisplay.textContent = email;
            otpFormEmail.value = email;
            otpModal.classList.remove('hidden');
            otpModal.classList.add('flex');
            startOtpTimer(10 * 60); // 10 minutes timer
        }
        
        // Handle resend OTP
        resendOtpButton.addEventListener('click', function(e) {
            e.preventDefault();
        
            // Disable resend button temporarily
            resendOtpButton.classList.add('pointer-events-none', 'opacity-50');
            
            const email = otpFormEmail.value;
            
            // Retrieve form data from the original registration form
            const registrationForm = document.getElementById('registrationForm');
            const formData = new FormData(registrationForm);
            
            // Ensure email is updated in the formData
            formData.set('email', email);
            
            // Add flag to indicate this is a resend OTP request
            formData.append('resend_otp', 'true');
            
            fetch('register_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    swal({
                        title: "OTP Resent",
                        text: "A new OTP has been sent to your email.",
                        icon: "success",
                        button: "OK",
                    });
                    startOtpTimer(10 * 60); // Reset timer
                } else {
                    swal({
                        title: "Error",
                        text: data.message || "Failed to resend OTP.",
                        icon: "error",
                        button: "OK",
                    });
                    resendOtpButton.classList.remove('pointer-events-none', 'opacity-50');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                swal({
                    title: "Error",
                    text: "There was a problem connecting to the server. Please try again.",
                    icon: "error",
                    button: "OK",
                });
                resendOtpButton.classList.remove('pointer-events-none', 'opacity-50');
            });
        });

        // Handle OTP form submission
        if (otpForm) {
            otpForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('otp_verification', true);
                
                fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        clearInterval(timerInterval);
                        otpModal.classList.add('hidden');
                        otpModal.classList.remove('flex');
                        
                        swal({
                            title: "Success!",
                            text: "Your account has been created successfully!",
                            icon: "success",
                            button: "Continue",
                        }).then(() => {
                            window.location.href = "login.php";
                        });
                    } else {
                        swal({
                            title: "Verification Failed",
                            text: data.message || "Invalid OTP. Please try again.",
                            icon: "error",
                            button: "OK",
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    swal({
                        title: "Error",
                        text: "There was a problem connecting to the server. Please try again.",
                        icon: "error",
                        button: "OK",
                    });
                });
            });
        }
        
        // Add keyboard navigation for OTP input
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('keydown', function(e) {
                // Only allow numbers and control keys
                if (!/^\d$/.test(e.key) && 
                    e.key !== 'Backspace' && 
                    e.key !== 'Delete' && 
                    e.key !== 'ArrowLeft' && 
                    e.key !== 'ArrowRight' && 
                    e.key !== 'Tab' && 
                    !e.ctrlKey && 
                    !e.metaKey) {
                    e.preventDefault();
                }
            });
        }
        
        // Modified form submission for OTP flow
        const registrationForm = document.getElementById('registrationForm');
        if (registrationForm) {
            registrationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                let isValid = true;
                const errorMessages = [];
                
                // Validate first name
                if (!validateName(firstName)) {
                    isValid = false;
                    firstName.classList.add('border-error');
                    errorMessages.push("First name is invalid. Use only letters.");
                }
                
                // Validate last name
                if (!validateName(lastName)) {
                    isValid = false;
                    lastName.classList.add('border-error');
                    errorMessages.push("Last name is invalid. Use only letters.");
                }
                
                // Optional middle name validation
                if (middleName.value && !validateName(middleName)) {
                    isValid = false;
                    middleName.classList.add('border-error');
                    errorMessages.push("Middle name is invalid. Use only letters.");
                }
                
                // Validate email
                if (!validateEmail(email)) {
                    isValid = false;
                    email.classList.add('border-error');
                    errorMessages.push("Email address is invalid.");
                }
                
                // Validate age
                const isAgeValid = validateAge();
                if (!isAgeValid) {
                    isValid = false;
                    errorMessages.push("You must be at least 18 years old to register.");
                }
                
                // Validate password
                const password = passwordField.value;
                let isPasswordValid = true;
                
                // Check all password requirements
                for (const [key, requirement] of Object.entries(requirements)) {
                    if (key === 'match') {
                        const confirmPassword = confirmPasswordField.value;
                        if (password !== confirmPassword) {
                            isPasswordValid = false;
                        }
                    } else if (!requirement.regex.test(password)) {
                        isPasswordValid = false;
                    }
                }
                
                if (!isPasswordValid) {
                    isValid = false;
                    errorMessages.push("Please ensure your password meets all requirements.");
                }
                
                // Prevent submission if validation fails
                if (!isValid) {
                    swal({
                        title: "Validation Error",
                        text: errorMessages.join("\n"),
                        icon: "error",
                        button: "OK",
                    });
                    return;
                }
                
                // If all validations pass, submit form data to server for OTP generation
                const formData = new FormData(this);
                
                fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.requires_otp) {
                            // Show OTP modal
                            showOtpModal(data.email);
                        } else {
                            // Direct registration success (unlikely with our new flow)
                            swal({
                                title: "Success!",
                                text: "Your account has been created successfully!",
                                icon: "success",
                                button: "Continue",
                            }).then(() => {
                                window.location.href = "login.php";
                            });
                        }
                    } else {
                        swal({
                            title: "Registration Failed",
                            text: data.message || "Failed to register. Please try again.",
                            icon: "error",
                            button: "OK",
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    swal({
                        title: "Error",
                        text: "There was a problem connecting to the server. Please try again.",
                        icon: "error",
                        button: "OK",
                    });
                });
            });
        }
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