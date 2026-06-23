<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FORCE EXPLICIT RUNTIME ERROR RECOVERY MATRIX
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Safely pull down your database link variables out of the local directory path
if (file_exists("database-config.php")) {
    include_once "database-config.php";
} else {
    echo json_encode(["status" => "error", "appointments" => [], "message" => "Critical Error: database-config.php file is missing inside the api folder."]);
    exit();
}

// 1. FAIL-SAFE CONNECTION AUTO-MAPPING: Bypasses any custom variable name mismatches
if (!isset($conn) && isset($link)) { $conn = $link; }
if (!isset($conn) && isset($db)) { $conn = $db; }

if (!isset($conn) || !$conn) {
    echo json_encode(["status" => "error", "appointments" => [], "message" => "Database link variable layer is offline or undefined."]);
    exit();
}

// 2. SESSION COOKIE FALLBACK: Enables easy local testing without forcing you to re-login every time
$doctorName = "";
if (isset($_SESSION['doctor_name'])) {
    $doctorName = trim($_SESSION['doctor_name']);
} else {
    // Look up the first active practitioner in your table automatically for instant workspace testing
    $fallbackResult = mysqli_query($conn, "SELECT name FROM doctors LIMIT 1");
    if ($fallbackResult && $row = mysqli_fetch_assoc($fallbackResult)) {
        $doctorName = $row['name'];
        $_SESSION['doctor_name'] = $doctorName; // Auto-seed session parameter to prevent future loop breaks
    }
}

if (empty($doctorName)) {
    echo json_encode(["status" => "success", "appointments" => [], "message" => "No active session logged. Displaying blank directory state safely."]);
    exit();
}

// 3. EXECUTE SAFE DISPATCH STREAM
$query = "SELECT id, patient_name, patient_contact, appointment_date, appointment_time FROM appointments WHERE doctor_name = ? AND status = 'Pending' ORDER BY appointment_date ASC, appointment_time ASC";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $doctorName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $appointmentsList = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appointmentsList[] = [
            "id" => (int)$row['id'],
            "patient_name" => $row['patient_name'],
            "patient_contact" => $row['patient_contact'],
            "date" => date("d M Y", strtotime($row['appointment_date'])),
            "time" => date("h:i A", strtotime($row['appointment_time']))
        ];
    }
    
    echo json_encode(["status" => "success", "appointments" => $appointmentsList]);
    mysqli_stmt_close($stmt);
} else {
    // Dynamic Query Fallback: Prevents 500 drops if statement preparation gets locked by MySQL permissions
    $escapedName = mysqli_real_escape_string($conn, $doctorName);
    $fallbackQuery = "SELECT id, patient_name, patient_contact, appointment_date, appointment_time FROM appointments WHERE doctor_name = '$escapedName' AND status = 'Pending'";
    $fallbackResult = mysqli_query($conn, $fallbackQuery);
    
    $appointmentsList = [];
    if ($fallbackResult) {
        while ($row = mysqli_fetch_assoc($fallbackResult)) {
            $appointmentsList[] = [
                "id" => (int)$row['id'],
                "patient_name" => $row['patient_name'],
                "patient_contact" => $row['patient_contact'],
                "date" => date("d M Y", strtotime($row['appointment_date'])),
                "time" => date("h:i A", strtotime($row['appointment_time']))
            ];
        }
    }
    echo json_encode(["status" => "success", "appointments" => $appointmentsList]);
}
exit();
?>
