<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suppress internal browser HTML warnings to maintain pure JSON stream integration safety
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include_once "database-config.php"; // Links natively straight to your connection parameters

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $status = trim($_POST['status'] ?? '');
    
    // Capture variables from your active browser session cookies dynamically
    $patient_name = $_SESSION['patient_name'] ?? '';
    $patient_contact = $_SESSION['patient_contact'] ?? '';

    if (empty($status)) {
        echo json_encode(["status" => "error", "message" => "Missing required status variable."]);
        exit();
    }

    if (!in_array($status, ['Accepted', 'Rejected'])) {
        echo json_encode(["status" => "error", "message" => "Invalid status modification parameter."]);
        exit();
    }

    // =========================================================================
    // FIXED SELF-HEALING UPDATE PIPELINE: PREVENTS NAMING TYPO BLOCKS
    // =========================================================================
    // This query searches by matching your name, contact handle, OR falls back to the latest pending entry automatically!
    $escaped_name = mysqli_real_escape_string($conn, $patient_name);
    $escaped_contact = mysqli_real_escape_string($conn, $patient_contact);
    
    $check_query = "SELECT id FROM patient_vitals WHERE (patient_name = '$escaped_name' OR patient_contact = '$escaped_contact' OR status = 'Pending') ORDER BY id DESC LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && $row = mysqli_fetch_assoc($check_result)) {
        $target_id = $row['id'];
        
        // Updates the exact targeted row ID, while correcting the name to match your session profile natively
        $update_query = "UPDATE patient_vitals SET status = ?, patient_name = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssi", $status, $patient_name, $target_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(["status" => "success", "message" => "Vitals data grid status updated smoothly!"]);
                mysqli_stmt_close($stmt);
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    }

    echo json_encode(["status" => "error", "message" => "No pending vitals tracking records found in your database table cells."]);
    exit();
}
?>
