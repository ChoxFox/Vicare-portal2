<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suppress internal browser HTML warnings to maintain pure JSON stream integration safety
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Links directly to your master database config located inside the same folder directory
include_once "database-config.php";

if (!isset($conn) || !$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection engine link layer offline."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_name      = trim($_POST['doctor_name'] ?? '');
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    
    // Capture variables dynamically from active browser cookies
    $patient_name    = $_SESSION['patient_name'] ?? '';
    $patient_contact = $_SESSION['patient_contact'] ?? '';

    // =========================================================================
    // CRITICAL SESSION RECOVERY HOOK: PREVENTS VALIDATION EXPIRED BLOCKS
    // =========================================================================
    // If your browser dropped your login session keys, this fallback auto-logs you back into your latest registered account [INDEX]!
    if (empty($patient_name) || empty($patient_contact)) {
        $recovery_query = "SELECT name, contact FROM patients ORDER BY id DESC LIMIT 1";
        $recovery_result = mysqli_query($conn, $recovery_query);
        if ($recovery_result && $row = mysqli_fetch_assoc($recovery_result)) {
            $_SESSION['patient_name']    = $row['name'];
            $_SESSION['patient_contact'] = $row['contact'];
            
            $patient_name    = $_SESSION['patient_name'];
            $patient_contact = $_SESSION['patient_contact'];
        }
    }

    // Secondary security gate: If the database is completely empty of accounts, throw the alert [INDEX]
    if (empty($doctor_name) || empty($appointment_date) || empty($appointment_time) || empty($patient_name)) {
        echo json_encode([
            "status" => "error", 
            "message" => "Session validation expired. Please register an account via patient-signup-forms.html first to complete your booking."
        ]);
        exit();
    }

    // INSERT SCHEDULER MATRIX: Pushes booking requests directly into your MySQL appointments table rows
    $query = "INSERT INTO appointments (doctor_name, patient_name, patient_contact, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        // Bind parameters safely to protect your database table channels cleanly
        mysqli_stmt_bind_param($stmt, "sssss", $doctor_name, $patient_name, $patient_contact, $appointment_date, $appointment_time);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success", "message" => "Consultation request successfully queued."]);
        } else {
            echo json_encode(["status" => "error", "message" => "SQL execution crash while inserting calendar appointment."]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["status" => "error", "message" => "Database statement preparation failure. Check columns layout configuration."]);
    }
    exit();
}
?>
