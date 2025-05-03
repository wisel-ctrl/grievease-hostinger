<?php
require_once '../db_connect.php';

// Get parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$bookings_per_page = 5; // Should match your main file

// Calculate offset
$offset = ($page - 1) * $bookings_per_page;

// Build base query
$query = "SELECT b.booking_id, b.booking_date, b.status, 
          CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', COALESCE(u.suffix, '')) AS customer_name,
          COALESCE(s.service_name, 'Custom Package') AS service_name
          FROM booking_tb b
          JOIN users u ON b.customerID = u.id
          LEFT JOIN services_tb s ON b.service_id = s.service_id
          WHERE b.status = 'Pending'";

// Add search conditions if provided
if (!empty($search)) {
    $query .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR s.service_name LIKE ?)";
    $searchParam = "%$search%";
}

// Add sorting
switch($sort) {
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
    case 'oldest':
        $query .= " ORDER BY b.booking_date ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY b.booking_date DESC";
}

// Add pagination
$query .= " LIMIT ?, ?";

// Prepare and execute query
$stmt = $conn->prepare($query);

if (!empty($search)) {
    $stmt->bind_param("ssii", $searchParam, $searchParam, $offset, $bookings_per_page);
} else {
    $stmt->bind_param("ii", $offset, $bookings_per_page);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format booking ID
        $booking_id = "#BK-" . date('Y', strtotime($row['booking_date'])) . "-" . str_pad($row['booking_id'], 3, '0', STR_PAD_LEFT);

        // Format date
        $formatted_date = date('M j, Y', strtotime($row['booking_date']));

        // Status badge class
        $status_class = "bg-yellow-100 text-yellow-800 border border-yellow-200";
        $status_icon = "fa-clock";
        if ($row['status'] == 'Accepted') {
            $status_class = "bg-green-100 text-green-600 border border-green-200";
            $status_icon = "fa-check-circle";
        } elseif ($row['status'] == 'Declined') {
            $status_class = "bg-red-100 text-red-600 border border-red-200";
            $status_icon = "fa-times-circle";
        }
        ?>
        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium"><?php echo htmlspecialchars($booking_id); ?></td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['customer_name']); ?></td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    <?php echo htmlspecialchars($row['service_name']); ?>
                </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($formatted_date); ?></td>
            <td class="px-4 py-3.5 text-sm">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                    <i class="fas <?php echo $status_icon; ?> mr-1"></i> <?php echo htmlspecialchars($row['status']); ?>
                </span>
            </td>
            <td class="px-4 py-3.5 text-sm">
                <div class="flex space-x-2">
                    <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" 
                            title="View Details" 
                            onclick="openBookingDetails(<?php echo $row['booking_id']; ?>)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php
    }
} else {
    ?>
    <tr>
        <td colspan="6" class="px-4 py-6 text-sm text-center">
            <div class="flex flex-col items-center">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No bookings found</p>
            </div>
        </td>
    </tr>
    <?php
}
?>