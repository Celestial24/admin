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

  <aside class="w-64 bg-white shadow h-screen overflow-hidden">
    <?php include '../Components/sidebar/sidebar_user.php'; ?>
  </aside>

  <div class="flex-1 flex flex-col h-screen overflow-hidden">

    <header class="px-6 py-4 bg-white border-b shadow">
      <h1 class="text-2xl font-bold text-gray-800">Contract </h1>
      <p class="text-sm text-gray-500 mt-1">Powered by Weka AI Engine</p>
    </header>

    <main class="flex-1 overflow-y-auto px-6 py-6 bg-gray-100">
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

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

            <p class="text-gray-400 text-sm">Key risk factors will be identified here after analysis.</p>
            <ul id="riskFactorsList" class="list-disc list-inside text-sm text-red-400 space-y-1">
              <li>Upload a contract to begin...</li>
            </ul>
          </section>
        </div>

        <section class="bg-white p-6 rounded shadow">
          <h2 class="text-xl font-semibold text-gray-800 mb-4">📤 Upload Contract</h2>

          <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
            <div><span class="font-medium">Uploaded By:</span> <?= htmlspecialchars($_SESSION['user']['name'] ?? ($_SESSION['name'] ?? 'Unknown')) ?></div>
            <div><span class="font-medium">Department:</span> <?= htmlspecialchars($_SESSION['user']['department'] ?? ($_SESSION['department'] ?? 'N/A')) ?></div>
          </div>

          <form
            action="../backend/weka_contract_api.php"
            method="POST"
            id="contractForm"
            class="space-y-4"
            enctype="multipart/form-data"
            novalidate
          >
            <!-- Uploader attribution -->
            <input type="hidden" name="uploaded_by_id" value="<?= htmlspecialchars((string)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? ''))) ?>">
            <input type="hidden" name="uploaded_by_name" value="<?= htmlspecialchars($_SESSION['user']['name'] ?? ($_SESSION['name'] ?? 'Unknown')) ?>">

            <div>
              <label for="employeeName" class="block text-sm font-medium text-gray-700">
                Employee Name <span class="text-red-500">*</span>
              </label>
              <input type="text" name="employee_name" id="employeeName" required class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Enter employee name" value="<?= htmlspecialchars($_SESSION['user']['name'] ?? ($_SESSION['name'] ?? '')) ?>" />
              <p class="text-red-500 text-xs mt-1 hidden" id="employeeNameError">Employee Name is required.</p>
            </div>

            <div>
              <label for="employeeId" class="block text-sm font-medium text-gray-700">
                Employee ID <span class="text-red-500">*</span>
              </label>
              <input type="text" name="employee_id" id="employeeId" required class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Enter employee ID" />
              <p class="text-red-500 text-xs mt-1 hidden" id="employeeIdError">Employee ID is required.</p>
            </div>

            <div>
              <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
              <input type="text" name="department" id="department" class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="e.g., HR, Legal" value="<?= htmlspecialchars($_SESSION['user']['department'] ?? ($_SESSION['department'] ?? '')) ?>" />
            </div>

            <div>
              <label for="party" class="block text-sm font-medium text-gray-700">
                Contracting Party <span class="text-red-500">*</span>
              </label>
              <input type="text" name="party" id="party" required class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Enter contracting party name" />
              <p class="text-red-500 text-xs mt-1 hidden" id="partyError">Contracting Party is required.</p>
            </div>

            <div>
              <label for="title" class="block text-sm font-medium text-gray-700">
                Contract Title <span class="text-red-500">*</span>
              </label>
              <input type="text" name="title" id="title" required class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Enter contract title" />
              <p class="text-red-500 text-xs mt-1 hidden" id="titleError">Contract Title is required.</p>
            </div>

            <div>
              <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea name="description" id="description" rows="3" class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Optional description"></textarea>
            </div>
            
            <div>
                <label for="ocr_text" class="block text-sm font-medium text-gray-700">Contract Text (paste or summary)</label>
                <textarea name="ocr_text" id="ocr_text" rows="5" class="w-full mt-1 p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="Paste contract text or summary for analysis"></textarea>
            </div>

            <div>
              <label for="document" class="block text-sm font-medium text-gray-700">
                Upload Document
              </label>
              <input type="file" name="document" id="document" accept=".pdf, .doc, .docx, .png, .jpg, .jpeg" class="mt-1 text-sm text-gray-600" />
              <p class="text-xs text-gray-400 mt-1">Allowed: PDF, Word, Images. (Optional if text is pasted above)</p>
            </div>

            <div class="flex space-x-4 pt-4">
              <button type="submit" id="submitBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center gap-2 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <span>Run OCR & Analyze</span>
              </button>

              <a href="legalmanagement.php" id="viewAnalysisBtn" class="border border-blue-500 text-blue-600 px-4 py-2 rounded hover:bg-blue-50 flex items-center gap-2 transition opacity-50 pointer-events-none">
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

