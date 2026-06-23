<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force a pure JSON response data stream to prevent frontend script parsing drops
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Links directly to your master database config file inside your api subfolder
include_once "api/database-config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contact  = trim($_POST['contact'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($contact) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please complete all credential fields."]);
        exit();
    }

    // Dynamic Lookup Statement: Checks your unified email and contact tracking row indices
    $query = "SELECT * FROM patients WHERE email = ? OR contact = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $contact, $contact);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Verifies password credentials matching encrypted hashes or local plain text test data
            if ($password === $row['password'] || password_verify($password, $row['password'])) {
                $_SESSION['patient_id'] = $row['id'];
                $_SESSION['patient_name'] = $row['name'];
                $_SESSION['patient_image'] = $row['image'] ?? '149071.png';
                
                echo json_encode(["status" => "success", "message" => "Authentication successful! Redirecting..."]);
                mysqli_stmt_close($stmt);
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    echo json_encode(["status" => "error", "message" => "Invalid email/mobile number or password entry."]);
    exit();
}
?>
