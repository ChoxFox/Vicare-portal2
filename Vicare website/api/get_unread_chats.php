<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include_once "database-config.php";

// DYNAMIC RESOLUTION: Automatically pulls your real doctor username  directly from your table 
$doctor_contact = "";
$doc_query = "SELECT email FROM doctors ORDER BY id DESC LIMIT 1";
$doc_res = mysqli_query($conn, $doc_query);

if ($doc_res && $doc_row = mysqli_fetch_assoc($doc_res)) {
    $doctor_contact = $doc_row['email']; 
} else {
    $doctor_contact = $_SESSION['doctor_contact'] ?? '';
}

$escaped_doc = mysqli_real_escape_string($conn, $doctor_contact);

// 1. CALCULATE GLOBAL NOTIFICATION COUNT TRIMS FOR UNREAD CHATS [INDEX]
$count_query = "SELECT COUNT(*) as unread_total FROM telehealth_chats WHERE recipient_identifier = '$escaped_doc' AND is_read = 0";
$count_res = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_res);
$total_unread = intval($count_row['unread_total'] ?? 0);

// 2. RETRIEVE ACTIVE PATIENT PROFILES WHO SENT THE UNREAD MESSAGES [INDEX]
$list_query = "SELECT c.sender_identifier, c.message_body, c.sent_at, p.name, p.image 
               FROM telehealth_chats c 
               JOIN patients p ON (c.sender_identifier = p.contact OR c.sender_identifier = p.email)
               WHERE c.recipient_identifier = '$escaped_doc' AND c.is_read = 0
               ORDER BY c.id DESC";
$list_res = mysqli_query($conn, $list_query);

$conversations = [];
$tracked_contacts = [];

if ($list_res) {
    while ($row = mysqli_fetch_assoc($list_res)) {
        if (in_array($row['sender_identifier'], $tracked_contacts)) {
            continue;
        }
        $tracked_contacts[] = $row['sender_identifier'];
        
        $conversations[] = [
            "contact" => $row['sender_identifier'],
            "name" => $row['name'],
            "msg" => htmlspecialchars($row['message_body'], ENT_QUOTES, 'UTF-8'),
            "img" => !empty($row['image']) ? $row['image'] : '149071.png',
            "time" => date("h:i A", strtotime($row['sent_at']))
        ];
    }
}

echo json_encode([
    "status" => "success",
    "total_unread" => $total_unread,
    "conversations" => $conversations
]);
exit();
?>
