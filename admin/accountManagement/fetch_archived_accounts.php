<?php
require_once('../../db_connect.php');

$userType = isset($_GET['user_type']) ? (int)$_GET['user_type'] : 0;

// Validate user type (2 for employee, 3 for customer)
if (!in_array($userType, [2, 3])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid user type']);
    exit;
}

// Fetch archived accounts (is_verified = 0)
$sql = "SELECT id, first_name, last_name, email, user_type 
        FROM users 
        WHERE user_type = ? AND is_verified = 0
        ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userType);
$stmt->execute();
$result = $stmt->get_result();

$tableContent = '';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format ID based on user type
        $formattedId = ($userType == 2) 
            ? "#EMP-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT)
            : "#CUST-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
        
        $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
        $email = htmlspecialchars($row['email']);
        $userTypeText = ($userType == 2) ? 'Employee' : 'Customer';
        
        $tableContent .= '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">' . $formattedId . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . $fullName . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . $email . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . $userTypeText . '</td>
            <td class="p-4 text-sm">
                <button class="px-3 py-1 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all" 
                        onclick="unarchiveAccount(' . $row['id'] . ')">
                    <i class="fas fa-undo-alt mr-1"></i> Unarchive
                </button>
            </td>
        </tr>';
    }
} else {
    $tableContent = '<tr class="border-b border-sidebar-border">
        <td colspan="5" class="p-4 text-sm text-center text-gray-500">No archived accounts found</td>
    </tr>';
}

header('Content-Type: application/json');
echo json_encode(['tableContent' => $tableContent]);

$conn->close();
?>