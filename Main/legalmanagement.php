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
  <!-- Tailwind Play CDN (for demo only) -->
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
    // Only Admin and Super Admin are supported
    window.APP_ROLE = <?php echo json_encode(($userRole === 'super_admin' || strtolower($userRole) === 'superadmin' || strtolower($userRole) === 'super') ? 'Super Admin' : 'Admin'); ?>;
    window.APP_EMPLOYEE_NAME = <?php echo json_encode($employeeName ?: 'Employee'); ?>;
  </script>
</head>
<body class="flex h-screen bg-gray-100 text-gray-800">

  <!-- Sidebar -->
  <aside class="fixed left-0 top-0 text-white">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <!-- Main Content -->
  <main id="mainContent" class="ml-64 flex flex-col flex-1 overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between border-b pb-4 px-6 py-4 bg-white">
      <h1 class="text-2xl font-semibold"> Contract Result & Risk Analysis</h1>
      <div class="flex items-center gap-3">
        <?php include __DIR__ . '/../profile.php'; ?>
        <a href="contract.php" id="btnOpenUpload" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Upload Contract</a>
        <button id="btnAlerts" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Alerts <span id="alertsCount" class="ml-2 inline-block bg-white text-red-600 px-2 rounded-full text-sm">0</span></button>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Center: List -->
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
                <th class="px-3 py-2 text-center">Access</th>
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

    <!-- Modals (hidden by default) -->
    <!-- Universal Modal -->
    <div id="universalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
      <div id="universalCard" class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gray-50">
          <h3 id="modalTitle" class="text-xl font-semibold text-gray-800">Modal Title</h3>
          <button onclick="closeModal('universalModal')" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">✕</button>
        </div>
        <div id="modalContent" class="p-6 overflow-y-auto">
          <!-- Content will be dynamically loaded here -->
        </div>
        <div id="modalActions" class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
          <!-- Action buttons will be dynamically loaded here -->
        </div>
      </div>
    </div>

    <!-- Alerts Modal -->
    <div id="modalAlerts" class="fixed inset-0 hidden items-center justify-center modal-backdrop">
      <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-2/3 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">High Risk Clause Alerts</h3>
          <button id="closeAlerts" class="text-gray-500">✕</button>
        </div>
        <div id="alertsList" class="space-y-3 text-sm"></div>
      </div>
    </div>

    <!-- View Details Modal -->
    <div id="modalView" class="fixed inset-0 hidden items-center justify-center modal-backdrop">
      <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-2/3 lg:w-1/2 p-6 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between mb-4 border-b pb-3">
          <h3 id="viewModalTitle" class="text-lg font-semibold">Contract Details</h3>
          <button id="closeView" class="text-gray-500">✕</button>
        </div>
        <div class="space-y-4 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <strong>Party:</strong> 
                    <span id="viewParty"></span>
                    <button id="togglePartyVisibility" onclick="togglePartyVisibility()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
                <div><strong>Expiry Date:</strong> <span id="viewExpiry"></span></div>
                <div><strong>Risk Level:</strong> <span id="viewRiskLevel"></span></div>
                <div><strong>Risk Score:</strong> <span id="viewRiskScore"></span></div>
                <div><strong>Weka Confidence:</strong> <span id="viewConfidence"></span></div>
                <div><strong>Employee ID:</strong> <span id="viewEmployeeId"></span></div>
                <div><strong>Employee:</strong> <span id="viewEmployeeName"></span></div>
                <div><strong>Category:</strong> <span id="viewCategory"></span></div>
            </div>
            <div>
                <h4 class="font-semibold mb-2">Risk Factors</h4>
                <ul id="viewRiskFactors" class="list-disc list-inside text-sm text-red-500 space-y-1"></ul>
            </div>
            <div>
                <h4 class="font-semibold mb-2">Recommendations</h4>
                <ul id="viewRecommendations" class="list-disc list-inside text-sm text-green-600 space-y-1"></ul>
            </div>
            <div>
                <h4 class="font-semibold mb-2">Full Contract Text</h4>
                <textarea id="viewText" rows="8" readonly class="w-full p-2 border rounded bg-gray-50 text-xs whitespace-pre-wrap break-words"></textarea>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t bg-gray-50 -mx-6 -mb-6 px-6 py-4">
          <div class="flex justify-end gap-3">
            <button onclick="editContractFromView()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Edit
            </button>
            <button onclick="deleteContractFromView()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
              Delete
            </button>
            <button onclick="updatePasswordFromView()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
              </svg>
              Update Password
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Weka Analysis Modal -->
    <div id="modalAnalysis" class="fixed inset-0 hidden items-center justify-center modal-backdrop">
      <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-2/3 lg:w-1/2 p-6 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between mb-4 border-b pb-3">
          <h3 id="analysisTitle" class="text-lg font-semibold">Weka AI Analysis</h3>
          <button id="closeAnalysis" class="text-gray-500">✕</button>
        </div>
        <div class="space-y-4 overflow-y-auto">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><strong>Risk Level:</strong> <span id="analysisRiskLevel"></span></div>
            <div><strong>Risk Score:</strong> <span id="analysisRiskScore"></span></div>
            <div><strong>Weka Confidence:</strong> <span id="analysisConfidence"></span></div>
            <div><strong>Probability of Dispute:</strong> <span id="analysisProbability"></span></div>
          </div>
          <div>
            <h4 class="font-semibold mb-2">Risk Factors</h4>
            <ul id="analysisRiskFactors" class="list-disc list-inside text-sm text-red-500 space-y-1"></ul>
          </div>
          <div>
            <h4 class="font-semibold mb-2">Recommendations</h4>
            <ul id="analysisRecommendations" class="list-disc list-inside text-sm text-green-600 space-y-1"></ul>
          </div>
        </div>
      </div>
    </div>

  </div>
  
  <!-- ✅ Chatbot: Toggle Button + Chat Window -->
