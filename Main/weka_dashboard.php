<?php
// Start session only once at the very top.
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
}

// Redirect if the session is not properly structured after normalization.
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../backend/sql/db.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Weka AI Contract Analysis Dashboard</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .risk-low { background: linear-gradient(90deg, #ecfccb, #bbf7d0); }
    .risk-medium { background: linear-gradient(90deg, #fde68a, #fca5a5); }
    .risk-high { background: linear-gradient(90deg, #fecaca, #f87171); }
    .pulse-animation { animation: pulse 2s infinite; }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
  </style>
</head>
<body class="flex h-screen bg-gray-100 text-gray-800">

  <aside class="fixed left-0 top-0 text-white">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <main id="mainContent" class="ml-64 flex flex-col flex-1 overflow-hidden">
    <header class="flex items-center justify-between border-b px-6 py-4 bg-white">
      <div>
        <h1 class="text-2xl font-semibold">🧠 Weka AI Contract Analysis Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">Advanced Risk Assessment & Legal Management</p>
      </div>
      <div class="flex items-center gap-3">
        <?php include __DIR__ . '/../profile.php'; ?>
        <button id="btnRefreshData" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          Refresh Data
        </button>
        <button id="btnExportReport" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          Export Report
        </button>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto p-6">
      <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Contracts</p>
                <p class="text-2xl font-semibold text-gray-900" id="totalContracts">0</p>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="p-2 bg-red-100 rounded-lg"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg></div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">High Risk</p>
                <p class="text-2xl font-semibold text-red-600" id="highRiskContracts">0</p>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="p-2 bg-yellow-100 rounded-lg"><svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Medium Risk</p>
                <p class="text-2xl font-semibold text-yellow-600" id="mediumRiskContracts">0</p>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="p-2 bg-green-100 rounded-lg"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Low Risk</p>
                <p class="text-2xl font-semibold text-green-600" id="lowRiskContracts">0</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white rounded-lg shadow p-6 flex flex-col h-80">
            <h3 class="text-lg font-semibold mb-4">Risk Distribution</h3>
            <div class="relative flex-1 w-full h-full">
              <canvas id="riskChart"></canvas>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow p-6 flex flex-col h-80">
            <h3 class="text-lg font-semibold mb-4">Weka AI Confidence Levels</h3>
            <div class="relative flex-1 w-full h-full">
              <canvas id="confidenceChart"></canvas>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow">
          <div class="p-6 border-b">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-red-600 flex items-center gap-2">
                <span class="pulse-animation">🚨</span> High Risk Contract Alerts
              </h3>
              <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium" id="alertCount">0 Alerts</span>
            </div>
          </div>
          <div class="p-6">
            <div id="highRiskAlerts" class="space-y-4"></div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow">
          <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">Recent Contract Analysis</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full table-auto text-left">
              <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                <tr>
                  <th class="px-4 py-3">Contract</th>
                  <th class="px-4 py-3">Party</th>
                  <th class="px-4 py-3">Risk Level</th>
                  <th class="px-4 py-3">Weka Confidence</th>
                  <th class="px-4 py-3">Analysis Date</th>
                  <th class="px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody id="recentContractsTable" class="text-sm"></tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
        let contractsData = [];
        let riskChart, confidenceChart;

        const createChart = (chartInstance, elementId, config) => {
            const ctx = document.getElementById(elementId).getContext('2d');
            if (chartInstance) chartInstance.destroy();
            return new Chart(ctx, config);
        };

        async function loadWekaData() {
            try {
                const response = await fetch('../backend/weka_contract_api.php?action=all');
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                const result = await response.json();
                if (result.success && Array.isArray(result.contracts)) {
                    contractsData = result.contracts;
                    updateDashboard();
                } else {
                    console.error('API response error or contracts not an array.');
                }
            } catch (error) {
                console.error('Error loading Weka data:', error);
            }
        }

        function updateMetrics() {
            const total = contractsData.length;
            const highRisk = contractsData.filter(c => c.risk_level === 'High').length;
            const mediumRisk = contractsData.filter(c => c.risk_level === 'Medium').length;
            const lowRisk = total - highRisk - mediumRisk;
            document.getElementById('totalContracts').textContent = total;
            document.getElementById('highRiskContracts').textContent = highRisk;
            document.getElementById('mediumRiskContracts').textContent = mediumRisk;
            document.getElementById('lowRiskContracts').textContent = lowRisk;
        }

        function updateCharts() {
            const riskCounts = {
                High: contractsData.filter(c => c.risk_level === 'High').length,
                Medium: contractsData.filter(c => c.risk_level === 'Medium').length,
                Low: contractsData.filter(c => c.risk_level === 'Low').length
            };
            riskChart = createChart(riskChart, 'riskChart', {
                type: 'doughnut',
                data: {
                    labels: ['High Risk', 'Medium Risk', 'Low Risk'],
                    datasets: [{
                        data: [riskCounts.High, riskCounts.Medium, riskCounts.Low],
                        backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                        borderColor: '#ffffff',
                        borderWidth: 2,
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });

            const confidenceRanges = {
                '90-100%': contractsData.filter(c => c.weka_confidence >= 90).length,
                '80-89%': contractsData.filter(c => c.weka_confidence >= 80 && c.weka_confidence < 90).length,
                '70-79%': contractsData.filter(c => c.weka_confidence >= 70 && c.weka_confidence < 80).length,
                '<70%': contractsData.filter(c => c.weka_confidence < 70).length
            };
            confidenceChart = createChart(confidenceChart, 'confidenceChart', {
                type: 'bar',
                data: {
                    labels: Object.keys(confidenceRanges),
                    datasets: [{
                        label: 'Contracts',
                        data: Object.values(confidenceRanges),
                        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                        borderRadius: 4,
                        barPercentage: 0.6,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grace: '10%' } }
                }
            });
        }

        function updateHighRiskAlerts() {
            const highRiskContracts = contractsData.filter(c => c.high_risk_alert);
            const alertsContainer = document.getElementById('highRiskAlerts');
            const alertCountEl = document.getElementById('alertCount');
            alertCountEl.textContent = `${highRiskContracts.length} Alert${highRiskContracts.length !== 1 ? 's' : ''}`;
            if (highRiskContracts.length === 0) {
                alertsContainer.innerHTML = '<div class="text-center text-gray-500 py-8">✅ No high-risk contracts detected</div>';
                return;
            }
            alertsContainer.innerHTML = highRiskContracts.map(c => {
                const riskFactors = c.risk_factors ? JSON.parse(c.risk_factors).map(f => `<li>${f}</li>`).join('') : '';
                return `
                <div class="border border-red-200 rounded-lg p-4 bg-red-50/50">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-red-800">${c.title}</h4>
                            <p class="text-sm text-red-600">Party: ${c.party} | Risk: ${c.risk_score}/100 | Confidence: ${c.weka_confidence}%</p>
                            ${riskFactors ? `<div class="mt-2"><p class="text-xs font-medium text-red-700">Risk Factors:</p><ul class="text-xs text-red-600 list-disc list-inside">${riskFactors}</ul></div>` : ''}
                        </div>
                        <div class="ml-4"><span class="inline-block px-3 py-1 bg-red-200 text-red-800 rounded-full text-xs font-medium">${c.risk_level} Risk</span></div>
                    </div>
                </div>`;
            }).join('');
        }

        function updateRecentContracts() {
            const tableBody = document.getElementById('recentContractsTable');
            const recentContracts = contractsData.slice(0, 10);
            if (recentContracts.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No contracts analyzed yet.</td></tr>';
                return;
            }
            tableBody.innerHTML = recentContracts.map(c => {
                const riskClass = { 'High': 'bg-red-100 text-red-800', 'Medium': 'bg-yellow-100 text-yellow-800', 'Low': 'bg-green-100 text-green-800' }[c.risk_level] || 'bg-gray-100';
                return `
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">${c.title}</td>
                    <td class="px-4 py-3">${c.party}</td>
                    <td class="px-4 py-3"><span class="inline-block px-3 py-1 rounded text-xs font-medium ${riskClass}">${c.risk_level}</span></td>
                    <td class="px-4 py-3"><span class="text-blue-600 font-medium">${c.weka_confidence}%</span></td>
                    <td class="px-4 py-3 text-gray-600">${new Date(c.created_at).toLocaleDateString()}</td>
                    <td class="px-4 py-3"><button onclick="viewContractDetails(${c.id})" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">View Details</button></td>
                </tr>`;
            }).join('');
        }

        function updateDashboard() {
            updateMetrics();
            updateCharts();
            updateHighRiskAlerts();
            updateRecentContracts();
        }

        window.viewContractDetails = (contractId) => {
            const contract = contractsData.find(c => c.id == contractId);
            if (!contract) return;
            let details = `--- Contract Analysis Details ---\n\n` +
                          `Title: ${contract.title}\nParty: ${contract.party}\n` +
                          `Risk Level: ${contract.risk_level} (Score: ${contract.risk_score}/100)\n` +
                          `Weka Confidence: ${contract.weka_confidence}%\n` +
                          `Probability of Dispute: ${contract.probability_percent}%\n\n`;
            if (contract.risk_factors) details += `Identified Risk Factors:\n${JSON.parse(contract.risk_factors).map(f => `• ${f}`).join('\n')}\n\n`;
            if (contract.recommendations) details += `AI Recommendations:\n${JSON.parse(contract.recommendations).map(r => `• ${r}`).join('\n')}`;
            alert(details);
        };

        function exportReport() {
            let csvContent = 'Contract Title,Party,Risk Level,Risk Score,Weka Confidence,Probability of Dispute (%),Analysis Date\n';
            contractsData.forEach(c => {
                const row = [c.title, c.party, c.risk_level, c.risk_score, c.weka_confidence, c.probability_percent, c.created_at];
                const csvRow = row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(',');
                csvContent += csvRow + '\n';
            });
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `weka-contract-analysis-${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        document.getElementById('btnRefreshData').addEventListener('click', loadWekaData);
        document.getElementById('btnExportReport').addEventListener('click', exportReport);
        loadWekaData();
        setInterval(loadWekaData, 30000);
    });
  </script>

</body>
</html>