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
  <title>Contracts - List</title>
  <link rel="icon" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .risk-low{background:#ecfdf5;color:#065f46}
    .risk-medium{background:#fffbeb;color:#92400e}
    .risk-high{background:#fee2e2;color:#991b1b}
    .blur-protected{filter:blur(6px); user-select:none;}
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
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Contracts</h1>
        <p class="text-sm text-gray-500 mt-1">Read-only list. Uploads are handled by Admin privacy page.</p>
      </div>
      <div class="text-sm text-gray-600">
        <span class="font-medium">Signed in:</span> <?= htmlspecialchars($userName) ?>
      </div>
    </header>

    <main class="flex-1 overflow-y-auto px-6 py-6 bg-gray-100">
      <div class="max-w-7xl mx-auto bg-white rounded shadow">
        <div class="p-4 flex items-center justify-between">
          <div class="text-gray-700 text-sm">Showing <span id="count">0</span> contracts</div>
          <div class="flex gap-2">
            <input id="search" type="text" placeholder="Search title/party..." class="border rounded px-3 py-1 text-sm">
            <button id="refresh" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">Refresh</button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="px-4 py-2 text-left">Title</th>
                <th class="px-4 py-2 text-left">Party</th>
                <th class="px-4 py-2 text-left">Risk</th>
                <th class="px-4 py-2 text-left">Confidence</th>
                <th class="px-4 py-2 text-left">Uploaded By</th>
                <th class="px-4 py-2 text-left">Date</th>
                <th class="px-4 py-2 text-left">Actions</th>
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
const countEl = document.getElementById('count');
const searchEl = document.getElementById('search');
const refreshBtn = document.getElementById('refresh');

function riskBadge(level, score){
  const cls = level==='High'?'risk-high':(level==='Medium'?'risk-medium':'risk-low');
  return `<span class="px-2 py-0.5 rounded ${cls}">${level} (${score})</span>`;
}

async function load(){
  try{
    const r = await fetch('../backend/weka_contract_api.php?action=all');
    const j = await r.json();
    if(!j.success){ tbody.innerHTML='<tr><td class="px-4 py-3" colspan="7">Failed to load.</td></tr>'; return; }
    const q = (searchEl.value||'').toLowerCase();
    const data = (j.contracts||[]).filter(c => (c.title+c.party).toLowerCase().includes(q));
    countEl.textContent = data.length;
    tbody.innerHTML = '';
    data.forEach(c=>{
      const tr = document.createElement('tr');
      tr.className='border-t';
      const partyCell = `<span class="blur-protected">${c.party||''}</span>`;
      tr.innerHTML = `
        <td class="px-4 py-3">${c.title||''}</td>
        <td class="px-4 py-3">${partyCell}</td>
        <td class="px-4 py-3">${riskBadge(c.risk_level||'Low', c.risk_score||0)}</td>
        <td class="px-4 py-3">${(c.weka_confidence??'N/A')}%</td>
        <td class="px-4 py-3">${c.uploaded_by_name || '—'}</td>
        <td class="px-4 py-3">${(c.created_at||'').replace('T',' ').slice(0,19)}</td>
        <td class="px-4 py-3">
          <a href="/admin/Main/legalmanagement.php" class="text-blue-600 hover:underline">View in Analysis</a>
        </td>`;
      tbody.appendChild(tr);
    });
  }catch(e){
    console.error(e);
    tbody.innerHTML='<tr><td class="px-4 py-3" colspan="7">Error loading data.</td></tr>';
  }
}

refreshBtn.addEventListener('click', load);
searchEl.addEventListener('input', load);
load();
</script>
</body>
</html>