<div class="fixed bottom-6 right-6 z-50">
  <!-- Toggle Button -->
  <button id="chatbotToggle"
    class="bg-gray-800 hover:bg-gray-700 text-white rounded-full p-2 shadow-lg transition-all duration-300 group relative">
    <img src="/admin/assets/image/logo2.png" alt="Admin Assistant" class="w-12 h-12 object-contain">
    <span class="absolute bottom-full mb-2 right-1/2 transform translate-x-1/2 bg-gray-900 text-white text-xs rounded py-1 px-2 opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-lg">
      Admin Assistant
    </span>
  </button>

  <!-- Chat Window -->
  <div id="chatbotBox"
    class="fixed bottom-24 right-6 w-[420px] bg-white border border-gray-200 rounded-xl shadow-xl opacity-0 scale-95 pointer-events-none transition-all duration-300 overflow-hidden">
    
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-blue-600 text-white">
      <h3 class="font-semibold">Admin Assistant</h3>
      <button id="chatbotClose" class="text-white hover:text-gray-200 text-xl leading-none">×</button>
    </div>

    <!-- Messages -->
    <div id="chatContent" class="p-4 h-64 overflow-y-auto text-sm bg-gray-50 space-y-4">
      <!-- Chat will load here -->
    </div>

    <!-- Quick Replies -->
    <div class="p-3 border-t border-gray-200 bg-white">
      <div class="flex flex-wrap gap-2 mb-3">
        <button class="quickBtn bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-1 rounded-lg text-xs" data-action="facilities">View Facilities</button>
        <button class="quickBtn bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1 rounded-lg text-xs" data-action="reservations">Check Reservations</button>
        <button class="quickBtn bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1 rounded-lg text-xs" data-action="maintenance">Maintenance Log</button>
      </div>
    </div>

    <!-- Input -->
    <div class="p-3 border-t border-gray-200 bg-white flex gap-2">
      <input id="userInput" type="text" placeholder="Ask me anything..."
        class="flex-1 rounded-lg px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      <button id="sendBtn"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Send</button>
    </div>
  </div>
