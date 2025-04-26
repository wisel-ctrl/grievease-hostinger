<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}
// Check for admin user type (user_type = 1)
if ($_SESSION['user_type'] != 1) {
    // Redirect to appropriate page based on user type
    switch ($_SESSION['user_type']) {
        case 2:
            header("Location: ../employee/index.php");
            break;
        case 3:
            header("Location: ../customer/index.php");
            break;
        default:
            // Invalid user_type
            session_destroy();
            header("Location: ../Landing_Page/login.php");
    }
    exit();
}
// Optional: Check for session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session has expired
    session_unset();
    session_destroy();
    header("Location: ../Landing_Page/login.php?timeout=1");
    exit();
}
// Update last activity time
$_SESSION['last_activity'] = time();
// Prevent caching for authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include database connection
require_once '../db_connect.php';

// Process ID validation requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    
    if ($action === 'approve') {
        // Update ID status to valid
        $stmt = $conn->prepare("UPDATE valid_id_tb SET is_validated = 'valid' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Also update the user's validated_id status
        $stmt = $conn->prepare("UPDATE users SET validated_id = 'yes' WHERE id = (SELECT id FROM valid_id_tb WHERE id = ?)");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Set success message
        $_SESSION['message'] = "ID successfully approved.";
        $_SESSION['message_type'] = "success";
    } 
    elseif ($action === 'deny') {
        $decline_reason = filter_input(INPUT_POST, 'decline_reason', FILTER_SANITIZE_STRING);
        
        if (empty($decline_reason)) {
            $_SESSION['message'] = "Please select a reason for declining the ID.";
            $_SESSION['message_type'] = "error";
        } else {
            // Update ID status to denied with reason
            $stmt = $conn->prepare("UPDATE valid_id_tb SET is_validated = 'denied', decline_reason = ? WHERE id = ?");
            $stmt->bind_param("si", $decline_reason, $id);
            $stmt->execute();
            
            // Set info message
            $_SESSION['message'] = "ID request denied. Reason: " . htmlspecialchars($decline_reason);
            $_SESSION['message_type'] = "info";
        }
    } 
    // Redirect to refresh the page
    header("Location: id_confirmation.php");
    exit();
}

