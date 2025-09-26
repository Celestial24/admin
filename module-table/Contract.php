<?php
session_start();

if (!isset($_SESSION['user_id']) && !(isset($_SESSION['user']) && isset($_SESSION['user']['id']))) {
    header('Location: ../auth/login.php');
    exit();
}

$userName = $_SESSION['user']['name'] ?? ($_SESSION['name'] ?? 'Unknown User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contracts - Incoming Legal Submissions</title>
  <link rel="icon" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .chip{border-radius:9999px;padding:.15rem .5rem;font-size:.7rem}
    .chip-draft{background:#eef2ff;color:#3730a3}
    .chip-approved{background:#ecfdf5;color:#065f46}
    .chip-review{background:#fffbeb;color:#92400e}
    .blur-protected{filter:blur(6px); user-select:none}
    .muted{color:#6b7280}
  </style>
</head>
<body class="bg-gray-100 h-screen flex font-sans">
  <!-- Sidebar -->
  <aside class="w-64 bg-white shadow h-screen overflow-hidden">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <div class="flex-1 flex flex-col h-screen overflow-hidden">
    <!-- Header -->
    <header class="px-6 py-4 bg-white border-b shadow flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Incoming Legal Submissions</h1>
      <div class="text-sm text-gray-600"><span class="font-medium">Signed in:</span> <?= htmlspecialchars($userName) ?></div>
    </header>

    <main class="flex-1 overflow-y-auto px-6 py-6 bg-gray-100">
      <div class="max-w-7xl mx-auto space-y-4">
        <!-- Filters -->
        <div class="bg-white rounded shadow p-4 flex flex-wrap gap-3 items-center">
          <div class="flex items-center gap-2">
            <span class="muted text-sm">Category:</span>
            <select id="filterType" class="border rounded px-3 py-1 text-sm">
              <option value="">All Categories</option>
              <option value="Contract">Contract</option>
              <option value="Policy">Policy</option>
              <option value="Case">Case</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <span class="muted text-sm">Status:</span>
            <select id="filterStatus" class="border rounded px-3 py-1 text-sm">
              <option value="">All Status</option>
              <option value="Draft">Draft</option>
              <option value="For Review">For Review</option>
              <option value="Approved">Approved</option>
            </select>
          </div>
          <div class="flex-1"></div>
          <div class="flex items-center gap-2">
            <input id="search" type="text" placeholder="Search documents..." class="border rounded px-3 py-1 text-sm w-64">
            <button id="btnClear" class="text-sm px-3 py-1 border rounded">CLEAR</button>
            <button id="refresh" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">Refresh</button>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded shadow overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="px-4 py-3 text-left">Document Information</th>
                <th class="px-4 py-3 text-left">Type</th>
                <th class="px-4 py-3 text-left">Uploaded By</th>
                <th class="px-4 py-3 text-left">Department</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Actions</th>
              </tr>
            </thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- View Contract Modal -->
  <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-11/12 md:w-3/4 lg:w-1/2 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between p-6 border-b">
        <h3 class="text-lg font-semibold">Contract Details</h3>
        <button onclick="closeModal('viewModal')" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>
      <div id="viewContent" class="p-6 space-y-4">
        <!-- Content will be loaded here -->
      </div>
    </div>
  </div>

  <!-- Password Modal -->
  <div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-96 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Enter Password</h3>
        <button onclick="closeModal('passwordModal')" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <div class="relative">
            <input type="password" id="passwordInput" class="w-full pr-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter password">
            <button type="button" id="togglePwd" class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700" tabindex="-1" aria-label="Toggle password visibility">👁️</button>
          </div>
        </div>
        <div class="flex justify-end gap-3">
          <button onclick="closeModal('passwordModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
          <button onclick="verifyPasswordAndProceed()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Verify</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Analysis Modal -->
  <div id="analysisModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-11/12 md:w-3/4 lg:w-1/2 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between p-6 border-b">
        <h3 class="text-lg font-semibold">Weka AI Analysis</h3>
        <button onclick="closeModal('analysisModal')" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>
      <div id="analysisContent" class="p-6 space-y-4">
        <!-- Analysis content will be loaded here -->
      </div>
      <div class="p-6 border-t bg-gray-50">
        <div class="flex justify-end gap-3">
          <button onclick="editContractFromAnalysis()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Edit Contract
          </button>
          <button onclick="deleteContractFromAnalysis()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Delete Contract
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Contract Modal -->
  <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-11/12 md:w-3/4 lg:w-1/2 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between p-6 border-b">
        <h3 class="text-lg font-semibold">Edit Contract</h3>
        <button onclick="closeModal('editModal')" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>
      <form id="editForm" class="p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
            <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Party</label>
            <input type="text" name="party" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="Other">Other</option>
              <option value="Employment">Employment</option>
              <option value="Supplier">Supplier</option>
              <option value="Lease">Lease</option>
              <option value="Service">Service</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
            <input type="text" name="employee_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
            <input type="text" name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <input type="text" name="department" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">View Password (leave empty to remove)</label>
          <input type="password" name="view_password" placeholder="Enter new password or leave empty" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contract Text (OCR)</label>
          <textarea name="ocr_text" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <div class="flex justify-end gap-3 pt-4 border-t">
          <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

<script>
const tbody = document.getElementById('tbody');
const searchEl = document.getElementById('search');
const statusEl = document.getElementById('filterStatus');
const typeEl = document.getElementById('filterType');
const clearBtn = document.getElementById('btnClear');
const refreshBtn = document.getElementById('refresh');

function statusChip(level){
  // Simple status mapping based on risk_level (customize to your workflow)
  if(level==='High') return `<span class="chip chip-review">For Review</span>`;
  if(level==='Low') return `<span class="chip chip-draft">Draft</span>`;
  return `<span class="chip chip-approved">Approved</span>`;
}

function docType(c){
  // If you later add a category field in API, map here. Default to 'Contract'
  return c.doc_category || 'Contract';
}

async function load(){
  try{
    const r = await fetch('../backend/weka_contract_api.php?action=all');
    const j = await r.json();
    const list = (j.contracts||[]);
    const q = (searchEl.value||'').toLowerCase();
    const s = statusEl.value||'';
    const t = typeEl.value||'';
    const data = list.filter(c=>{
      const matchesQ = (c.title+c.party).toLowerCase().includes(q);
      const matchesS = !s || (s==='Draft' && c.risk_level==='Low') || (s==='For Review' && c.risk_level==='High') || (s==='Approved' && c.risk_level==='Medium');
      const matchesT = !t || (docType(c)===t);
      return matchesQ && matchesS && matchesT;
    });
    tbody.innerHTML='';
    data.forEach(c=>{
      const tr = document.createElement('tr');
      tr.className='border-t align-top';
      const date = (c.created_at||'').replace('T',' ').slice(0,19) || '';
      tr.innerHTML = `
        <td class="px-4 py-3">
          <div class="font-medium text-gray-900">${c.title||'Untitled'}</div>
          <div class="text-xs muted mt-1 blur-protected">${c.party||''}</div>
          <div class="text-xs text-blue-600 mt-1">${c.id?('LD-'+String(c.id).padStart(4,'0')):''}</div>
        </td>
        <td class="px-4 py-3">${docType(c)}</td>
        <td class="px-4 py-3">—</td>
        <td class="px-4 py-3">${c.department||'N/A'}</td>
        <td class="px-4 py-3">${statusChip(c.risk_level||'Low')}</td>
        <td class="px-4 py-3">${date}</td>
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <button onclick="viewContract(${c.id})" class="text-blue-600 hover:text-blue-800" title="View Details">Open</button>
            <span class="text-gray-300">|</span>
            <button onclick="viewAnalysis(${c.id})" class="text-purple-600 hover:text-purple-800" title="View Analysis">Analysis</button>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
  }catch(e){
    tbody.innerHTML='<tr><td class="px-4 py-3" colspan="7">Error loading data.</td></tr>';
  }
}

// Clear filters and reload
function clearFilters(){
  searchEl.value='';
  statusEl.value='';
  typeEl.value='';
  load();
}

// Refresh table data
function refreshList(){
  load();
}

// Modal functions
function closeModal(modalId) {
  document.getElementById(modalId).classList.add('hidden');
  document.getElementById(modalId).classList.remove('flex');
}

function openModal(modalId) {
  document.getElementById(modalId).classList.remove('hidden');
  document.getElementById(modalId).classList.add('flex');
}

// Global variables for password modal
let currentContractId = null;
let pendingAction = null;

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

// Show password modal
function showPasswordModal(contractId, action) {
  currentContractId = contractId;
  pendingAction = action;
  document.getElementById('passwordInput').value = '';
  openModal('passwordModal');
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
    closeModal('passwordModal');
    if (pendingAction === 'view') {
      await viewContractWithPassword(currentContractId, password);
    } else if (pendingAction === 'analysis') {
      await viewAnalysisWithPassword(currentContractId, password);
    } else if (pendingAction === 'edit') {
      await editContractWithPassword(currentContractId, password);
    } else if (pendingAction === 'delete') {
      await deleteContractWithPassword(currentContractId, password);
    }
  } else {
    alert('Invalid password. Access denied.');
  }
}

// Edit contract with password
async function editContractWithPassword(contractId, password) {
  try {
    const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}&password=${encodeURIComponent(password)}`);
    const result = await response.json();
    
    if (result.success) {
      populateEditForm(result.contract, contractId, password);
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
      closeModal('analysisModal');
      load(); // Reload the table
    } else {
      alert('Error deleting contract: ' + deleteResult.message);
    }
  } catch (error) {
    console.error('Error deleting contract:', error);
    alert('Error deleting contract. Please try again.');
  }
}

// View Analysis function
async function viewAnalysis(contractId) {
  try {
    const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}`);
    const result = await response.json();
    if (result && result.success) {
      displayAnalysisDetails(result.contract);
    } else {
      // treat any failure as password required
      showPasswordModal(contractId, 'analysis');
    }
  } catch (error) {
    console.error('Error viewing analysis:', error);
    alert('Error loading analysis details.');
  }
}

// View Analysis with password
async function viewAnalysisWithPassword(contractId, password) {
  try {
    const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}&password=${encodeURIComponent(password)}`);
    const result = await response.json();
    
    if (result.success) {
      displayAnalysisDetails(result.contract);
    } else {
      alert('Error loading analysis details.');
    }
  } catch (error) {
    console.error('Error viewing analysis:', error);
    alert('Error loading analysis details.');
  }
}

// View Contract with password
async function viewContractWithPassword(contractId, password) {
  try {
    const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}&password=${encodeURIComponent(password)}`);
    const result = await response.json();
    
    if (result.success) {
      displayContractDetails(result.contract);
    } else {
      alert('Error loading contract details.');
    }
  } catch (error) {
    console.error('Error viewing contract:', error);
    alert('Error loading contract details.');
  }
}

// Display analysis details in modal
function displayAnalysisDetails(contract) {
  // Store contract ID for edit/delete actions
  window.currentAnalysisContractId = contract.id;
  window.currentAnalysisContractPassword = null; // Will be set if password was used
  
  const content = document.getElementById('analysisContent');
  const riskClass = {
    'High': 'bg-red-100 text-red-800',
    'Medium': 'bg-yellow-100 text-yellow-800',
    'Low': 'bg-green-100 text-green-800'
  }[contract.risk_level] || 'bg-gray-100 text-gray-800';
  
  content.innerHTML = `
    <div class="mb-4">
      <h4 class="text-lg font-semibold text-gray-800">${contract.title}</h4>
      <p class="text-sm text-gray-600">Party: ${contract.party}</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div class="bg-gray-50 p-4 rounded-lg">
        <h5 class="font-semibold text-gray-700 mb-2">Risk Assessment</h5>
        <div class="space-y-2">
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Risk Level:</span>
            <span class="inline-block px-2 py-1 rounded-full text-xs ${riskClass}">${contract.risk_level}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Risk Score:</span>
            <span class="font-semibold">${contract.risk_score}/100</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Weka Confidence:</span>
            <span class="font-semibold text-blue-600">${contract.weka_confidence}%</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Dispute Probability:</span>
            <span class="font-semibold">${contract.probability_percent}%</span>
          </div>
        </div>
      </div>
      
      <div class="bg-gray-50 p-4 rounded-lg">
        <h5 class="font-semibold text-gray-700 mb-2">Contract Info</h5>
        <div class="space-y-2 text-sm">
          <div><strong>Category:</strong> ${contract.category || 'Other'}</div>
          <div><strong>Department:</strong> ${contract.department || 'N/A'}</div>
          <div><strong>Employee ID:</strong> ${contract.employee_id || 'N/A'}</div>
          <div><strong>Created:</strong> ${new Date(contract.created_at).toLocaleDateString()}</div>
        </div>
      </div>
    </div>
    
    ${contract.risk_factors && contract.risk_factors.length > 0 ? `
    <div class="mb-4">
      <h5 class="font-semibold text-red-700 mb-2">⚠️ Risk Factors Identified</h5>
      <ul class="list-disc list-inside text-sm text-red-600 space-y-1 bg-red-50 p-3 rounded">
        ${contract.risk_factors.map(factor => `<li>${factor}</li>`).join('')}
      </ul>
    </div>
    ` : ''}
    
    ${contract.recommendations && contract.recommendations.length > 0 ? `
    <div class="mb-4">
      <h5 class="font-semibold text-green-700 mb-2">💡 AI Recommendations</h5>
      <ul class="list-disc list-inside text-sm text-green-600 space-y-1 bg-green-50 p-3 rounded">
        ${contract.recommendations.map(rec => `<li>${rec}</li>`).join('')}
      </ul>
    </div>
    ` : ''}
    
    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
      <h5 class="font-semibold text-blue-700 mb-2">📋 Analysis Summary</h5>
      <p class="text-sm text-blue-600">
        This contract has been analyzed by Weka AI with ${contract.weka_confidence}% confidence. 
        ${contract.risk_level === 'High' ? 'Immediate legal review is recommended due to high-risk factors.' : 
          contract.risk_level === 'Medium' ? 'Standard review process is recommended.' : 
          'This contract appears to be low-risk and follows standard practices.'}
      </p>
    </div>
  `;
  
  openModal('analysisModal');
}

// View contract function
async function viewContract(contractId) {
  try {
    const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}`);
    const result = await response.json();
    if (result && result.success) {
      displayContractDetails(result.contract);
    } else {
      showPasswordModal(contractId, 'view');
    }
  } catch (error) {
    console.error('Error viewing contract:', error);
    alert('Error loading contract details.');
  }
}

// Display contract details in modal
function displayContractDetails(contract) {
  const content = document.getElementById('viewContent');
  const riskClass = {
    'High': 'bg-red-100 text-red-800',
    'Medium': 'bg-yellow-100 text-yellow-800',
    'Low': 'bg-green-100 text-green-800'
  }[contract.risk_level] || 'bg-gray-100 text-gray-800';
  
  content.innerHTML = `
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div><strong>Title:</strong> ${contract.title}</div>
      <div><strong>Party:</strong> ${contract.party}</div>
      <div><strong>Category:</strong> ${contract.category || 'Other'}</div>
      <div><strong>Employee Name:</strong> ${contract.employee_name || 'N/A'}</div>
      <div><strong>Employee ID:</strong> ${contract.employee_id || 'N/A'}</div>
      <div><strong>Department:</strong> ${contract.department || 'N/A'}</div>
      <div><strong>Risk Level:</strong> <span class="inline-block px-2 py-1 rounded-full text-xs ${riskClass}">${contract.risk_level}</span></div>
      <div><strong>Risk Score:</strong> ${contract.risk_score}/100</div>
      <div><strong>Weka Confidence:</strong> ${contract.weka_confidence}%</div>
      <div><strong>Probability of Dispute:</strong> ${contract.probability_percent}%</div>
      <div><strong>Created:</strong> ${new Date(contract.created_at).toLocaleString()}</div>
      <div><strong>Updated:</strong> ${new Date(contract.updated_at).toLocaleString()}</div>
    </div>
    <div>
      <strong>Description:</strong>
      <p class="mt-1 text-gray-700">${contract.description || 'No description provided.'}</p>
    </div>
    ${contract.risk_factors && contract.risk_factors.length > 0 ? `
    <div>
      <strong>Risk Factors:</strong>
      <ul class="mt-1 list-disc list-inside text-red-600 space-y-1">
        ${contract.risk_factors.map(factor => `<li>${factor}</li>`).join('')}
      </ul>
    </div>
    ` : ''}
    ${contract.recommendations && contract.recommendations.length > 0 ? `
    <div>
      <strong>Recommendations:</strong>
      <ul class="mt-1 list-disc list-inside text-green-600 space-y-1">
        ${contract.recommendations.map(rec => `<li>${rec}</li>`).join('')}
      </ul>
    </div>
    ` : ''}
    <div>
      <strong>Contract Text (OCR):</strong>
      <div class="mt-1 p-3 bg-gray-50 rounded text-xs whitespace-pre-wrap max-h-40 overflow-y-auto">${contract.ocr_text || 'No OCR text available.'}</div>
    </div>
  `;
  
  openModal('viewModal');
}

// Edit contract function
async function editContract(contractId) {
  try {
    // Try without password first
    const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}`);
    const result = await response.json();
    if (result && result.success) {
      populateEditForm(result.contract, contractId);
      return;
    }
    // ask for password
    const password = prompt('Enter password to edit this contract:');
    if (!password) return;
    const responseWithPassword = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}&password=${encodeURIComponent(password)}`);
    const resultWithPassword = await responseWithPassword.json();
    if (resultWithPassword && resultWithPassword.success) {
      populateEditForm(resultWithPassword.contract, contractId, password);
    } else {
      alert('Invalid password or error loading contract for editing.');
    }
  } catch (error) {
    console.error('Error editing contract:', error);
    alert('Error loading contract for editing.');
  }
}

