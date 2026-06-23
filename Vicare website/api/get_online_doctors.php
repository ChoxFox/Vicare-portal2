<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suppress internal browser HTML warnings to maintain pure JSON data stream integration
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Links natively directly to your renamed database config file in the same folder
include_once "database-config.php";

$response = ["status" => "error", "doctors" => [], "message" => "An unhandled directory transaction exception occurred."];

if (!isset($conn) || !$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection engine link layer offline."]);
    exit();
}

// Scans doctor directory rows where the active state is flagged as Online
$query = "SELECT name, specialty, image, status FROM doctors WHERE status = 'Online' ORDER BY name ASC";
$result = mysqli_query($conn, $query);

if ($result) {
    $doctorsList = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $doctorsList[] = [
            "name" => $row['name'],
            "specialty" => $row['specialty'],
            "image" => $row['image'] ? trim($row['image']) : '149071.png',
            "status" => $row['status']
        ];
    }
    $response = ["status" => "success", "doctors" => $doctorsList];
} else {
    $response = ["status" => "error", "message" => "Failed to fetch practitioners directory tables."];
}

echo json_encode($response);
exit();
?>
