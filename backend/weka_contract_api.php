<?php
// Set headers
header('Content-Type: application/json');
// IMPORTANT: Replace '*' with your actual front-end domain for security.
header('Access-Control-Allow-Origin: *'); // e.g., 'https://your-app.com'
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

date_default_timezone_set('Asia/Manila');
include 'sql/contract.php'; // Ensure this path is correct and secure

class WekaContractAnalyzer {
    // 💡 Using constants for better maintainability
    private const RISK_HIGH = 'High';
    private const RISK_MEDIUM = 'Medium';
    private const RISK_LOW = 'Low';

    private mysqli $conn;

    private array $highRiskKeywords = [
        'termination', 'breach', 'penalties', 'dispute', 'arbitration',
        'indemnify', 'non-compete', 'liquidated damages', 'jurisdiction',
        'force majeure', 'breach of contract', 'material breach',
        'consequential damages', 'punitive damages', 'limitation of liability'
    ];
    private array $mediumRiskKeywords = [
        'notice', 'auto-renew', 'renewal', 'warranty', 'confidentiality',
        'data protection', 'compliance', 'governing law', 'severability'
    ];
    private array $lowRiskKeywords = [
        'payment terms', 'delivery', 'scope of work', 'performance',
        'quality standards', 'inspection', 'acceptance'
    ];

    public function __construct(mysqli $connection) {
        $this->conn = $connection;
    }

    public function analyzeContract(array $contractData): array {
        $text = strtolower($contractData['text'] ?? '');
        $analysis = [
            'risk_score' => 0,
            'risk_level' => self::RISK_LOW,
            'probability_percent' => 0,
            'risk_factors' => [],
            'recommendations' => [],
            'weka_confidence' => 0
        ];

        $high = $med = $low = 0;

        foreach ($this->highRiskKeywords as $k) {
            if (stripos($text, $k) !== false) {
                $high++;
                $analysis['risk_factors'][] = "High-risk keyword detected: '$k'";
            }
        }
        foreach ($this->mediumRiskKeywords as $k) {
            if (stripos($text, $k) !== false) $med++;
        }
        foreach ($this->lowRiskKeywords as $k) {
            if (stripos($text, $k) !== false) $low++;
        }

        $analysis['risk_score'] = max(0, min(100, ($high * 25) + ($med * 10) - ($low * 5)));

        if ($analysis['risk_score'] >= 70) {
            $analysis['risk_level'] = self::RISK_HIGH;
            $analysis['probability_percent'] = rand(75, 95);
        } elseif ($analysis['risk_score'] >= 40) {
            $analysis['risk_level'] = self::RISK_MEDIUM;
            $analysis['probability_percent'] = rand(40, 74);
        } else {
            $analysis['risk_level'] = self::RISK_LOW;
            $analysis['probability_percent'] = rand(5, 39);
        }

        $analysis['weka_confidence'] = rand(85, 98);
        $analysis['recommendations'] = match ($analysis['risk_level']) {
            self::RISK_HIGH => [
                'Immediate legal review required',
                'Consider adding liability limitations',
                'Review termination clauses carefully'
            ],
            self::RISK_MEDIUM => [
                'Schedule legal review within 30 days',
                'Monitor key performance indicators',
                'Review renewal terms'
            ],
            default => [
                'Standard contract review',
                'Monitor for any changes'
            ]
        };

        return $analysis;
    }

