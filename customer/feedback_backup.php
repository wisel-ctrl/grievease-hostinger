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

// Get user data
require_once '../db_connect.php';
$user_id = $_SESSION['user_id'];

// Get user information
$query = "SELECT first_name, last_name, email, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$first_name = $user_data['first_name'];
$last_name = $user_data['last_name'];
$email = $user_data['email'];
$profile_picture = $user_data['profile_picture'];
$stmt->close();

// Get notification count
$notifications_count = 0;
$notif_query = "SELECT COUNT(*) as count FROM booking_tb WHERE customerID = ? AND status = 'Pending'";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$notif_data = $notif_result->fetch_assoc();
$notifications_count = $notif_data['count'];
$notif_stmt->close();

// Handle form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? 0;
    $feedback = trim($_POST['feedback'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5';
    } elseif (empty($feedback)) {
        $error = 'Please provide your feedback';
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, rating, feedback, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $rating, $feedback);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - GrievEase</title>
    <?php include 'faviconLogo.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../tailwind.js"></script>
    <style>
        :root {
            --navbar-height: 64px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9F6F0;
        }
        
        .main-content {
            padding-top: var(--navbar-height);
        }
        
        .rating-container {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .rating-input {
            display: none;
        }
        
        .rating-label {
            font-size: 3rem;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .rating-label:hover,
        .rating-label:hover ~ .rating-label {
            color: #fbbf24;
            transform: scale(1.1);
        }
        
        .rating-input:checked ~ .rating-label {
            color: #f59e0b;
            animation: starPulse 0.3s ease;
        }
        
        @keyframes starPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease forwards;
            opacity: 0;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        .delay-3 { animation-delay: 0.6s; }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .pulse-slow {
            animation: pulseSlow 3s infinite;
        }
        
        @keyframes pulseSlow {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(202, 138, 4, 0.4);
            }
            70% {
                transform: scale(1.03);
                box-shadow: 0 0 0 15px rgba(202, 138, 4, 0);
            }
        }
    </style>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
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
