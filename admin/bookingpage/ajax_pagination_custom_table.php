<?php
require_once '../../db_connect.php';
session_start();

// Get parameters from request
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$sort = isset($_POST['sort']) ? $_POST['sort'] : 'newest';

// Validate and sanitize inputs
$page = max(1, $page);
$bookings_per_page = 5; // Changed to 5 per page as requested
$offset = ($page - 1) * $bookings_per_page;

// Build base query for custom bookings (adjust according to your custom bookings table structure)
$query = "SELECT b.booking_id, b.booking_date, b.status, 
          CONCAT(
              UPPER(LEFT(u.first_name, 1)), LOWER(SUBSTRING(u.first_name, 2)), ' ',
              UPPER(LEFT(COALESCE(u.middle_name, ''), 1)), LOWER(SUBSTRING(COALESCE(u.middle_name, ''), 2)), ' ',
              UPPER(LEFT(u.last_name, 1)), LOWER(SUBSTRING(u.last_name, 2)), ' ',
              UPPER(LEFT(COALESCE(u.suffix, ''), 1)), LOWER(SUBSTRING(COALESCE(u.suffix, ''), 2))
          ) AS customer_name,
          'Custom Package' AS service_name
          FROM booking_tb b
          JOIN users u ON b.customerID = u.id
          WHERE b.service_id IS NULL"; // Assuming custom bookings have NULL service_id

// Add search conditions if provided
if (!empty($search)) {
    $query .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR b.booking_id LIKE ?)";
    $searchParam = "%$search%";
}

// Add sorting
switch ($sort) {
    case 'id_asc':
        $query .= " ORDER BY b.booking_id ASC";
        break;
    case 'id_desc':
        $query .= " ORDER BY b.booking_id DESC";
        break;
    case 'customer_asc':
        $query .= " ORDER BY customer_name ASC";
        break;
    case 'customer_desc':
        $query .= " ORDER BY customer_name DESC";
        break;
    case 'newest':
        $query .= " ORDER BY b.booking_date DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY b.booking_date ASC";
        break;
    default:
        $query .= " ORDER BY b.booking_date DESC";
}

// Count total bookings for pagination
$countQuery = "SELECT COUNT(*) as total FROM booking_tb b
               JOIN users u ON b.customerID = u.id
               WHERE b.service_id IS NULL";

if (!empty($search)) {
    $countQuery .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR b.booking_id LIKE ?)";
}

$stmt = $conn->prepare($countQuery);
if (!empty($search)) {
    $stmt->bind_param("ss", $searchParam, $searchParam);
}
$stmt->execute();
$result = $stmt->get_result();
$total_bookings = $result->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $bookings_per_page);

// Get paginated data
$query .= " LIMIT ?, ?";
$stmt = $conn->prepare($query);

if (!empty($search)) {
    $stmt->bind_param("ssii", $searchParam, $searchParam, $offset, $bookings_per_page);
} else {
    $stmt->bind_param("ii", $offset, $bookings_per_page);
}

$stmt->execute();
$result = $stmt->get_result();

// Generate HTML for bookings
$html = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $booking_id = "#BK-" . date('Y', strtotime($row['booking_date'])) . "-" . str_pad($row['booking_id'], 3, '0', STR_PAD_LEFT);
        $formatted_date = date('M j, Y', strtotime($row['booking_date']));
        
        // Customize status display as needed
        $status_class = "bg-yellow-100 text-yellow-800 border border-yellow-200";
        $status_icon = "fa-clock";
        
        $html .= '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">';
        $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">' . htmlspecialchars($booking_id) . '</td>';
        $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text">' . htmlspecialchars($row['customer_name']) . '</td>';
        $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text">';
        $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">';
        $html .= htmlspecialchars($row['service_name']) . '</span></td>';
        $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text">' . htmlspecialchars($formatted_date) . '</td>';
        $html .= '<td class="px-4 py-3.5 text-sm">';
        $html .= '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ' . $status_class . '">';
        $html .= '<i class="fas ' . $status_icon . ' mr-1"></i> ' . htmlspecialchars($row['status']) . '</span></td>';
        $html .= '<td class="px-4 py-3.5 text-sm">';
        $html .= '<div class="flex space-x-2">';
        $html .= '<button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" ';
        $html .= 'title="View Details" onclick="openCustomBookingDetails(' . $row['booking_id'] . ')">';
        $html .= '<i class="fas fa-eye"></i></button></div></td></tr>';
    }
} else {
    $html .= '<tr><td colspan="6" class="px-4 py-6 text-sm text-center">';
    $html .= '<div class="flex flex-col items-center">';
    $html .= '<i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>';
    $html .= '<p class="text-gray-500">No custom bookings found</p></div></td></tr>';
}

// Prepare response
$response = [
    'html' => $html,
    'paginationInfo' => $total_bookings > 0 ? 
        "Showing " . ($offset + 1) . " - " . min($offset + $bookings_per_page, $total_bookings) . " of $total_bookings " . ($total_bookings != 1 ? "bookings" : "booking") : 
        "No custom bookings found",
    'totalBookings' => $total_bookings,
    'currentPage' => $page
];

header('Content-Type: application/json');
echo json_encode($response);
?>