// Populate edit form
function populateEditForm(contract, contractId, password = null) {
  const form = document.getElementById('editForm');
  form.contractId = contractId; // Store contract ID for submission
  form.currentPassword = password; // Store password for submission
  
  // Populate form fields
  form.elements.title.value = contract.title || '';
  form.elements.party.value = contract.party || '';
  form.elements.category.value = contract.category || 'Other';
  form.elements.employee_name.value = contract.employee_name || '';
  form.elements.employee_id.value = contract.employee_id || '';
  form.elements.department.value = contract.department || '';
  form.elements.description.value = contract.description || '';
  form.elements.view_password.value = ''; // Don't show current password
  form.elements.ocr_text.value = contract.ocr_text || '';
  
  openModal('editModal');
}

// Delete contract function
async function deleteContract(contractId) {
  if (!confirm('Are you sure you want to delete this contract? This action cannot be undone.')) {
    return;
  }
  
  // Check if password is required
  try {
    const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}`);
    const result = await response.json();
    let password = '';
    if (!(result && result.success)) {
      password = prompt('Enter password to delete this contract:');
      if (!password) return;
      const isValid = await verifyPassword(contractId, password);
      if (!isValid) { alert('Invalid password. Access denied.'); return; }
    }
    
    const deleteResponse = await fetch('../backend/weka_contract_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete&contract_id=${contractId}&password=${encodeURIComponent(password)}`
    });
    const deleteResult = await deleteResponse.json();
    
    if (deleteResult.success) {
      alert('Contract deleted successfully!');
      load(); // Reload the table
    } else {
      alert('Error deleting contract: ' + deleteResult.message);
    }
  } catch (error) {
    console.error('Error deleting contract:', error);
    alert('Error deleting contract. Please try again.');
  }
}

