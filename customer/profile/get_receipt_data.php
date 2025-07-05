<?php
header('Content-Type: application/json');
require_once '../../db_connect.php'; // Adjust path as needed

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);
$packageType = $input['packageType'] ?? '';
$id = $input['id'] ?? '';

if (empty($packageType)) {
    echo json_encode(['error' => 'Package type is required']);
    exit;
}

if (empty($id)) {
    echo json_encode(['error' => 'ID is required']);
    exit;
}

try {

    switch ($packageType) {
        case 'traditional-funeral':
            $query = "
                SELECT 
                    i.installment_ID, 
                    b.branch_name, 
                    i.Client_Name, 
                    s.discounted_price,
                    i.Before_Balance, 
                    i.After_Payment_Balance, 
                    i.Payment_Amount, 
                    i.Payment_Timestamp, 
                    i.Method_of_Payment,
                    sv.service_name
                FROM 
                    installment_tb AS i
                JOIN 
                    branch_tb AS b ON i.Branch_id = b.branch_id
                LEFT JOIN 
                    sales_tb AS s ON i.sales_id = s.sales_id
                LEFT JOIN 
                    services_tb AS sv ON s.service_id = sv.service_id
                WHERE 
                    i.installment_ID = ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $id);
            break;

        case 'custom-package':
            $query = "
                SELECT 
                    b.branch_name, 
                    ci.custom_installment_id, 
                    ci.client_name, 
                    cs.discounted_price, 
                    ci.before_balance, 
                    ci.payment_amount, 
                    ci.after_payment_balance, 
                    ci.payment_timestamp, 
                    ci.method_of_payment,
                    'Custom Package' as service_name
                FROM 
                    custom_installment_tb as ci 
                JOIN 
                    branch_tb as b ON ci.branch_id = b.branch_id 
                JOIN 
                    customsales_tb as cs ON cs.customsales_id = ci.customsales_id
                WHERE 
                    ci.custom_installment_id = ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $id);
            break;

        case 'life-plan':
            $query = "
                SELECT 
                    l.lplogs_id, 
                    l.customer_id, 
                    l.lifeplan_id, 
                    l.installment_amount, 
                    l.current_balance, 
                    l.new_balance, 
                    l.log_date,
                    lp.custom_price as discounted_price,
                    b.branch_name,
                    s.service_name,
                    CONCAT(
                        UPPER(LEFT(u.first_name, 1)), LOWER(SUBSTRING(u.first_name, 2)), ' ',
                        IFNULL(CONCAT(UPPER(LEFT(u.middle_name, 1)), LOWER(SUBSTRING(u.middle_name, 2)), ' '), ''),
                        UPPER(LEFT(u.last_name, 1)), LOWER(SUBSTRING(u.last_name, 2)), ' ',
                        COALESCE(u.suffix, '')
                    ) AS planholder_name
                FROM 
                    lifeplan_logs_tb AS l
                JOIN 
                    lifeplan_tb AS lp ON l.lifeplan_id = lp.lifeplan_id
                JOIN 
                    branch_tb AS b ON lp.branch_id = b.branch_id
                JOIN 
                    services_tb AS s ON lp.service_id = s.service_id
                JOIN 
                    users AS u ON l.customer_id = u.id
                WHERE 
                    l.lplogs_id = ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $id);
            break;

        default:
            echo json_encode(['error' => 'Invalid package type']);
            exit;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        echo json_encode(['error' => 'No receipt data found']);
        exit;
    }

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>