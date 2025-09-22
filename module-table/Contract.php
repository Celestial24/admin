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
        <td class="px-4 py-3">${c.uploaded_by_name||'Unknown'}</td>
        <td class="px-4 py-3">${c.department||'N/A'}</td>
        <td class="px-4 py-3">${statusChip(c.risk_level||'Low')}</td>
        <td class="px-4 py-3">${date}</td>
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <a href="/admin/Main/legalmanagement.php" title="Open" class="text-blue-600 hover:text-blue-800">Open</a>
            <span class="text-gray-300">|</span>
            <button class="text-green-600 hover:text-green-800" title="Approve" disabled>✓</button>
            <button class="text-red-600 hover:text-red-800" title="Decline" disabled>✕</button>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
  }catch(e){
    tbody.innerHTML='<tr><td class="px-4 py-3" colspan="7">Error loading data.</td></tr>';
  }
}

clearBtn.addEventListener('click', ()=>{ searchEl.value=''; statusEl.value=''; typeEl.value=''; load(); });
refreshBtn.addEventListener('click', load);
searchEl.addEventListener('input', load);
statusEl.addEventListener('change', load);
typeEl.addEventListener('change', load);
load();
</script>
</body>
</html>