// Fetch pending ID validation requests with branch location
$stmt = $conn->prepare("
    SELECT v.id as validation_id, v.image_path, v.is_validated, v.id,
           u.first_name, u.last_name, u.email, u.phone_number, u.branch_loc,
           b.branch_name
    FROM valid_id_tb v
    JOIN users u ON v.id = u.id
    LEFT JOIN branch_tb b ON u.branch_loc = b.branch_id
    WHERE v.is_validated = 'no'
    ORDER BY v.id DESC
");
$stmt->execute();
$result = $stmt->get_result();
$pending_ids = $result->fetch_all(MYSQLI_ASSOC);

// Count stats
$total_pending = count($pending_ids);

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM valid_id_tb WHERE is_validated = 'valid'");
$stmt->execute();
$result = $stmt->get_result();
$approved = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM valid_id_tb WHERE is_validated = 'denied'");
$stmt->execute();
$result = $stmt->get_result();
$denied = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Confirmation</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Hedvig+Letters+Serif:opsz@12..24&display=swap" rel="stylesheet">
    <!-- Include SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
     /* Base Typography */
      body {
        font-family: 'Hedvig Letters Serif', serif;
      }
      
      /* Message status indicators */
      .message-new {
        border-left: 3px solid #CA8A04; /* Using your sidebar accent color */
      }
      
      .message-read {
        border-left: 3px solid transparent;
      }
      
      /* Header Styles */
      h1 {
        font-family: 'Cinzel', serif;
        font-size: 1.5rem; /* 24px */
        font-weight: 700;
        color: #1E293B; /* slate-800 */
      }
      
      h2 {
        font-family: 'Cinzel', serif;
        font-size: 1.25rem; /* 20px */
        font-weight: 600;
        color: #1E293B; /* slate-800 */
      }
      
      h3 {
        font-family: 'Cinzel', serif;
        font-size: 1.125rem; /* 18px */
        font-weight: 600;
        color: #1E293B; /* slate-800 */
      }
      
      h5 {
        font-family: 'Cinzel', serif;
        font-size: 0.875rem; /* 14px */
        font-weight: 500;
        color: #CA8A04; /* sidebar accent color */
        text-transform: uppercase;
        letter-spacing: 0.05em;
      }
      
      /* Text Colors */
      .text-sidebar-accent {
        color: #CA8A04;
      }
      
      .text-sidebar-text {
        color: #334155; /* slate-700 */
      }
      
      /* Button Styles */
      button {
        font-family: 'Hedvig Letters Serif', serif;
        font-size: 0.875rem; /* 14px */
        transition: all 0.3s ease;
      }
      
      /* Input Fields */
      input, textarea {
        font-family: 'Hedvig Letters Serif', serif;
        font-size: 0.875rem; /* 14px */
        border: 1px solid #CBD5E1; /* slate-300 */
        border-radius: 0.375rem; /* 6px */
      }
      
      /* Icons */
      .fas {
        color: #64748B; /* slate-500 */
        transition: color 0.3s ease;
      }
      
      /* Hover States */
      button:hover .fas {
        color: #1E293B; /* slate-800 */
      }
      
      /* Message Bubbles */
      .admin-message {
        background-color: #CA8A04; /* sidebar accent */
        color: white;
      }
      
      .customer-message {
        background-color: #F1F5F9; /* slate-100 */
        color: #1E293B; /* slate-800 */
      }
      
      /* Timestamp Text */
      .message-time {
        font-size: 0.75rem; /* 12px */
        color: #64748B; /* slate-500 */
      }
      
      /* Badges */
      .badge {
        font-size: 0.75rem; /* 12px */
        background-color: #CA8A04; /* sidebar accent */
        color: white;
      }
      
      /* Ensure sidebar maintains styling */
      #sidebar {
        background-color: white !important;
        z-index: 50 !important;
        font-family: 'Hedvig Letters Serif', serif;
      }
      
      /* Mobile Responsiveness */
      @media (max-width: 768px) {
        #sidebar.translate-x-0 {
          background-color: white !important;
          box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        h1 {
          font-size: 1.25rem; /* 20px */
        }
        
        h2 {
          font-size: 1.125rem; /* 18px */
        }
      }
      
      /* Custom scrollbar to match sidebar */
      .scrollbar-thin::-webkit-scrollbar {
        width: 4px;
        height: 4px;
      }
      .scrollbar-thin::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
      }
      .scrollbar-thin::-webkit-scrollbar-thumb {
        background: rgba(202, 138, 4, 0.6);
        border-radius: 4px;
      }
      .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: rgba(202, 138, 4, 0.9);
      }
      
      /* ID Card styles */
      .id-card {
        transition: all 0.3s ease;
      }
      
      .id-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      }
      
      /* Image modal */
      .modal {
        display: none;
        position: fixed;
        z-index: 100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.8);
      }
      
      .modal-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 90%;
      }
      
      .modal-content img {
        margin: auto;
        display: block;
        max-width: 100%;
        max-height: 90vh;
      }
      
      .close {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
      }
      
      .close:hover,
      .close:focus {
        color: #CA8A04;
        text-decoration: none;
        cursor: pointer;
      }
    </style>
</head>
<body class="flex bg-gray-50">
<?php include 'admin_sidebar.php'; ?>

