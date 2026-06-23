<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include_once "database-config.php";

$recipient = trim($_GET['recipient'] ?? '');
$sender    = trim($_GET['sender'] ?? ''); // FIXED: Reads active sender from JavaScript explicitly!

// Self-healing fallback mapping if parameter is dropped in transit
if (empty($sender)) {
    $sender = $_SESSION['patient_contact'] ?? ($_SESSION['doctor_contact'] ?? '');
}

if (empty($sender) || empty($recipient)) {
    echo json_encode(["status" => "success", "logs" => []]);
    exit();
}

$escaped_sender = mysqli_real_escape_string($conn, $sender);
$escaped_target = mysqli_real_escape_string($conn, $recipient);

// Automatically set message flags to read when loaded
mysqli_query($conn, "UPDATE telehealth_chats SET is_read = 1 WHERE sender_identifier = '$escaped_target' AND recipient_identifier = '$escaped_sender'");

// Double-barrel query matches both emails, phone numbers, and cross-tab IDs flawlessly [INDEX]
$query = "SELECT * FROM telehealth_chats 
          WHERE (sender_identifier = '$escaped_sender' AND recipient_identifier = '$escaped_target')
             OR (sender_identifier = '$escaped_target' AND recipient_identifier = '$escaped_sender')
          ORDER BY id ASC LIMIT 100";

$result = mysqli_query($conn, $query);
$logs = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = [
            "is_me" => ($row['sender_identifier'] === $sender),
            "text"  => htmlspecialchars($row['message_body'], ENT_QUOTES, 'UTF-8'),
            "time"  => date("h:i A", strtotime($row['sent_at']))
        ];
    }
}

echo json_encode(["status" => "success", "logs" => $logs]);
exit();
?>