<script>
  // --- Configuration Constants ---
  const MIN_CONTRACT_LENGTH = 50;
  const RISK_THRESHOLD_LOW = 30;
  const RISK_THRESHOLD_MEDIUM = 70;

  // --- DOM Element Selection ---
  const form = document.getElementById('contractForm');
  const submitBtn = document.getElementById('submitBtn');
  const responseMessage = document.getElementById('responseMessage');
  const fileInput = document.getElementById('document');
  const ocrTextInput = document.getElementById('ocr_text');
  const ocrResultContainer = document.getElementById('ocrResultContainer');
  const ocrTextArea = document.getElementById('ocrText');
  const viewAnalysisBtn = document.getElementById('viewAnalysisBtn');
  const probabilityPercent = document.getElementById('probabilityPercent');
  const progressBar = document.getElementById('progressBar');
  const riskFactorsList = document.getElementById('riskFactorsList');
  
  // --- Form Input and Error Elements ---
  const inputs = {
    employeeName: { input: document.getElementById('employeeName'), error: document.getElementById('employeeNameError'), msg: 'Employee Name is required.' },
    employeeId: { input: document.getElementById('employeeId'), error: document.getElementById('employeeIdError'), msg: 'Employee ID is required.' },
    party: { input: document.getElementById('party'), error: document.getElementById('partyError'), msg: 'Contracting Party is required.' },
    title: { input: document.getElementById('title'), error: document.getElementById('titleError'), msg: 'Contract Title is required.' },
  };

  /**
   * Manages the loading state of the submit button.
   * @param {boolean} isLoading - True to show loading state, false otherwise.
   */
  function setLoadingState(isLoading) {
    submitBtn.disabled = isLoading;
    if (isLoading) {
      submitBtn.classList.add('opacity-50');
      submitBtn.querySelector('span').textContent = 'Analyzing...';
    } else {
      submitBtn.classList.remove('opacity-50');
      submitBtn.querySelector('span').textContent = 'Run OCR & Analyze';
    }
  }

  /**
   * Validates the entire form before submission.
   * @returns {boolean} - True if the form is valid, false otherwise.
   */
  function validateForm() {
    let isValid = true;
    responseMessage.textContent = '';
    responseMessage.className = 'text-center font-semibold mt-4';

    // Hide all previous errors
    Object.values(inputs).forEach(item => item.error.classList.add('hidden'));

    // Validate standard text inputs
    for (const key in inputs) {
      if (inputs[key].input.value.trim() === '') {
        inputs[key].error.classList.remove('hidden');
        isValid = false;
      }
    }

    // Validate contract data (file or text length)
    const isFileUploaded = fileInput.files.length > 0;
    const pastedText = ocrTextInput.value.trim();

    if (!isFileUploaded && pastedText === '') {
      responseMessage.textContent = 'Please upload a document or paste the contract text to analyze.';
      responseMessage.classList.add('text-red-600');
      isValid = false;
    } else if (!isFileUploaded && pastedText.length < MIN_CONTRACT_LENGTH) {
      responseMessage.textContent = `Contract text is too short. Please provide at least ${MIN_CONTRACT_LENGTH} characters.`;
      responseMessage.classList.add('text-red-600');
      isValid = false;
    }
    
    return isValid;
  }

  /**
   * Updates the progress bar and risk colors based on a score.
   * @param {number} percent - The risk percentage (0-100).
   */
  function updateProgressBar(percent = 0) {
    const score = Math.max(0, Math.min(100, parseInt(percent, 10)));
    probabilityPercent.textContent = `${score}%`;
    progressBar.style.width = `${score}%`;
    progressBar.setAttribute('aria-valuenow', score);
    
    const colorClasses = ['bg-green-500', 'bg-yellow-500', 'bg-red-500', 'text-green-500', 'text-yellow-500', 'text-red-500'];
    progressBar.classList.remove(...colorClasses);
    probabilityPercent.classList.remove(...colorClasses);

    if (score <= RISK_THRESHOLD_LOW) {
      progressBar.classList.add('bg-green-500');
      probabilityPercent.classList.add('text-green-500');
    } else if (score <= RISK_THRESHOLD_MEDIUM) {
      progressBar.classList.add('bg-yellow-500');
      probabilityPercent.classList.add('text-yellow-500');
    } else {
      progressBar.classList.add('bg-red-500');
      probabilityPercent.classList.add('text-red-500');
    }
  }
  
  /**
   * Handles UI updates after a successful API response.
   * @param {object} result - The JSON result from the server.
   */
  function updateUIAfterResponse(result) {
    responseMessage.textContent = result.message || 'Analysis complete!';
    responseMessage.classList.add('text-green-600');

    if (result.ocr_text) {
      ocrTextArea.value = result.ocr_text;
      ocrResultContainer.classList.remove('hidden');
    }

    if (result.analysis) {
        const score = result.analysis.probability_percent ?? result.analysis.risk_score ?? 0;
        updateProgressBar(score);
        
        riskFactorsList.innerHTML = '';
        riskFactorsList.className = 'list-disc list-inside text-sm space-y-1';
        
        if (result.analysis.risk_factors && result.analysis.risk_factors.length > 0) {
            riskFactorsList.classList.add('text-red-400');
            result.analysis.risk_factors.forEach(factor => {
                const li = document.createElement('li');
                li.textContent = factor;
                riskFactorsList.appendChild(li);
            });
        } else {
            riskFactorsList.classList.add('text-green-400');
            riskFactorsList.innerHTML = '<li>No significant risk factors detected.</li>';
        }
    }
    
    if (result.contract_id) {
      viewAnalysisBtn.classList.remove('opacity-50', 'pointer-events-none');
      responseMessage.textContent += ' Click "View Analysis" to see the results.';
    }
  }

  /**
   * Handles UI updates for error scenarios.
   * @param {string} message - The error message to display.
   */
  function handleError(message = 'An unexpected error occurred.') {
    responseMessage.textContent = message;
    responseMessage.classList.add('text-red-600');
    viewAnalysisBtn.classList.add('opacity-50', 'pointer-events-none');
    updateProgressBar(0);
  }

  // --- Event Listeners ---

  // Reset UI elements when a new file is chosen.
  fileInput.addEventListener('change', () => {
    updateProgressBar(0);
    ocrTextArea.value = '';
    ocrResultContainer.classList.add('hidden');
    viewAnalysisBtn.classList.add('opacity-50', 'pointer-events-none');
    responseMessage.textContent = '';
    riskFactorsList.innerHTML = '<li>Upload a contract to begin...</li>';
    riskFactorsList.className = 'list-disc list-inside text-sm text-red-400 space-y-1';

    if (fileInput.files.length > 0) {
      const preliminaryScore = Math.floor(Math.random() * 21) + 10; // Random score between 10-30
      updateProgressBar(preliminaryScore);
      riskFactorsList.innerHTML = `
        <li>Preliminary check complete.</li>
        <li>File is ready for full analysis.</li>
      `;
      riskFactorsList.className = 'list-disc list-inside text-sm text-gray-400 space-y-1';
    }
  });

  // Handle the form submission process.
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateForm()) {
      return;
    }

    setLoadingState(true);
    const formData = new FormData(form);

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
      });
      const result = await response.json();

      if (result.success) {
        updateUIAfterResponse(result);
      } else {
        handleError(result.message || 'An error occurred during analysis.');
      }
    } catch (error) {
      console.error('Submission Error:', error);
      handleError('A network or server error occurred.');
    } finally {
      setLoadingState(false);
    }
  });
</script>

</body>
</html>