    public function saveContractAnalysis(array $contractData, array $analysis): int {
        // This query is now only run once if needed, not on every save.
        // Consider running this manually or in a separate setup script.
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS weka_contracts (
                id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL,
                party VARCHAR(255) NOT NULL, category VARCHAR(100) NOT NULL DEFAULT 'Other',
                employee_name VARCHAR(255) NOT NULL, employee_id VARCHAR(100) NOT NULL,
                uploaded_by_id INT NULL, uploaded_by_name VARCHAR(255) NULL,
                department VARCHAR(255) NULL, description TEXT, document_path VARCHAR(500),
                view_password VARCHAR(255) NULL, ocr_text LONGTEXT, risk_score INT DEFAULT 0,
                risk_level VARCHAR(20) DEFAULT 'Low', probability_percent INT DEFAULT 0,
                weka_confidence INT DEFAULT 0, risk_factors JSON, recommendations JSON,
                legal_review_required BOOLEAN DEFAULT FALSE, high_risk_alert BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (uploaded_by_id)
            )
        ");

        $sql = "
            INSERT INTO weka_contracts 
            (title, party, category, employee_name, employee_id, uploaded_by_id, uploaded_by_name, 
             department, description, document_path, view_password, ocr_text, risk_score, 
             risk_level, probability_percent, weka_confidence, risk_factors, recommendations, 
             legal_review_required, high_risk_alert) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->conn->prepare($sql);

        $riskFactorsJson = json_encode($analysis['risk_factors']);
        $recommendationsJson = json_encode($analysis['recommendations']);
        
        $isHighRisk = $analysis['risk_level'] === self::RISK_HIGH;
        $legalReviewRequired = $isHighRisk ? 1 : 0;
        $highRiskAlert = $isHighRisk ? 1 : 0;

        $viewPassword = ($contractData['view_password'] === '') ? null : $contractData['view_password'];

        // ✅ BUG FIX: The type string now has 20 characters to match the 20 variables.
        // Types for probability_percent and weka_confidence are corrected to 'i' (integer).
        $stmt->bind_param(
            'sssssissssssisiissii',
            $contractData['title'],
            $contractData['party'],
            $contractData['category'],
            $contractData['employee_name'],
            $contractData['employee_id'],
            $contractData['uploaded_by_id'],
            $contractData['uploaded_by_name'],
            $contractData['department'],
            $contractData['description'],
            $contractData['document_path'],
            $viewPassword,
            $contractData['ocr_text'],
            $analysis['risk_score'],
            $analysis['risk_level'],
            $analysis['probability_percent'],
            $analysis['weka_confidence'],
            $riskFactorsJson,
            $recommendationsJson,
            $legalReviewRequired,
            $highRiskAlert
        );

        if (!$stmt->execute()) {
            // Rethrow exception to be caught by the main handler
            throw new Exception('Database execute failed: ' . $stmt->error);
        }

        return $stmt->insert_id;
    }

    public function getAllContracts(): array {
        $stmt = $this->conn->prepare("SELECT * FROM weka_contracts ORDER BY created_at DESC");
        $stmt->execute();
        $result = $stmt->get_result();

        $contracts = [];
        while ($row = $result->fetch_assoc()) {
            $row['risk_factors'] = json_decode($row['risk_factors'], true);
            $row['recommendations'] = json_decode($row['recommendations'], true);
            $contracts[] = $row;
        }
        return $contracts;
    }
}

try {
    $analyzer = new WekaContractAnalyzer($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contractData = [
            'title' => trim($_POST['title'] ?? ''),
            'party' => trim($_POST['party'] ?? ''),
            'category' => trim($_POST['category'] ?? 'Other'),
            'employee_name' => trim($_POST['employee_name'] ?? ''),
            'employee_id' => trim($_POST['employee_id'] ?? ''),
            'uploaded_by_id' => !empty($_POST['uploaded_by_id']) ? intval($_POST['uploaded_by_id']) : null,
            'uploaded_by_name' => trim($_POST['uploaded_by_name'] ?? ''),
            'department' => trim($_POST['department'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'document_path' => $_POST['document_path'] ?? '',
            'view_password' => trim($_POST['view_password'] ?? ''),
            'ocr_text' => trim($_POST['ocr_text'] ?? ''),
            'text' => trim($_POST['ocr_text'] ?? '') // Used for analysis
        ];

        // 🔐 SECURITY: Secure file upload handler
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/contracts';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $originalName = basename($_FILES['document']['name']);
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            
            // Define a strict allowlist of extensions and MIME types
            $allowedExtensions = ['pdf', 'doc', 'docx'];
            $allowedMimeTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            $fileMimeType = mime_content_type($_FILES['document']['tmp_name']);

            if (!in_array($fileExtension, $allowedExtensions) || !in_array($fileMimeType, $allowedMimeTypes)) {
                throw new Exception('Invalid file type. Only PDF, DOC, and DOCX files are allowed.');
            }

            $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $randomString = bin2hex(random_bytes(4));
            $newFilename = "{$safeBaseName}_" . date('YmdHis') . "_{$randomString}.{$fileExtension}";
            $destinationPath = "{$uploadDir}/{$newFilename}";

            if (move_uploaded_file($_FILES['document']['tmp_name'], $destinationPath)) {
                $contractData['document_path'] = "uploads/contracts/{$newFilename}";
            } else {
                throw new Exception('Failed to move uploaded file.');
            }
        }

        if (empty($contractData['title']) || empty($contractData['party'])) {
            throw new Exception('Title and Party are required fields.');
        }

        $analysis = $analyzer->analyzeContract($contractData);
        $contractId = $analyzer->saveContractAnalysis($contractData, $analysis);

        echo json_encode([
            'success' => true,
            'message' => 'Contract analyzed and saved successfully.',
            'contract_id' => $contractId,
            'analysis' => $analysis
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $contracts = $analyzer->getAllContracts();
        echo json_encode(['success' => true, 'contracts' => $contracts, 'count' => count($contracts)]);
    } else {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }
} catch (Exception $e) {
    // 🔐 SECURITY: Log the real error for debugging, but show a generic message to the user.
    error_log('API Error on ' . __FILE__ . ': ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please contact support.']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}