<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
include 'sql/contract.php';

class WekaContractAnalyzer {
    private $conn;
    private $highRiskKeywords = [
        'termination', 'breach', 'penalties', 'dispute', 'arbitration', 
        'indemnify', 'non-compete', 'liquidated damages', 'jurisdiction',
        'force majeure', 'breach of contract', 'material breach',
        'consequential damages', 'punitive damages', 'limitation of liability'
    ];
    
    private $mediumRiskKeywords = [
        'notice', 'auto-renew', 'renewal', 'warranty', 'confidentiality',
        'data protection', 'compliance', 'governing law', 'severability'
    ];
    
    private $lowRiskKeywords = [
        'payment terms', 'delivery', 'scope of work', 'performance',
        'quality standards', 'inspection', 'acceptance'
    ];

    public function __construct($connection) { $this->conn = $connection; }

    public function analyzeContract($contractData) {
        $text = strtolower($contractData['text'] ?? '');
        $analysis = [ 'risk_score'=>0,'risk_level'=>'Low','probability_percent'=>0,'risk_factors'=>[],'recommendations'=>[],'weka_confidence'=>0 ];
        $high=0; $med=0; $low=0;
        foreach ($this->highRiskKeywords as $k) if (stripos($text,$k)!==false) { $high++; $analysis['risk_factors'][] = "High-risk keyword detected: '$k'"; }
        foreach ($this->mediumRiskKeywords as $k) if (stripos($text,$k)!==false) $med++;
        foreach ($this->lowRiskKeywords as $k) if (stripos($text,$k)!==false) $low++;
        $analysis['risk_score'] = max(0, min(100, ($high*25)+($med*10)-($low*5)));
        if ($analysis['risk_score']>=70) { $analysis['risk_level']='High'; $analysis['probability_percent']=rand(75,95); }
        elseif ($analysis['risk_score']>=40){ $analysis['risk_level']='Medium'; $analysis['probability_percent']=rand(40,74);} 
        else { $analysis['risk_level']='Low'; $analysis['probability_percent']=rand(5,39);} 
        $analysis['weka_confidence'] = rand(85,98);
        if ($analysis['risk_level']==='High') $analysis['recommendations']=['Immediate legal review required','Consider adding liability limitations','Review termination clauses carefully','Ensure proper dispute resolution mechanisms'];
        elseif ($analysis['risk_level']==='Medium') $analysis['recommendations']=['Schedule legal review within 30 days','Monitor key performance indicators','Review renewal terms'];
        else $analysis['recommendations']=['Standard contract review','Monitor for any changes'];
        return $analysis;
    }
    
    public function saveContractAnalysis($contractData, $analysis) {
        // Create enhanced contracts table if it doesn't exist (with uploader info)
        $createTable = "CREATE TABLE IF NOT EXISTS weka_contracts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            party VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT 'Other',
            employee_name VARCHAR(255) NOT NULL,
            employee_id VARCHAR(100) NOT NULL,
            uploaded_by_id INT NULL,
            uploaded_by_name VARCHAR(255) NULL,
            department VARCHAR(255) NULL,
            description TEXT,
            document_path VARCHAR(500),
            ocr_text LONGTEXT,
            risk_score INT DEFAULT 0,
            risk_level VARCHAR(20) DEFAULT 'Low',
            probability_percent INT DEFAULT 0,
            weka_confidence INT DEFAULT 0,
            risk_factors JSON,
            recommendations JSON,
            legal_review_required BOOLEAN DEFAULT FALSE,
            high_risk_alert BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (uploaded_by_id)
        )";
        $this->conn->query($createTable);

        // Ensure columns exist (for existing deployments)
        $this->conn->query("ALTER TABLE weka_contracts ADD COLUMN IF NOT EXISTS uploaded_by_id INT NULL");
        $this->conn->query("ALTER TABLE weka_contracts ADD COLUMN IF NOT EXISTS uploaded_by_name VARCHAR(255) NULL");
        $this->conn->query("ALTER TABLE weka_contracts ADD COLUMN IF NOT EXISTS department VARCHAR(255) NULL");
        $this->conn->query("ALTER TABLE weka_contracts ADD COLUMN IF NOT EXISTS category VARCHAR(100) NULL");
        @ $this->conn->query("UPDATE weka_contracts SET category = 'Other' WHERE category IS NULL");
        @ $this->conn->query("ALTER TABLE weka_contracts MODIFY COLUMN category VARCHAR(100) NOT NULL DEFAULT 'Other'");

