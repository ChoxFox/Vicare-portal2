<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force a pure JSON response data stream to prevent frontend script parsing drops
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Links directly to your renamed database config file inside your api subfolder
include_once "api/database-config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contact  = trim($_POST['contact'] ?? '');
    $license  = trim($_POST['license'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($contact) || empty($license) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please complete all credential fields."]);
        exit();
    }

    // Dynamic Lookup Statement: Scans against both email/mobile formats AND your medical license column
    $query = "SELECT * FROM doctors WHERE (email = ? OR contact = ?) AND license = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $contact, $contact, $license);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Verifies password credentials cleanly matching your current plain text login format
            if ($password === $row['password'] || password_verify($password, $row['password'])) {
                $_SESSION['doctor_id'] = $row['id'];
                $_SESSION['doctor_name'] = $row['name'];
                $_SESSION['doctor_image'] = $row['image'] ?? '149071.png';
                
                echo json_encode(["status" => "success", "message" => "Authentication successful! Redirecting..."]);
                mysqli_stmt_close($stmt);
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    echo json_encode(["status" => "error", "message" => "Invalid credentials or medical license number entries."]);
    exit();
}
?>
