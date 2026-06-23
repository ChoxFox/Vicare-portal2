<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force a clean JSON response data stream to prevent frontend script parsing drops
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Links natively straight to your connection configuration parameters file
include_once "database-config.php";

$response = ["status" => "error", "message" => "An unhandled schedule transaction exception occurred."];

if (!isset($conn) || !$conn) {
    echo json_encode(["status" => "error", "message" => "Database link layer offline."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_id = trim($_POST['appointment_id'] ?? '');
    $action         = trim($_POST['action'] ?? ''); // Expects 'Accepted' or 'Denied'

    if (empty($appointment_id) || empty($action)) {
        echo json_encode(["status" => "error", "message" => "Missing required scheduling identifier codes."]);
        exit();
    }

    if (!in_array($action, ['Accepted', 'Denied'])) {
        echo json_encode(["status" => "error", "message" => "Invalid calendar status modification type parameter."]);
        exit();
    }

    // UPDATE PIPELINE: Modifies the status column inside your relational appointments table row [INDEX]
    $query = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        // Bind parameters safely to prevent server-side injection vulnerabilities
        mysqli_stmt_bind_param($stmt, "si", $action, $appointment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $response = [
                "status" => "success", 
                "message" => "Appointment request successfully marked as " . $action . "!"
            ];
        } else {
            $response = ["status" => "error", "message" => "SQL execution fault while updating calendar record rows."];
        }
        mysqli_stmt_close($stmt);
    } else {
        $response = ["status" => "error", "message" => "Database statement preparation failure. Check column definitions."];
    }
}

echo json_encode($response);
exit();
?>
