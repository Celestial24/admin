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
    /* small helper styles for demo */
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
        <button id="btnAlerts" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Alerts <span id="alertsCount" class="ml-2 inline-block bg-white text-red-600 px-2 rounded-full text-sm">0</span></button>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto p-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <section class="col-span-1 lg:col-span-3 bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold">Contracts</h2>
          <div class="flex items-center gap-3">
            <div class="relative">
              <input id="contractSearch" type="text" placeholder="Search contracts..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" />
              <span class="absolute left-3 top-2.5 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
              </span>
            </div>
            <div class="text-sm text-gray-500">Showing <span id="countContracts">0</span> contracts</div>
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

  <!-- Modal for Viewing Contract Details -->
  <div id="viewModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 modal-backdrop transition-opacity" aria-hidden="true"></div>
      <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
      <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
              <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modal-title">Contract Details</h3>
              
              <!-- Contract Basic Info -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                  <p id="modalEmployeeId" class="text-sm text-gray-900 bg-gray-50 p-2 rounded"></p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
                  <p id="modalEmployeeName" class="text-sm text-gray-900 bg-gray-50 p-2 rounded"></p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                  <p id="modalTitle" class="text-sm text-gray-900 bg-gray-50 p-2 rounded font-medium"></p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                  <p id="modalCategory" class="text-sm text-gray-900 bg-gray-50 p-2 rounded"></p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Party</label>
                  <p id="modalParty" class="text-sm text-gray-900 bg-gray-50 p-2 rounded"></p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                  <p id="modalExpiry" class="text-sm text-gray-900 bg-gray-50 p-2 rounded"></p>
                </div>
              </div>

              <!-- Risk Analysis -->
              <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-2">Risk Analysis</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weka Risk Level</label>
                    <p id="modalRiskLevel" class="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                      <!-- Dynamic risk badge -->
                    </p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confidence</label>
                    <p id="modalConfidence" class="text-sm text-gray-900 bg-gray-50 p-2 rounded"></p>
                  </div>
                </div>
              </div>

              <!-- Set Password Section (Admin Only) -->
              <div id="passwordSection" class="hidden mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-2">Set View Password</h4>
                <p class="text-sm text-gray-600 mb-3">Set a password to protect access to this contract's full details.</p>
                <div class="flex gap-2">
                  <input type="password" id="passwordInput" placeholder="Enter password" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                  <button id="setPasswordBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">Set Password</button>
                </div>
                <p id="currentPassword" class="text-xs text-gray-500 mt-2">Current: None set</p>
              </div>

              <!-- Reveal Text Button (For protected text) -->
              <div id="revealTextSection" class="mb-4 hidden">
                <button id="revealTextBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Reveal Full Text</button>
              </div>

              <!-- Full Contract Text (Optional, blurred if no password) -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contract Text (OCR Extracted)</label>
                <div id="modalContractText" class="text-sm text-gray-900 bg-gray-50 p-3 rounded max-h-40 overflow-y-auto blur-protected">Full text is protected. Use the button above to reveal.</div>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
          <button id="closeModalBtn" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Close</button>
        </div>
      </div>
    </div>
  </div>

    <script>
    // In-memory data store
    const store = {
      contracts: [],
      archived: [],
      alerts: [],
      audit: []
    };

    // Demo data for fallback (remove once backend works)
    const demoContracts = [
      {
        id: '1',
        title: 'Sample Service Agreement',
        party: 'ABC Corp',
        expiry: '2024-12-31',
        score: 0.85,
        level: 'High',
        weka_confidence: 92,
        employee_id: 'EMP001',
        employee_name: 'John Doe',
        category: 'Service',
        text: 'This is a sample contract text for service agreement between ABC Corp and the company. It includes terms like payment, duration, etc. Risky clauses: Unlimited liability, no termination.',
        view_password: null // No password initially
      },
      {
        id: '2',
        title: 'NDA Contract',
        party: 'XYZ Ltd',
        expiry: '2025-06-15',
        score: 0.45,
        level: 'Medium',
        weka_confidence: 78,
        employee_id: 'EMP002',
        employee_name: 'Jane Smith',
        category: 'Confidentiality',
        text: 'Non-disclosure agreement sample text. Parties agree to keep information confidential for 2 years. Includes penalties for breach.',
        view_password: null // No password initially
      }
    ];

    // Render function for contracts table
    function renderContracts(filter = '') {
      console.log('Rendering contracts with filter:', filter); // Debug log
      const tbody = document.getElementById('contractsTableBody');
      tbody.innerHTML = '';
      const role = window.APP_ROLE || 'Employee';
      const isAdmin = role === 'Admin';
      
      const list = store.contracts.filter(c =>
        JSON.stringify(c).toLowerCase().includes(filter.toLowerCase())
      );
      
      document.getElementById('countContracts').innerText = list.length;
      
      list.forEach(c => {
        const tr = document.createElement('tr');
        tr.className = 'border-t';
        
        // Temporarily force access for testing (remove in production)
        const accessAllowed = true; // Override: (c.access || []).map(a => a.trim()).includes(role) || isAdmin;
        const maskedParty = isAdmin ? c.party : '••••••';
        const confidenceCell = c.weka_confidence ? `<div class="text-blue-600 font-medium">${c.weka_confidence}%</div>` : '—';
        const employee = c.employee_name || c.uploaded_by_name || window.APP_EMPLOYEE_NAME || 'Employee';
        
        tr.innerHTML = `
          <td class="px-3 py-3">${c.employee_id || 'N/A'}</td>
          <td class="px-3 py-3">${employee}</td>
          <td class="px-3 py-3 font-medium">${c.title}</td>
          <td class="px-3 py-3">${c.category || '—'}</td>
          <td class="px-3 py-3 ${isAdmin ? '' : 'blur-protected'}">${maskedParty}</td>
          <td class="px-3 py-3">${c.expiry || '—'}</td>
          <td class="px-3 py-3">
            <div class="inline-block px-3 py-1 rounded ${c.level === 'High' ? 'risk-high' : c.level === 'Medium' ? 'risk-medium' : 'risk-low'}">
              ${c.level} (${c.score}) ${c.level === 'High' ? '<span class="ml-1