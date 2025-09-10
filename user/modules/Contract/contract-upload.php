<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'contract_legal';
$username = 'root';
$password = '';

$mysqli = new mysqli($host, $username, $password, $dbname);
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'MySQL connection failed: ' . $mysqli->connect_error]);
    exit;
}
$mysqli->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Use POST.']);
    exit;
}

$employeeName = trim($_POST['employeeName'] ?? '');
$employeeId = trim($_POST['employeeId'] ?? '');
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($employeeName === '') {
    echo json_encode(['success' => false, 'message' => 'Employee Name is required.']);
    exit;
}
if ($employeeId === '') {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required.']);
    exit;
}
if ($title === '') {
    echo json_encode(['success' => false, 'message' => 'Contract title is required.']);
    exit;
}

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload failed.']);
    exit;
}

$allowed_types = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/png',
    'image/jpeg',
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['document']['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_types, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type: ' . $mime]);
    exit;
}

$upload_dir = __DIR__ . '/../../uploads/contracts/';
if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
    echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
    exit;
}

$ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
$filename = uniqid('contract_', true) . '.' . $ext;
$path = $upload_dir . $filename;

if (!move_uploaded_file($_FILES['document']['tmp_name'], $path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
    exit;
}

// Dummy OCR extraction placeholder
$ocrText = "This contract includes termination clauses, breach penalties, and arbitration agreements.";

// Simple keyword-based risk scoring
$highRiskKeywords = ['termination', 'breach', 'penalties', 'dispute', 'arbitration', 'indemnify', 'non-compete'];
$score = 0;
foreach ($highRiskKeywords as $keyword) {
    if (stripos($ocrText, $keyword) !== false) {
        $score += 15;  // Each keyword adds 15% risk
    }
}
$riskScore = min($score, 100);

// Generate a random probability (0-100) for UI display or your logic
$probabilityPercent = rand(0, 100);

// Save both risk score and probabilityPercent to DB
$stmt = $mysqli->prepare(
    "INSERT INTO contracts (employee_name, employee_id, title, description, document_path, ocr_text, risk_score, probability_percent) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    unlink($path);
    echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param(
    'ssssssii',
    $employeeName,
    $employeeId,
    $title,
    $description,
    $filename,
    $ocrText,
    $riskScore,
    $probabilityPercent
);

if ($stmt->execute()) {
    $color = ($probabilityPercent < 50) ? 'green' : 'yellow';

    echo json_encode([
        'success' => true,
        'message' => 'Contract uploaded successfully.',
        'contractId' => $stmt->insert_id,
        'ocrText' => $ocrText,
        'probabilityPercent' => $probabilityPercent,
        'color' => $color
    ]);
} else {
    unlink($path);
    echo json_encode(['success' => false, 'message' => 'DB execute error: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();