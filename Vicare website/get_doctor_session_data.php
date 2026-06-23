<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suppress internal browser HTML warnings to maintain pure JSON stream integration safety
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include_once "api/database-config.php"; // Connects natively to your renamed database config file

$response = ["status" => "logged_out", "doctor_name" => "", "doctor_image" => ""];

// Check if the practitioner session keys are actively logged inside the browser cookie matrix
if (isset($_SESSION['doctor_id']) && !empty($_SESSION['doctor_id'])) {
    $response = [
        "status" => "success",
        "doctor_name" => $_SESSION['doctor_name'] ?? "Medical Professional",
        "doctor_image" => $_SESSION['doctor_image'] ?? "149071.png"
    ];
}

echo json_encode($response);
exit();
?>
