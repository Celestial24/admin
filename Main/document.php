<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "document";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed");

$name = $_POST['docName'] ?? '';
$dept = $_POST['docDepartment'] ?? '';
$claimedBy = $_POST['claimedBy'] ?? '';

$uploadDir = "../uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if (isset($_FILES['docFile']) && $_FILES['docFile']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['docFile'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = uniqid('doc_', true) . '.' . $ext;
    $target = $uploadDir . $safeName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $size = filesize($target);
        $stmt = $conn->prepare("INSERT INTO documents (name, department, claimed_by, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $dept, $claimedBy, $safeName, $size);
        $stmt->execute();
        echo "✅ File uploaded successfully.";
    } else {
        echo "❌ Failed to move file.";
    }
} else {
    echo "❌ File upload error.";
}

// ✅ Only query after file processing
$result = $conn->query("SELECT * FROM documents ORDER BY uploaded_at DESC");

// ✅ Then close the connection
$conn->close();
?>


<section class="bg-white p-6 rounded-lg shadow mt-6">
  <h2 class="text-lg font-semibold text-gray-800 mb-4">📚 Uploaded Files</h2>
  <table class="min-w-full table-auto border border-gray-300">
    <thead class="bg-gray-100">
      <tr>
        <th class="px-4 py-2 text-left">Name</th>
        <th class="px-4 py-2 text-left">Department</th>
        <th class="px-4 py-2 text-left">Claimed By</th>
        <th class="px-4 py-2 text-left">Size</th>
        <th class="px-4 py-2 text-left">Uploaded</th>
        <th class="px-4 py-2 text-left">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr class="border-t">
          <td class="px-4 py-2"><?= htmlspecialchars($row['name']) ?></td>
          <td class="px-4 py-2"><?= htmlspecialchars($row['department']) ?></td>
          <td class="px-4 py-2"><?= htmlspecialchars($row['claimed_by']) ?></td>
          <td class="px-4 py-2"><?= round($row['file_size'] / 1024, 2) ?> KB</td>
          <td class="px-4 py-2"><?= $row['uploaded_at'] ?></td>
          <td class="px-4 py-2">
            <a href="/admin/uploads/<?= urlencode($row['file_path']) ?>" target="_blank" class="text-blue-600 underline">View</a>
            |
            <a href="/admin/uploads/<?= urlencode($row['file_path']) ?>" download class="text-green-600 underline">Download</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</section>

?>


<form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data" class="space-y-4">
  <input type="text" name="docName" placeholder="Document Name" required class="border px-4 py-2 w-full rounded" />
  <select name="docDepartment" class="border px-4 py-2 w-full rounded" required>
    <option value="">Select Department</option>
    <option value="Front Desk">Front Desk</option>
    <option value="Kitchen">Kitchen</option>
    <option value="HR">HR</option>
    <option value="Housekeeping">Housekeeping</option>
  </select>
  <input type="text" name="claimedBy" placeholder="Claimed By" class="border px-4 py-2 w-full rounded" />
  <input type="file" name="docFile" accept=".pdf,.doc,.docx,.txt" required class="border px-4 py-2 w-full rounded" />
  <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Upload</button>
</form>

  <!-- Script -->
  <script>
    // Initialize Lucide icons
    lucide.createIcons();
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
  