<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
include_once "database-config.php";

$is_doctor = isset($_SESSION['doctor_id']);
$users = [];

if ($is_doctor) {
    // Doctors query registered patients list rows
    $res = mysqli_query($conn, "SELECT name, contact, image FROM patients ORDER BY id DESC LIMIT 15");
    while($row = mysqli_fetch_assoc($res)) {
        $users[] = ["name" => $row['name'], "contact" => $row['contact'], "img" => $row['image']];
    }
} else {
    // Patients fetch available doctor practitioners
    $res = mysqli_query($conn, "SELECT name, email, image, specialty FROM doctors ORDER BY id DESC LIMIT 15");
    while($row = mysqli_fetch_assoc($res)) {
        $users[] = ["name" => $row['name'], "contact" => $row['email'], "img" => $row['image'], "subtitle" => $row['specialty']];
    }
}

echo json_encode(["status" => "success", "users" => $users]);
exit();
?>
