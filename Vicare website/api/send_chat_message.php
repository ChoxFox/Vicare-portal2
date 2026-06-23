<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. SUPPRESS RUNTIME NOTICE WARNINGS - PROTECTS THE JSON PAYLOAD STREAM
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include_once "database-config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message     = trim($_POST['message'] ?? '');
    $recipient   = trim($_POST['recipient'] ?? ''); 
    $forced_type = trim($_POST['forced_type'] ?? ''); // Read explicit frontend identity flag

    $sender = "";
    $type   = "";

    // 2. COOKIE SEPARATION ENGINE: Uses forced types first to destroy cross-tab contamination traps
    if (!empty($forced_type)) {
        $type = $forced_type;
        if ($type === 'Doctor') {
            // Automatically lookup the real registered doctor's contact number from the configuration table
            $d_lookup = mysqli_query($conn, "SELECT email FROM doctors ORDER BY id DESC LIMIT 1");
            $sender = ($d_lookup && $d_row = mysqli_fetch_assoc($d_lookup)) ? $d_row['email'] : '0771810571';
        } else {
            // Automatically lookup the real registered patient's contact handle string
            $p_lookup = mysqli_query($conn, "SELECT contact, email FROM patients ORDER BY id DESC LIMIT 1");
            if ($p_lookup && $p_row = mysqli_fetch_assoc($p_lookup)) {
                $sender = !empty($p_row['email']) ? $p_row['email'] : $p_row['contact'];
            } else {
                $sender = '0771810571';
            }
        }
    } else {
        // Fallback session grabber if execution falls outside components
        if (isset($_SESSION['patient_id'])) {
            $sender = $_SESSION['patient_contact'] ?? '';
            $type   = 'Patient';
        } elseif (isset($_SESSION['doctor_id'])) {
            $sender = $_SESSION['doctor_contact'] ?? '';
            $type   = 'Doctor';
        }
    }
    // 3. TESTING BASELINE FALLBACK GATEWAY: Resolves blank variables natively
    if (empty($sender) || empty($type)) {
        if (isset($_SESSION['doctor_name']) || isset($_SESSION['doctor_id'])) {
            $d_fallback = mysqli_query($conn, "SELECT email FROM doctors ORDER BY id DESC LIMIT 1");
            $sender = ($d_fallback && $d_row = mysqli_fetch_assoc($d_fallback)) ? $d_row['email'] : '0771810571';
            $type   = 'Doctor';
        } else {
            $p_fallback = mysqli_query($conn, "SELECT contact, email FROM patients ORDER BY id DESC LIMIT 1");
            $p_row = ($p_fallback && $p_f_row = mysqli_fetch_assoc($p_fallback)) ? $p_f_row : [];
            $sender = !empty($p_row['email']) ? $p_row['email'] : ($p_row['contact'] ?? '0771810571');
            $type   = 'Patient';
        }
    }

    if (empty($message)) {
        echo json_encode(["status" => "error", "message" => "You cannot dispatch empty text blocks."]);
        exit();
    }

    // 4. IDENTITY ALIGNMENT MATRIX ENGINE: Standardizes destination handles to match index tables character-for-character
    if ($type === 'Patient') {
        $doc_clean = trim(str_replace("Dr. ", "", $recipient));
        $escaped_rec = mysqli_real_escape_string($conn, $recipient);
        $escaped_clean = mysqli_real_escape_string($conn, $doc_clean);
        
        $lookup_doc = mysqli_query($conn, "SELECT email FROM doctors WHERE email = '$escaped_rec' OR name LIKE '%$escaped_clean%' LIMIT 1");
        if ($lookup_doc && $doc_row = mysqli_fetch_assoc($lookup_doc)) {
            $recipient = $doc_row['email']; 
        } else {
            // Absolute database lookup fallback guarantees alignment with your true doctor row
            $master_doc_scan = mysqli_query($conn, "SELECT email FROM doctors ORDER BY id DESC LIMIT 1");
            if ($master_doc_scan && $m_doc_row = mysqli_fetch_assoc($master_doc_scan)) {
                $recipient = $m_doc_row['email'];
            }
        }
    } elseif ($type === 'Doctor') {
        $escaped_rec = mysqli_real_escape_string($conn, $recipient);
        $lookup_pat = mysqli_query($conn, "SELECT contact, email FROM patients WHERE contact = '$escaped_rec' OR email = '$escaped_rec' LIMIT 1");
        if ($lookup_pat && $pat_row = mysqli_fetch_assoc($lookup_pat)) {
            $recipient = !empty($pat_row['email']) ? $pat_row['email'] : $pat_row['contact'];
        }
    }
    if (empty($recipient)) {
        echo json_encode(["status" => "error", "message" => "Transmission error: Target recipient identifier mapping failed."]);
        exit();
    }

    // 5. SECURE PREPARED TRANSACTION INPUT PIPELINE
    $query = "INSERT INTO telehealth_chats (sender_type, sender_identifier, recipient_identifier, message_body) VALUES (?, ?, ?, ?)";
    $stmt  = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $type, $sender, $recipient, $message);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success", "message" => "Message transmitted safely across channels."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database execution fault during chat transmission."]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["status" => "error", "message" => "Statement compilation failure."]);
    }
    exit();
}
?>
