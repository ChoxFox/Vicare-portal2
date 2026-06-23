<?php
// Prevent manual script interception parameters injection
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit("Direct access denied.");
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "vicare_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Critical MySQL Link Failure: " . mysqli_connect_error()]);
    exit();
}

// Enforce standard utf8mb4 character set parameters cleanly
mysqli_set_charset($conn, "utf8mb4");
?>
