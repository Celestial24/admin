<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../../../auth/login.php");
    exit();
}

// Include database connection
include '../../../backend/sql/db.php';

// Get contract ID from URL
$contractId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$contractId) {
    http_response_code(400);
    exit('Invalid contract ID.');
}

try {
    $stmt = $conn->prepare("SELECT * FROM contracts WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $contractId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        exit("Contract not found.");
    }

    $contract = $result->fetch_assoc();
    $stmt->close();

    $riskScore = isset($contract['probability_percent']) ? intval($contract['probability_percent']) : 0;

} catch (Exception $e) {
    http_response_code(500);
    exit("Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contract Analysis - ATIERA</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="../../../assets/image/logo2.png">
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 text-white">
        <?php include '../../../Components/sidebar/sidebar_admin.php'; ?>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-4 bg-white shadow-sm">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-800">Contract Analysis</h2>
                <p class="text-gray-600 mt-1">Detailed analysis and risk assessment</p>
            </div>
            <div class="flex items-center space-x-4 p-6">
                <a href="../../../Main/contract.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i data-lucide="arrow-left" class="w-4 h-4 inline mr-1"></i>Back to Upload
                </a>
            </div>
        </div>

        <!-- Content -->
        <section class="flex-1 overflow-y-auto p-6 bg-gray-50">
            
            <!-- Contract Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                
                <!-- Contract Details -->
                <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Contract Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contract ID</label>
                            <p class="mt-1 text-sm text-gray-900 font-mono"><?= htmlspecialchars($contract['id']) ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee Name</label>
                            <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($contract['employee_name']) ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee ID</label>
                            <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($contract['employee_id']) ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contract Title</label>
                            <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($contract['title']) ?></p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($contract['description'] ?: 'No description provided') ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Uploaded Date</label>
                            <p class="mt-1 text-sm text-gray-900"><?= date('M j, Y g:i A', strtotime($contract['created_at'])) ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">File Name</label>
                            <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($contract['document_path']) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Risk Assessment -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Risk Assessment</h3>
                    
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Risk Score</span>
                            <span class="text-sm font-semibold <?= $riskScore <= 30 ? 'text-green-600' : ($riskScore <= 70 ? 'text-yellow-600' : 'text-red-600') ?>">
                                <?= $riskScore ?>%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full <?= $riskScore <= 30 ? 'bg-green-500' : ($riskScore <= 70 ? 'bg-yellow-500' : 'bg-red-500') ?>" 
                                 style="width: <?= $riskScore ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Risk Level</h4>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $riskScore <= 30 ? 'bg-green-100 text-green-800' : ($riskScore <= 70 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                            <?= $riskScore <= 30 ? 'Low Risk' : ($riskScore <= 70 ? 'Medium Risk' : 'High Risk') ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- OCR Text -->
            <?php if ($contract['ocr_text']): ?>
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Extracted Text (OCR)</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap font-mono"><?= htmlspecialchars($contract['ocr_text']) ?></pre>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Actions</h3>
                <div class="flex space-x-4">
                    <button onclick="downloadContract()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i data-lucide="download" class="w-4 h-4 inline mr-1"></i>Download Contract
                    </button>
                    <button onclick="printAnalysis()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                        <i data-lucide="printer" class="w-4 h-4 inline mr-1"></i>Print Analysis
                    </button>
                    <button onclick="shareAnalysis()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        <i data-lucide="share" class="w-4 h-4 inline mr-1"></i>Share Analysis
                    </button>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Action functions
        function downloadContract() {
            alert('Download functionality would be implemented here');
        }
        
        function printAnalysis() {
            window.print();
        }
        
        function shareAnalysis() {
            alert('Share functionality would be implemented here');
        }
    </script>

</body>
</html>