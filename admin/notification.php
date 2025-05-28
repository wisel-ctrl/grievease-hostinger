<?php
require_once '../db_connect.php';

// Query for pending funeral bookings
$funeralQuery = "SELECT 
                    b.booking_id,
                    CONCAT(b.deceased_lname, ', ', b.deceased_fname, ' ', IFNULL(b.deceased_midname, '')) AS deceased_name,
                    b.booking_date AS notification_date,
                    IFNULL(s.service_name, 'Custom Package') AS service_name,
                    'funeral' AS notification_type,
                    'Booking_acceptance.php' AS link_base
                 FROM booking_tb b
                 LEFT JOIN services_tb s ON b.service_id = s.service_id
                 WHERE b.status = 'Pending'
                 ORDER BY b.booking_date DESC";

// Query for pending life plan bookings
$lifeplanQuery = "SELECT 
                    lb.lpbooking_id AS booking_id,
                    CONCAT(lb.benefeciary_lname, ', ', lb.benefeciary_fname, ' ', IFNULL(lb.benefeciary_mname, '')) AS deceased_name,
                    lb.initial_date AS notification_date,
                    s.service_name,
                    'lifeplan' AS notification_type,
                    'Booking_acceptance.php' AS link_base
                  FROM lifeplan_booking_tb lb
                  JOIN services_tb s ON lb.service_id = s.service_id
                  WHERE lb.booking_status = 'pending'
                  ORDER BY lb.initial_date DESC";

// Query for pending ID validations
$idValidationQuery = "SELECT 
                        id AS booking_id,
                        '' AS deceased_name,
                        upload_at AS notification_date,
                        'ID Validation' AS service_name,
                        'id_validation' AS notification_type,
                        'id_confirmation.php' AS link_base
                     FROM valid_id_tb
                     WHERE is_validated = 'no'
                     ORDER BY upload_at DESC";

// Execute all queries
$funeralResult = $conn->query($funeralQuery);
$lifeplanResult = $conn->query($lifeplanQuery);
$idValidationResult = $conn->query($idValidationQuery);

// Store results in arrays
$funeralNotifications = [];
$lifeplanNotifications = [];
$idValidationNotifications = [];

if ($funeralResult && $funeralResult->num_rows > 0) {
    while ($row = $funeralResult->fetch_assoc()) {
        $funeralNotifications[] = $row;
    }
}

if ($lifeplanResult && $lifeplanResult->num_rows > 0) {
    while ($row = $lifeplanResult->fetch_assoc()) {
        $lifeplanNotifications[] = $row;
    }
}

if ($idValidationResult && $idValidationResult->num_rows > 0) {
    while ($row = $idValidationResult->fetch_assoc()) {
        $idValidationNotifications[] = $row;
    }
}

// Count totals
$totalFuneral = count($funeralNotifications);
$totalLifeplan = count($lifeplanNotifications);
$totalIdValidation = count($idValidationNotifications);
$totalPending = $totalFuneral + $totalLifeplan + $totalIdValidation;

