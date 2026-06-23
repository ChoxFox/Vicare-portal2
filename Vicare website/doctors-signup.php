<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force a clean JSON response data stream to prevent frontend script parsing drops
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Links directly to your newly renamed database configuration file inside your api subfolder
include_once "api/database-config.php";

$response = ["status" => "error", "message" => "An unhandled registration drop occurred."];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $contact    = trim($_POST['contact'] ?? '');
    $license    = trim($_POST['license'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($contact) || empty($license) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please complete all registration field entries."]);
        exit();
    }

    // Combine your names smoothly matching your database schema table text layout parameters
    $full_doctor_name = "Dr. " . $first_name . " " . $last_name;
    
    // Check if the email or license already exists inside your newly built database tables
    $check_query = "SELECT id FROM doctors WHERE email = ? OR contact = ? OR license = ? LIMIT 1";
    $check_stmt = mysqli_prepare($conn, $check_query);
    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, "sss", $contact, $contact, $license);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            echo json_encode(["status" => "error", "message" => "This contact address or medical license number is already registered."]);
            mysqli_stmt_close($check_stmt);
            exit();
        }
        mysqli_stmt_close($check_stmt);
    }

    // Handle profile image upload processing safely behind the scenes
    $final_image_name = "149071.png"; // Fallback default image setting
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['profile_pic']['tmp_name'];
        $file_original_name = $_FILES['profile_pic']['name'];
        $file_extension = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
        
        // Generate a clean, unique timestamped file title name mapping configuration
        $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        $upload_target_path = 'uploads/' . $new_filename;

        if (move_uploaded_file($file_tmp_path, $upload_target_path)) {
            $final_image_name = $new_filename;
        }
    }

    // INSERT PIPELINE: Pushes your verified registration data directly into your MySQL tables
    $insert_query = "INSERT INTO doctors (name, email, contact, license, password, image, specialty, status) VALUES (?, ?, ?, ?, ?, ?, 'General Practitioner', 'Online')";
    $insert_stmt = mysqli_prepare($conn, $insert_query);

    if ($insert_stmt) {
        // Keeps values raw or applies plaintext storage matching your current platform authentication logic
        mysqli_stmt_bind_param($insert_stmt, "ssssss", $full_doctor_name, $contact, $contact, $license, $password, $final_image_name);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            // Assign session credentials securely so they transfer natively after registration
            $_SESSION['doctor_id'] = mysqli_insert_id($conn);
            $_SESSION['doctor_name'] = $full_doctor_name;
            $_SESSION['doctor_image'] = $final_image_name;

            $response = ["status" => "success", "message" => "Account created successfully!"];
        } else {
            $response = ["status" => "error", "message" => "SQL Execution Error. Could not save user rows into database."];
        }
        mysqli_stmt_close($insert_stmt);
    } else {
        $response = ["status" => "error", "message" => "Database statement compilation fault. Re-verify your MySQL table configurations."];
    }
}

echo json_encode($response);
exit();
?>
