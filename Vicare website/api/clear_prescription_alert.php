<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
include_once "database-config.php";

$patient_contact = $_SESSION['patient_contact'] ?? '';

if (!empty($patient_contact)) {
    $escaped = mysqli_real_escape_string($conn, $patient_contact);
    // Switch active prescription display flag to zero while keeping file paths stored securely inside history rows
    mysqli_query($conn, "UPDATE patient_prescriptions SET prescription_exist = 0, has_previous_prescription = 1, prev_file_path = file_path WHERE patient_contact = '$escaped'");
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}
exit();
?>
