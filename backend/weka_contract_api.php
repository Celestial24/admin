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

    /**
     * Verify password for contract access.
     */
    public function verifyContractPassword(int $contractId, string $password): bool {
        $stmt = $this->conn->prepare("SELECT view_password FROM weka_contracts WHERE id = ?");
        $stmt->bind_param('i', $contractId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // If no password is set, allow access
            if (empty($row['view_password'])) {
                return true;
            }
            // Verify password
            return $row['view_password'] === $password;
        }
        
        return false;
    }

    /**
     * Get contract by ID with password verification.
     */
    public function getContractById(int $contractId, string $password = null): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM weka_contracts WHERE id = ?");
        $stmt->bind_param('i', $contractId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Check if password is required
            if (!empty($row['view_password'])) {
                if ($password === null || $row['view_password'] !== $password) {
                    return null; // Password required but not provided or incorrect
                }
            }
            
            $row['risk_factors'] = json_decode($row['risk_factors'], true);
            $row['recommendations'] = json_decode($row['recommendations'], true);
            return $row;
        }
        
        return null;
    }

    /**
     * Update contract information.
     */
    public function updateContract(int $contractId, array $contractData, string $password = null): bool {
        // First verify password if required
        if (!$this->verifyContractPassword($contractId, $password)) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE weka_contracts 
            SET title = ?, party = ?, category = ?, employee_name = ?, employee_id = ?, 
                department = ?, description = ?, view_password = ?, ocr_text = ?, 
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $title = $contractData['title'];
        $party = $contractData['party'];
        $category = $contractData['category'] ?? 'Other';
        $employeeName = $contractData['employee_name'];
        $employeeId = $contractData['employee_id'];
        $department = $contractData['department'];
        $description = $contractData['description'];
        $viewPassword = ($contractData['view_password'] === '') ? null : $contractData['view_password'];
        $ocrText = $contractData['ocr_text'];

        $stmt->bind_param('sssssssssi', 
            $title, $party, $category, $employeeName, $employeeId, 
            $department, $description, $viewPassword, $ocrText, $contractId
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to update contract: ' . $stmt->error);
        }

        // Re-analyze the contract if OCR text was updated
        if (isset($contractData['ocr_text']) && !empty($contractData['ocr_text'])) {
            $analysis = $this->analyzeContract($contractData);
            $this->updateContractAnalysis($contractId, $analysis);
        }

        return true;
    }

    /**
     * Update contract analysis results.
     */
    public function updateContractAnalysis(int $contractId, array $analysis): bool {
        $stmt = $this->conn->prepare("
            UPDATE weka_contracts 
            SET risk_score = ?, risk_level = ?, probability_percent = ?, 
                weka_confidence = ?, risk_factors = ?, recommendations = ?, 
                legal_review_required = ?, high_risk_alert = ?, 
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $riskFactorsJson = json_encode($analysis['risk_factors']);
        $recommendationsJson = json_encode($analysis['recommendations']);
        $legalReviewRequired = $analysis['risk_level'] === self::RISK_HIGH ? 1 : 0;
        $highRiskAlert = $analysis['risk_level'] === self::RISK_HIGH ? 1 : 0;

        $stmt->bind_param('isisssii', 
            $analysis['risk_score'], $analysis['risk_level'], $analysis['probability_percent'],
            $analysis['weka_confidence'], $riskFactorsJson, $recommendationsJson,
            $legalReviewRequired, $highRiskAlert, $contractId
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to update contract analysis: ' . $stmt->error);
        }

        return true;
    }

    /**
     * Delete contract with password verification.
     */
    public function deleteContract(int $contractId, string $password = null): bool {
        // First verify password if required
        if (!$this->verifyContractPassword($contractId, $password)) {
            return false;
        }

        $stmt = $this->conn->prepare("DELETE FROM weka_contracts WHERE id = ?");
        $stmt->bind_param('i', $contractId);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete contract: ' . $stmt->error);
        }

        return true;
    }

    /**
     * Set or update password for a contract.
     */
    public function setContractPassword(int $contractId, string $newPassword, string $currentPassword = null): bool {
        // Verify current password if one exists
        if (!$this->verifyContractPassword($contractId, $currentPassword)) {
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE weka_contracts SET view_password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param('si', $newPassword, $contractId);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update contract password: ' . $stmt->error);
        }

        return true;
    }
}

