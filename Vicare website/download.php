<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suppress errors to prevent corrupting the binary download stream buffer
error_reporting(0);
ini_set('display_errors', 0);

include_once "database-config.php";

// Privacy Gate: Protect the download gate by ensuring only authenticated users can pull data
if (!isset($_SESSION['patient_id']) && !isset($_SESSION['doctor_id'])) {
    http_response_code(403);
    die("Access Denied: Unauthenticated data transfer request.");
}

$file = trim($_GET['file'] ?? '');

if (empty($file)) {
    http_response_code(400);
    die("Bad Request: No target file parameter provided.");
}

// Security Boundary: Block directory traversal attacks (prevent hacking system files)
$cleanPath = str_replace(array('../', '..\\'), '', $file);
$fullPath = "../" . $cleanPath;

if (!file_exists($fullPath) || is_dir($fullPath)) {
    http_response_code(404);
    die("File Not Found: The requested prescription document does not exist on this server.");
}

// Extract file extensions safely
$extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$allowedTypes = ['pdf', 'txt'];

if (!in_array($extension, $allowedTypes)) {
    http_response_code(415);
    die("Unsupported Media Type: Unauthorized download file extension.");
}

// Grab the patient's name to generate a clean, professional download title string
$downloadName = "Prescription_Document." . $extension;
if (isset($_SESSION['patient_name'])) {
    $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $_SESSION['patient_name']);
    $downloadName = "Prescription_" . $safeName . "." . $extension;
}

// Clear any open output buffers to protect file encoding states
if (ob_get_level()) {
    ob_end_clean();
}

// FORCED BINARY DOWNLOAD HEADERS MATRIX
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));

// Read the document out of server storage and push it natively to the browser download tray
readfile($fullPath);
exit();
?>
