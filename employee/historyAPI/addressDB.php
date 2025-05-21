<?php
// Database connection
require_once '../../addressDB.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getRegions':
        $query = "SELECT region_id, region_name FROM table_region ORDER BY region_name";
        $result = mysqli_query($conn, $query);
        $regions = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $regions[] = $row;
        }
        echo json_encode($regions);
        break;

    case 'getProvinces':
        if (isset($_GET['region_id'])) {
            $region_id = mysqli_real_escape_string($conn, $_GET['region_id']);
            $query = "SELECT province_id, province_name FROM table_province WHERE region_id = ? ORDER BY province_name";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $region_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $provinces = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $provinces[] = $row;
            }
            echo json_encode($provinces);
        }
        break;

    case 'getMunicipalities':
        if (isset($_GET['province_id'])) {
            $province_id = mysqli_real_escape_string($conn, $_GET['province_id']);
            $query = "SELECT municipality_id, municipality_name FROM table_municipality WHERE province_id = ? ORDER BY municipality_name";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $province_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $municipalities = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $municipalities[] = $row;
            }
            echo json_encode($municipalities);
        }
        break;

    case 'getBarangays':
        if (isset($_GET['municipality_id'])) {
            $municipality_id = mysqli_real_escape_string($conn, $_GET['municipality_id']);
            $query = "SELECT barangay_id, barangay_name FROM table_barangay WHERE municipality_id = ? ORDER BY barangay_name";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $municipality_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $barangays = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $barangays[] = $row;
            }
            echo json_encode($barangays);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

// Close database connection
$conn->close();
?>