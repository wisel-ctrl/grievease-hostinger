<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_type = $_POST['booking_type'] ?? '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$status = $_POST['status'] ?? 'all';
$sort = $_POST['sort'] ?? 'newest';
$items_per_page = 5; // Number of bookings per page
$offset = ($page - 1) * $items_per_page;

$response = ['success' => false, 'html' => '', 'pagination' => '', 'total_pages' => 0];

if ($booking_type === 'traditional') {
    // Build count query for Traditional Funeral bookings
    $count_query = "SELECT COUNT(*) as total FROM booking_tb WHERE customerID = ?";
    if ($status !== 'all') {
        $count_query .= " AND status = ?";
    }
    $stmt = $conn->prepare($count_query);
    if ($status !== 'all') {
        $stmt->bind_param("is", $user_id, $status);
    } else {
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $total_pages = ceil($total_items / $items_per_page);

    // Build main query for Traditional Funeral bookings
    $query = "SELECT b.*, s.service_name, s.selling_price, br.branch_name 
              FROM booking_tb b
              LEFT JOIN services_tb s ON b.service_id = s.service_id
              JOIN branch_tb br ON b.branch_id = br.branch_id
              WHERE b.customerID = ?";
    if ($status !== 'all') {
        $query .= " AND b.status = ?";
    }
    $query .= " ORDER BY b.booking_date " . ($sort === 'newest' ? 'DESC' : 'ASC') . " LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    if ($status !== 'all') {
        $stmt->bind_param("isii", $user_id, $status, $items_per_page, $offset);
    } else {
        $stmt->bind_param("iii", $user_id, $items_per_page, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = [];
    while ($booking = $result->fetch_assoc()) {
        $bookings[] = $booking;
    }
    $stmt->close();

    // Generate HTML for Traditional Funeral bookings
    ob_start();
    if (count($bookings) > 0) {
        foreach ($bookings as $booking) {
            $status_class = '';
            $status_text = '';
            switch ($booking['status']) {
                case 'Pending':
                    $status_class = 'bg-yellow-600/10 text-yellow-600';
                    $status_text = 'Pending';
                    break;
                case 'Accepted':
                    $status_class = 'bg-green-500/10 text-green-500';
                    $status_text = 'Accepted';
                    break;
                case 'Declined':
                    $status_class = 'bg-red-500/10 text-red-500';
                    $status_text = 'Declined';
                    break;
                case 'Cancelled':
                    $status_class = 'bg-gray-500/10 text-gray-500';
                    $status_text = 'Cancelled';
                    break;
                default:
                    $status_class = 'bg-blue-500/10 text-blue-500';
                    $status_text = $booking['status'];
            }
            $booking_date = date('F j, Y', strtotime($booking['booking_date']));
            $burial_date = $booking['deceased_dateOfBurial'] ? date('F j, Y', strtotime($booking['deceased_dateOfBurial'])) : 'Not set';
            $deceased_name = $booking['deceased_lname'] . ', ' . $booking['deceased_fname'];
            if (!empty($booking['deceased_midname'])) {
                $deceased_name .= ' ' . $booking['deceased_midname'];
            }
            if (!empty($booking['deceased_suffix'])) {
                $deceased_name .= ' ' . $booking['deceased_suffix'];
            }
            $service_name = $booking['service_name'] ?? 'Customize Package';
            $selling_price = $booking['selling_price'] ?? 0;
            $price = number_format($selling_price, 2);
            $amount_paid = $booking['amount_paid'] ? number_format($booking['amount_paid'], 2) : '0.00';
            $balance = number_format($selling_price - ($booking['amount_paid'] ?? 0), 2);
            ?>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-4">
                <div class="bg-navy bg-opacity-10 px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="<?php echo $status_class; ?> text-xs px-2 py-1 rounded-full"><?php echo $status_text; ?></span>
                        <p class="text-sm text-gray-500">Booking ID: <?php echo $booking['booking_id']; ?></p>
                    </div>
                    <h4 class="font-hedvig text-lg text-navy mb-2"><?php echo htmlspecialchars($service_name); ?></h4>
                </div>
                <div class="p-4 sm:p-6 space-y-3 sm:space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                            <p class="text-sm text-gray-500">Deceased Name</p>
                            <p class="text-navy"><?php echo htmlspecialchars(ucwords(strtolower($deceased_name))); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Branch</p>
                            <p class="text-navy"><?php echo htmlspecialchars(ucwords(strtolower($booking['branch_name']))); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Burial Date</p>
                            <p class="text-navy"><?php echo htmlspecialchars($burial_date); ?></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                            <p class="text-sm text-gray-500">Total Amount</p>
                            <p class="text-navy font-bold">₱<?php echo htmlspecialchars($price); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Amount Paid</p>
                            <p class="text-navy">₱<?php echo htmlspecialchars($amount_paid); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Balance</p>
                            <p class="text-navy">₱<?php echo htmlspecialchars($balance); ?></p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button class="view-details bg-navy/5 text-navy px-3 py-1 rounded hover:bg-navy/10 transition text-sm mr-2" data-booking="<?php echo $booking['booking_id']; ?>">
                            <i class="fas fa-file-alt mr-1"></i> View Details
                        </button>
                        <?php if ($booking['status'] === 'Accepted' && empty($booking['deathcert_url'])): ?>
                            <button class="upload-death-cert bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm mr-2" data-booking="<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-upload mr-1"></i> Upload Death Cert
                            </button>
                        <?php endif; ?>
                        <?php if ($booking['status'] === 'Accepted'): ?>
                            <button class="view-receipt bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition text-sm mr-2" data-booking="<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-receipt mr-1"></i> View Receipt
                            </button>
                        <?php endif; ?>
                        <?php if ($booking['status'] === 'Pending' || $booking['status'] === 'Declined'): ?>
                            <button class="modify-booking bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 transition text-sm mr-2" data-booking="<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-edit mr-1"></i> Modify
                            </button>
                        <?php endif; ?>
                        <?php if ($booking['status'] === 'Pending'): ?>
                            <button class="cancel-booking bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition text-sm" data-booking="<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </button>
                        <?php elseif ($booking['status'] === 'Cancelled'): ?>
                            <span class="text-gray-500 text-sm py-1 px-3">
                                <i class="fas fa-ban mr-1"></i> Cancelled
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<p class="text-gray-500">You have no traditional funeral bookings yet.</p>';
    }
    $response['html'] = ob_get_clean();

    // Generate pagination HTML (show only 3 pages, with First and Last buttons)
    ob_start();
    ?>
    <div class="flex justify-center items-center space-x-2">
        <button class="pagination-btn px-3 py-1 rounded-lg bg-gray-200 hover:bg-gray-300 disabled:opacity-50" data-page="1" <?php echo $page <= 1 ? 'disabled' : ''; ?>>First</button>
        <button class="pagination-btn px-3 py-1 rounded-lg bg-gray-200 hover:bg-gray-300 disabled:opacity-50" data-page="<?php echo $page - 1; ?>" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
        <?php
        // Calculate the range of pages to display (max 3 pages)
        $start_page = max(1, $page - 1);
        $end_page = min($total_pages, $page + 1);
        if ($end_page - $start_page < 2) {
            if ($start_page == 1) {
                $end_page = min($total_pages, $start_page + 2);
            } else {
                $end_page = min($total_pages, $page + 1);
                $start_page = max(1, $end_page - 2);
            }
        }
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <button class="pagination-btn px-3 py-1 rounded-lg <?php echo $i === $page ? 'bg-yellow-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
        <?php endfor; ?>
        <button class="pagination-btn px-3 py-1 rounded-lg bg-gray-200 hover:bg-gray-300 disabled:opacity-50" data-page="<?php echo $page + 1; ?>" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
        <button class="pagination-btn px-3 py-1 rounded-lg bg-gray-200 hover:bg-gray-300 disabled:opacity-50" data-page="<?php echo $total_pages; ?>" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Last</button>
    </div>
    <?php
    $response['pagination'] = ob_get_clean();
    $response['success'] = true;
    $response['total_pages'] = $total_pages;

} elseif ($booking_type === 'lifeplan') {
    // Build count query for Life Plan bookings
    $count_query = "SELECT COUNT(*) as total FROM lifeplan_booking_tb WHERE customer_id = ?";
    if ($status !== 'all') {
        $count_query .= " AND booking_status = ?";
    }
    $stmt = $conn->prepare($count_query);
    if ($status !== 'all') {
        $stmt->bind_param("is", $user_id, $status);
    } else {
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $total_pages = ceil($total_items / $items_per_page);

    // Build main query for Life Plan bookings
    $query = "SELECT lb.*, s.service_name, s.selling_price as package_price, br.branch_name 
              FROM lifeplan_booking_tb lb
              LEFT JOIN services_tb s ON lb.service_id = s.service_id
              JOIN branch_tb br ON lb.branch_id = br.branch_id
              WHERE lb.customer_id = ?";
    if ($status !== 'all') {
        $query .= " AND lb.booking_status = ?";
    }
    $query .= " ORDER BY lb.initial_date " . ($sort === 'newest' ? 'DESC' : 'ASC') . " LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    if ($status !== 'all') {
        $stmt->bind_param("isii", $user_id, $status, $items_per_page, $offset);
    } else {
        $stmt->bind_param("iii", $user_id, $items_per_page, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $lifeplan_bookings = [];
    while ($booking = $result->fetch_assoc()) {
        $lifeplan_bookings[] = $booking;
    }
    $stmt->close();

    // Generate HTML for Life Plan bookings
    ob_start();
    if (count($lifeplan_bookings) > 0) {
        foreach ($lifeplan_bookings as $booking) {
            $status_class = '';
            $status_text = '';
            switch ($booking['booking_status']) {
                case 'pending':
                    $status_class = 'bg-yellow-600/10 text-yellow-600';
                    $status_text = 'Pending';
                    break;
                case 'accepted':
                    $status_class = 'bg-green-500/10 text-green-500';
                    $status_text = 'Accepted';
                    break;
                case 'decline':
                    $status_class = 'bg-red-500/10 text-red-500';
                    $status_text = 'Declined';
                    break;
                case 'cancel':
                    $status_class = 'bg-gray-500/10 text-gray-500';
                    $status_text = 'Cancelled';
                    break;
                default:
                    $status_class = 'bg-blue-500/10 text-blue-500';
                    $status_text = ucfirst($booking['booking_status']);
            }
            $booking_date = date('F j, Y', strtotime($booking['initial_date']));
            $end_date = $booking['end_date'] ? date('F j, Y', strtotime($booking['end_date'])) : 'Not set';
            $beneficiary_name = $booking['benefeciary_lname'] . ', ' . $booking['benefeciary_fname'];
            if (!empty($booking['benefeciary_mname'])) {
                $beneficiary_name .= ' ' . $booking['benefeciary_mname'];
            }
            if (!empty($booking['benefeciary_suffix'])) {
                $beneficiary_name .= ' ' . $booking['benefeciary_suffix'];
            }
            $service_name = $booking['service_name'] ?? 'Customize Package';
            $selling_price = $booking['package_price'] ?? 0;
            $price = number_format($selling_price, 2);
            $amount_paid = $booking['amount_paid'] ? number_format($booking['amount_paid'], 2) : '0.00';
            $balance = number_format($selling_price - ($booking['amount_paid'] ?? 0), 2);
            ?>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-4">
                <div class="bg-blue-600 bg-opacity-10 px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="<?php echo $status_class; ?> text-xs px-2 py-1 rounded-full"><?php echo $status_text; ?></span>
                        <p class="text-sm text-gray-500">Life Plan ID: <?php echo $booking['lpbooking_id']; ?></p>
                    </div>
                    <h4 class="font-hedvig text-lg text-blue-600 mb-2"><?php echo htmlspecialchars($service_name); ?> (Life Plan)</h4>
                </div>
                <div class="p-4 sm:p-6 space-y-3 sm:space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                            <p class="text-sm text-gray-500">Beneficiary Name</p>
                            <p class="text-blue-600"><?php echo htmlspecialchars(ucwords(strtolower($beneficiary_name))); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Branch</p>
                            <p class="text-blue-600"><?php echo htmlspecialchars(ucwords(strtolower($booking['branch_name']))); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">End Date</p>
                            <p class="text-blue-600"><?php echo htmlspecialchars($end_date); ?></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                            <p class="text-sm text-gray-500">Total Amount</p>
                            <p class="text-blue-600 font-bold">₱<?php echo htmlspecialchars($price); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Amount Paid</p>
                            <p class="text-blue-600">₱<?php echo htmlspecialchars($amount_paid); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Balance</p>
                            <p class="text-blue-600">₱<?php echo htmlspecialchars($balance); ?></p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button class="view-lifeplan-details bg-blue-600/5 text-blue-600 px-3 py-1 rounded hover:bg-blue-600/10 transition text-sm mr-2" data-booking="<?php echo $booking['lpbooking_id']; ?>">
                            <i class="fas fa-file-alt mr-1"></i> View Details
                        </button>
                        <?php if ($booking['booking_status'] === 'pending' || $booking['booking_status'] === 'decline'): ?>
                            <button class="modify-lifeplan-booking bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 transition text-sm mr-2" data-booking="<?php echo $booking['lpbooking_id']; ?>">
                                <i class="fas fa-edit mr-1"></i> Modify
                            </button>
                        <?php endif; ?>
                        <?php if ($booking['booking_status'] === 'pending'): ?>
                            <button class="cancel-lifeplan-booking bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition text-sm" data-booking="<?php echo $booking['lpbooking_id']; ?>">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<p class="text-gray-500">You have no life plan bookings yet.</p>';
    }
    $response['html'] = ob_get_clean();

    // Generate pagination HTML (show only 3 pages, with First and Last buttons)
    ob_start();
    ?>
    <div class="flex justify-center items-center space-x-2">
        <button class="pagination-btn px-3 py-1 rounded-lg bg-gray-200 hover:bg-gray-300 disabled:opacity-50" data-page="1" <?php echo $page <= 1 ? 'disabled' : ''; ?>>First</button>
        <button class="pagination-btn px-3 py-1 rounded-lg bg-gray-200 hover:bg-gray-300 disabled:opacity-50" data-page="<?php echo $page - 1; ?>" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
        <?php
        // Calculate the range of pages to display (max 3 pages)
        $start_page = max(1, $page - 1);
        $end_page = min($total_pages, $page + 1);
        if ($end_page - $start_page < 2) {
            if ($start_page == 1) {
                $end_page = min($total_pages, $start_page + 2);
            } else {
                $end_page = min($total_pages, $page + 1);
                $start_page = max(1, $end_page - 2);
            }
        }
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <button class="pagination-btn px-3 py-1 rounded-lg <?php echo $i === $page ? 'bg-yellow-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
        <?php endfor; ?>
        <button class="pagination-btn px-3 py-1 rounded-lg bg-gray-200 hover:bg-gray-300 disabled:opacity-50" data-page="<?php echo $page + 1; ?>" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
        <button class="pagination-btn px-3 py-1 rounded-lg bg-gray-200 hover:bg-gray-300 disabled:opacity-50" data-page="<?php echo $total_pages; ?>" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Last</button>
    </div>
    <?php
    $response['pagination'] = ob_get_clean();
    $response['success'] = true;
    $response['total_pages'] = $total_pages;
}

echo json_encode($response);
?>