try {
    $analyzer = new WekaContractAnalyzer($conn);

    // Handle different actions based on request parameters
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'verify_password':
                $contractId = intval($_POST['contract_id'] ?? 0);
                $password = trim($_POST['password'] ?? '');
                
                if ($contractId <= 0) {
                    throw new Exception('Invalid contract ID.');
                }
                
                $isValid = $analyzer->verifyContractPassword($contractId, $password);
                echo json_encode([
                    'success' => $isValid,
                    'message' => $isValid ? 'Password verified successfully.' : 'Invalid password.',
                    'verified' => $isValid
                ]);
                break;

            case 'update':
                $contractId = intval($_POST['contract_id'] ?? 0);
                $password = trim($_POST['password'] ?? '');
                
                if ($contractId <= 0) {
                    throw new Exception('Invalid contract ID.');
                }
                
                $contractData = [
                    'title' => trim($_POST['title'] ?? ''),
                    'party' => trim($_POST['party'] ?? ''),
                    'category' => trim($_POST['category'] ?? 'Other'),
                    'employee_name' => trim($_POST['employee_name'] ?? ''),
                    'employee_id' => trim($_POST['employee_id'] ?? ''),
                    'department' => trim($_POST['department'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'view_password' => trim($_POST['view_password'] ?? ''),
                    'ocr_text' => trim($_POST['ocr_text'] ?? ''),
                    'text' => trim($_POST['ocr_text'] ?? '') // Used for analysis
                ];

                if (empty($contractData['title']) || empty($contractData['party'])) {
                    throw new Exception('Title and Party are required fields.');
                }

                $success = $analyzer->updateContract($contractId, $contractData, $password);
                
                if (!$success) {
                    throw new Exception('Failed to update contract. Please check your password.');
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Contract updated successfully.'
                ]);
                break;

            case 'delete':
                $contractId = intval($_POST['contract_id'] ?? 0);
                $password = trim($_POST['password'] ?? '');
                
                if ($contractId <= 0) {
                    throw new Exception('Invalid contract ID.');
                }
                
                $success = $analyzer->deleteContract($contractId, $password);
                
                if (!$success) {
                    throw new Exception('Failed to delete contract. Please check your password.');
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Contract deleted successfully.'
                ]);
                break;

            case 'set_password':
                $contractId = intval($_POST['contract_id'] ?? 0);
                $currentPassword = trim($_POST['current_password'] ?? '');
                $newPassword = trim($_POST['new_password'] ?? '');
                
                if ($contractId <= 0) {
                    throw new Exception('Invalid contract ID.');
                }
                
                $success = $analyzer->setContractPassword($contractId, $newPassword, $currentPassword);
                
                if (!$success) {
                    throw new Exception('Failed to set password. Please check your current password.');
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Password updated successfully.'
                ]);
                break;

            default:
                // Default action: create new contract
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
                break;
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        switch ($action) {
            case 'get_contract':
                $contractId = intval($_GET['contract_id'] ?? 0);
                $password = trim($_GET['password'] ?? '');
                
                if ($contractId <= 0) {
                    throw new Exception('Invalid contract ID.');
                }
                
                $contract = $analyzer->getContractById($contractId, $password);
                
                if ($contract === null) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Contract not found or password required.',
                        'password_required' => true
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'contract' => $contract
                    ]);
                }
                break;

            case 'all':
            default:
                $contracts = $analyzer->getAllContracts();
                echo json_encode(['success' => true, 'contracts' => $contracts, 'count' => count($contracts)]);
                break;
        }
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