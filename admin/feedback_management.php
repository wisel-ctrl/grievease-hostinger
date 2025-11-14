<?php
session_start();

include 'faviconLogo.php'; 

date_default_timezone_set('Asia/Manila');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: ../Landing_Page/login.php");
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

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../db_connect.php';

// Get user's first name from database
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name'];
$last_name = $row['last_name'];
$profile_picture = $row['profile_picture'] ? '../' . $row['profile_picture'] : '../default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - GrieveEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
        }
        .sidebar-link.active {
            background-color: #FEF3C7;
            color: #92400E;
            font-weight: 500;
        }
        .sidebar-link:hover:not(.active) {
            background-color: #FEF9C3;
        }
        .shadow-sidebar {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .star-rating {
            color: #fbbf24;
        }
        .star-rating .far {
            color: #d1d5db;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
        <!-- Header with breadcrumb and welcome message -->
        <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar border border-sidebar-border">
            <div>
                <h1 class="text-2xl font-bold text-sidebar-text">Feedback Management</h1>
            </div>
            <div class="relative">
                <button id="branchFilterToggle" class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover transition-colors">
                    <i class="fas fa-filter text-sidebar-accent"></i>
                    <span>Filter</span>
                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>
                <div id="branchFilterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border py-1">
                    <div class="space-y-1">
                        <button class="w-full text-left px-4 py-2 text-sm text-sidebar-text hover:bg-sidebar-hover transition-colors" data-branch="all" data-branch-id="all">
                            <i class="fas fa-globe-americas text-sidebar-accent mr-2"></i> All Feedback
                        </button>
                        <button class="w-full text-left px-4 py-2 text-sm text-sidebar-text hover:bg-sidebar-hover transition-colors" data-branch="visible" data-branch-id="visible">
                            <i class="fas fa-eye text-sidebar-accent mr-2"></i> Visible
                        </button>
                        <button class="w-full text-left px-4 py-2 text-sm text-sidebar-text hover:bg-sidebar-hover transition-colors" data-branch="hidden" data-branch-id="hidden">
                            <i class="fas fa-eye-slash text-sidebar-accent mr-2"></i> Hidden
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
            <!-- Total Feedback Card -->
            <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border overflow-hidden">
                <div class="p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Feedback</p>
                            <h3 class="text-2xl font-bold text-sidebar-text mt-1">0</h3>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-comment-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <span class="text-xs text-green-500">
                            <i class="fas fa-arrow-up mr-1"></i> 0% from last month
                        </span>
                    </div>
                </div>
            </div>

            <!-- Average Rating Card -->
            <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border overflow-hidden">
                <div class="p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Average Rating</p>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold text-sidebar-text mr-2">0.0</span>
                                <div class="star-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-star text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <span class="text-xs text-green-500">
                            <i class="fas fa-arrow-up mr-1"></i> 0% from last month
                        </span>
                    </div>
                </div>
            </div>

            <!-- Positive Feedback Card -->
            <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border overflow-hidden">
                <div class="p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Positive Feedback</p>
                            <h3 class="text-2xl font-bold text-sidebar-text mt-1">0</h3>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <i class="fas fa-thumbs-up text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <span class="text-xs text-green-500">
                            <i class="fas fa-arrow-up mr-1"></i> 0% from last month
                        </span>
                    </div>
                </div>
            </div>

            <!-- Response Rate Card -->
            <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border overflow-hidden">
                <div class="p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Response Rate</p>
                            <h3 class="text-2xl font-bold text-sidebar-text mt-1">0%</h3>
                        </div>
                        <div class="p-3 bg-amber-100 rounded-full">
                            <i class="fas fa-reply text-amber-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <span class="text-xs text-green-500">
                            <i class="fas fa-arrow-up mr-1"></i> 0% from last month
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-4 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Search Bar -->
                <div class="relative flex-grow max-w-2xl">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="searchFeedback" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sidebar-accent focus:border-sidebar-accent" placeholder="Search feedback...">
                </div>
                
                <!-- Sort Dropdown -->
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-sort-amount-down text-gray-400"></i>
                        </div>
                        <select id="sortBy" class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sidebar-accent focus:border-sidebar-accent appearance-none bg-white">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="highest">Highest Rating</option>
                            <option value="lowest">Lowest Rating</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback List -->
        <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border overflow-hidden">
            <div class="overflow-x-auto scrollbar-thin" id="feedbackTableContainer">
                <div id="loadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
                </div>
                
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-sidebar-border">
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-user text-sidebar-accent"></i> Customer
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1)">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-star text-sidebar-accent"></i> Rating
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-comment-alt text-sidebar-accent"></i> Feedback
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-center text-sm font-medium text-sidebar-text whitespace-nowrap">
                                <div class="flex items-center gap-1.5 justify-center">
                                    <i class="fas fa-eye text-sidebar-accent"></i> Visibility
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="feedbackTableBody">
                        <!-- Sample Feedback 1 -->
                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name=John+Doe&background=4f46e5&color=fff" alt="">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-sidebar-text">John Doe</div>
                                        <div class="text-xs text-gray-500">john@example.com</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center">
                                    <div class="star-rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                    <span class="ml-2 text-sm text-gray-500">4.0</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="text-sm text-sidebar-text">Great service! The team was very professional and handled everything with care.</div>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 whitespace-nowrap">
                                Nov 14, 2023
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <button type="button" class="toggle-visibility inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fas fa-eye mr-1"></i> Visible
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Sample Feedback 2 -->
                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name=Jane+Smith&background=ec4899&color=fff" alt="">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-sidebar-text">Jane Smith</div>
                                        <div class="text-xs text-gray-500">jane@example.com</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center">
                                    <div class="star-rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                    <span class="ml-2 text-sm text-gray-500">3.0</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="text-sm text-sidebar-text">Good experience overall, but there was a slight delay in the service.</div>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 whitespace-nowrap">
                                Nov 10, 2023
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <button type="button" class="toggle-visibility inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-gray-400 hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                    <i class="fas fa-eye-slash mr-1"></i> Hidden
                                </button>
                            </td>
                        </tr>
                        
                        <!-- No Feedback Message -->
                        <tr id="noFeedbackMessage" class="hidden">
                            <td colspan="5" class="px-4 py-6 text-center">
                                <div class="flex flex-col items-center justify-center py-8">
                                    <div class="bg-gray-100 p-4 rounded-full mb-3">
                                        <i class="fas fa-comment-slash text-gray-400 text-2xl"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-1">No feedback found</h3>
                                    <p class="text-sm text-gray-500">Try adjusting your search or filter to find what you're looking for.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
                        Showing 1-2 of 2 feedbacks
                    </div>
                    <div id="paginationContainer" class="flex space-x-2">
                        <button class="px-3 py-1.5 border border-sidebar-border rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-1.5 bg-sidebar-accent text-white rounded-lg text-sm font-medium hover:bg-sidebar-accent/90">
                            1
                        </button>
                        <button class="px-3 py-1.5 border border-sidebar-border rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-4 text-center text-sm text-gray-500 mt-8">
        <p>© 2025 GrieveEase.</p>
    </footer>
    </div>

    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileHamburger = document.getElementById('mobile-hamburger');
            if (mobileHamburger) {
                mobileHamburger.addEventListener('click', function() {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('-translate-x-full');
                        document.querySelector('.main-content').classList.toggle('ml-64');
                    }
                });
            }

            // Toggle feedback visibility for individual feedback items
            document.addEventListener('click', function(e) {
                if (e.target.closest('.toggle-visibility')) {
                    const button = e.target.closest('.toggle-visibility');
                    const icon = button.querySelector('i');
                    const isVisible = button.textContent.trim().includes('Visible');
                    
                    // Show loading state
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Updating...';
                    button.disabled = true;
                    
                    // Simulate API call
                    setTimeout(() => {
                        if (isVisible) {
                            // Hide feedback
                            button.innerHTML = '<i class="fas fa-eye-slash mr-1"></i> Hidden';
                            button.className = 'toggle-visibility inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-gray-400 hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500';
                            // In a real app, make an AJAX call to update visibility in the database
                            console.log('Hiding feedback');
                        } else {
                            // Show feedback
                            button.innerHTML = '<i class="fas fa-eye mr-1"></i> Visible';
                            button.className = 'toggle-visibility inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500';
                            // In a real app, make an AJAX call to update visibility in the database
                            console.log('Showing feedback');
                        }
                        button.disabled = false;
                        
                        // Show success message
                        showToast('Feedback visibility updated', 'success');
                    }, 500);
                }
            });
            
            // Branch filter toggle
            const branchFilterToggle = document.getElementById('branchFilterToggle');
            const branchFilterDropdown = document.getElementById('branchFilterDropdown');
            
            if (branchFilterToggle && branchFilterDropdown) {
                branchFilterToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    branchFilterDropdown.classList.toggle('hidden');
                    this.querySelector('i.fa-chevron-down').classList.toggle('rotate-180');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    if (!branchFilterDropdown.classList.contains('hidden')) {
                        branchFilterDropdown.classList.add('hidden');
                        branchFilterToggle.querySelector('i.fa-chevron-down').classList.remove('rotate-180');
                    }
                });
                
                // Prevent dropdown from closing when clicking inside it
                branchFilterDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                
                // Handle filter selection
                const filterButtons = branchFilterDropdown.querySelectorAll('button');
                filterButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const branch = this.getAttribute('data-branch');
                        const branchId = this.getAttribute('data-branch-id');
                        
                        // Update active state
                        filterButtons.forEach(btn => {
                            btn.classList.remove('bg-sidebar-hover');
                            btn.querySelector('i').classList.remove('text-sidebar-accent');
                        });
                        this.classList.add('bg-sidebar-hover');
                        this.querySelector('i').classList.add('text-sidebar-accent');
                        
                        // Update button text
                        branchFilterToggle.querySelector('span').textContent = this.textContent.trim();
                        
                        // In a real app, you would filter the feedback by branch here
                        console.log('Filtering by branch:', branch, branchId);
                        
                        // Close dropdown
                        branchFilterDropdown.classList.add('hidden');
                        branchFilterToggle.querySelector('i.fa-chevron-down').classList.remove('rotate-180');
                        
                        // Show loading state
                        const container = document.getElementById('feedbackTableContainer');
                        const loadingIndicator = document.getElementById('loadingIndicator');
                        const tableBody = document.getElementById('feedbackTableBody');
                        const noFeedbackMessage = document.getElementById('noFeedbackMessage');
                        
                        if (container && loadingIndicator && tableBody && noFeedbackMessage) {
                            loadingIndicator.classList.remove('hidden');
                            container.style.minHeight = '200px';
                            
                            // Simulate API call
                            setTimeout(() => {
                                loadingIndicator.classList.add('hidden');
                                
                                // In a real app, you would update the table with filtered data
                                // For now, we'll just show/hide the no feedback message based on the filter
                                if (branchId === 'hidden') {
                                    // Show only hidden feedback
                                    tableBody.querySelectorAll('tr').forEach(row => {
                                        if (row.id !== 'noFeedbackMessage') {
                                            const isHidden = row.querySelector('.toggle-visibility').textContent.includes('Hidden');
                                            row.style.display = isHidden ? '' : 'none';
                                        }
                                    });
                                    noFeedbackMessage.style.display = tableBody.querySelector('tr:not([style*="display: none"]):not(#noFeedbackMessage)') ? 'none' : '';
                                } else if (branchId === 'visible') {
                                    // Show only visible feedback
                                    tableBody.querySelectorAll('tr').forEach(row => {
                                        if (row.id !== 'noFeedbackMessage') {
                                            const isVisible = row.querySelector('.toggle-visibility').textContent.includes('Visible');
                                            row.style.display = isVisible ? '' : 'none';
                                        }
                                    });
                                    noFeedbackMessage.style.display = tableBody.querySelector('tr:not([style*="display: none"]):not(#noFeedbackMessage)') ? 'none' : '';
                                } else {
                                    // Show all feedback
                                    tableBody.querySelectorAll('tr').forEach(row => {
                                        row.style.display = '';
                                    });
                                    noFeedbackMessage.style.display = 'none';
                                }
                            }, 500);
                        }
                    });
                });
            }
            
            // Search functionality
            const searchInput = document.getElementById('searchFeedback');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#feedbackTableBody tr:not(#noFeedbackMessage)');
                    let hasResults = false;
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                            hasResults = true;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Show/hide no results message
                    const noFeedbackMessage = document.getElementById('noFeedbackMessage');
                    if (noFeedbackMessage) {
                        if (!hasResults && searchTerm) {
                            noFeedbackMessage.classList.remove('hidden');
                        } else {
                            noFeedbackMessage.classList.add('hidden');
                        }
                    }
                });
            }
            
            // Sort functionality
            const sortSelect = document.getElementById('sortBy');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    const sortValue = this.value;
                    console.log('Sorting by:', sortValue);
                    // In a real app, you would make an AJAX call to sort the feedback
                    // For now, we'll just show a loading state and then revert
                    const container = document.getElementById('feedbackTableContainer');
                    const loadingIndicator = document.getElementById('loadingIndicator');
                    
                    if (container && loadingIndicator) {
                        loadingIndicator.classList.remove('hidden');
                        container.style.minHeight = '200px';
                        
                        setTimeout(() => {
                            loadingIndicator.classList.add('hidden');
                            showToast(`Feedback sorted by ${this.options[this.selectedIndex].text}`, 'success');
                        }, 500);
                    }
                });
            }
            
            // Show toast notification
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg text-white ${
                    type === 'success' ? 'bg-green-500' : 'bg-red-500'
                }`;
                toast.textContent = message;
                document.body.appendChild(toast);
                
                // Remove toast after 3 seconds
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }
        });

        // Function to update average rating stars
        function updateAverageRating(rating) {
            const stars = document.querySelectorAll('.star-rating i');
            stars.forEach((star, index) => {
                if (index < Math.floor(rating)) {
                    star.className = 'fas fa-star';
                } else if (index < Math.ceil(rating)) {
                    star.className = 'fas fa-star-half-alt';
                } else {
                    star.className = 'far fa-star';
                }
            });
        }

        // Initialize with sample data (in a real app, this would come from the server)
        document.addEventListener('DOMContentLoaded', function() {
            // Set sample average rating (4.2 out of 5)
            updateAverageRating(4.2);
            
            // Set sample total feedback count
            const totalFeedbackElement = document.querySelector('.bg-gradient-to-r.from-blue-100 .text-2xl');
            if (totalFeedbackElement) {
                totalFeedbackElement.textContent = '42';
            }
        });
    </script>
</body>
</html>
