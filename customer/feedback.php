<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check if user is a customer
if ($_SESSION['user_type'] != 3) {
    // Redirect to appropriate dashboard
    switch ($_SESSION['user_type']) {
        case 1:
            header("Location: ../admin/index.php");
            break;
        case 2:
            header("Location: ../employee/index.php");
            break;
        default:
            header("Location: ../Landing_Page/login.php");
    }
    exit();
}

// Handle form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? 0;
    $feedback = trim($_POST['feedback'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5';
    } elseif (empty($feedback)) {
        $error = 'Please provide your feedback';
    } else {
        require_once '../db_connect.php';
        
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, rating, feedback, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $rating, $feedback);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - GrieveEase</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --navbar-height: 4rem;
        }
        body {
            padding-top: var(--navbar-height);
            background-color: #f5f5dc; /* Cream background */
        }
        .rating-input {
            display: none;
        }
        .rating-label {
            font-size: 2rem;
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating-input:checked ~ .rating-label,
        .rating-label:hover,
        .rating-label:hover ~ .rating-label {
            color: #f59e0b; /* Yellow color for selected/hovered stars */
        }
        .rating-input:checked ~ .rating-label {
            color: #f59e0b;
        }
        .btn-primary {
            background-color: #1e3a8a; /* Navy blue */
            color: white;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #1e40af; /* Darker navy blue on hover */
        }
    </style>
</head>
<body class="font-sans">
    <!-- Navigation Bar (same as profile.php) -->
    <nav class="bg-black text-white shadow-md w-full fixed top-0 left-0 z-50 px-4 sm:px-6 lg:px-8" style="height: var(--navbar-height);">
        <div class="flex justify-between items-center h-16">
            <a href="index.php" class="flex items-center space-x-2">
                <img src="../Landing_Page/Landing_images/logo.png" alt="Logo" class="h-[42px] w-[38px]">
                <span class="text-yellow-600 text-3xl">GrieveEase</span>
            </a>
            <div class="flex items-center space-x-6">
                <a href="profile.php" class="text-white hover:text-yellow-500 transition-colors">
                    <i class="fas fa-user-circle text-2xl"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-navy p-6 border-b border-gray-100">
                <h1 class="text-2xl font-bold text-white">Share Your Feedback</h1>
                <p class="text-blue-100 mt-1">We value your opinion and would love to hear about your experience.</p>
            </div>

            <!-- Feedback Form -->
            <div class="p-6">
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <strong class="font-bold">Thank You!</strong>
                        <span class="block sm:inline"> Your feedback has been submitted successfully.</span>
                        <div class="mt-2">
                            <a href="profile.php" class="text-blue-600 hover:underline">← Back to Profile</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                            <strong class="font-bold">Error!</strong>
                            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="space-y-6">
                        <!-- Rating -->
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                How would you rate your experience? <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center space-x-2">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" class="rating-input" <?php echo (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $i; ?>" class="rating-label" title="<?php echo $i; ?> stars">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Feedback -->
                        <div>
                            <label for="feedback" class="block text-gray-700 text-sm font-medium mb-2">
                                Your Feedback <span class="text-red-500">*</span>
                            </label>
                            <textarea id="feedback" name="feedback" rows="6" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Please share your thoughts about our service..."
                                required><?php echo htmlspecialchars($_POST['feedback'] ?? ''); ?></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
                            <a href="profile.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 text-center">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 rounded-md text-white bg-navy hover:bg-blue-800 transition-colors">
                                Submit Feedback
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Make star rating interactive
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.rating-input');
            
            stars.forEach(star => {
                star.addEventListener('change', function() {
                    // Optional: You can add visual feedback when a star is selected
                });
            });
        });
    </script>
</body>
</html>
