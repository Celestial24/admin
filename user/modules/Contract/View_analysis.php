<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database config
$host = 'localhost';
$dbname = 'contract_legal';
$user = 'root';
$pass = '';

// Get contract ID from URL
$contractId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$contractId) {
    http_response_code(400);
    exit('Invalid contract ID.');
}

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new Exception("DB connection failed: " . $conn->connect_error);
    }

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
    $conn->close();

    $analysisData = json_decode($contract['analysis'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $analysisData = null;
    }

    $riskScore = isset($contract['probability_percent']) ? intval($contract['probability_percent']) : 0;

} catch (Exception $e) {
    http_response_code(500);
    exit("Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Contract Analysis - <?= htmlspecialchars($contract['title']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans p-8">
  <div class="max-w-4xl bg-white shadow rounded p-6 mx-auto">

    <h1 class="text-3xl font-bold mb-4"><?= htmlspecialchars($contract['title']) ?></h1>
    <p class="text-gray-600 mb-6"><?= nl2br(htmlspecialchars($contract['description'])) ?></p>
    <p class="text-sm text-gray-400 mb-8">Uploaded: <?= htmlspecialchars($contract['created_at']) ?></p>

    <section>
      <h2 class="text-xl font-semibold mb-2">📄 OCR Text</h2>
      <textarea readonly rows="10" class="w-full p-4 border rounded bg-gray-50 text-sm font-mono text-gray-700"><?= 
        htmlspecialchars($contract['ocr_text'] ?: 'No OCR text found.') 
      ?></textarea>
    </section>

    <section class="mt-6">
      <h2 class="text-xl font-semibold mb-2">🚨 Contract Analysis</h2>

      <?php if ($analysisData): ?>
        <div class="grid grid-cols-2 gap-4 mb-6">
          <?php
            $fields = [
              'Contract Type' => $analysisData['contractType'] ?? 'N/A',
              'Effective Date' => $analysisData['effectiveDate'] ?? 'N/A',
              'Expiration Date' => $analysisData['expirationDate'] ?? 'N/A',
              'Risk Level' => $analysisData['riskLevel'] ?? 'N/A',
            ];
            foreach ($fields as $label => $value):
          ?>
            <div class="bg-gray-100 rounded p-4">
              <p class="text-xs font-bold uppercase text-gray-600"><?= htmlspecialchars($label) ?></p>
              <p class="text-lg font-semibold <?= $label === 'Risk Level' ? 'text-red-600' : '' ?>">
                <?= htmlspecialchars($value) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($analysisData['issues'])): ?>
          <div class="mb-6">
            <h3 class="font-semibold text-sm uppercase text-gray-700">Issues</h3>
            <ul class="list-disc list-inside text-sm text-red-600">
              <?php foreach ($analysisData['issues'] as $issue): ?>
                <li><?= htmlspecialchars($issue) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (!empty($analysisData['recommendedActions'])): ?>
          <div class="mb-6">
            <h3 class="font-semibold text-sm uppercase text-gray-700">Recommended Actions</h3>
            <ul class="list-disc list-inside text-sm text-green-600">
              <?php foreach ($analysisData['recommendedActions'] as $action): ?>
                <li><?= htmlspecialchars($action) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p class="text-gray-500">No analysis data available.</p>
      <?php endif; ?>
    </section>

    <section class="mt-6">
      <h2 class="text-xl font-semibold mb-2 text-white bg-gray-900 p-3 rounded">🧠 Weka AI Score</h2>
      <div class="p-4 bg-gray-900 text-white rounded shadow">
        <p class="mb-2">
          Dispute probability (12 months): 
          <span class="text-yellow-400 font-bold"><?= $riskScore ?>%</span>
        </p>

        <?php
          $barColor = 'bg-yellow-500';
          if ($riskScore >= 80) $barColor = 'bg-red-500';
          elseif ($riskScore < 50) $barColor = 'bg-green-500';
        ?>

        <div class="w-full h-3 bg-gray-700 rounded-full overflow-hidden mb-4">
          <div class="<?= $barColor ?> h-full" style="width: <?= $riskScore ?>%;"></div>
        </div>
      </div>
    </section>

    <div class="mt-6 flex justify-between text-sm">
      <a href="/admin/main/contract.php" class="text-blue-600 hover:underline">← Back to Contracts</a>
      <a href="/admin/main/random.php" class="text-blue-600 hover:underline">🎲 Random Contract</a>
    </div>

  </div>
</body>
</html>