</div>

  <script>
    // Demo in-memory store
    const store = {
      contracts: [],
      archived: [],
      alerts: [],
      audit: []
    };

    // Utilities
    function uid() { return Math.random().toString(36).slice(2,9); }
    function todayPlusDays(n){ const d=new Date(); d.setDate(d.getDate()+n); return d.toISOString().slice(0,10); }

    // Risk analysis: simple keyword matching for demo
    const riskyWords = {
      high: ['indemnify','penalty','liquidated damages','termination for convenience','jurisdiction'],
      medium: ['notice','auto-renew','renewal','warranty','limitation of liability'],
      low: ['confidentiality','data protection','compliance']
    };

    function analyzeRisk(text){
      let score=50;
      const t = text.toLowerCase();
      riskyWords.high.forEach(w=>{ if(t.includes(w)) score += 25; });
      riskyWords.medium.forEach(w=>{ if(t.includes(w)) score += 10; });
      riskyWords.low.forEach(w=>{ if(t.includes(w)) score -= 5; });
      if(score<0) score=0; if(score>100) score=100;
      let level = 'Low';
      if(score>=70) level='High'; else if(score>=40) level='Medium';
      return {score, level};
    }

    // Render functions
    function renderContracts(filter=''){
      const tbody = document.getElementById('contractsTableBody');
      tbody.innerHTML = '';
      const role = window.APP_ROLE || 'Admin'; // Default to Admin for testing
      const isAdmin = role === 'Admin';
      const list = store.contracts.filter(c => (("employee "+String(c.id).padStart(3,'0'))+(c.employee_name||'')+(c.uploaded_by_name||'')+(c.title||'')+(c.category||'')+(c.party||'')+(c.text||'')).toLowerCase().includes(filter.toLowerCase()));
      document.getElementById('countContracts').innerText = list.length;
      list.forEach(c=>{
        const tr = document.createElement('tr');
        tr.className = 'border-t';
        const accessAllowed = true; // Always allow access to Views button
        const maskedParty = isAdmin ? c.party : '••••••';
        const confidenceCell = c.weka_confidence ? `<div class="text-blue-600 font-medium">${c.weka_confidence}%</div>` : '—';
        const employee = c.employee_name || c.uploaded_by_name || window.APP_EMPLOYEE_NAME || 'Employee';
        tr.innerHTML = `
          <td class="px-3 py-3 align-top break-words whitespace-normal">Employee ${String(c.id).padStart(3,'0')}</td>
          <td class="px-3 py-3 align-top break-words whitespace-normal">${c.employee_name || '—'}</td>
          <td class="px-3 py-3 align-top font-medium break-words whitespace-normal">${c.title}</td>
          <td class="px-3 py-3 align-top break-words whitespace-normal">${c.category || 'Other'}</td>
          <td class="px-3 py-3 align-top ${isAdmin?'':'blur-protected'} break-words whitespace-normal">${maskedParty}</td>
          <td class="px-3 py-3 align-top break-words whitespace-normal">${c.expiry || '—'}</td>
          <td class="px-3 py-3 align-top break-words whitespace-normal">
            <div class="inline-block px-3 py-1 rounded ${c.level==='High'?'risk-high':c.level==='Medium'?'risk-medium':'risk-low'}">
              ${c.level} (${c.score}) ${c.level==='High'?'<span class="ml-1 text-xs">⚠️</span>':''}
            </div>
          </td>
          <td class="px-3 py-3 align-top text-sm break-words whitespace-normal">${confidenceCell}</td>
          <td class="px-3 py-3 align-top text-sm text-gray-600 break-words whitespace-normal">${c.access.join(', ')}</td>
          <td class="px-3 py-3 align-top">
            <div class="flex gap-2">
              <button class="btnRestricted px-2 py-1 bg-gray-100 text-gray-700 border border-gray-300 rounded text-xs hover:bg-gray-200 cursor-pointer" data-id="${c.id}">Restricted</button>
            </div>
          </td>
        `;
        tbody.appendChild(tr);
      });

      // Wire up action buttons
      const openWithPasswordGuard = async (id, fn) => {
        const c = store.contracts.find(x=>x.id===id);
        if (!c) return;
        
        // Check if contract has password protection
        if (c.view_password && c.view_password.trim() !== '') {
          showPasswordModal(id, 'view');
        } else {
          fn(id);
        }
      }
      document.querySelectorAll('.btnRestricted').forEach(b=> b.addEventListener('click', e=>{
        const id=e.target.dataset.id; showPasswordModal(id, 'view');
      }));

      renderAudit();
    }

    // Global variables for password modal
    let currentContractId = null;
    let pendingAction = null;
    let currentContract = null;

    // Password verification helper (use get_contract with password)
    async function verifyPassword(contractId, password) {
      try {
        const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}&password=${encodeURIComponent(password)}`);
        const result = await response.json();
        return !!(result && result.success);
      } catch (error) {
        console.error('Password verification error:', error);
        return false;
      }
    }

    // Show universal modal
    function showUniversalModal(title, content, actions, size) {
      document.getElementById('modalTitle').textContent = title;
      document.getElementById('modalContent').innerHTML = content;
      document.getElementById('modalActions').innerHTML = actions;
      // Resize card based on requested size
      const card = document.getElementById('universalCard');
      if (card){
        card.classList.remove('max-w-4xl','max-w-2xl','max-w-xl','max-w-lg','max-w-md');
        if (size === 'sm') card.classList.add('max-w-md');
        else if (size === 'md') card.classList.add('max-w-lg');
        else if (size === 'lg') card.classList.add('max-w-xl');
        else if (size === 'xl') card.classList.add('max-w-2xl');
        else card.classList.add('max-w-4xl');
      }
      openModal('universalModal');
    }

    // Show password modal
    function showPasswordModal(contractId, action) {
      currentContractId = contractId;
      pendingAction = action;
      
      const content = `
        <div>
          <label class=\"block text-sm font-medium text-gray-700 mb-1\">Password</label>
          <div class=\"relative\">
            <input type=\"password\" id=\"passwordInput\" class=\"w-full pr-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500\" placeholder=\"Enter password\">
            <button type=\"button\" id=\"togglePwd\" class=\"absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700\" tabindex=\"-1\" aria-label=\"Toggle password visibility\">👁️</button>
          </div>
        </div>
      `;
      
      const actions = `
        <button onclick="closeModal('universalModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
        <button onclick="verifyPasswordAndProceed()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Verify</button>
      `;
      
      // Use small width for password prompt
      showUniversalModal('Enter Password', content, actions, 'sm');
      // Wire eye toggle for this modal after render
      setTimeout(()=>{
        const t=document.getElementById('togglePwd');
        if(t){ t.addEventListener('click',()=>{ const i=document.getElementById('passwordInput'); if(!i) return; i.type = i.type==='password'?'text':'password'; t.textContent = i.type==='password'?'👁️':'🙈'; }); }
      },0);
    }

    // Show new password modal
    function showNewPasswordModal(contractId) {
      currentContractId = contractId;
      
      const content = `
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
          <input type="password" id="newPasswordInput" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter new password (leave empty to remove)">
          <p class="text-xs text-gray-500 mt-1">Leave empty to remove password protection</p>
        </div>
      `;
      
      const actions = `
        <button onclick="closeModal('universalModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
        <button onclick="setNewPassword()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Set Password</button>
      `;
      
      showUniversalModal('Set New Password', content, actions);
    }

    // Open modal helper
    function openModal(modalId) {
      document.getElementById(modalId).classList.remove('hidden');
      document.getElementById(modalId).classList.add('flex');
    }

    // Close modal helper
    function closeModal(modalId) {
      document.getElementById(modalId).classList.add('hidden');
      document.getElementById(modalId).classList.remove('flex');
      
      // Clear universal modal content when closing
      if (modalId === 'universalModal') {
        document.getElementById('modalTitle').textContent = 'Modal Title';
        document.getElementById('modalContent').innerHTML = '<!-- Content will be dynamically loaded here -->';
        document.getElementById('modalActions').innerHTML = '<!-- Action buttons will be dynamically loaded here -->';
      }
    }

    // Verify password and proceed with action
    async function verifyPasswordAndProceed() {
      const password = document.getElementById('passwordInput').value;
      if (!password) {
        alert('Please enter a password');
        return;
      }
      
      const isValid = await verifyPassword(currentContractId, password);
      if (isValid) {
        closeModal('universalModal');
        if (pendingAction === 'view') {
          await viewContractWithPassword(currentContractId, password);
        } else if (pendingAction === 'edit') {
          await editContractWithPassword(currentContractId, password);
        } else if (pendingAction === 'delete') {
          await deleteContractWithPassword(currentContractId, password);
        } else if (pendingAction === 'updatePassword') {
          await updatePasswordWithPassword(currentContractId, password);
        }
      } else {
        alert('Invalid password. Access denied.');
      }
    }

    // View contract with password
    async function viewContractWithPassword(contractId, password) {
      try {
        const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}&password=${encodeURIComponent(password)}`);
        const result = await response.json();
        
        if (result.success) {
          currentContract = result.contract;
          displayContractDetails(result.contract);
        } else {
          alert('Error loading contract details.');
        }
      } catch (error) {
        console.error('Error viewing contract:', error);
        alert('Error loading contract details.');
      }
    }

    // Toggle party visibility
    function togglePartyVisibility() {
      const partyElement = document.getElementById('viewParty');
      const toggleButton = document.getElementById('togglePartyVisibility');
      
      if (partyElement.textContent === '••••••') {
        // Show actual party name
        partyElement.textContent = currentContract ? currentContract.party : 'Unknown';
        // Change icon to eye with slash
        toggleButton.innerHTML = `
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
          </svg>
        `;
      } else {
        // Hide party name
        partyElement.textContent = '••••••';
        // Change icon back to eye
        toggleButton.innerHTML = `
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
          </svg>
        `;
      }
    }

    // Edit contract from view
    async function editContractFromView() {
      if (!currentContract) return;
      
      if (currentContract.view_password && currentContract.view_password.trim() !== '') {
        showPasswordModal(currentContract.id, 'edit');
      } else {
        // No password required, proceed with edit
        showEditForm(currentContract);
      }
    }

    // Show edit form modal
    function showEditForm(contract) {
      const content = `
        <div class="max-h-96 overflow-y-auto">
          <form id="editContractForm" class="space-y-6">
            <!-- Basic Information Section -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="text-sm font-semibold text-gray-800 mb-3 border-b pb-2">Basic Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                  <input type="text" name="title" value="${contract.title || ''}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Party *</label>
                  <input type="text" name="party" value="${contract.party || ''}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                  <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="Other" ${contract.category === 'Other' ? 'selected' : ''}>Other</option>
                    <option value="Employment" ${contract.category === 'Employment' ? 'selected' : ''}>Employment</option>
                    <option value="Supplier" ${contract.category === 'Supplier' ? 'selected' : ''}>Supplier</option>
                    <option value="Lease" ${contract.category === 'Lease' ? 'selected' : ''}>Lease</option>
                    <option value="Service" ${contract.category === 'Service' ? 'selected' : ''}>Service</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                  <input type="text" name="department" value="${contract.department || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
              </div>
            </div>

            <!-- Employee Information Section -->
            <div class="bg-blue-50 p-4 rounded-lg">
              <h4 class="text-sm font-semibold text-gray-800 mb-3 border-b pb-2">Employee Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
                  <input type="text" name="employee_name" value="${contract.employee_name || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                  <input type="text" name="employee_id" value="${contract.employee_id || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
              </div>
            </div>

            <!-- Contract Details Section -->
            <div class="bg-green-50 p-4 rounded-lg">
              <h4 class="text-sm font-semibold text-gray-800 mb-3 border-b pb-2">Contract Details</h4>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                  <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Enter contract description...">${contract.description || ''}</textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Contract Text (OCR)</label>
                  <textarea name="ocr_text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Enter contract text content...">${contract.text || ''}</textarea>
                </div>
              </div>
            </div>

            <!-- Security Section -->
            <div class="bg-yellow-50 p-4 rounded-lg">
              <h4 class="text-sm font-semibold text-gray-800 mb-3 border-b pb-2">Security Settings</h4>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">View Password</label>
                <input type="password" name="view_password" placeholder="Enter new password or leave empty to remove" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <p class="text-xs text-gray-500 mt-1">Leave empty to remove password protection</p>
              </div>
            </div>
          </form>
        </div>
      `;
      
      const actions = `
        <button onclick="closeModal('universalModal')" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">Cancel</button>
        <button onclick="saveContractEdit()" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          Save Changes
        </button>
      `;
      
      showUniversalModal('Edit Contract Details', content, actions);
    }

    // Delete contract from view
    async function deleteContractFromView() {
      if (!currentContract) return;
      
      if (!confirm('Are you sure you want to delete this contract? This action cannot be undone.')) {
        return;
      }
      
      if (currentContract.view_password && currentContract.view_password.trim() !== '') {
        showPasswordModal(currentContract.id, 'delete');
      } else {
        // No password required, proceed with delete
        deleteContract(currentContract.id);
      }
    }

    // Update password from view
    async function updatePasswordFromView() {
      if (!currentContract) return;
      
      if (currentContract.view_password && currentContract.view_password.trim() !== '') {
        showPasswordModal(currentContract.id, 'updatePassword');
      } else {
        // No current password, show new password modal
        showNewPasswordModal(currentContract.id);
      }
    }

    // Show change password form
    function showChangePasswordForm(contractId) {
      const content = `
        <form id="changePasswordForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
            <input type="password" id="currentPasswordInput" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter current password" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
            <input type="password" id="newPasswordInput" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter new password" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
            <input type="password" id="confirmPasswordInput" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Confirm new password" required>
          </div>
          <div class="text-xs text-gray-500">
            <p>• Leave new password empty to remove password protection</p>
            <p>• Password must be at least 6 characters long</p>
          </div>
        </form>
      `;
      
      const actions = `
        <button onclick="closeModal('universalModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
        <button onclick="changePassword()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Change Password</button>
      `;
      
      currentContractId = contractId;
      showUniversalModal('Change Password', content, actions);
    }

    // Edit contract with password
    async function editContractWithPassword(contractId, password) {
      try {
        const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}&password=${encodeURIComponent(password)}`);
        const result = await response.json();
        
        if (result.success) {
          showEditForm(result.contract);
        } else {
          alert('Error loading contract for editing.');
        }
      } catch (error) {
        console.error('Error editing contract:', error);
        alert('Error loading contract for editing.');
      }
    }

    // Delete contract with password
    async function deleteContractWithPassword(contractId, password) {
      try {
        const deleteResponse = await fetch('../backend/weka_contract_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=delete&contract_id=${contractId}&password=${encodeURIComponent(password)}`
        });
        const deleteResult = await deleteResponse.json();
        
        if (deleteResult.success) {
          alert('Contract deleted successfully!');
          closeModal('modalView');
          loadWekaContracts(); // Reload contracts
        } else {
          alert('Error deleting contract: ' + deleteResult.message);
        }
      } catch (error) {
        console.error('Error deleting contract:', error);
        alert('Error deleting contract. Please try again.');
      }
    }

    // Update password with current password
    async function updatePasswordWithPassword(contractId, currentPassword) {
      // Pre-fill the current password in the form
      showChangePasswordForm(contractId);
      
      // Set the current password after the modal is shown
      setTimeout(() => {
        const currentPasswordInput = document.getElementById('currentPasswordInput');
        if (currentPasswordInput) {
          currentPasswordInput.value = currentPassword;
        }
      }, 100);
    }

    // Set contract password (when no current password)
    async function setContractPassword(contractId, newPassword) {
      try {
        const response = await fetch('../backend/weka_contract_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=set_password&contract_id=${contractId}&new_password=${encodeURIComponent(newPassword)}`
        });
        const result = await response.json();
        
        if (result.success) {
          alert('Password set successfully!');
          closeModal('modalView');
          loadWekaContracts(); // Reload contracts
        } else {
          alert('Error setting password: ' + result.message);
        }
      } catch (error) {
        console.error('Error setting password:', error);
        alert('Error setting password. Please try again.');
      }
    }

    // Set new password from modal
    async function setNewPassword() {
      const newPassword = document.getElementById('newPasswordInput').value;
      
      try {
        const response = await fetch('../backend/weka_contract_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=set_password&contract_id=${currentContractId}&new_password=${encodeURIComponent(newPassword)}`
        });
        const result = await response.json();
        
        if (result.success) {
          alert('Password set successfully!');
          closeModal('universalModal');
          closeModal('modalView');
          loadWekaContracts(); // Reload contracts
        } else {
          alert('Error setting password: ' + result.message);
        }
      } catch (error) {
        console.error('Error setting password:', error);
        alert('Error setting password. Please try again.');
      }
    }

    // Save contract edit
    async function saveContractEdit() {
      const form = document.getElementById('editContractForm');
      const formData = new FormData(form);
      
      const contractData = {
        title: formData.get('title'),
        party: formData.get('party'),
        category: formData.get('category'),
        employee_name: formData.get('employee_name'),
        employee_id: formData.get('employee_id'),
        department: formData.get('department'),
        description: formData.get('description'),
        view_password: formData.get('view_password'),
        ocr_text: formData.get('ocr_text')
      };
      
      // Validate required fields
      if (!contractData.title || !contractData.party) {
        alert('Title and Party are required fields.');
        return;
      }
      
      try {
        const response = await fetch('../backend/weka_contract_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=update&contract_id=${currentContract.id}&${Object.entries(contractData).map(([k,v]) => `${k}=${encodeURIComponent(v)}`).join('&')}`
        });
        const result = await response.json();
        
        if (result.success) {
          alert('Contract updated successfully!');
          closeModal('universalModal');
          closeModal('modalView');
          loadWekaContracts(); // Reload contracts
        } else {
          alert('Error updating contract: ' + result.message);
        }
      } catch (error) {
        console.error('Error updating contract:', error);
        alert('Error updating contract. Please try again.');
      }
    }

    // Change password function
    async function changePassword() {
      const currentPassword = document.getElementById('currentPasswordInput').value;
      const newPassword = document.getElementById('newPasswordInput').value;
      const confirmPassword = document.getElementById('confirmPasswordInput').value;
      
      // Validate inputs
      if (!currentPassword) {
        alert('Please enter current password.');
        return;
      }
      
      if (newPassword !== confirmPassword) {
        alert('New passwords do not match.');
        return;
      }
      
      if (newPassword && newPassword.length < 6) {
        alert('New password must be at least 6 characters long.');
        return;
      }
      
      try {
        const response = await fetch('../backend/weka_contract_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=set_password&contract_id=${currentContractId}&new_password=${encodeURIComponent(newPassword)}&current_password=${encodeURIComponent(currentPassword)}`
        });
        const result = await response.json();
        
        if (result.success) {
          if (newPassword) {
            alert('Password changed successfully!');
          } else {
            alert('Password removed successfully!');
          }
          closeModal('universalModal');
          closeModal('modalView');
          loadWekaContracts(); // Reload contracts
        } else {
          alert('Error changing password: ' + result.message);
        }
      } catch (error) {
        console.error('Error changing password:', error);
        alert('Error changing password. Please try again.');
      }
    }
    
    function renderAudit(){
      const ul = document.getElementById('auditTrail');
      ul.innerHTML='';
      store.audit.slice().reverse().slice(0,10).forEach(a=>{
        const li = document.createElement('li');
        li.className='text-xs text-gray-600';
        li.innerText = `${a.when} — ${a.msg}`;
        ul.appendChild(li);
      });
    }

    function viewContract(id){
        const c = store.contracts.find(x=>x.id===id);
        if(!c) return;
        
        // Check if password is required
        if (c.view_password && c.view_password.trim() !== '') {
          showPasswordModal(id, 'view');
        } else {
          // No password required, show contract directly
          displayContractDetails(c);
        }
    }

    // Display contract details in modal
    function displayContractDetails(c) {
        currentContract = c;
        const role = window.APP_ROLE || 'Admin';
        const isAdmin = role === 'Admin';

        // Populate Modal
        document.getElementById('viewModalTitle').innerText = c.title;
        document.getElementById('viewParty').innerText = isAdmin ? c.party : '••••••';
        document.getElementById('viewExpiry').innerText = c.expiry || '—';
        document.getElementById('viewRiskLevel').innerHTML = `<span class="inline-block px-2 py-1 rounded-full text-xs ${c.level==='High'?'bg-red-100 text-red-800':c.level==='Medium'?'bg-yellow-100 text-yellow-800':'bg-green-100 text-green-800'}">${c.level}</span>`;
        document.getElementById('viewRiskScore').innerText = c.score;
        document.getElementById('viewConfidence').innerText = `${c.weka_confidence || 'N/A'}%`;
        document.getElementById('viewText').value = isAdmin ? (c.text || 'No text available.') : '•••••• (restricted)';
        document.getElementById('viewEmployeeId').innerText = `Employee ${String(c.id).padStart(3,'0')}`;
        document.getElementById('viewEmployeeName').innerText = c.employee_name || '—';
        document.getElementById('viewCategory').innerText = c.category || 'Other';
        
        const riskFactorsUl = document.getElementById('viewRiskFactors');
        riskFactorsUl.innerHTML = '';
        if(c.risk_factors && c.risk_factors.length > 0) {
            c.risk_factors.forEach(f => {
                const li = document.createElement('li');
                li.textContent = f;
                riskFactorsUl.appendChild(li);
            });
        } else {
            riskFactorsUl.innerHTML = '<li>No significant risk factors identified.</li>';
        }
        
        const recommendationsUl = document.getElementById('viewRecommendations');
        recommendationsUl.innerHTML = '';
        if(c.recommendations && c.recommendations.length > 0) {
            c.recommendations.forEach(r => {
                const li = document.createElement('li');
                li.textContent = r;
                recommendationsUl.appendChild(li);
            });
        } else {
            recommendationsUl.innerHTML = '<li>No specific recommendations.</li>';
        }

        // Show Modal
        openModal('modalView');

        audit(`Viewed details for contract "${c.title}" by ${role}`);
    }

    function analyzeContract(id){
      const c = store.contracts.find(x=>x.id===id);
      if(!c) return;
      
      // Populate analysis modal
      document.getElementById('analysisTitle').innerText = `Weka AI Analysis — ${c.title}`;
      document.getElementById('analysisRiskLevel').innerHTML = `<span class="inline-block px-2 py-1 rounded-full text-xs ${c.level==='High'?'bg-red-100 text-red-800':c.level==='Medium'?'bg-yellow-100 text-yellow-800':'bg-green-100 text-green-800'}">${c.level}</span>`;
      document.getElementById('analysisRiskScore').innerText = `${c.score}/100`;
      document.getElementById('analysisConfidence').innerText = `${c.weka_confidence || 'N/A'}%`;
      document.getElementById('analysisProbability').innerText = `${c.probability_percent || 'N/A'}%`;

      const rf = document.getElementById('analysisRiskFactors');
      rf.innerHTML = '';
      if (c.risk_factors && c.risk_factors.length) {
        c.risk_factors.forEach(item=>{ const li=document.createElement('li'); li.textContent=item; rf.appendChild(li); });
      } else { rf.innerHTML = '<li>No significant risk factors identified.</li>'; }

      const rec = document.getElementById('analysisRecommendations');
      rec.innerHTML = '';
      if (c.recommendations && c.recommendations.length) {
        c.recommendations.forEach(item=>{ const li=document.createElement('li'); li.textContent=item; rec.appendChild(li); });
      } else { rec.innerHTML = '<li>No specific recommendations.</li>'; }

      // Show modal
      const m = document.getElementById('modalAnalysis');
      m.classList.remove('hidden');
      m.classList.add('flex');

      audit(`Analyzed contract "${c.title}" with Weka AI — Risk ${c.level} (${c.score})`);
    }
    
    function showHighRiskDetails(id){
      const c = store.contracts.find(x=>x.id===id);
      if(!c) return;
      
      let detailsText = `🚨 HIGH RISK CONTRACT ALERT 🚨\n\n`;
      detailsText += `Contract: ${c.title}\n`;
      detailsText += `Party: ${c.party}\n`;
      detailsText += `Risk Level: ${c.level} (${c.score}/100)\n`;
      detailsText += `Weka Confidence: ${c.weka_confidence || 'N/A'}%\n\n`;
      
      if(c.risk_factors && c.risk_factors.length > 0) {
        detailsText += `⚠️ CRITICAL RISK FACTORS:\n${c.risk_factors.map(f => `• ${f}`).join('\n')}\n\n`;
      }
      
      if(c.recommendations && c.recommendations.length > 0) {
        detailsText += `📋 IMMEDIATE ACTIONS REQUIRED:\n${c.recommendations.map(r => `• ${r}`).join('\n')}\n\n`;
      }
      
      detailsText += `⚡ This contract requires immediate legal review!`;
      
      alert(detailsText);
      audit(`High risk alert viewed for "${c.title}"`);
    }

    function archiveContract(id){
      const idx = store.contracts.findIndex(x=>x.id===id);
      if(idx===-1) return;
      const [c] = store.contracts.splice(idx,1);
      store.archived.push(c);
      audit(`Archived contract "${c.title}"`);
      renderContracts('');
    }

    async function editContract(id){
      const c = store.contracts.find(x=>x.id===id);
      if(!c) return;
      
      // Create edit form
      const editForm = document.createElement('div');
      editForm.className = 'fixed inset-0 flex items-center justify-center modal-backdrop';
      editForm.innerHTML = `
        <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-2/3 lg:w-1/2 p-6 max-h-[90vh] flex flex-col">
          <div class="flex items-center justify-between mb-4 border-b pb-3">
            <h3 class="text-lg font-semibold">Edit Contract</h3>
            <button id="closeEdit" class="text-gray-500">✕</button>
          </div>
          <form id="editContractForm" class="space-y-4 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" name="title" value="${c.title}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Party</label>
                <input type="text" name="party" value="${c.party}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="Other" ${c.category === 'Other' ? 'selected' : ''}>Other</option>
                  <option value="Employment" ${c.category === 'Employment' ? 'selected' : ''}>Employment</option>
                  <option value="Supplier" ${c.category === 'Supplier' ? 'selected' : ''}>Supplier</option>
                  <option value="Lease" ${c.category === 'Lease' ? 'selected' : ''}>Lease</option>
                  <option value="Service" ${c.category === 'Service' ? 'selected' : ''}>Service</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
                <input type="text" name="employee_name" value="${c.employee_name || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                <input type="text" name="employee_id" value="${c.employee_id || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                <input type="text" name="department" value="${c.department || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">${c.description || ''}</textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">View Password (leave empty to remove)</label>
              <input type="password" name="view_password" placeholder="Enter new password or leave empty" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Contract Text (OCR)</label>
              <textarea name="ocr_text" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">${c.text || ''}</textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
              <button type="button" id="cancelEdit" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Changes</button>
            </div>
          </form>
        </div>
      `;
      
      document.body.appendChild(editForm);
      
      // Handle form submission
      document.getElementById('editContractForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const contractData = {
          title: formData.get('title'),
          party: formData.get('party'),
          category: formData.get('category'),
          employee_name: formData.get('employee_name'),
          employee_id: formData.get('employee_id'),
          department: formData.get('department'),
          description: formData.get('description'),
          view_password: formData.get('view_password'),
          ocr_text: formData.get('ocr_text')
        };
        
        try {
          const response = await fetch('../backend/weka_contract_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update&contract_id=${id}&${Object.entries(contractData).map(([k,v]) => `${k}=${encodeURIComponent(v)}`).join('&')}`
          });
          const result = await response.json();
          
          if (result.success) {
            alert('Contract updated successfully!');
            document.body.removeChild(editForm);
            loadWekaContracts(); // Reload contracts
            audit(`Updated contract "${contractData.title}"`);
          } else {
            alert('Error updating contract: ' + result.message);
          }
        } catch (error) {
          console.error('Update error:', error);
          alert('Error updating contract. Please try again.');
        }
      });
      
      // Handle close buttons
      document.getElementById('closeEdit').addEventListener('click', () => {
        document.body.removeChild(editForm);
      });
      document.getElementById('cancelEdit').addEventListener('click', () => {
        document.body.removeChild(editForm);
      });
    }

    async function deleteContract(id){
      const c = store.contracts.find(x=>x.id===id);
      if(!c) return;
      
      if (!confirm(`Are you sure you want to delete the contract "${c.title}"? This action cannot be undone.`)) {
        return;
      }
      
      try {
        const response = await fetch('../backend/weka_contract_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=delete&contract_id=${id}`
        });
        const result = await response.json();
        
        if (result.success) {
          alert('Contract deleted successfully!');
          loadWekaContracts(); // Reload contracts
          audit(`Deleted contract "${c.title}"`);
        } else {
          alert('Error deleting contract: ' + result.message);
        }
      } catch (error) {
        console.error('Delete error:', error);
        alert('Error deleting contract. Please try again.');
      }
    }

    function audit(msg){
      store.audit.push({when:new Date().toLocaleString(), msg});
      renderAudit();
    }
    
    // Alerts modal
    document.getElementById('btnAlerts').addEventListener('click', ()=>{
      document.getElementById('modalAlerts').classList.remove('hidden');
      document.getElementById('modalAlerts').classList.add('flex');
      renderAlerts();
    });
    document.getElementById('closeAlerts').addEventListener('click', ()=>{
      document.getElementById('modalAlerts').classList.add('hidden');
      document.getElementById('modalAlerts').classList.remove('flex');
    });

    document.getElementById('closeView').addEventListener('click', ()=>{
        document.getElementById('modalView').classList.add('hidden');
        document.getElementById('modalView').classList.remove('flex');
    });

    document.getElementById('closeAnalysis').addEventListener('click', ()=>{
      const m=document.getElementById('modalAnalysis');
      m.classList.add('hidden');
      m.classList.remove('flex');
    });

    function updateAlertsCount(){ document.getElementById('alertsCount').innerText = store.alerts.length; }
    function renderAlerts(){
      const div = document.getElementById('alertsList'); div.innerHTML='';
      if(store.alerts.length===0) div.innerHTML = '<div class="text-sm text-gray-600">No alerts</div>';
      store.alerts.slice().reverse().forEach(a=>{
        const el = document.createElement('div');
        el.className='p-3 border rounded';
        el.innerHTML = `<div class="font-medium">${a.title}</div><div class="text-xs text-gray-600">${a.issue} — ${a.when}</div>`;
        div.appendChild(el);
      });
    }

    // Load contracts from Weka database
    async function loadWekaContracts(){
      try {
        const response = await fetch('../backend/weka_contract_api.php?action=all');
        const result = await response.json();
        
        if (result.success && result.contracts) {
          // Clear existing contracts
          store.contracts = [];
          
          // Add Weka contracts to store
          result.contracts.forEach(c => {
            const contract = {
              id: c.id.toString(),
              title: c.title,
              party: c.party,
              category: c.category, // Include category from database
              expiry: c.created_at ? new Date(c.created_at).toISOString().slice(0,10) : '',
              text: c.ocr_text || '',
              access: ['Manager', 'Employee'], // Default access
              score: c.risk_score,
              level: c.risk_level,
              weka_confidence: c.weka_confidence,
              risk_factors: c.risk_factors || [],
              recommendations: c.recommendations || [],
              probability_percent: c.probability_percent,
              employee_name: c.employee_name, // Include employee name
              view_password: c.view_password, // Include password for protection
            };
            
            store.contracts.push(contract);
            
            // Add high-risk alerts
            if (c.high_risk_alert) {
              store.alerts.push({
                id: c.id.toString(),
                title: c.title,
                issue: `High risk contract: ${c.risk_factors ? c.risk_factors.join(', ') : 'Multiple risk factors detected'}`,
                when: new Date(c.created_at).toLocaleString(),
                weka_confidence: c.weka_confidence
              });
            }
          });
          
          updateAlertsCount();
          audit(`Loaded ${result.contracts.length} contracts from Weka database`);
          renderContracts('');
        }
      } catch (error) {
        console.error('Error loading Weka contracts:', error);
        // Fallback to demo data
        seedDemoData();
      }
    }
    
    // Seed demo data (fallback)
    function seedDemoData(){
      const samples = [
        {title:'Supplier Agreement A', party:'Acme Supplies', expiry: todayPlusDays(90), text:'Standard supply agreement. Includes indemnify clause and termination for convenience.', access:['Manager','Employee']},
        {title:'Employment Contract - John D', party:'Acme Inc', expiry: todayPlusDays(365), text:'Employment terms, confidentiality, data protection and limited warranty.', access:['Manager']},
        {title:'Lease Agreement - Office', party:'Green Properties', expiry: todayPlusDays(30), text:'Lease with auto-renewal, notice periods, and liquidated damages clause.', access:['Manager','Employee']},
        {title:'NDA with Vendor', party:'Vendor X', expiry: '', text:'Confidentiality and data protection obligations.', access:['Employee']}
      ];
      samples.forEach(s=>{
        const id=uid();
        const a=analyzeRisk(s.text);
        store.contracts.push({id,title:s.title,party:s.party,expiry:s.expiry,text:s.text,access:s.access,score:a.score,level:a.level});
        if(a.level==='High') store.alerts.push({id,title:s.title,issue:'High risk clauses detected on seed',when:new Date().toLocaleString()});
      });
      updateAlertsCount();
      audit('Seeded demo contracts');
      renderContracts('');
    }

    // Load Weka contracts on page load
    loadWekaContracts();

    // Wire up search bar
    const contractSearch = document.getElementById('contractSearch');
    if (contractSearch) {
      contractSearch.addEventListener('input', (e) => {
        renderContracts(e.target.value || '');
      });
    }
  </script>
</body>
</html>

    