// Function to calculate time ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d % 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    $diff->d = $days;
    
    foreach ($string as $k => &$v) {
        if ($k === 'w') {
            if ($weeks) {
                $v = $weeks . ' ' . $v . ($weeks > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        } elseif ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Notification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600&family=Hedvig+Letters+Serif:opsz,wght@12..24,400&display=swap" rel="stylesheet">
    
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
    <style>
        .notification-enter {
            animation: slideInFromRight 0.3s ease-out;
        }
        
        @keyframes slideInFromRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .loading-skeleton {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        .button-loading {
            pointer-events: none;
            opacity: 0.7;
        }
        
        .ripple-effect {
            position: relative;
            overflow: hidden;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .focus-visible:focus {
            outline: 2px solid #CA8A04;
            outline-offset: 2px;
        }
        
        .notification-priority-high {
            border-left-width: 4px;
            animation: pulse-border 2s infinite;
        }
        
        @keyframes pulse-border {
            0%, 100% { border-left-color: #EF4444; }
            50% { border-left-color: #FCA5A5; }
        }
    </style>
</head>

<body class="bg-primary font-inter">
    
    <!-- Header -->
    <header class="bg-sidebar-bg shadow-sidebar border-b border-sidebar-border sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <button 
                        onclick="history.back()" 
                        class="mr-4 p-2 rounded-lg bg-sidebar-hover text-sidebar-text hover:bg-border transition-all duration-200 focus-visible:focus transform hover:scale-105"
                        aria-label="Go back to previous page"
                        title="Go back"
                    >
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-semibold text-sidebar-text font-playfair">Notifications</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <span class="bg-sidebar-accent text-white text-sm px-3 py-1 rounded-full font-medium shadow-sm animate-pulse">
                            <span id="total-notifications"><?php echo $totalPending; ?></span> Pending
                        </span>
                    </div>
                    <button 
                        onclick="showHelpModal()"
                        class="p-2 rounded-lg text-gray-400 hover:text-sidebar-accent hover:bg-sidebar-hover transition-all duration-200 focus-visible:focus"
                        aria-label="Help and information"
                        title="Need help?"
                    >
                        <i class="fas fa-question-circle text-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-sidebar-bg rounded-lg p-6 shadow-card">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-sidebar-accent"></div>
                <span class="text-sidebar-text">Processing...</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
        <!-- Quick Actions Bar -->
        <div class="mb-6 bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border p-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button 
                        onclick="markAllAsRead()" 
                        class="ripple-effect flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                        title="Mark all notifications as read"
                    >
                        <i class="fas fa-check-double mr-2" aria-hidden="true"></i>
                        Mark All Read
                    </button>
                    <button 
                        onclick="refreshNotifications()" 
                        class="ripple-effect flex items-center px-4 py-2 bg-sidebar-hover text-sidebar-text rounded-lg hover:bg-border transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                        title="Refresh notifications"
                    >
                        <i class="fas fa-sync-alt mr-2" aria-hidden="true"></i>
                        Refresh
                    </button>
                    
                </div>
                <div class="flex items-center space-x-3">
                    <label for="sort-select" class="text-sm text-gray-600 font-medium">Sort by:</label>
                    <select 
                        id="sort-select"
                        onchange="sortNotifications(this.value)"
                        class="px-3 py-2 border border-input-border rounded-lg text-sm bg-sidebar-bg text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-sidebar-accent transition-all duration-200"
                        aria-label="Sort notifications"
                    >
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="priority">High Priority</option>
                        <option value="type">By Type</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="mb-8">
            <div class="border-b border-sidebar-border">
                <nav class="-mb-px flex space-x-1 overflow-x-auto" role="tablist">
                    <button 
                        onclick="filterNotifications('all')" 
                        class="filter-tab active whitespace-nowrap pb-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 focus-visible:focus"
                        role="tab"
                        aria-selected="true"
                        title="View all notifications"
                    >
                        <i class="fas fa-list mr-2" aria-hidden="true"></i>
                        All Notifications
                        <span class="ml-2 bg-sidebar-accent text-white text-xs rounded-full px-2 py-0.5"><?php echo $totalPending; ?></span>
                    </button>
                    <button 
                        onclick="filterNotifications('funeral')" 
                        class="filter-tab whitespace-nowrap pb-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 focus-visible:focus"
                        role="tab"
                        aria-selected="false"
                        title="View funeral booking notifications"
                    >
                        <i class="fas fa-cross mr-2" aria-hidden="true"></i>
                        Funeral Bookings
                        <span class="ml-2 bg-blue-500 text-white text-xs rounded-full px-2 py-0.5"><?php echo $totalFuneral; ?></span>
                    </button>
                    <button 
                        onclick="filterNotifications('lifeplan')" 
                        class="filter-tab whitespace-nowrap pb-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 focus-visible:focus"
                        role="tab"
                        aria-selected="false"
                        title="View life plan booking notifications"
                    >
                        <i class="fas fa-heart mr-2" aria-hidden="true"></i>
                        Life Plan Bookings
                        <span class="ml-2 bg-purple-500 text-white text-xs rounded-full px-2 py-0.5"><?php echo $totalLifeplan; ?></span>
                    </button>
                    <button 
                        onclick="filterNotifications('validation')" 
                        class="filter-tab whitespace-nowrap pb-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 focus-visible:focus"
                        role="tab"
                        aria-selected="false"
                        title="View ID validation notifications"
                    >
                        <i class="fas fa-id-card mr-2" aria-hidden="true"></i>
                        ID Validations
                        <span class="ml-2 bg-yellow-500 text-white text-xs rounded-full px-2 py-0.5"><?php echo $totalIdValidation; ?></span>
                    </button>
                </nav>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="mb-6 bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border p-4">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                    <input 
                        type="text" 
                        placeholder="Search notifications..." 
                        class="w-full pl-10 pr-4 py-2 border border-input-border rounded-lg text-sm bg-sidebar-bg text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-sidebar-accent transition-all duration-200"
                        oninput="searchNotifications(this.value)"
                        aria-label="Search notifications"
                    >
                </div>
                <div class="flex items-center space-x-2">
                    <label class="flex items-center space-x-2 text-sm">
                        <input 
                            type="checkbox" 
                            onchange="toggleUnreadOnly(this.checked)"
                            class="rounded border-input-border text-sidebar-accent focus:ring-sidebar-accent focus:ring-2"
                        >
                        <span>Unread only</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden text-center py-12">
            <div class="bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border p-8">
                <i class="fas fa-bell-slash text-6xl text-gray-300 mb-4" aria-hidden="true"></i>
                <h3 class="text-lg font-semibold text-sidebar-text mb-2">No notifications found</h3>
                <p class="text-gray-600 mb-4">Try adjusting your filters or check back later for new notifications.</p>
                <button 
                    onclick="resetFilters()"
                    class="px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-colors"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div id="notifications-container" class="space-y-4">
            <?php if ($totalPending === 0): ?>
                <div class="text-center py-12">
                    <div class="bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border p-8">
                        <i class="fas fa-check-circle text-6xl text-green-500 mb-4" aria-hidden="true"></i>
                        <h3 class="text-lg font-semibold text-sidebar-text mb-2">No pending notifications</h3>
                        <p class="text-gray-600 mb-4">All caught up! You have no pending notifications at this time.</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Funeral Bookings -->
                <?php foreach ($funeralNotifications as $notification): 
                    $timeAgo = time_elapsed_string($notification['notification_date']);
                    $isUrgent = (strtotime($notification['notification_date']) > strtotime('-1 day'));
                ?>
                <div class="notification-item funeral <?php echo $isUrgent ? 'notification-priority-high' : ''; ?> bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="<?php echo $isUrgent ? 'high' : 'medium'; ?>" data-timestamp="<?php echo strtotime($notification['notification_date']); ?>">
                    <div class="flex items-start p-6">
                        <div class="absolute left-0 top-0 bottom-0 w-1 <?php echo $isUrgent ? 'bg-red-500' : 'bg-blue-500'; ?>"></div>
                        <div class="flex-shrink-0 <?php echo $isUrgent ? 'bg-red-100' : 'bg-blue-100'; ?> rounded-full p-3 mr-4">
                            <i class="fas fa-cross <?php echo $isUrgent ? 'text-red-600' : 'text-blue-600'; ?> text-lg" aria-hidden="true"></i>
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-grow">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-sidebar-text"><?php echo $isUrgent ? 'URGENT: ' : ''; ?>Funeral Booking Request</h3>
                                        <?php if ($isUrgent): ?>
                                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded-full">High Priority</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sidebar-text"><?php echo htmlspecialchars($notification['deceased_name']); ?> - <?php echo htmlspecialchars($notification['service_name']); ?></p>
                                </div>
                                <div class="flex items-center space-x-2 ml-4">
                                    <span class="h-3 w-3 bg-blue-600 rounded-full" aria-label="Unread notification"></span>
                                    <span class="text-xs text-gray-500">Unread</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                                <div class="flex items-center">
                                    <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                    <span><?php echo $timeAgo; ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-2" aria-hidden="true"></i>
                                    <span>Booking Date: <?php echo date('F j, Y', strtotime($notification['notification_date'])); ?></span>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="<?php echo $notification['link_base']; ?>" 
                                        class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                        title="View detailed booking information"
                                    >
                                        <i class="fas fa-eye mr-2" aria-hidden="true"></i>View Details
                                    </a>
                                </div>
                                <button 
                                    onclick="markAsRead(this)" 
                                    class="text-gray-400 hover:text-sidebar-accent transition-colors p-2 rounded focus-visible:focus"
                                    title="Mark as read"
                                    aria-label="Mark notification as read"
                                >
                                    <i class="fas fa-check-circle text-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Life Plan Bookings -->
                <?php foreach ($lifeplanNotifications as $notification): 
                    $timeAgo = time_elapsed_string($notification['notification_date']);
                ?>
                <div class="notification-item lifeplan bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="medium" data-timestamp="<?php echo strtotime($notification['notification_date']); ?>">
                    <div class="flex items-start p-6">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-purple-500"></div>
                        <div class="flex-shrink-0 bg-purple-100 rounded-full p-3 mr-4">
                            <i class="fas fa-heart text-purple-600 text-lg" aria-hidden="true"></i>
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-grow">
                                    <h3 class="text-lg font-semibold text-sidebar-text">New Life Plan Booking Request</h3>
                                    <p class="text-sidebar-text mt-1"><?php echo htmlspecialchars($notification['deceased_name']); ?> - <?php echo htmlspecialchars($notification['service_name']); ?></p>
                                </div>
                                <div class="flex items-center space-x-2 ml-4">
                                    <span class="h-3 w-3 bg-purple-600 rounded-full" aria-label="Unread notification"></span>
                                    <span class="text-xs text-gray-500">Unread</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                                <div class="flex items-center">
                                    <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                    <span><?php echo $timeAgo; ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-2" aria-hidden="true"></i>
                                    <span>Initial Date: <?php echo date('F j, Y', strtotime($notification['notification_date'])); ?></span>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="<?php echo $notification['link_base']; ?>" 
                                        class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                        title="View detailed booking information"
                                    >
                                        <i class="fas fa-eye mr-2" aria-hidden="true"></i>View Details
                                    </a>
                                </div>
                                <button 
                                    onclick="markAsRead(this)" 
                                    class="text-gray-400 hover:text-sidebar-accent transition-colors p-2 rounded focus-visible:focus"
                                    title="Mark as read"
                                    aria-label="Mark notification as read"
                                >
                                    <i class="fas fa-check-circle text-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- ID Validations -->
                <?php foreach ($idValidationNotifications as $notification): 
                    $timeAgo = time_elapsed_string($notification['notification_date']);
                ?>
                <div class="notification-item validation bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="medium" data-timestamp="<?php echo strtotime($notification['notification_date']); ?>">
                    <div class="flex items-start p-6">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-yellow-500"></div>
                        <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3 mr-4">
                            <i class="fas fa-id-card text-yellow-600 text-lg" aria-hidden="true"></i>
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-grow">
                                    <h3 class="text-lg font-semibold text-sidebar-text">ID Validation Required</h3>
                                    <p class="text-sidebar-text mt-1">New identification document uploaded for verification</p>
                                </div>
                                <div class="flex items-center space-x-2 ml-4">
                                    <span class="h-3 w-3 bg-yellow-600 rounded-full" aria-label="Unread notification"></span>
                                    <span class="text-xs text-gray-500">Unread</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                                <div class="flex items-center">
                                    <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                    <span><?php echo $timeAgo; ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-user mr-2" aria-hidden="true"></i>
                                    <span>ID #<?php echo htmlspecialchars($notification['booking_id']); ?></span>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="<?php echo $notification['link_base']; ?>" 
                                        class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                        title="Review uploaded ID document"
                                    >
                                        <i class="fas fa-eye mr-2" aria-hidden="true"></i>Review ID
                                    </a>
                                </div>
                                <button 
                                    onclick="markAsRead(this)" 
                                    class="text-gray-400 hover:text-sidebar-accent transition-colors p-2 rounded focus-visible:focus"
                                    title="Mark as read"
                                    aria-label="Mark notification as read"
                                >
                                    <i class="fas fa-check-circle text-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Load More Button -->
        <?php if ($totalPending > 0): ?>
        <div class="text-center mt-8">
            <button 
                onclick="loadMoreNotifications()" 
                class="ripple-effect px-6 py-3 bg-sidebar-hover text-sidebar-text rounded-lg hover:bg-border transition-all duration-200 font-medium transform hover:scale-105 focus-visible:focus"
                id="load-more-btn"
                title="Load additional notifications"
            >
                <i class="fas fa-chevron-down mr-2" aria-hidden="true"></i>
                Load More Notifications
            </button>
        </div>

        <!-- Pagination Info -->
        <div class="text-center mt-4 text-sm text-gray-600">
            Showing <span id="current-count"><?php echo $totalPending; ?></span> of <span id="total-count"><?php echo $totalPending; ?></span> notifications
        </div>
        <?php endif; ?>
    </main>

    <!-- Help Modal -->
    <div id="help-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-sidebar-bg rounded-lg shadow-card max-w-2xl mx-4 max-h-96 overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-sidebar-text">Notification Help</h3>
                    <button 
                        onclick="closeHelpModal()" 
                        class="text-gray-400 hover:text-sidebar-accent p-1 rounded focus-visible:focus"
                        aria-label="Close help modal"
                    >
                        <i class="fas fa-times text-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="space-y-4 text-sm text-gray-700">
                    <div>
                        <h4 class="font-semibold text-sidebar-text mb-2">Notification Types</h4>
                        <ul class="space-y-2">
                            <li class="flex items-center"><span class="w-3 h-3 bg-blue-500 rounded-full mr-3"></span>Funeral Bookings - New service requests and updates</li>
                            <li class="flex items-center"><span class="w-3 h-3 bg-purple-500 rounded-full mr-3"></span>Life Plan Bookings - Insurance and payment plan requests</li>
                            <li class="flex items-center"><span class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></span>ID Validations - Document verification requests</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sidebar-text mb-2">Priority Levels</h4>
                        <ul class="space-y-2">
                            <li class="flex items-center"><span class="w-3 h-3 bg-red-500 rounded-full mr-3"></span>High Priority - Urgent actions required</li>
                            <li class="flex items-center"><span class="w-3 h-3 bg-orange-500 rounded-full mr-3"></span>Medium Priority - Important but not urgent</li>
                            <li class="flex items-center"><span class="w-3 h-3 bg-green-500 rounded-full mr-3"></span>Low Priority - Standard processing</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sidebar-text mb-2">Quick Actions</h4>
                        <p>Use keyboard shortcuts: <kbd class="bg-gray-100 px-2 py-1 rounded text-xs">Ctrl+A</kbd> to mark all as read, <kbd class="bg-gray-100 px-2 py-1 rounded text-xs">R</kbd> to refresh</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-sidebar-bg rounded-lg shadow-card max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-question-circle text-sidebar-accent text-2xl mr-3" aria-hidden="true"></i>
                    <h3 class="text-lg font-semibold text-sidebar-text">Confirm Action</h3>
                </div>
                <p class="text-gray-700 mb-6" id="confirmation-message">Are you sure you want to perform this action?</p>
                <div class="flex justify-end space-x-3">
                    <button 
                        onclick="closeConfirmationModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors focus-visible:focus"
                    >
                        Cancel
                    </button>
                    <button 
                        id="confirm-action-btn"
                        class="px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-colors focus-visible:focus"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 right-4 bg-success text-white px-6 py-4 rounded-lg shadow-card transform translate-x-full transition-transform duration-300 z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3" aria-hidden="true"></i>
            <span id="toast-message">Action completed successfully</span>
            <button onclick="closeToast()" class="ml-4 text-white hover:text-gray-200" aria-label="Close notification">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <script>
        // Global variables
        let currentFilter = 'all';
        let currentSort = 'newest';
        let searchQuery = '';
        let unreadOnly = false;
        let confirmationCallback = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeEventListeners();
            updateNotificationCounts();
            
                        // Set up keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+A to mark all as read
                if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    markAllAsRead();
                }
                // R to refresh
                if (e.key === 'r' && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    refreshNotifications();
                }
            });
        });

        // Initialize event listeners
        function initializeEventListeners() {
            // Tab click handlers
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active', 'border-sidebar-accent', 'text-sidebar-accent'));
                    this.classList.add('active', 'border-sidebar-accent', 'text-sidebar-accent');
                });
            });
        }

        // Filter notifications by type
        function filterNotifications(type) {
            currentFilter = type;
            applyFilters();
        }

        // Sort notifications
        function sortNotifications(sortType) {
            currentSort = sortType;
            applyFilters();
        }

        // Search notifications
        function searchNotifications(query) {
            searchQuery = query.toLowerCase();
            applyFilters();
        }

        // Toggle unread only filter
        function toggleUnreadOnly(checked) {
            unreadOnly = checked;
            applyFilters();
        }

        // Apply all active filters
        function applyFilters() {
            const notifications = document.querySelectorAll('.notification-item');
            let visibleCount = 0;
            
            notifications.forEach(notification => {
                const type = notification.classList.contains('funeral') ? 'funeral' : 
                             notification.classList.contains('lifeplan') ? 'lifeplan' : 'validation';
                const isUnread = notification.querySelector('.bg-blue-600, .bg-purple-600, .bg-yellow-600') !== null;
                const content = notification.textContent.toLowerCase();
                
                // Type filter
                const typeMatch = currentFilter === 'all' || type === currentFilter;
                
                // Search filter
                const searchMatch = searchQuery === '' || content.includes(searchQuery);
                
                // Unread filter
                const unreadMatch = !unreadOnly || isUnread;
                
                // Combined visibility
                const shouldShow = typeMatch && searchMatch && unreadMatch;
                
                notification.style.display = shouldShow ? 'block' : 'none';
                if (shouldShow) visibleCount++;
            });
            
            // Show/hide empty state
            const emptyState = document.getElementById('empty-state');
            if (visibleCount === 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
            
            // Update counts
            document.getElementById('current-count').textContent = visibleCount;
            
            // Sort notifications
            sortNotificationElements();
        }

        // Sort notification elements based on current sort
        function sortNotificationElements() {
            const container = document.getElementById('notifications-container');
            const notifications = Array.from(document.querySelectorAll('.notification-item[style*="display: block"]'));
            
            notifications.sort((a, b) => {
                const aPriority = a.getAttribute('data-priority');
                const bPriority = b.getAttribute('data-priority');
                const aTimestamp = parseInt(a.getAttribute('data-timestamp'));
                const bTimestamp = parseInt(b.getAttribute('data-timestamp'));
                
                if (currentSort === 'priority') {
                    // High priority first
                    if (aPriority === 'high' && bPriority !== 'high') return -1;
                    if (bPriority === 'high' && aPriority !== 'high') return 1;
                    // Then by timestamp (newest first)
                    return bTimestamp - aTimestamp;
                } else if (currentSort === 'type') {
                    // Group by type
                    const aType = a.classList.contains('funeral') ? 1 : 
                                 a.classList.contains('lifeplan') ? 2 : 3;
                    const bType = b.classList.contains('funeral') ? 1 : 
                                 b.classList.contains('lifeplan') ? 2 : 3;
                    if (aType !== bType) return aType - bType;
                    // Then by timestamp (newest first)
                    return bTimestamp - aTimestamp;
                } else if (currentSort === 'oldest') {
                    // Oldest first
                    return aTimestamp - bTimestamp;
                } else {
                    // Default: newest first
                    return bTimestamp - aTimestamp;
                }
            });
            
            // Re-append sorted elements
            notifications.forEach(notification => {
                container.appendChild(notification);
            });
        }

        // Mark a single notification as read
        function markAsRead(button) {
            const notification = button.closest('.notification-item');
            const dot = notification.querySelector('.bg-blue-600, .bg-purple-600, .bg-yellow-600');
            const statusText = notification.querySelector('.text-xs.text-gray-500');
            
            if (dot) {
                dot.remove();
                statusText.textContent = 'Read';
                
                // Show toast
                showToast('Notification marked as read');
                
                // Update counts
                updateNotificationCounts();
            }
        }

        // Mark all notifications as read
        function markAllAsRead() {
            showConfirmationModal(
                'Are you sure you want to mark all notifications as read?',
                function() {
                    document.querySelectorAll('.notification-item').forEach(notification => {
                        const dot = notification.querySelector('.bg-blue-600, .bg-purple-600, .bg-yellow-600');
                        const statusText = notification.querySelector('.text-xs.text-gray-500');
                        
                        if (dot) {
                            dot.remove();
                            if (statusText) statusText.textContent = 'Read';
                        }
                    });
                    
                    // Show toast
                    showToast('All notifications marked as read');
                    
                    // Update counts
                    updateNotificationCounts();
                    
                    closeConfirmationModal();
                }
            );
        }

        // Refresh notifications
        function refreshNotifications() {
            showLoadingOverlay();
            
            // Simulate API call with timeout
            setTimeout(() => {
                // In a real app, this would fetch new data from the server
                hideLoadingOverlay();
                showToast('Notifications refreshed');
            }, 1000);
        }

        // Load more notifications
        function loadMoreNotifications() {
            showLoadingOverlay();
            
            // Simulate API call with timeout
            setTimeout(() => {
                // In a real app, this would fetch more notifications from the server
                hideLoadingOverlay();
                showToast('More notifications loaded');
            }, 1000);
        }

        // Reset all filters
        function resetFilters() {
            currentFilter = 'all';
            currentSort = 'newest';
            searchQuery = '';
            unreadOnly = false;
            
            // Reset UI elements
            document.querySelectorAll('.filter-tab').forEach((tab, index) => {
                if (index === 0) {
                    tab.classList.add('active', 'border-sidebar-accent', 'text-sidebar-accent');
                } else {
                    tab.classList.remove('active', 'border-sidebar-accent', 'text-sidebar-accent');
                }
            });
            
            document.getElementById('sort-select').value = 'newest';
            document.querySelector('input[type="text"]').value = '';
            document.querySelector('input[type="checkbox"]').checked = false;
            
            // Reapply filters
            applyFilters();
        }

        // Update notification counts in the UI
        function updateNotificationCounts() {
            const unreadCounts = {
                funeral: document.querySelectorAll('.notification-item.funeral .bg-blue-600').length,
                lifeplan: document.querySelectorAll('.notification-item.lifeplan .bg-purple-600').length,
                validation: document.querySelectorAll('.notification-item.validation .bg-yellow-600').length
            };
            
            const totalUnread = unreadCounts.funeral + unreadCounts.lifeplan + unreadCounts.validation;
            
            // Update total count
            document.getElementById('total-notifications').textContent = totalUnread;
            
            // Update tab counts
            document.querySelector('.filter-tab:nth-child(1) span').textContent = totalUnread;
            document.querySelector('.filter-tab:nth-child(2) span').textContent = unreadCounts.funeral;
            document.querySelector('.filter-tab:nth-child(3) span').textContent = unreadCounts.lifeplan;
            document.querySelector('.filter-tab:nth-child(4) span').textContent = unreadCounts.validation;
        }

        // Modal and toast functions
        function showHelpModal() {
            document.getElementById('help-modal').classList.remove('hidden');
        }

        function closeHelpModal() {
            document.getElementById('help-modal').classList.add('hidden');
        }

        function showConfirmationModal(message, callback) {
            document.getElementById('confirmation-message').textContent = message;
            document.getElementById('confirmation-modal').classList.remove('hidden');
            confirmationCallback = callback;
            
            // Set up confirm button
            document.getElementById('confirm-action-btn').onclick = function() {
                if (confirmationCallback) confirmationCallback();
            };
        }

        function closeConfirmationModal() {
            document.getElementById('confirmation-modal').classList.add('hidden');
            confirmationCallback = null;
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
            
            // Auto-hide after 3 seconds
            setTimeout(closeToast, 3000);
        }

        function closeToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
        }

        // Loading overlay functions
        function showLoadingOverlay() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        }

        function hideLoadingOverlay() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }

        // Ripple effect for buttons
        document.querySelectorAll('.ripple-effect').forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    </script>
</body>
</html>