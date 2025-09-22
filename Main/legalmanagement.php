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

// Current user role
$userRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'Employee');

// Create DB connection for Weka integration
include '../backend/sql/db.php';
$wekaConn = $conn; // Use existing connection
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Contract Management - Demo (HTML + Tailwind)</title>
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
    // Expose current role to JS
    window.APP_ROLE = <?php echo json_encode($userRole ?: 'Employee'); ?>;
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
          <div class="text-sm text-gray-500">Showing <span id="countContracts">0</span> contracts</div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full table-auto text-left">
            <thead class="text-xs text-gray-500 uppercase">
              <tr>
                <th class="px-3 py-2">Title</th>
                <th class="px-3 py-2">Party</th>
                <th class="px-3 py-2">Expiry</th>
                <th class="px-3 py-2">Weka Risk</th>
                <th class="px-3 py-2">Confidence</th>
                <th class="px-3 py-2">Uploaded By</th>
                <th class="px-3 py-2">Department</th>
                <th class="px-3 py-2">Access</th>
                <th class="px-3 py-2">Actions</th>
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
                <div><strong>Party:</strong> <span id="viewParty"></span></div>
                <div><strong>Expiry Date:</strong> <span id="viewExpiry"></span></div>
                <div><strong>Risk Level:</strong> <span id="viewRiskLevel"></span></div>
                <div><strong>Risk Score:</strong> <span id="viewRiskScore"></span></div>
                <div><strong>Weka Confidence:</strong> <span id="viewConfidence"></span></div>
                <div><strong>Uploaded By:</strong> <span id="viewUploadedBy"></span></div>
                <div><strong>Department:</strong> <span id="viewDepartment"></span></div>
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
      const role = window.APP_ROLE || 'Employee';
      const isAdmin = role === 'Admin';
      const list = store.contracts.filter(c => (c.title+c.party+c.text).toLowerCase().includes(filter.toLowerCase()));
      document.getElementById('countContracts').innerText = list.length;
      list.forEach(c=>{
        const tr = document.createElement('tr');
        tr.className = 'border-t';
        const accessAllowed = (c.access.map(a=>a.trim()).includes(role)) || isAdmin;
        const maskedParty = isAdmin ? c.party : '••••••';
        const confidenceCell = c.weka_confidence ? `<div class="text-blue-600 font-medium">${c.weka_confidence}%</div>` : '—';
        tr.innerHTML = `
          <td class="px-3 py-3 align-top font-medium break-words whitespace-normal">${c.title}</td>
          <td class="px-3 py-3 align-top ${isAdmin?'':'blur-protected'} break-words whitespace-normal">${maskedParty}</td>
          <td class="px-3 py-3 align-top break-words whitespace-normal">${c.expiry || '—'}</td>
          <td class="px-3 py-3 align-top break-words whitespace-normal">
            <div class="inline-block px-3 py-1 rounded ${c.level==='High'?'risk-high':c.level==='Medium'?'risk-medium':'risk-low'}">
              ${c.level} (${c.score}) ${c.level==='High'?'<span class="ml-1 text-xs">⚠️</span>':''}
            </div>
          </td>
          <td class="px-3 py-3 align-top text-sm break-words whitespace-normal">${confidenceCell}</td>
          <td class="px-3 py-3 align-top text-sm break-words whitespace-normal">${c.uploaded_by_name || 'Unknown'}</td>
          <td class="px-3 py-3 align-top text-sm break-words whitespace-normal">${c.department || 'N/A'}</td>
          <td class="px-3 py-3 align-top text-sm text-gray-600 break-words whitespace-normal">${c.access.join(', ')}</td>
          <td class="px-3 py-3 align-top">
            <div class="flex gap-2">
              <button class="btnView px-2 py-1 border rounded text-xs" data-id="${c.id}" ${accessAllowed?'':'disabled'}>${accessAllowed?'View Details':'Restricted'}</button>
              ${accessAllowed?`<button class="btnAnalyze px-2 py-1 border rounded text-xs" data-id="${c.id}">Weka Analysis</button>`:''}
              ${c.level==='High'&&isAdmin?`<button class="btnHighRisk px-2 py-1 bg-red-100 text-red-700 border border-red-300 rounded text-xs" data-id="${c.id}">High Risk</button>`:''}
              ${isAdmin?`<button class="btnArchive px-2 py-1 border rounded text-xs" data-id="${c.id}">Archive</button>`:''}
            </div>
          </td>
        `;
        tbody.appendChild(tr);
      });

      // Wire up action buttons
      document.querySelectorAll('.btnView').forEach(b=> b.addEventListener('click', e=>{
        const id=e.target.dataset.id; viewContract(id);
      }));
      document.querySelectorAll('.btnAnalyze').forEach(b=> b.addEventListener('click', e=>{
        const id=e.target.dataset.id; analyzeContract(id);
      }));
      document.querySelectorAll('.btnHighRisk').forEach(b=> b.addEventListener('click', e=>{
        const id=e.target.dataset.id; showHighRiskDetails(id);
      }));
      document.querySelectorAll('.btnArchive').forEach(b=> b.addEventListener('click', e=>{
        const id=e.target.dataset.id; archiveContract(id);
      }));

      renderAudit();
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
        
        const role = window.APP_ROLE || 'Employee';
        const isAdmin = role === 'Admin';
        if(!(c.access.includes(role) || isAdmin)) {
            alert('You do not have access to view this contract');
            return;
        }

        // Populate Modal
        document.getElementById('viewModalTitle').innerText = c.title;
        document.getElementById('viewParty').innerText = isAdmin ? c.party : '••••••';
        document.getElementById('viewExpiry').innerText = c.expiry || '—';
        document.getElementById('viewRiskLevel').innerHTML = `<span class="inline-block px-2 py-1 rounded-full text-xs ${c.level==='High'?'bg-red-100 text-red-800':c.level==='Medium'?'bg-yellow-100 text-yellow-800':'bg-green-100 text-green-800'}">${c.level}</span>`;
        document.getElementById('viewRiskScore').innerText = c.score;
        document.getElementById('viewConfidence').innerText = `${c.weka_confidence || 'N/A'}%`;
        document.getElementById('viewText').value = isAdmin ? (c.text || 'No text available.') : '•••••• (restricted)';
        document.getElementById('viewUploadedBy').innerText = c.uploaded_by_name || 'Unknown';
        document.getElementById('viewDepartment').innerText = c.department || 'N/A';
        
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
        document.getElementById('modalView').classList.remove('hidden');
        document.getElementById('modalView').classList.add('flex');

        audit(`Viewed details for contract "${c.title}" by ${role}`);
    }

    function analyzeContract(id){
      const c = store.contracts.find(x=>x.id===id);
      if(!c) return;
      
      // Show Weka analysis details
      let analysisText = `Weka AI Analysis for "${c.title}":\n\n`;
      analysisText += `Risk Level: ${c.level}\n`;
      analysisText += `Risk Score: ${c.score}/100\n`;
      analysisText += `Weka Confidence: ${c.weka_confidence || 'N/A'}%\n`;
      analysisText += `Probability of Dispute: ${c.probability_percent || 'N/A'}%\n\n`;
      
      if(c.risk_factors && c.risk_factors.length > 0) {
        analysisText += `Risk Factors:\n${c.risk_factors.map(f => `• ${f}`).join('\n')}\n\n`;
      }
      
      if(c.recommendations && c.recommendations.length > 0) {
        analysisText += `Recommendations:\n${c.recommendations.map(r => `• ${r}`).join('\n')}`;
      }
      
      alert(analysisText);
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
              expiry: c.created_at ? new Date(c.created_at).toISOString().slice(0,10) : '',
              text: c.ocr_text || '',
              access: ['Manager', 'Employee'], // Default access
              score: c.risk_score,
              level: c.risk_level,
              weka_confidence: c.weka_confidence,
              risk_factors: c.risk_factors || [],
              recommendations: c.recommendations || [],
              probability_percent: c.probability_percent,
              uploaded_by: c.uploaded_by || 'N/A',
              uploaded_by_name: c.uploaded_by_name || 'N/A', // Added for display
              department: c.department || 'N/A'
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
  </script>
</body>
</html>

