<?php
session_start();

// Normalize session like user dashboard
$hasLegacySession = isset($_SESSION['user_id']);
$hasStructuredSession = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
if (!$hasStructuredSession && $hasLegacySession) {
    $_SESSION['user'] = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
    $hasStructuredSession = true;
}
if (!$hasStructuredSession) {
    header("Location: ../auth/login.php");
    exit();
}

// Current user role and name
$userRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'Employee');
$employeeName = $_SESSION['user']['name'] ?? ($_SESSION['name'] ?? 'Employee');

// Create DB connection for Weka integration
include '../backend/sql/db.php';
$wekaConn = $conn; // Use existing connection
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Contract Result</title>
  <link rel="icon" type="image/png" href="../assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .risk-low { background: linear-gradient(90deg,#ecfccb,#bbf7d0); }
    .risk-medium { background: linear-gradient(90deg,#fde68a,#fca5a5); }
    .risk-high { background: linear-gradient(90deg,#fecaca,#f87171); }
    .modal-backdrop { background: rgba(0,0,0,0.4); }
    .blur-protected { filter: blur(6px); pointer-events: none; user-select: none; }
  </style>
  <script>
    // Expose current role and employee name to JS
    window.APP_ROLE = <?php echo json_encode($userRole ?: 'Employee'); ?>;
    window.APP_EMPLOYEE_NAME = <?php echo json_encode($employeeName ?: 'Employee'); ?>;
  </script>
</head>
<body class="flex h-screen bg-gray-100 text-gray-800">

  <aside class="fixed left-0 top-0 text-white">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <main id="mainContent" class="ml-64 flex flex-col flex-1 overflow-hidden">
    <div class="flex items-center justify-between border-b pb-4 px-6 py-4 bg-white">
      <h1 class="text-2xl font-semibold"> Contract Result & Risk Analysis</h1>
      <div class="flex items-center gap-3">
        <?php include __DIR__ . '/../profile.php'; ?>
        <a href="contract.php" id="btnOpenUpload" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Upload Contract</a>
        <button id="btnAlerts" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
          Alerts <span id="alertsCount" class="ml-2 inline-block bg-white text-red-600 px-2 rounded-full text-sm">0</span>
        </button>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto p-6">
      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="col-span-1 lg:col-span-3 bg-white rounded-lg shadow p-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold">Contracts</h2>
            <div class="flex items-center gap-3">
              <div class="relative">
                <input id="contractSearch" type="text" placeholder="Search contracts..." 
                       class="pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" />
                <span class="absolute left-3 top-2.5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" 
                       stroke-linecap="round" stroke-linejoin="round">
                       <circle cx="11" cy="11" r="8"></circle>
                       <path d="m21 21-4.3-4.3"></path>
                  </svg>
                </span>
              </div>
              <div class="text-sm text-gray-500">
                Showing <span id="countContracts">0</span> contracts
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full table-auto text-center">
              <thead class="text-xs text-gray-500 uppercase">
                <tr>
                  <th class="px-3 py-2 text-center">Employee ID</th>
                  <th class="px-3 py-2 text-center">Employee</th>
                  <th class="px-3 py-2 text-center">Title</th>
                  <th class="px-3 py-2 text-center">Category</th>
                  <th class="px-3 py-2 text-center">Party</th>
                  <th class="px-3 py-2 text-center">Expiry</th>
                  <th class="px-3 py-2 text-center">Weka Risk</th>
                  <th class="px-3 py-2 text-center">Confidence</th>
                  <th class="px-3 py-2 text-center">Actions</th>
                </tr>
              </thead>
              <tbody id="contractsTableBody" class="text-sm"></tbody>
            </table>
          </div>

          <hr class="my-4" />
          <h3 class="font-medium mb-2">Audit Trail (Recent)</h3>
          <ul id="auditTrail" class="text-sm max-h-40 overflow-auto space-y-2"></ul>
        </section>
      </div>
    </div>
  </main>

  <script src="/admin/assets/js/contracts.js"></script>
</body>
</html>
d