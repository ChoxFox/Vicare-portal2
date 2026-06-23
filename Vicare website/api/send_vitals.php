<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force a clean JSON response data stream to prevent frontend script parsing drops
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Links directly to your master connection config inside the same subfolder
include_once "database-config.php";

$response = ["status" => "error", "message" => "An unhandled clinical data exception occurred."];

if (!isset($conn) || !$conn) {
    echo json_encode(["status" => "error", "message" => "Database link layer offline."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_name    = trim($_POST['patient_name'] ?? '');
    $patient_contact = trim($_POST['patient_contact'] ?? '');
    $blood_pressure  = trim($_POST['blood_pressure'] ?? '');
    $heart_rate      = trim($_POST['heart_rate'] ?? '');
    $respiration     = trim($_POST['respiration'] ?? '');
    $temperature     = trim($_POST['temperature'] ?? '');

    // Safely identify the logged-in doctor from active session keys
    $doctor_name = $_SESSION['doctor_name'] ?? 'Dr. Medical Professional';

    if (empty($patient_name) || empty($patient_contact) || empty($blood_pressure) || empty($heart_rate) || empty($respiration) || empty($temperature)) {
        echo json_encode(["status" => "error", "message" => "Please complete all patient detail and vitals input boxes."]);
        exit();
    }

    // =========================================================================
    // CRITICAL UX VALIDATION GATE: VERIFY IF THE PATIENT TRULY EXISTS
    // =========================================================================
    // Scans your master patients table to see if this contact address matches a registered user
    $patient_check_query = "SELECT id, name FROM patients WHERE email = ? OR contact = ? LIMIT 1";
    $p_stmt = mysqli_prepare($conn, $patient_check_query);
    
    // Create a persistent variable to lock down the exact registered account name to prevent dashboard desyncs
    $validated_account_name = $patient_name; 
    
    if ($p_stmt) {
        mysqli_stmt_bind_param($p_stmt, "ss", $patient_contact, $patient_contact);
        mysqli_stmt_execute($p_stmt);
        $p_result = mysqli_stmt_get_result($p_stmt);
        
        if ($p_row = mysqli_fetch_assoc($p_result)) {
            // OPTIONAL DOUBLE-CHECK: Ensures the typed name closely matches what is in the user's account
            if (strtolower(trim($p_row['name'])) !== strtolower($patient_name)) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "The contact information matches a patient, but the name does not match their registered account name ('" . $p_row['name'] . "')."
                ]);
                mysqli_stmt_close($p_stmt);
                exit();
            }
            // LOCK NAME: Enforce the exact database string case style to safeguard dashboard loading matches
            $validated_account_name = $p_row['name']; 
        } else {
            // ABORT TRANSACTION: The typed email or mobile number cannot be found in your database records
            echo json_encode([
                "status" => "error", 
                "message" => "Invalid Recipient: No registered patient found with the mobile number or email address: '" . $patient_contact . "'. Vitals were not sent."
            ]);
            mysqli_stmt_close($p_stmt);
            exit();
        }
        mysqli_stmt_close($p_stmt);
    }

    // Check if this patient record alert row track already exists inside your vitals table
    $check_query = "SELECT id FROM patient_vitals WHERE patient_contact = ? AND status = 'Pending' LIMIT 1";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, "s", $patient_contact);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            // FIXED UPDATE PIPELINE: Aligned parameters count and placeholders length perfectly to prevent fatal execution crashes
            $update_query = "UPDATE patient_vitals SET patient_name = ?, doctor_name = ?, blood_pressure = ?, heart_rate = ?, respiration = ?, temperature = ? WHERE patient_contact = ? AND status = 'Pending'";
            $update_stmt = mysqli_prepare($conn, $update_query);
            if ($update_stmt) {
                // FIXED BIND: Maps exactly 7 string tokens ("sssssss") to your 7 parameters smoothly
                mysqli_stmt_bind_param($update_stmt, "sssssss", $validated_account_name, $doctor_name, $blood_pressure, $heart_rate, $respiration, $temperature, $patient_contact);
                if (mysqli_stmt_execute($update_stmt)) {
                    $response = ["status" => "success", "message" => "Patient telemetry record metrics updated successfully!"];
                }
                mysqli_stmt_close($update_stmt);
            }
            mysqli_stmt_close($check_stmt);
            echo json_encode($response);
            exit();
        }
        mysqli_stmt_close($check_stmt);
    }

    // INSERT PIPELINE: Pushes your verified registration data directly into your MySQL tables
    $insert_query = "INSERT INTO patient_vitals (patient_name, patient_contact, doctor_name, blood_pressure, heart_rate, respiration, temperature, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
    $insert_stmt = mysqli_prepare($conn, $insert_query);

    if ($insert_stmt) {
        mysqli_stmt_bind_param($insert_stmt, "sssssss", $validated_account_name, $patient_contact, $doctor_name, $blood_pressure, $heart_rate, $respiration, $temperature);
        if (mysqli_stmt_execute($insert_stmt)) {
            $response = ["status" => "success", "message" => "Vitals telemetry pushed to patient dashboard successfully!"];
        } else {
            $response = ["status" => "error", "message" => "SQL Execution Fault. Could not write vitals into server tables."];
        }
        mysqli_stmt_close($insert_stmt);
    } else {
        $response = ["status" => "error", "message" => "Database statement preparation failure. Check column definitions."];
    }
}

echo json_encode($response);
exit();
?>
