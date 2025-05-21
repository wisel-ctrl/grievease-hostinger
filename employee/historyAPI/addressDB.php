<?php
// Database connection
$addressDB = new mysqli('localhost', 'u349622494_phAddress', 'Grievease_2k25', 'u349622494_phAddress');

if ($addressDB->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $addressDB->connect_error]));
}

// Set header to return JSON
header('Content-Type: application/json');

// Get the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'getRegions':
            $query = "SELECT region_id, region_name FROM table_region ORDER BY region_name";
            $result = $addressDB->query($query);
            if (!$result) {
                throw new Exception($addressDB->error);
            }
            $regions = array();
            while ($row = $result->fetch_assoc()) {
                $regions[] = $row;
            }
            echo json_encode($regions);
            break;

        case 'getProvinces':
            if (isset($_GET['region_id'])) {
                $region_id = $addressDB->real_escape_string($_GET['region_id']);
                $query = "SELECT province_id, province_name FROM table_province WHERE region_id = ? ORDER BY province_name";
                $stmt = $addressDB->prepare($query);
                if (!$stmt) {
                    throw new Exception($addressDB->error);
                }
                $stmt->bind_param("i", $region_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $provinces = array();
                while ($row = $result->fetch_assoc()) {
                    $provinces[] = $row;
                }
                echo json_encode($provinces);
                $stmt->close();
            }
            break;

        case 'getMunicipalities':
            if (isset($_GET['province_id'])) {
                $province_id = $addressDB->real_escape_string($_GET['province_id']);
                $query = "SELECT municipality_id, municipality_name FROM table_municipality WHERE province_id = ? ORDER BY municipality_name";
                $stmt = $addressDB->prepare($query);
                if (!$stmt) {
                    throw new Exception($addressDB->error);
                }
                $stmt->bind_param("i", $province_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $municipalities = array();
                while ($row = $result->fetch_assoc()) {
                    $municipalities[] = $row;
                }
                echo json_encode($municipalities);
                $stmt->close();
            }
            break;

        case 'getBarangays':
            if (isset($_GET['municipality_id'])) {
                $municipality_id = $addressDB->real_escape_string($_GET['municipality_id']);
                $query = "SELECT barangay_id, barangay_name FROM table_barangay WHERE municipality_id = ? ORDER BY barangay_name";
                $stmt = $addressDB->prepare($query);
                if (!$stmt) {
                    throw new Exception($addressDB->error);
                }
                $stmt->bind_param("i", $municipality_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $barangays = array();
                while ($row = $result->fetch_assoc()) {
                    $barangays[] = $row;
                }
                echo json_encode($barangays);
                $stmt->close();
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

// Close database connection
$addressDB->close();
?>