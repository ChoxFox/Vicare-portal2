<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suppress internal browser HTML warnings to maintain pure JSON stream integration safety
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// FIXED INCLUSION PATH: Looks into your api subfolder to extract your database connection configuration
include_once "api/database-config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $contact   = trim($_POST['contact'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($firstName) || empty($lastName) || empty($contact) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit();
    }

    if (strlen($password) < 8) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters."]);
        exit();
    }

    // Check if user already exists
    $checkQuery = "SELECT id FROM patients WHERE email = ? OR contact = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "ss", $contact, $contact);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(["status" => "error", "message" => "Account already exists with this email or mobile number."]);
        mysqli_stmt_close($stmt);
        exit();
    }
    mysqli_stmt_close($stmt);

    // Hash the password securely matching professional encryption algorithms
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Profile image processing
    $imageName = "149071.png"; // Default fallback image
    
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $targetDir = "uploads/";
        $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
        $fileName = basename($_FILES["profile_pic"]["name"]);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $imageName = bin2hex(random_bytes(16)) . "." . $fileExtension;
            $targetFilePath = $targetDir . $imageName;
            move_uploaded_file($fileTmpPath, $targetFilePath);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid image format. Only JPG, PNG, and GIF allowed."]);
            exit();
        }
    }

    // Combine your names smoothly matching your unified database schema parameters
    $fullPatientName = $firstName . " " . $lastName;

    // FIXED INSERT PIPELINE: Aligned to map your unified schema cells ('name', 'email', 'contact', 'password', 'image')
    $insertQuery = "INSERT INTO patients (name, email, contact, password, image) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insertQuery);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $fullPatientName, $contact, $contact, $hashedPassword, $imageName);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['patient_name'] = htmlspecialchars($fullPatientName, ENT_QUOTES, 'UTF-8');
            $_SESSION['patient_image'] = htmlspecialchars($imageName, ENT_QUOTES, 'UTF-8');
            
            echo json_encode([
                "status" => "success",
                "name" => $_SESSION['patient_name'],
                "image" => "uploads/" . $_SESSION['patient_image']
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database table mismatch error during user cell insertion."]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["status" => "error", "message" => "Database preparation error. Statement compilation failed."]);
    }
    exit();
}
?>