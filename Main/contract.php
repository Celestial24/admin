<!-- Main/contract.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contract - Admin</title>
  <link rel="icon" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow: hidden;
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>
<body class="bg-gray-100 h-screen flex font-sans">

  <!-- Sidebar -->
  <aside class="w-64 bg-white shadow h-screen overflow-hidden">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <!-- Main content -->
  <div class="flex-1 flex flex-col h-screen overflow-hidden">

    <!-- Header -->
    <header class="px-6 py-4 bg-white border-b shadow">
      <h1 class="text-2xl font-bold text-gray-800">Contract & Legal Management</h1>
      <p class="text-sm text-gray-500 mt-1">Powered by Weka AI Engine</p>
    </header>

    <!-- Main area -->
    <main class="flex-1 overflow-y-auto px-6 py-6 bg-gray-100">
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Weka AI Analysis -->
        <div class="xl:col-span-2 space-y-6">
          <section class="bg-gray-900 rounded-lg shadow-lg p-6 text-white">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">🧠 Weka AI Analysis</h2>

            <div class="mb-4">
              <p class="text-gray-400 flex items-center gap-2">
                Probability of dispute within 12 months:
                <span id="probabilityPercent" class="font-semibold text-red-500">0%</span>
              </p>
              <div 
                class="w-full bg-gray-800 rounded-full h-4 mt-2 overflow-hidden" 
                role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                >
                <div id="progressBar" class="h-4 w-0 bg-red-500 transition-all duration-500 ease-in-out"></div>
              </div>
            </div>

            <p class="text-gray-400 text-sm">Key risk factors:</p>
            <ul class="list-disc list-inside text-sm text-red-400 space-y-1">
              <li>High ambiguity in contract clauses</li>
              <li>Recent legal disputes with partner</li>
              <li>Unfavorable jurisdiction terms</li>
            </ul>
          </section>
        </div>

        <!-- Upload Form -->
        <section class="bg-white p-6 rounded shadow">
          <h2 class="text-xl font-semibold text-gray-800 mb-4">📤 Upload Contract</h2>

          <form
            action="../user/modules/Contract/contract_api.php"
            method="POST"
            id="contractForm"
            class="space-y-4"
            enctype="multipart/form-data"
            novalidate
          >
            <div>
              <label for="employeeName" class="block text-sm font-medium text-gray-700">
                Employee Name <span class="text-red-500">*</span>
              </label>
              <input type="text" name="employeeName" id="employeeName" required class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Enter employee name" />
            </div>

            <div>
              <label for="employeeId" class="block text-sm font-medium text-gray-700">
                Employee ID <span class="text-red-500">*</span>
              </label>
              <input type="text" name="employeeId" id="employeeId" required class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Enter employee ID" />
            </div>

            <div>
              <label for="title" class="block text-sm font-medium text-gray-700">
                Contract Title <span class="text-red-500">*</span>
              </label>
              <input type="text" name="title" id="title" required class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Enter contract title" />
            </div>

            <div>
              <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea name="description" id="description" rows="3" class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Optional description"></textarea>
            </div>

            <div>
              <label for="document" class="block text-sm font-medium text-gray-700">
                Upload Document <span class="text-red-500">*</span>
              </label>
              <input type="file" name="document" id="document" required accept=".pdf, .doc, .docx, .png, .jpg, .jpeg" class="mt-1 text-sm text-gray-600" />
              <p class="text-xs text-gray-400 mt-1">Allowed: PDF, Word, Images</p>
            </div>

            <div class="flex space-x-4 pt-4">
              <button type="submit" id="submitBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center gap-2 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <span>Run OCR & Analyze</span>
              </button>

              <a href="#" id="viewAnalysisBtn" class="border border-blue-500 text-blue-600 px-4 py-2 rounded hover:bg-blue-50 flex items-center gap-2 transition opacity-50 pointer-events-none">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>View Analysis</span>
              </a>
            </div>

            <p id="responseMessage" class="text-center font-semibold mt-4"></p>

            <div id="ocrResultContainer" class="hidden pt-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">📄 Extracted OCR Text</label>
              <textarea id="ocrText" rows="6" readonly class="w-full p-2 border rounded bg-gray-50 text-sm text-gray-800"></textarea>
            </div>
          </form>
        </section>

      </div>
    </main>
  </div>

  <<script>
  const form = document.getElementById('contractForm');
  const submitBtn = document.getElementById('submitBtn');
  const responseMessage = document.getElementById('responseMessage');
  const fileInput = document.getElementById('document');
  const ocrTextArea = document.getElementById('ocrText');
  const ocrResultContainer = document.getElementById('ocrResultContainer');
  const viewAnalysisBtn = document.getElementById('viewAnalysisBtn');
  const probabilityPercent = document.getElementById('probabilityPercent');
  const progressBar = document.getElementById('progressBar');

  function updateProgressBar(percent = 0) {
    // Clamp percent to 0-100
    percent = Math.max(0, Math.min(100, parseInt(percent)));

    probabilityPercent.textContent = `${percent}%`;
    progressBar.style.width = `${percent}%`;
    progressBar.setAttribute('aria-valuenow', percent);

    // Reset colors
    progressBar.classList.remove('bg-green-500', 'bg-yellow-500', 'bg-red-500');
    probabilityPercent.classList.remove('text-green-500', 'text-yellow-500', 'text-red-500');

    // Apply color logic
    if (percent <= 30) {
      progressBar.classList.add('bg-green-500');
      probabilityPercent.classList.add('text-green-500');
    } else if (percent <= 70) {
      progressBar.classList.add('bg-yellow-500');
      probabilityPercent.classList.add('text-yellow-500');
    } else {
      progressBar.classList.add('bg-red-500');
      probabilityPercent.classList.add('text-red-500');
    }
  }

  fileInput.addEventListener('change', () => {
    updateProgressBar(0);
    ocrTextArea.value = '';
    ocrResultContainer.classList.add('hidden');
    viewAnalysisBtn.classList.add('opacity-50', 'pointer-events-none');
    responseMessage.textContent = '';
    responseMessage.className = 'text-center font-semibold mt-4';
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    responseMessage.textContent = '';
    responseMessage.className = 'text-center font-semibold mt-4';

    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-50');
    submitBtn.querySelector('span').textContent = 'Uploading...';

    const formData = new FormData(form);

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        responseMessage.classList.add('text-green-600');
        responseMessage.textContent = result.message;

        // Show OCR Text
        if (result.ocrText) {
          ocrTextArea.value = result.ocrText;
          ocrResultContainer.classList.remove('hidden');
        } else {
          ocrResultContainer.classList.add('hidden');
        }

        // Update risk score bar
        const score = result.probabilityPercent ?? result.risk_score ?? 0;
        updateProgressBar(score);

        // Enable "View Analysis"
        if (result.contractId) {
          viewAnalysisBtn.href = `../user/modules/Contract/View_analysis.php?id=${result.contractId}`;
          viewAnalysisBtn.classList.remove('opacity-50', 'pointer-events-none');
        }
      } else {
        responseMessage.classList.add('text-red-600');
        responseMessage.textContent = result.message;
        viewAnalysisBtn.classList.add('opacity-50', 'pointer-events-none');
        updateProgressBar(0);
      }
    } catch (error) {
      console.error(error);
      responseMessage.classList.add('text-red-600');
      responseMessage.textContent = 'An unexpected error occurred.';
      updateProgressBar(0);
    } finally {
      submitBtn.disabled = false;
      submitBtn.classList.remove('opacity-50');
      submitBtn.querySelector('span').textContent = 'Run OCR & Analyze';
    }
  });
</script>

</body>
</html>