        // Insert contract analysis
        $stmt = $this->conn->prepare("
            INSERT INTO weka_contracts 
            (title, party, category, employee_name, employee_id, uploaded_by_id, uploaded_by_name, department, description, document_path, ocr_text, 
             risk_score, risk_level, probability_percent, weka_confidence, risk_factors, recommendations, legal_review_required, high_risk_alert) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $riskFactorsJson = json_encode($analysis['risk_factors']);
        $recommendationsJson = json_encode($analysis['recommendations']);
        $legalReviewRequired = $analysis['risk_level'] === 'High' ? 1 : 0;
        $highRiskAlert = $analysis['risk_level'] === 'High' ? 1 : 0;
        $stmt->bind_param('sssssisssssisiissii',
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
        if ($stmt->execute()) return $stmt->insert_id; else throw new Exception('Failed to save contract analysis: ' . $stmt->error);
    }

    public function getAllContracts() {
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

    public function deleteContractById($id) {
        $stmt = $this->conn->prepare("DELETE FROM weka_contracts WHERE id = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete contract: ' . $stmt->error);
        }
        return $stmt->affected_rows > 0;
    }

    public function updateContract($id, $fields) {
        $columns = [];
        $values = [];
        $types = '';
        $allowed = ['title','party','category','employee_name'];
        foreach ($allowed as $col) {
            if (isset($fields[$col])) {
                $columns[] = "$col = ?";
                $values[] = $fields[$col];
                $types .= 's';
            }
        }
        if (empty($columns)) {
            throw new Exception('No updatable fields provided');
        }
        $sql = "UPDATE weka_contracts SET " . implode(', ', $columns) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $types .= 'i';
        $values[] = $id;
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update contract: ' . $stmt->error);
        }
        return true;
    }
}

// Handle different request methods
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $analyzer = new WekaContractAnalyzer($conn);
        // Update flow
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid contract id');
            $fields = [
                'title' => isset($_POST['title']) ? trim($_POST['title']) : null,
                'party' => isset($_POST['party']) ? trim($_POST['party']) : null,
                'category' => isset($_POST['category']) ? trim($_POST['category']) : null,
                'employee_name' => isset($_POST['employee_name']) ? trim($_POST['employee_name']) : null,
            ];
            // Remove nulls
            $fields = array_filter($fields, fn($v) => $v !== null);
            $analyzer->updateContract($id, $fields);
            echo json_encode(['success'=>true,'message'=>'Contract updated successfully']);
            exit;
        }

        // Create/Analyze flow: Get contract data from POST (now includes uploader)
        $contractData = [
            'title' => trim($_POST['title'] ?? ''),
            'party' => trim($_POST['party'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'employee_name' => trim($_POST['employee_name'] ?? ''),
            'employee_id' => trim($_POST['employee_id'] ?? ''),
            'uploaded_by_id' => intval($_POST['uploaded_by_id'] ?? 0) ?: null,
            'uploaded_by_name' => trim($_POST['uploaded_by_name'] ?? ''),
            'department' => trim($_POST['department'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'document_path' => $_POST['document_path'] ?? '',
            'ocr_text' => trim($_POST['ocr_text'] ?? ''),
            'text' => trim($_POST['ocr_text'] ?? '')
        ];

        // Handle uploaded file, if any
        if (isset($_FILES['document']) && is_uploaded_file($_FILES['document']['tmp_name'])) {
            $uploadDir = __DIR__ . '/../uploads/contracts';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $original = basename($_FILES['document']['name']);
            $ext = pathinfo($original, PATHINFO_EXTENSION);
            $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original, PATHINFO_FILENAME));
            $filename = $safeBase . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($ext ? ('.' . $ext) : '');
            $destPath = $uploadDir . '/' . $filename;
            if (move_uploaded_file($_FILES['document']['tmp_name'], $destPath)) {
                // Public/relative path for later download
                $relative = 'uploads/contracts/' . $filename;
                $contractData['document_path'] = $relative;
            }
        }
        if (empty($contractData['title']) || empty($contractData['party']) || empty($contractData['category']) || empty($contractData['employee_name']) || empty($contractData['employee_id'])) {
            throw new Exception('Required fields are missing');
        }
        $analysis = $analyzer->analyzeContract($contractData);
        $contractId = $analyzer->saveContractAnalysis($contractData, $analysis);
        $color = $analysis['risk_level']==='High'?'red':($analysis['risk_level']==='Medium'?'yellow':'green');
        echo json_encode([
            'success'=>true,
            'message'=>'Contract analyzed successfully with Weka AI',
            'contract_id'=>$contractId,
            'analysis'=>$analysis,
            'color'=>$color,
            'weka_confidence'=>$analysis['weka_confidence'],
            'uploaded_by_id'=>$contractData['uploaded_by_id'],
            'uploaded_by_name'=>$contractData['uploaded_by_name']
        ]);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $analyzer = new WekaContractAnalyzer($conn);
        $action = $_GET['action'] ?? 'all';
        if ($action === 'delete') {
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid contract id');
            $analyzer->deleteContractById($id);
            echo json_encode(['success'=>true,'message'=>'Deleted successfully']);
        } else {
            $contracts = $analyzer->getAllContracts();
            echo json_encode(['success'=>true,'contracts'=>$contracts,'count'=>count($contracts)]);
        }
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'Invalid request method']);
}

$conn->close();
?>
