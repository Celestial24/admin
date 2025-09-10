
<?php

$host = "localhost";
$user = "root";
$password = ""; // Change if your MySQL password is not empty
$database = "document"; // Change to your actual database name

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Document Management & Archiving</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

  <!-- Header -->
  <header class="bg-white shadow">
    <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
      <h1 class="text-xl font-bold text-gray-800">📁 Document Archive Center</h1>
      <span class="text-sm text-gray-500">Hotel & Restaurant Management</span>
    </div>
  </header>

  <!-- Main Content -->
  <main class="max-w-6xl mx-auto px-4 mt-8 space-y-8">

    <!-- Upload Section -->
    <section class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-lg font-semibold mb-4 text-gray-800">Upload Document</h2>
      <form id="uploadForm" class="space-y-4">
        <input type="text" id="docName" placeholder="Document Name" class="border px-4 py-2 w-full rounded" required />
        <select id="docDepartment" class="border px-4 py-2 w-full rounded" required>
          <option value="">Select Department</option>
          <option value="Front Desk">Front Desk</option>
          <option value="Kitchen">Kitchen</option>
          <option value="HR">HR</option>
          <option value="Housekeeping">Housekeeping</option>
        </select>
        <input type="text" id="claimedBy" placeholder="Claimed By" class="border px-4 py-2 w-full rounded" />
        <textarea id="docContent" placeholder="Paste contract or content here..." class="border px-4 py-2 w-full rounded h-24"></textarea>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Upload</button>
      </form>
    </section>

    <!-- Retrieval Section -->
    <section class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-lg font-semibold mb-4 text-gray-800">🔍 Retrieve Document</h2>
      <div class="space-y-4">
        <input type="text" id="retrieveInput" placeholder="Enter document name..." class="border px-4 py-2 w-full rounded" />
        <button id="retrieveBtn" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">Retrieve</button>

        <div id="retrieveResult" class="border p-4 rounded bg-gray-50 text-sm text-gray-700 hidden">
          <p><strong>Document:</strong> <span id="rName"></span></p>
          <p><strong>Department:</strong> <span id="rDept"></span></p>
          <p><strong>Claimed By:</strong> <span id="rClaimed"></span></p>
          <p><strong>Version:</strong> <span id="rVersion"></span></p>
          <p><strong>Date:</strong> <span id="rDate"></span></p>
          <p><strong>Compliance Markers:</strong> <span id="rAnalysis"></span></p>
          <div class="mt-4">
            <p class="font-semibold">📄 Document Content:</p>
            <pre id="rContent" class="bg-white p-2 rounded border text-gray-800 mt-2 whitespace-pre-wrap"></pre>
          </div>
        </div>
        <p id="retrieveNotFound" class="text-red-600 hidden">❌ Document not found.</p>
      </div>
    </section>

    <!-- Filters -->
    <section class="bg-white p-6 rounded-lg shadow flex justify-between items-center">
      <h2 class="text-lg font-semibold text-gray-800">Archived Documents</h2>
      <select id="departmentFilter" class="border rounded px-4 py-2 text-sm">
        <option value="all">All Departments</option>
        <option value="Front Desk">Front Desk</option>
        <option value="Kitchen">Kitchen</option>
        <option value="HR">HR</option>
        <option value="Housekeeping">Housekeeping</option>
      </select>
    </section>

    <!-- Table Section -->
    <section class="bg-white p-6 rounded-lg shadow">
      <div class="overflow-x-auto">
        <table class="min-w-full table-auto border border-gray-300">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Document</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Dept</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Claimed By</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Version</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Date</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Analysis</th>
            </tr>
          </thead>
          <tbody id="archiveTableBody" class="divide-y divide-gray-200">
            <!-- Dynamic rows -->
          </tbody>
        </table>
        <p id="emptyMessage" class="text-gray-500 mt-4">No archived documents found.</p>
      </div>
    </section>

    <!-- Audit Trail -->
    <section class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-lg font-semibold mb-2 text-gray-800">🕵️ Audit Trail</h2>
      <ul id="auditTrail" class="list-disc ml-5 text-sm text-gray-600 space-y-1"></ul>
    </section>

  </main>

  <!-- Footer -->
  <footer class="text-center text-gray-500 text-sm py-6">
    &copy; 2025 Hotel & Restaurant Management System
  </footer>

  <!-- Script -->
  <script>
    const ARCHIVE_KEY = "archivedDocuments";
    const AUDIT_KEY = "auditTrail";

    const archiveTableBody = document.getElementById("archiveTableBody");
    const departmentFilter = document.getElementById("departmentFilter");
    const emptyMessage = document.getElementById("emptyMessage");
    const auditTrail = document.getElementById("auditTrail");

    const uploadForm = document.getElementById("uploadForm");
    const docName = document.getElementById("docName");
    const docDepartment = document.getElementById("docDepartment");
    const claimedBy = document.getElementById("claimedBy");
    const docContent = document.getElementById("docContent");

    // Retrieval Section Elements
    const retrieveInput = document.getElementById("retrieveInput");
    const retrieveBtn = document.getElementById("retrieveBtn");
    const retrieveResult = document.getElementById("retrieveResult");
    const retrieveNotFound = document.getElementById("retrieveNotFound");
    const rName = document.getElementById("rName");
    const rDept = document.getElementById("rDept");
    const rClaimed = document.getElementById("rClaimed");
    const rVersion = document.getElementById("rVersion");
    const rDate = document.getElementById("rDate");
    const rAnalysis = document.getElementById("rAnalysis");
    const rContent = document.getElementById("rContent");

    let archivedDocs = JSON.parse(localStorage.getItem(ARCHIVE_KEY)) || [];
    let auditLogs = JSON.parse(localStorage.getItem(AUDIT_KEY)) || [];

    // Util: Fake Contract Analyzer
    function analyzeContract(content) {
      const markers = ["confidential", "expiry", "penalty", "non-compliance", "termination"];
      return markers.filter(marker => content.toLowerCase().includes(marker)).join(", ") || "None";
    }

    // Util: Log audit trail
    function logAudit(action) {
      const timestamp = new Date().toLocaleString();
      auditLogs.push(`${timestamp}: ${action}`);
      localStorage.setItem(AUDIT_KEY, JSON.stringify(auditLogs));
      renderAuditTrail();
    }

    function renderAuditTrail() {
      auditTrail.innerHTML = "";
      auditLogs.slice().reverse().forEach(log => {
        const li = document.createElement("li");
        li.textContent = log;
        auditTrail.appendChild(li);
      });
    }

    function renderTable(dept = "all") {
      archiveTableBody.innerHTML = "";

      const filtered = dept === "all"
        ? archivedDocs
        : archivedDocs.filter(doc => doc.department === dept);

      if (filtered.length === 0) {
        emptyMessage.style.display = "block";
        return;
      }

      emptyMessage.style.display = "none";

      filtered.forEach(doc => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td class="px-4 py-2 text-sm text-gray-800">${doc.name}</td>
          <td class="px-4 py-2 text-sm text-gray-600">${doc.department}</td>
          <td class="px-4 py-2 text-sm text-gray-600">${doc.claimedBy || "—"}</td>
          <td class="px-4 py-2 text-sm text-gray-600">${doc.version}</td>
          <td class="px-4 py-2 text-sm text-gray-600">${doc.archivedDate}</td>
          <td class="px-4 py-2 text-sm text-gray-600">${doc.analysis}</td>
        `;
        archiveTableBody.appendChild(row);
      });
    }

    // Upload Handler
    uploadForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const name = docName.value.trim();
      const department = docDepartment.value;
      const claimed = claimedBy.value.trim();
      const content = docContent.value.trim();
      const now = new Date().toLocaleDateString();

      if (!name || !department) return;

      const existing = archivedDocs.find(doc => doc.name === name);
      const version = existing ? existing.version + 1 : 1;

      const newDoc = {
        name,
        department,
        claimedBy: claimed,
        content,
        size: content.length * 2, // rough estimation in bytes
        archivedDate: now,
        version,
        analysis: analyzeContract(content)
      };

      // Remove old version if exists
      archivedDocs = archivedDocs.filter(doc => doc.name !== name);
      archivedDocs.push(newDoc);
      localStorage.setItem(ARCHIVE_KEY, JSON.stringify(archivedDocs));

      logAudit(`Uploaded "${name}" version ${version} to ${department}`);

      // Reset
      uploadForm.reset();
      renderTable(departmentFilter.value);
    });

    departmentFilter.addEventListener("change", () => {
      renderTable(departmentFilter.value);
    });

    // Retrieval Logic
    retrieveBtn.addEventListener("click", () => {
      const query = retrieveInput.value.trim();
      if (!query) return;

      const doc = archivedDocs.find(d => d.name.toLowerCase() === query.toLowerCase());

      if (doc) {
        rName.textContent = doc.name;
        rDept.textContent = doc.department;
        rClaimed.textContent = doc.claimedBy || "—";
        rVersion.textContent = doc.version;
        rDate.textContent = doc.archivedDate;
        rAnalysis.textContent = doc.analysis;
        rContent.textContent = doc.content;

        retrieveResult.classList.remove("hidden");
        retrieveNotFound.classList.add("hidden");

        logAudit(`Retrieved document: "${doc.name}"`);
      } else {
        retrieveResult.classList.add("hidden");
        retrieveNotFound.classList.remove("hidden");
      }
    });

    // Optional: Hide result on input change
    retrieveInput.addEventListener("input", function () {
      retrieveResult.classList.add("hidden");
      retrieveNotFound.classList.add("hidden");
    });

    // Init
    renderTable();
    renderAuditTrail();
  </script>
</body>
</html>
  