<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <h1 class="mb-4 md:mb-0">ID Verification Management</h1>
            <div class="flex space-x-4">
                <!-- Stats Cards -->
                <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-xs uppercase text-gray-500 font-medium">Pending</div>
                    <div class="text-xl font-bold text-yellow-500"><?php echo $total_pending; ?></div>
                </div>
                <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-xs uppercase text-gray-500 font-medium">Approved</div>
                    <div class="text-xl font-bold text-green-500"><?php echo $approved; ?></div>
                </div>
                <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-xs uppercase text-gray-500 font-medium">Declined</div>
                    <div class="text-xl font-bold text-red-500"><?php echo $denied; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Notifications/Messages -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-blue-50 text-blue-800 border border-blue-200'; ?> flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas <?php echo $_SESSION['message_type'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-info-circle text-blue-500'; ?> mr-3"></i>
                    <span><?php echo $_SESSION['message']; ?></span>
                </div>
                <button class="text-gray-500 hover:text-gray-700 focus:outline-none" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php 
            // Clear message after displaying
            unset($_SESSION['message']); 
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>
        
        <!-- Main Content -->
        <?php if($total_pending > 0): ?>
            <div class="mb-4">
                <h2 class="mb-2">Pending ID Verifications</h2>
                <p class="text-sm text-gray-600">Review and process submitted identification documents.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($pending_ids as $id_request): ?>
                    <div class="id-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                        <!-- ID Preview -->
                        <div class="relative h-48 overflow-hidden bg-gray-100 cursor-pointer" onclick="openModal('uploads/valid_ids/<?php echo htmlspecialchars(basename($id_request['image_path'])); ?>')">
                            <img src="uploads/valid_ids/<?php echo htmlspecialchars(basename($id_request['image_path'])); ?>" alt="ID image" class="w-full h-full object-cover">
                            <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-center py-1 text-xs">
                                Click to enlarge
                            </div>
                        </div>
                        
                        <!-- ID Information -->
                        <div class="p-4">
                            <h3 class="font-medium"><?php echo htmlspecialchars($id_request['first_name'] . ' ' . $id_request['last_name']); ?></h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                    <?php echo htmlspecialchars($id_request['email']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-phone mr-2 text-gray-400"></i>
                                    <?php echo htmlspecialchars($id_request['phone_number']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                                    <?php echo htmlspecialchars($id_request['branch_name'] ? $id_request['branch_name'] : 'No branch assigned'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex border-t border-gray-200">
                            <form method="POST" action="" class="w-1/2" id="approveForm<?php echo $id_request['validation_id']; ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_request['validation_id']); ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="button" class="w-full py-3 text-green-600 hover:bg-green-50 focus:outline-none focus:bg-green-50 transition duration-150"
                                        onclick="confirmAction('approveForm<?php echo $id_request['validation_id']; ?>', 'approve')">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Approve
                                </button>
                            </form>
                            <div class="w-px bg-gray-200"></div>
                            <form method="POST" action="" class="w-1/2" id="denyForm<?php echo $id_request['validation_id']; ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_request['validation_id']); ?>">
                                <input type="hidden" name="action" value="deny">
                                <button type="button" class="w-full py-3 text-red-600 hover:bg-red-50 focus:outline-none focus:bg-red-50 transition duration-150"
                                        onclick="confirmDeny('denyForm<?php echo $id_request['validation_id']; ?>')">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    Decline
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="text-5xl text-gray-300 mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="text-xl font-medium text-gray-700 mb-2">All Caught Up!</h2>
                <p class="text-gray-500">There are no pending ID verification requests at this time.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <div class="modal-content">
        <img id="modalImage" src="" alt="ID Document Full View">
    </div>
</div>


<script>
    // Image modal functionality
    function openModal(imagePath) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        
        modal.style.display = "flex";
        modalImg.src = imagePath;
    }
    
    function closeModal() {
        document.getElementById('imageModal').style.display = "none";
    }
    
    // Close modal when clicking outside the image
    window.onclick = function(event) {
        const modal = document.getElementById('imageModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    
    // Handle escape key to close modal
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeModal();
        }
    });
    
    // SweetAlert confirmation for approve/deny actions
    function confirmAction(formId, actionType) {
        const actionText = actionType === 'approve' ? 'approve' : 'deny';
        const actionTitle = actionType === 'approve' ? 'Approve ID Verification' : 'Deny ID Verification';
        const actionMessage = actionType === 'approve' 
            ? 'Are you sure you want to approve this ID verification?' 
            : 'Are you sure you want to deny this ID verification?';
        const iconType = actionType === 'approve' ? 'question' : 'warning';
        
        Swal.fire({
            title: actionTitle,
            text: actionMessage,
            icon: iconType,
            showCancelButton: true,
            confirmButtonColor: actionType === 'approve' ? '#10B981' : '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, ' + actionText + ' it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }
    
let currentDenyFormId = null;

function openCustomReasonModal(formId) {
    currentDenyFormId = formId;
    document.getElementById('customReasonModal').classList.remove('hidden');
}

function closeCustomReasonModal() {
    document.getElementById('customReasonModal').classList.add('hidden');
    document.getElementById('customDeclineReason').value = '';
}

function cancelCustomReason() {
    closeCustomReasonModal();
    // Reopen the SweetAlert
    confirmDeny(currentDenyFormId);
}

function saveCustomReason() {
    const customReason = document.getElementById('customDeclineReason').value.trim();
    if (!customReason) {
        Swal.fire('Error', 'Please enter a decline reason', 'error');
        return;
    }
    
    // Submit the form with the custom reason
    const form = document.getElementById(currentDenyFormId);
    
    // Remove any existing hidden input for decline_reason
    const existingInput = form.querySelector('input[name="decline_reason"]');
    if (existingInput) {
        form.removeChild(existingInput);
    }
    
    // Add the custom reason to the form
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'decline_reason';
    input.value = customReason;
    form.appendChild(input);
    
    // Submit the form
    form.submit();
}

function confirmDeny(formId) {
    const declineReasons = [
        'Incomplete Document Visibility',
        'Cropped or Cut-off Text',
        'Blurry or Low-Quality Image',
        'Glare or Shadows',
        'Missing Critical Details',
        'Others...'
    ];
    
    // Create HTML for the select dropdown
    let selectHtml = '<select id="swalDeclineReason" class="w-full mt-3 p-2 border border-gray-300 rounded focus:ring-yellow-500 focus:border-yellow-500">';
    selectHtml += '<option value="">Select a reason for decline</option>';
    declineReasons.forEach(reason => {
        selectHtml += `<option value="${reason}">${reason}</option>`;
    });
    selectHtml += '</select>';
    
    Swal.fire({
        title: 'Deny ID Verification',
        html: `Are you sure you want to deny this ID verification?<br><br>${selectHtml}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Confirm Decline',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        preConfirm: () => {
            const reason = document.getElementById('swalDeclineReason').value;
            if (!reason) {
                Swal.showValidationMessage('Please select a reason for decline');
                return false;
            }
            
            if (reason === 'Others...') {
                // Show custom reason modal and prevent form submission
                openCustomReasonModal(formId);
                return false;
            }
            
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const form = document.getElementById(formId);
            
            // Remove any existing hidden input for decline_reason
            const existingInput = form.querySelector('input[name="decline_reason"]');
            if (existingInput) {
                form.removeChild(existingInput);
            }
            
            // Add the selected reason to the form
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'decline_reason';
            input.value = result.value;
            form.appendChild(input);
            
            // Submit the form
            form.submit();
        }
    });
    
    // Add event listener for the select change
    setTimeout(() => {
        const selectElement = document.getElementById('swalDeclineReason');
        if (selectElement) {
            selectElement.addEventListener('change', function() {
                if (this.value === 'Others...') {
                    // Close the SweetAlert and open custom reason modal
                    Swal.close();
                    openCustomReasonModal(formId);
                }
            });
        }
    }, 100);
}
</script>

<!-- Custom Decline Reason Modal -->
<div id="customReasonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-medium mb-4">Enter Custom Decline Reason</h3>
        <textarea id="customDeclineReason" class="w-full p-3 border border-gray-300 rounded mb-4" rows="4" placeholder="Please specify the reason for declining..."></textarea>
        <div class="flex justify-end space-x-3">
            <button onclick="cancelCustomReason()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
            <button onclick="saveCustomReason()" class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">Save Reason</button>
        </div>
    </div>
</div>
</body>
</html>