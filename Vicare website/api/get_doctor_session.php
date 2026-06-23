<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include_once "database-config.php";

// ISOLATION LAYER: Read keys that belong EXCLUSIVELY to doctors
$doctor_id      = $_SESSION['doctor_id'] ?? '';
$doctor_name    = $_SESSION['doctor_name'] ?? '';
$doctor_contact = $_SESSION['doctor_contact'] ?? '';

// If a doctor session isn't found, return a clean logged_out status instead of guessing names
if (empty($doctor_id) || empty($doctor_name)) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "logged_out", "message" => "Please log in again."]);
    exit();
}

$master_payload = [
    "status" => "success",
    "doctor_id" => $doctor_id,
    "doctor_name" => $doctor_name,
    "doctor_contact" => $doctor_contact,
    "doctor_image" => $_SESSION['doctor_image'] ?? '149071.png',
    "pending_appointments" => []
];

$clean_doc_string = str_replace("Dr. ", "", $doctor_name);
$escaped_doctor = mysqli_real_escape_string($conn, $clean_doc_string);

$app_query = "SELECT id, patient_name, patient_contact, appointment_date, appointment_time FROM appointments WHERE (doctor_name LIKE '%$escaped_doctor%' OR doctor_name = '".mysqli_real_escape_string($conn, $doctor_name)."') AND status = 'Pending' ORDER BY appointment_date ASC LIMIT 5";
$app_res = mysqli_query($conn, $app_query);

if ($app_res) {
    while ($row = mysqli_fetch_assoc($app_res)) {
        $master_payload["pending_appointments"][] = [
            "appointment_id" => $row['id'],
            "patient_name" => $row['patient_name'],
            "patient_contact" => $row['patient_contact'],
            "app_date" => date("d M Y", strtotime($row['appointment_date'])),
            "app_time" => date("h:i A", strtotime($row['appointment_time']))
        ];
    }
}

echo json_encode($master_payload);
exit();
?>
