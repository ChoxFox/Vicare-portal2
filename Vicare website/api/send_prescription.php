<?php
// Initialize session tracking channels securely before any data stream processing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. SUPPRESS RUNTIME NOTICE BLOCKS - SECURES PURE JSON RESPONSE PAYLOADS
error_reporting(0);
ini_set('display_errors', 0);

include_once "database-config.php";

// 2. DELAYED CONTENT-TYPE DECLARATION: Unblocks multipart form boundary streams
if (!file_exists("fpdf.php")) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "fpdf.php core library file is missing inside your api subfolder."]);
    exit();
}
require_once "fpdf.php";

if (!isset($conn) || !$conn) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Database link variable layer offline."]);
    exit();
}

if (!isset($_SESSION['doctor_name'])) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Unauthorized access request. Please log in again."]);
    exit();
}

$doctorName = $_SESSION['doctor_name'];
$doctorContact = $_SESSION['doctor_contact'] ?? 'No Contact Info Found';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patientContact = trim($_POST['patient_contact'] ?? '');
    $patientName    = trim($_POST['patient_name'] ?? '');
    $diagnosis      = trim($_POST['diagnosis'] ?? '');
    
    // Unpack multi-medicine nested data arrays from JavaScript FormData
    $meds = $_POST['meds'] ?? [];
    $medicineNames = []; $dosages = []; $frequencies = []; $durations = [];

    if (!empty($meds) && is_array($meds)) {
        foreach ($meds as $med) {
            $medicineNames[] = $med['name'] ?? '';
            $dosages[]       = $med['dose'] ?? '';
            $frequencies[]   = $med['freq'] ?? '';
            $durations[]     = $med['dur'] ?? '';
        }
    }

    if (empty($patientContact) || empty($patientName) || empty($diagnosis)) {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Patient details and diagnosis are required fields."]);
        exit();
    }

    // SCANS DATABASE STABLE PATIENTS DIRECTORY TABLE
    $checkQuery = "SELECT name FROM patients WHERE email = ? OR contact = ? LIMIT 1";
    $stmtCheck = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmtCheck, "ss", $patientContact, $patientContact);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);

    if (mysqli_num_rows($resultCheck) === 0) {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error", 
            "message" => "🚨 Prescription Blocked: The contact information '" . $patientContact . "' cannot be found in your database. Register via patient-signup-forms.html first."
        ]);
        mysqli_stmt_close($stmtCheck);
        exit();
    } else {
        $pRow = mysqli_fetch_assoc($resultCheck);
        $patientName = $pRow['name']; // Sync database spelling format natively
    }
    mysqli_stmt_close($stmtCheck);
    // ====== INITIALIZE FPDF COMPILER ENGINE MODULE ======
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetMargins(20, 20, 20);

    // DOUBLE GRAPHIC BORDER FRAME MATCHING ORIGINAL DESIGN
    $pdf->SetLineWidth(0.8); $pdf->SetDrawColor(63, 78, 161); $pdf->Rect(10, 10, 190, 277);
    $pdf->SetLineWidth(0.2); $pdf->SetDrawColor(148, 163, 184); $pdf->Rect(12, 12, 186, 273);
    
    // SOLID HEADER RECTANGLE BLOCK BANNER
    $pdf->SetFillColor(63, 78, 161); $pdf->Rect(15, 15, 180, 25, 'F');

    // CUSTOM MIXED COLOR HEADING DESIGN ENGINE
    $pdf->SetY(19); $pdf->SetFont('Arial', 'B', 15); $pdf->SetX(43); 
    $pdf->SetTextColor(61, 254, 216); $pdf->Cell($pdf->GetStringWidth('Vicare '), 8, 'Vicare ', 0, 0);
    $pdf->SetTextColor(255, 255, 255); $pdf->Cell(0, 8, 'healthcare management platform', 0, 1);
    $pdf->SetFont('Arial', 'I', 9); $pdf->Cell(0, 4, 'Official Digital Health Transaction Prescription Record Sheet', 0, 1, 'C');

    $isEmail = (strpos($doctorContact, '@') !== false);
    $contactLabel = $isEmail ? 'Practitioner Email: ' : 'Practitioner Phone: ';
    $pdf->SetY(45); $pdf->SetFont('Arial', 'B', 9); $pdf->SetTextColor(63, 78, 161);
    $pdf->Cell(0, 6, $contactLabel . htmlspecialchars($doctorContact, ENT_QUOTES, 'UTF-8'), 0, 1, 'C'); 
    
    $pdf->Ln(2); $pdf->SetDrawColor(203, 213, 225); $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); $pdf->Ln(4);
    $pdf->SetTextColor(17, 24, 39); $pdf->SetFont('Arial', 'B', 10);
    
    $pdf->Cell(32, 6, 'Issued Date:', 0, 0, 'L'); $pdf->SetFont('Arial', '', 10); $pdf->Cell(0, 6, date('d M Y'), 0, 1, 'L'); 
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(32, 6, 'Practitioner:', 0, 0, 'L'); $pdf->SetFont('Arial', '', 10); $pdf->Cell(0, 6, htmlspecialchars($doctorName), 0, 1, 'L'); 
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(32, 6, 'Patient Name:', 0, 0, 'L'); $pdf->SetFont('Arial', '', 10); $pdf->Cell(0, 6, htmlspecialchars($patientName), 0, 1, 'L');

    $pdf->Ln(4); $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); $pdf->Ln(6);

    // DIAGNOSTIC EXPLANATORY TEXT BLOCK LAYOUT DESIGN
    $pdf->SetFont('Arial', 'B', 11); $pdf->SetTextColor(63, 78, 161); $pdf->Cell(0, 6, 'DIAGNOSIS & CLINICAL OBSERVATION', 0, 1);
    $pdf->Ln(2); $pdf->SetFont('Arial', 'I', 10); $pdf->SetTextColor(51, 65, 85); $pdf->MultiCell(0, 6, htmlspecialchars($diagnosis));
    
    $pdf->Ln(4); $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); $pdf->Ln(6);

    // RX MEDICATIONS LOOP INJECTION LAYER
    $pdf->SetFont('Arial', 'B', 11); $pdf->SetTextColor(63, 78, 161); $pdf->Cell(0, 6, 'Rx (PRESCRIBED MEDICATIONS)', 0, 1);
    $pdf->SetTextColor(15, 23, 42); $pdf->Ln(3);

    $medicationCounter = 1;
    if (!empty($medicineNames)) {
        for ($i = 0; $i < count($medicineNames); $i++) {
            $mName = trim($medicineNames[$i] ?? '');
            $mDose = trim($dosages[$i] ?? '500mg');
            $mFreq = trim($frequencies[$i] ?? 'Daily');
            $mDur  = trim($durations[$i] ?? '5 Days');

            if (!empty($mName)) {
                $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(0, 6, $medicationCounter . '. ' . htmlspecialchars($mName) . ' (' . htmlspecialchars($mDose) . ')', 0, 1);
                $pdf->SetFont('Arial', '', 10); $pdf->SetX(25); 
                $pdf->Cell(0, 6, 'Instructions: Take ' . htmlspecialchars($mFreq) . ' for ' . htmlspecialchars($mDur) . '.', 0, 1);
                $pdf->Ln(2); $medicationCounter++;
            }
        }
    } else {
        $pdf->SetFont('Arial', 'I', 10); $pdf->Cell(0, 6, 'No compound chemical items specified.', 0, 1);
    }

    // ====== DYNAMIC FAINT VICARE WATERMARK BRANDING LOGO INJECTION ======
    // Uses standard Arial core rendering styles to prevent 500 folder drops while forcing clear transparency lines
    $pdf->SetY(200); 
    $pdf->SetFont('Arial', 'B', 50);
    $pdf->SetTextColor(245, 247, 250); // Ultra-faint slate gray watermark tone
    $pdf->Cell(0, 15, 'VICARE', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->SetTextColor(248, 250, 252);
    $pdf->Cell(0, 6, 'Telehealth Certified Medical Document', 0, 1, 'C');

    // ABSOLUTE FIXED CENTERED FOOTER NOTES DESIGN
    $pdf->SetY(268); $pdf->SetDrawColor(203, 213, 225); $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 9); $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 4, 'Digitally Validated Security Document Trail via Vicare Medical Platform.', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 8); $pdf->Cell(0, 4, 'This record serves as an official clinical transaction item copy matching active registrations.', 0, 1, 'C');

    $file_hash_name = "Prescription_" . bin2hex(random_bytes(8)) . ".pdf";
    $target_save_path = "../uploads/" . $file_hash_name;
    $db_save_path = "uploads/" . $file_hash_name;

    if (!file_exists("../uploads")) {
        mkdir("../uploads", 0777, true);
    }

    $pdf->Output('F', $target_save_path);

    mysqli_query($conn, "UPDATE patient_prescriptions SET prescription_exist = 0, has_previous_prescription = 1, prev_file_path = file_path WHERE patient_contact = '" . mysqli_real_escape_string($conn, $patientContact) . "'");

    $insert_query = "INSERT INTO patient_prescriptions (patient_name, patient_contact, doctor_name, diagnosis, file_path, prescription_exist) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = mysqli_prepare($conn, $insert_query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $patientName, $patientContact, $doctorName, $diagnosis, $db_save_path);
        if (mysqli_stmt_execute($stmt)) {
            header('Content-Type: application/json');
            echo json_encode(["status" => "success", "message" => "Custom stylized FPDF prescription sheet generated successfully!"]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Database transaction failure while writing prescription record rows."]);
        }
        mysqli_stmt_close($stmt);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Database query processing statement preparation failure."]);
    }
    exit();
}
?>