// Handle edit form submission
document.getElementById('editForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const form = e.target;
  const contractId = form.contractId;
  const currentPassword = form.currentPassword || '';
  
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
  
  try {
    const response = await fetch('../backend/weka_contract_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=update&contract_id=${contractId}&password=${encodeURIComponent(currentPassword)}&${Object.entries(contractData).map(([k,v]) => `${k}=${encodeURIComponent(v)}`).join('&')}`
    });
    const result = await response.json();
    
    if (result.success) {
      alert('Contract updated successfully!');
      closeModal('editModal');
      load(); // Reload the table
    } else {
      alert('Error updating contract: ' + result.message);
    }
  } catch (error) {
    console.error('Update error:', error);
    alert('Error updating contract. Please try again.');
  }
});

// Edit contract from analysis modal
async function editContractFromAnalysis() {
  const contractId = window.currentAnalysisContractId;
  if (!contractId) return;
  
  try {
    const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}`);
    const result = await response.json();
    if (result && result.success) {
      populateEditForm(result.contract, contractId);
    } else {
      showPasswordModal(contractId, 'edit');
    }
  } catch (error) {
    console.error('Error editing contract:', error);
    alert('Error loading contract for editing.');
  }
}

// Delete contract from analysis modal
async function deleteContractFromAnalysis() {
  const contractId = window.currentAnalysisContractId;
  if (!contractId) return;
  
  if (!confirm('Are you sure you want to delete this contract? This action cannot be undone.')) {
    return;
  }
  
  try {
    const response = await fetch(`../backend/weka_contract_api.php?action=get_contract&contract_id=${contractId}`);
    const result = await response.json();
    if (!(result && result.success)) { showPasswordModal(contractId, 'delete'); return; }
    
    const deleteResponse = await fetch('../backend/weka_contract_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete&contract_id=${contractId}&password=`
    });
    const deleteResult = await deleteResponse.json();
    
    if (deleteResult.success) {
      alert('Contract deleted successfully!');
      closeModal('analysisModal');
      load(); // Reload the table
    } else {
      alert('Error deleting contract: ' + deleteResult.message);
    }
  } catch (error) {
    console.error('Error deleting contract:', error);
    alert('Error deleting contract. Please try again.');
  }
}

clearBtn.addEventListener('click', clearFilters);
refreshBtn.addEventListener('click', refreshList);
searchEl.addEventListener('input', load);
statusEl.addEventListener('change', load);
typeEl.addEventListener('change', load);
load();
</script>

<script>
// Eye toggle for password field
document.addEventListener('click', function(e){
  if(e.target && e.target.id==='togglePwd'){
    const input = document.getElementById('passwordInput');
    input.type = input.type === 'password' ? 'text' : 'password';
  }
});
</script>
</body>
</html>
