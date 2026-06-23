<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include_once "database-config.php";

// ISOLATION LAYER: Read keys that belong EXCLUSIVELY to patients
$patient_id      = $_SESSION['patient_id'] ?? '';
$patient_name    = $_SESSION['patient_name'] ?? '';
$patient_contact = $_SESSION['patient_contact'] ?? '';

if (empty($patient_id) || empty($patient_name)) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "logged_out", "message" => "Please log in again."]);
    exit();
}

$master_payload = [
    "status" => "success",
    "patient_id" => $patient_id,
    "patient_name" => $patient_name,
    "patient_contact" => $patient_contact,
    "patient_image" => $_SESSION['patient_image'] ?? '149071.png',
    "vitals_exist" => false,
    "vitals_status" => "",
    "blood_pressure" => "--/--",
    "heart_rate" => "--",
    "respiration" => "--",
    "temperature" => "--",
    "doctor_name" => "",
    "prescription_exist" => false,
    "p_diagnosis" => "",
    "p_doctor_name" => "",
    "file_path" => "",
    "has_previous_prescription" => false,
    "prev_file_path" => "",
    "appointments_list" => []
];

$escaped_contact = mysqli_real_escape_string($conn, $patient_contact);
$v_query = "SELECT * FROM patient_vitals WHERE patient_contact = '$escaped_contact' ORDER BY id DESC LIMIT 1";
$v_res = mysqli_query($conn, $v_query);

if ($v_res && mysqli_num_rows($v_res) > 0) {
    $v_row = mysqli_fetch_assoc($v_res);
    $master_payload["vitals_exist"]   = true;
    $master_payload["vitals_status"]  = $v_row['status'];
    $master_payload["blood_pressure"] = $v_row['blood_pressure'];
    $master_payload["heart_rate"]     = $v_row['heart_rate'];
    $master_payload["respiration"]    = $v_row['respiration'];
    $master_payload["temperature"]    = $v_row['temperature'];
    $master_payload["doctor_name"]    = $v_row['doctor_name'];
}

$p_query = "SELECT * FROM patient_prescriptions WHERE patient_contact = '$escaped_contact' ORDER BY id DESC LIMIT 1";
$p_res = mysqli_query($conn, $p_query);

if ($p_res && mysqli_num_rows($p_res) > 0) {
    $p_row = mysqli_fetch_assoc($p_res);
    if ($p_row['prescription_exist'] == 1) {
        $master_payload["prescription_exist"] = true;
        $master_payload["p_diagnosis"]        = $p_row['diagnosis'];
        $master_payload["p_doctor_name"]      = $p_row['doctor_name'];
        $master_payload["file_path"]          = $p_row['file_path'];
    }
    if ($p_row['has_previous_prescription'] == 1) {
        $master_payload["has_previous_prescription"] = true;
        $master_payload["prev_file_path"]            = $p_row['prev_file_path'];
    }
}

$app_query = "SELECT doctor_name, appointment_date, appointment_time, status FROM appointments WHERE patient_contact = '$escaped_contact' ORDER BY id DESC LIMIT 6";
$app_res = mysqli_query($conn, $app_query);

if ($app_res) {
    while ($row = mysqli_fetch_assoc($app_res)) {
        $master_payload["appointments_list"][] = [
            "app_doctor_name" => $row['doctor_name'],
            "app_date" => date("d M Y", strtotime($row['appointment_date'])),
            "app_time" => date("h:i A", strtotime($row['appointment_time'])),
            "app_status" => $row['status']
        ];
    }
}

echo json_encode($master_payload);
exit();
?>
