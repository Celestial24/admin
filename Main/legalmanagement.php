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
        category: 'Service'
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
        category: 'Confidentiality'
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
              ${c.level} (${c.score}) ${c.level === 'High' ? '<span class="ml-1 text-xs">⚠️</span>' : ''}
            </div>
          </td>
          <td class="px-3 py-3 text-sm">${confidenceCell}</td>
          <td class="px-3 py-3">
            <div class="flex justify-center gap-2">
              <button class="btnView px-2 py-1 border rounded text-xs bg-blue-500 text-white hover:bg-blue-600" data-id="${c.id}" ${accessAllowed ? '' : 'disabled'}>${accessAllowed ? 'View' : 'Restricted'}</button>
              ${isAdmin ? `<button class="btnArchive px-2 py-1 border rounded text-xs bg-red-500 text-white hover:bg-red-600" data-id="${c.id}">Archive</button>` : ''}
            </div>
          </td>
        `;
        tbody.appendChild(tr);
      });

      // Wire up action buttons
      attachActionListeners();
      console.log('Attached listeners to', document.querySelectorAll('.btnView, .btnArchive').length, 'buttons'); // Debug log
    }

    // Attaches all event listeners for the table buttons
    function attachActionListeners() {
      document.querySelectorAll('.btnView').forEach(b => {
        b.addEventListener('click', e => {
          console.log('View button clicked:', e.target.dataset.id); // Debug log
          const id = e.target.dataset.id;
          viewContract(id);
        });
      });

      document.querySelectorAll('.btnArchive').forEach(b => {
        b.addEventListener('click', e => {
          console.log('Archive button clicked:', e.target.dataset.id); // Debug log
          const id = e.target.dataset.id;
          archiveContract(id);
        });
      });
    }
    
    function viewContract(id) {
        console.log('Viewing contract:', id); // Debug log
        alert(`Viewing details for contract ID: ${id}`);
    }

    function archiveContract(id) {
        console.log('Archiving contract:', id); // Debug log
        if (confirm('Are you sure you want to archive this contract?')) {
            const idx = store.contracts.findIndex(c => c.id === id);
            if (idx === -1) return;
            
            const [contract] = store.contracts.splice(idx, 1);
            store.archived.push(contract);
            audit(`Archived contract "${contract.title}"`);
            renderContracts(document.getElementById('contractSearch').value);
        }
    }
    
    function audit(msg) {
        const ul = document.getElementById('auditTrail');
        store.audit.unshift({ when: new Date().toLocaleString(), msg });
        
        const li = document.createElement('li');
        li.className = 'text-xs text-gray-600';
        li.innerText = `${store.audit[0].when} — ${store.audit[0].msg}`;
        ul.insertBefore(li, ul.firstChild);
        if (ul.children.length > 10) {
            ul.removeChild(ul.lastChild);
        }
    }
    
    // Load contracts from the backend API
    async function loadWekaContracts() {
      try {
        console.log('Fetching contracts from API...'); // Debug log
        const response = await fetch('../backend/weka_contract_api.php');
        const result = await response.json();
        
        if (result.success && Array.isArray(result.contracts)) {
          store.contracts = result.contracts.map(c => ({
            id: c.id.toString(),
            title: c.title,
            party: c.party,
            expiry: c.created_at ? new Date(c.created_at).toISOString().slice(0, 10) : '',
            text: c.ocr_text || '',
            access: ['Manager', 'Employee'], // Default access
            score: c.risk_score,
            level: c.risk_level,
            weka_confidence: c.weka_confidence,
            employee_id: c.employee_id,
            employee_name: c.employee_name,
            category: c.category
          }));
          
          audit(`Loaded ${result.contracts.length} contracts from database`);
          console.log('API loaded successfully:', store.contracts.length, 'contracts'); // Debug log
        } else {
            throw new Error(result.message || 'Failed to load contracts');
        }
      } catch (error) {
        console.error('Error loading contracts:', error); // Debug log
        // Fallback to demo data
        store.contracts = demoContracts.map(c => ({ ...c, access: ['Manager', 'Employee'] }));
        audit(`API failed. Loaded ${demoContracts.length} demo contracts for testing.`);
        console.log('Using demo data:', store.contracts); // Debug log
      } finally {
        renderContracts('');
      }
    }

    // Initial load and search event
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM loaded. Role:', window.APP_ROLE); // Debug log
        loadWekaContracts();
        document.getElementById('contractSearch').addEventListener('input', e => {
            renderContracts(e.target.value);
        });
    });

  </script>
</body>
</html>
